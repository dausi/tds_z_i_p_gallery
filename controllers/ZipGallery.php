<?php
namespace Concrete\Package\TdsZIPGallery\Controller;

defined('C5_EXECUTE') or die("Access Denied.");

/**
 * Class ZipGallery
 *
 * A representation of an image gallery from a ZIP archive
 *
 * Copyright 2016, 2017 - TDSystem Beratung & Training - Thomas Dausner
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

use Doctrine\DBAL\Exception\InvalidArgumentException;
use finfo;
use Punic\Data;
use \ZipArchive;

class ZipGallery extends ZipArchive
{
	protected $zipStat;
	protected $cache;
	protected $cacheNamePrefix;
	protected $zip;
	protected $zipFilePath;
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
	protected $processedFile;
	protected $media;

	/**
     * Opens a ZIP file and scans it for contained files.
     *
     * @param string $zipFilePath
     * @param string $cachePrefix
     */
	public function __construct($zipFilePath, $cachePrefix)
	{
		$this->entries = 0;
		$this->zipFilePath = DIR_BASE . '/' . trim($zipFilePath, '/');
		$this->zip = new ZipArchive;
		if ($this->zip->open($this->zipFilePath) == true)
		{
			$this->cacheNamePrefix = $cachePrefix . ':';
			$this->zipStat = stat($this->zipFilePath);
			$this->entries = $this->zip->numFiles;
			$this->cache = new ZipGalleryCache;
			$this->cache->setIgnorePatterns([
				're' => '/\.json$/',
				'db' => '%.json'
			]);
		}

		register_shutdown_function(function() {
			$error = error_get_last();
			if ($error['type'] === E_ERROR)
			{
				$errmsg = 'Processed: '.$this->processedFile.'<br/>'.$error['message'].'<br/>file: '.$error['file'].' line '.$error['line'];
				error_log($errmsg);
				$errmsg = '{"error":"' . urlencode ($errmsg). '"}';
				header('HTTP/1.1 200 OK');
				header('Content-Type: application/json');
				header('Content-Length: '. strlen($errmsg));
				echo $errmsg;
			}
		});
	}

	public function  __destruct()
	{
	}

    /**
     * Get file identified by file name from ZIP archive.
     * Returns data or FALSE.
     *
     * @param string $filename
     * @param integer $maxWidth
     * @return Data|null
     * @throws InvalidArgumentException
     */
	public function getFile($filename, $maxWidth)
	{
		$data = null;

		if ($this->entries > 0)
		{
			if ($maxWidth > 0)
			{
				$data = $this->getThumb($filename, $maxWidth, -1);
			}
			else
			{
                if ($filename == '.')
                {
                    $filename = $this->media[0]['name'];
                }
				$data = $this->zip->getFromName($filename);
			}
		}
		return $data;
	}

    /**
     * Get file identified by file name from DB cache.
     * Returns data or null.
     *
     * @param string filename
     * @return Data|null
     * @throws InvalidArgumentException
     */
	private function getFromDBCache($filename)
	{
		$data = null;

		if ($this->entries > 0)
		{
			$data = $this->cache->getEntryDB($this->zipStat['mtime'], $this->cacheNamePrefix . $filename);
		}
		return $data;
	}

    /**
     * Get entries from ZIP archive as JSON array
     *
     * @param integer $tnSize
     * @param bool $doJson
     * @return string|null
     * @throws InvalidArgumentException
     */
	public function getInfo($tnSize, $doJson = true)
	{
		$info = null;
		if ($this->entries > 0)
		{
			// ZIP file is open, look for cached info entry
			$info = $this->getFromDBCache('info.json');
			if (!empty($info))
            {
                $this->media = json_decode($info, true);
                if (!$doJson)
                {
                    $info = $this->media;
                }
            }
            else
			{
				/** ZIP file info is not in cache, generate and set into cache
                 * @var $fileInfo finfo
                 */
				$fileInfo = new finfo(FILEINFO_NONE);
				$entryNum = 0;
				for ($i = 0; $i < $this->zip->numFiles; $i++)
				{
					$stat = $this->zip->statIndex($i);
					$filename = $stat['name'];

					if (preg_match('/jpe?g$/i', $filename) === 1)
					{
						// ZIP entry is relevant file
						$this->processedFile = $filename;
						$data = $this->zip->getFromName($filename);
						// init decoded IPTC fields with pseudo 'filename'
						$iptcDecoded = [
							'filename' => $filename
						];
						if (($exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($data), null, true)) !== false)
						{
							getimagesizefromstring($data, $imgInfo);
							if (isset($imgInfo['APP13']) && ($iptc = iptcparse($imgInfo['APP13'])) != null)
							{
								foreach ($iptc as $key => $value)
								{
									$idx = isset($this->iptcFields[$key]) ? $this->iptcFields[$key] : $key;
									if ($idx != $key)
									{
										$iptcDecoded[$idx] = $value;
									}
								}
							}
						}
						$exifData = [];
						foreach ($exif as $exKey => $exValue)
						{
							foreach ($exValue as $key => $value)
							{
								if (is_array($value) || $fileInfo->buffer($value) != 'data')
								{
									$value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
									if (!empty($value))
									{
										$exifData[$exKey][$key] = $value;
									}
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
                /**
                 * @var $info Data
                 */
				$info = json_encode($this->media, JSON_PARTIAL_OUTPUT_ON_ERROR);
				$this->cache->setEntryDB($this->cacheNamePrefix . 'info.json', $info);
				if (!$doJson)
                {
                    $info = $this->media;
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
                $info = $doJson ? json_encode($this->media, JSON_PARTIAL_OUTPUT_ON_ERROR) : $this->media;
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
     * @return Data|null
     * @throws InvalidArgumentException
     */
	public function getThumb($filename, $new_width, $new_height)
	{
		$tnFilename = $new_width . 'x' . $new_height . '/' . $filename;
		$data = $this->getFromDBCache($tnFilename);
		if ($data === null)
		{
			// not in cache, create
			$this->processedFile = $filename;
			$data = $this->getFile($filename, 0);
			if ($data != null)
			{
				$im = imagecreatefromstring($data);
				list($width, $height) = getimagesizefromstring($data);
				if ($new_height < 0)
				{
					//
					// new-_width = max-width
					//
					$new_height = intval($new_width * $height / $width); 
				}
				else if ($new_width < 0)
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
				$thumbNail = imagecreatetruecolor($new_width, $new_height);
				imagecopyresampled($thumbNail, $im, 0, 0, $x, $y, $new_width, $new_height, $width, $height);

				ob_start();
				if (imagejpeg($thumbNail, null))
				{
                    /**
                     * @var $data Data
                     */
					$data = ob_get_contents();
					$this->cache->setEntryDB($this->cacheNamePrefix . $tnFilename, $data);
				}
				ob_end_clean();
			}
		}
		return $data;
	}
}