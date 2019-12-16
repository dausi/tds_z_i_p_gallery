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
use Concrete\Package\TdsZIPGallery\Src\ZipGallery;
use Database;
use File;

class Galleries extends AbstractController
{
	public $zipUrl;
	public $zipId;

    public function setParams($url, $id)
    {
        $this->zipUrl = $url;
        $this->zipId  = $id;
        return $this;
    }

	private function getFileUrl($fileId)
	{
		$f = File::getByID($fileId);
		return isset($f) ? substr($f->getUrl(), strlen(BASE_URL)) : null;
	}

	private function getIDfromName($fileName) {
		$db = Database::connection();
		return $db->fetchColumn('SELECT FileVersions.fID FROM FileVersions WHERE FileVersions.fvIsApproved = 1 and FileVersions.fvFilename = ?', [$fileName]);
	}

	private function isZip($query)
	{
		$this->zipUrl = '';
		$valid = isset($query['zip']);
		if ($valid)
		{
			$this->zipUrl = $query['zip'];
			$this->zipId = $this->getIDfromName(basename($this->zipUrl));
		}
		else
		{
			$valid = isset($query['zipId']);
			if ($valid)
			{
				$this->zipId = $query['zipId'];
				$this->zipUrl = $this->getFileUrl($this->zipId);
			}
		}
		return $valid;
	}
	
	public function getInfo()
	{
		parse_str($_SERVER['QUERY_STRING'], $query);
		if ($this->isZip($query))
		{
			$tnSize = null;
			if (isset($query['tnw']) || isset($query['tnh']))
			{
				$tnSize = [
					'tnw' => isset($query['tnw']) ? $query['tnw'] : 50,
					'tnh' => isset($query['tnh']) ? $query['tnh'] : 50
				];
			}
			header('Access-Control-Allow-Origin: *');
			header('Content-Type: application/json');
			$zip = new ZipGallery($this, false);
			echo $zip->getInfo($tnSize);
		}
	}

	public function getImage()
	{
		parse_str($_SERVER['QUERY_STRING'], $query);
		if ($this->isZip($query) && isset($query['file']))
		{
			$maxWidth = -1;
			if (isset($query['max-width']))
				$maxWidth = $query['max-width'];
			header('Content-Disposition: attachment; filename="'. $query['file'] . '"');
			header('Content-Type: image/jpeg'); // JPG picture
			$zip = new ZipGallery($this, isset($query['cache']));
			echo $zip->getFile($query['file'], $maxWidth);
		}
	}

	public function getThumb()
	{
		parse_str($_SERVER['QUERY_STRING'], $query);
		if ($this->isZip($query) && isset($query['file']))
		{
            $tnw = isset($query['tnw']) ? $query['tnw'] : 50;
            $tnh = isset($query['tnh']) ? $query['tnh'] : 50;
            $filename = $query['file'];
            $zip = new ZipGallery($this, false);
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
	
	public function getUrl()
	{
		parse_str($_SERVER['QUERY_STRING'], $query);
		
	}

	public function getViewObject()
	{
	}
}
