<?php
/**
 * ZIP Image Gallery controller.
 *
 * Copyright 2017, 2018 - TDSystem Beratung & Training - Thomas Dausner
 *
 * the ZIP Image Gallery controller URLS are
 *
 * 		/ccm/tds_z_i_p_gallery/galleries/getinfo  => public function getInfo()
 * 		/ccm/tds_z_i_p_gallery/galleries/getimage => public function getImage()
 * 		/ccm/tds_z_i_p_gallery/galleries/getthumb => public function getThumb()
 *
 * mandatory parameter is
 *
 *		zip=path-to-zip-archive
 *		zipId=fileId-of-zipfile
 *
 * additional parameter for methods getImage()
 *
 *		max-width=WIDTH		maximum width of image [px]
 *
 * additional parameters for methods getInfo() and getThumb()
 *
 *		tnw=WIDTH	thumbnail width [px]
 *		tnh=HEIGHT	thumbnail height [px]
 *
 */
namespace Concrete\Package\TdsZIPGallery\Controller;

defined('C5_EXECUTE') or die("Access Denied.");

use Concrete\Core\Controller\AbstractController;
use Database;
use Concrete\Core\File\File;
use Concrete\Core\Http\Request;

class Gallery extends AbstractController
{
    /**
     * @var $zipPath string
     * @var $cachePrefix string
     * @var $zipId integer
     * @var $query mixed
     */
	private $zipPath;
	private $cachePrefix;
	private $zipId;
	private $query;

    public function setParams($path, $id)
    {
        $this->zipPath = $path;
        $this->zipId  = $id;
        return $this;
    }

	private function getFilePath($fileId)
	{
		$file = File::getByID($fileId);
		return isset($file) ? $file->getRelativePath() : null;
	}

	private function getIDfromName($fileName) {
		$db = Database::connection();
		return $db->fetchColumn('SELECT FileVersions.fID FROM FileVersions WHERE FileVersions.fvIsApproved = 1 and FileVersions.fvFilename = ?', [$fileName]);
	}

    /**
     * Parse QUERY_STRING and leaves result in $this->>query
     *
     *  - has zip=zipUrl    determine ID of zipFile
     *  - has zipId=nn      determine path of zipFile
     *
     *  and set $this->>zipPath, $this->>zipID
     *
     * @return bool if zip file found (from zipUrl or zipId
     */
	private function isZip()
	{
        $server = Request::getInstance()->server;

        parse_str($server->get('QUERY_STRING'), $query);
        $this->query = $query;
		$this->zipPath = '';
		$valid = isset($this->query['zip']);
		if ($valid)
		{
			$this->zipPath = $this->query['zip'];
			$this->zipId = $this->getIDfromName(basename($this->zipPath));
		}
		else
		{
			$valid = isset($this->query['zipId']);
			if ($valid)
			{
				$this->zipId = $this->query['zipId'];
				$this->zipPath = $this->getFilePath($this->zipId);
			}
		}
		if ($valid)
        {
            $this->cachePrefix = ':' . $this->zipId;
        }
		return $valid;
	}
	
	public function getInfo()
	{
		if ($this->isZip())
		{
			$tnSize = null;
			if (isset($this->query['tnw']) || isset($this->query['tnh']))
			{
				$tnSize = [
					'tnw' => isset($this->query['tnw']) ? $this->query['tnw'] : 50,
					'tnh' => isset($this->query['tnh']) ? $this->query['tnh'] : 50
				];
			}
			header('Access-Control-Allow-Origin: *');
			header('Content-Type: application/json');
			$zip = new ZipGallery($this->zipPath, $this->cachePrefix);
			echo $zip->getInfo($tnSize);
		}
	}

	public function getImage()
	{
		if ($this->isZip() && isset($this->query['file']))
		{
			$maxWidth = -1;
			if (isset($this->query['max-width']))
				$maxWidth = $this->query['max-width'];
			header('Content-Disposition: attachment; filename="'. $this->query['file'] . '"');
			header('Content-Type: image/jpeg'); // JPG picture
            $zip = new ZipGallery($this->zipPath, $this->cachePrefix);
			echo $zip->getFile($this->query['file'], $maxWidth);
		}
	}

	public function getImageFromFileID($zipFileID, $imageName, $width)
    {
        $zipGallery = new ZipGallery($this->getFilePath($zipFileID), ':' . $zipFileID);
        return $zipGallery->getFile($imageName, $width);
    }

	public function getThumb()
	{
		if ($this->isZip() && isset($this->query['file']))
		{
            $tnw = isset($this->query['tnw']) ? $this->query['tnw'] : 50;
            $tnh = isset($this->query['tnh']) ? $this->query['tnh'] : 50;
            $filename = $this->query['file'];
            $zip = new ZipGallery($this->zipPath, $this->cachePrefix);
            if (substr($filename, 0, 1) == '.')
            {
                $info = $zip->getInfo([
                    'tnw' => $tnw, 'tnh' => $tnh
                ], false);
                $fileId = intval(substr($filename, 1)) % count($info);
                $filename = $info[$fileId]['name'];
            }
			header('Content-Disposition: attachment; filename="'. $tnw . 'x' . $tnh . '#' . $filename . '"');
			header('Content-Type: image/jpeg'); // JPG picture
			echo $zip->getThumb($filename, $tnw, $tnh);
		}
	}
	
	public function getViewObject()
	{
	}
}
