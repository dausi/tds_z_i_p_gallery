<?php
/**
 * ZIP Image Gallery controller.
 *
 * Copyright 2017 - TDSystem Beratung & Training - Thomas Dausner (aka dausi)
 *
 * the ZIP Image Gallery controller URLS are
 *
 * 		/ccm/tds_z_i_p_gallery/galleries/getinfo  => public fundtion getInfo()
 * 		/ccm/tds_z_i_p_gallery/galleries/getimage => public fundtion getImage()
 * 		/ccm/tds_z_i_p_gallery/galleries/getthumb => public fundtion getThumb()
 *
 * mandatory parameter is
 *
 *		zip=path-to-zip-archive
 *
 * additional parameters for methods getInfo() and getThumb()
 *
 *		tnw=WIDTH	thumbnail width [px]
 *		tnh=HEIGHT	thumbnail height [px]
 *
 */
namespace Concrete\Package\TdsZIPGallery\Controller;

defined('C5_EXECUTE') or die(_("Access Denied."));

use Concrete\Core\Controller\AbstractController;
use Concrete\Package\TdsZIPGallery\Src\ZipGallery;

class Galleries extends AbstractController
{
	public function getInfo()
	{
		parse_str($_SERVER['QUERY_STRING'], $query);
		if (isset($query['zip']))
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
			$zip = new ZipGallery($query['zip']);
			echo $zip->getInfo($tnSize);
		}
	}

	public function getImage()
	{
		parse_str($_SERVER['QUERY_STRING'], $query);
		if (isset($query['zip']) && isset($query['file']))
		{
			header('Content-Disposition: attachment; filename="'. $query['file'] . '"');
			header('Content-Type: image/jpeg'); // JPG picture
			$zip = new ZipGallery($query['zip']);
			echo $zip->getFromZip($query['file']);
		}
	}

	public function getThumb()
	{
		parse_str($_SERVER['QUERY_STRING'], $query);
		if (isset($query['zip']) && isset($query['file']))
		{
			$tnw = isset($query['tnw']) ? $query['tnw'] : 50;
			$tnh = isset($query['tnh']) ? $query['tnh'] : 50;
			header('Content-Disposition: attachment; filename="'. $tnw . 'x' . $tnh . '#' . $query['file'] . '"');
			header('Content-Type: image/jpeg'); // JPG picture
			$zip = new ZipGallery($query['zip']);
			echo $zip->getThumb($query['file'], $tnw, $tnh);
		}
	}

	public function getViewObject()
	{
	}
}
