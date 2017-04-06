<?php
/**
 * Class ZipGallery
 *
 * A representation of an image gallery from a ZIP archive
 *
 * Copyright 2016, 2017 - TDSystem Beratung & Training - Thomas Dausner (aka dausi)
 *
 * ZIP archive info as well as thumbnail images are cached.
 *
 * Each cache entry file names consists of
 * - the full path to the zip file (leading slash '/' stripped)
 * - thumbnail WxH (if applicable), separated by a slash '/'
 * - name of the file from the zip archive, separated by a slash '/'
 *
 * A cache entry name could look like:
 *
 *	application/files/9014/8986/0065/gallery-1.zip/50x50/2016-05-23_10-38-10.jpg
 *
 * having:
 *
 *	application/files/9014/8986/0065/gallery-1.zip                                path to zip file
 *	                                              /50x50                          thumbs WxH
 *                                                      /2016-05-23_10-38-10.jpg  file name
 */
namespace Concrete\Package\TdsZIPGallery\Src;

defined('C5_EXECUTE') or die("Access Denied.");

use Concrete\Package\TdsZIPGallery\Src\ZipGalleryCache;
use \ZipArchive;

class ZipGallery extends ZipArchive
{

	protected $zipFilename;
	protected $zipStat;
	protected $cache;
	protected $cacheNamePrefix;
	protected $zip;
	protected $entries;
	protected $iptcFields = [
	    '2#005' => 'title',
	    '2#010' => 'urgency',
	    '2#015' => 'category',
	    '2#020' => 'subcategories',
	    '2#025' => 'subject',
		'2#040' => 'specialInstructions',
	    '2#055' => 'cdate',
	    '2#080' => 'authorByline',
	    '2#085' => 'authorTitle',
	    '2#090' => 'city',
	    '2#095' => 'state',
	    '2#101' => 'country',
	    '2#103' => 'OTR',
	    '2#105' => 'headline',
	    '2#110' => 'source',
	    '2#115' => 'photoSource',
	    '2#116' => 'copyright',
	    '2#120' => 'caption',
	    '2#122' => 'captionWriter'
	];
	protected $media;

	/**
     * Opens a ZIP file and scans it for contained files.
     *
     * @param string $zipFilename
     */
	public function __construct($zipFilename)
	{
		$this->entries = 0;
		$this->zipFilename = trim($zipFilename, '/');
		$pathToZip = DIR_BASE . '/' . $this->zipFilename;
		$this->zip = new ZipArchive;
		if ($this->zip->open($pathToZip) == true)
		{
			$this->zipStat = stat($pathToZip);
			$this->entries = $this->zip->numFiles;
			$this->cache = new ZipGalleryCache;
			$this->cacheNamePrefix = ltrim($this->zipFilename, '/') . '/';
			$this->cache->setIgnorePatterns([
				're' => '/\.json$/',
				'db' => '%.json'
			]);
		}
	}

	public function  __destruct()
	{
	}

    /**
     * Get file identified by file name from ZIP archive.
     * Returns data or FALSE.
     *
     * @param string filename
     */
	public function getFromZip($filename)
	{
		$data = FALSE;

		if ($this->entries > 0)
		{
			$data = $this->zip->getFromName($filename);
		}
		return $data;
	}
	/**
     * Get file identified by file name from cache.
     * Returns data or null.
     *
     * @param string filename
     */
	private function getFromCache($filename)
	{
		$data = null;

		if ($this->entries > 0)
		{
			$data = $this->cache->getEntry($this->zipStat['mtime'], $this->cacheNamePrefix . $filename);
		}
		return $data;
	}
	/**
     * Get entries from ZIP archive as JSON array
     */
	public function getInfo($tnSize)
	{
		$info = null;
		if ($this->entries > 0)
		{
			// ZIP file is open, look for cached info entry
			if (($info = $this->getFromCache('info.json')) === null)
			{
				// ZIP file info is not in cache, generate and set into cache
				$finfo = new \finfo(FILEINFO_NONE);
				$entryNum = 0;
				for ($i = 0; $i < $this->zip->numFiles; $i++)
				{
					$stat = $this->zip->statIndex($i);
					$filename = $stat['name'];

					if (preg_match('/jpe?g$/i', $filename) === 1)
					{
						// ZIP entry is relevant file
						$data = $this->zip->getFromName($filename);
						// init decoded IPTC fields with pseudo 'filename'
						$iptcDecoded = [
							'filename' => $filename
						];
						if (($exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($data), null, true)) !== false)
						{
							getimagesizefromstring($data, $imgInfo);
							if (isset($imgInfo['APP13']))
							{
								if (($iptc = iptcparse($imgInfo['APP13'])) != null)
									foreach ($iptc as $key => $value)
									{
										$idx = isset($this->iptcFields[$key]) ? $this->iptcFields[$key] : $key;
										$iptcDecoded[$idx] = $value;
									}
							}
						}
						$exifData = [];
						foreach ($exif as $exKey => $exValue)
						{
							foreach ($exValue as $key => $value)
							{
								if (is_array($value) || $finfo->buffer($value) != 'data')
								{
									$exifData[$exKey][$key] = $value;
								}
							}
						}

						$this->media[$entryNum++] = [
							'name' => $filename,
							'exif' => $exifData,
							'iptc' => $iptcDecoded
						];
					}
				}
				$info = json_encode($this->media, JSON_PARTIAL_OUTPUT_ON_ERROR);
				$this->cache->setEntry($this->cacheNamePrefix . 'info.json', $info);
			}
			else
			{
				if ($tnSize !== null)
				{
					$this->media = json_decode($info, true);
				}
			}
			if ($tnSize !== null)
			{	/*
				 * enrich json by thumbs
				 */
				$tnw = $tnSize['tnw'];
				$tnh = $tnSize['tnh'];
				foreach($this->media as $idx => $value)
				{
					$filename = $this->media[$idx]['name'];
					$this->media[$idx]['thumbnail'] = base64_encode($this->getThumb($filename, $tnw, $tnh));
				}
				$info = json_encode($this->media, JSON_PARTIAL_OUTPUT_ON_ERROR);
			}
		}
		return $info;
	}
	/**
     * Generate thumb from file identified by file name.
     * Outputs thumbnail and returns true or false in case of error.
     *
     * @param string filename
     * @param int new_width
     * @param int new_height
     */
	public function getThumb($filename, $new_width, $new_height)
	{
		$tnFilename = $new_width . 'x' . $new_height . '/' . $filename;
		$data = $this->getFromCache($tnFilename);
		if ($data === null)
		{
			// not in cache, create
			$data = $this->getFromZip($filename);
			if ($data != null)
			{
				$im = imagecreatefromstring($data);
				list($width, $height) = getimagesizefromstring($data);
				if ($new_width < 0)
				{
					//
					// fixed height, flexible width
					//
					$new_width = intval($new_height * $width / $height);
				}
				$x = $y = 0;
				if ($new_width == $new_height)
				{
					//
					// square thumbnail
					//
					if ($width > $height)
					{
						$x = intval(($width - $height ) / 2);
						$width = $height;
					}
					else
					{
						$y = intval(($height - $width ) / 2);
						$height = $width;
					}
				}
				$tnail = imagecreatetruecolor($new_width, $new_height);
				imagecopyresampled($tnail, $im, 0, 0, $x, $y, $new_width, $new_height, $width, $height);

				ob_start();
				if (imagejpeg($tnail, null))
				{
					$data = ob_get_contents();
					$this->cache->setEntry($this->cacheNamePrefix . $tnFilename, $data);
				}
				ob_end_clean();
			}
		}
		return $data;
	}
}