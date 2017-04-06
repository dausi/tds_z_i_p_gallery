<?php
/**
 * ZIP image gallery add-on controller.
 *
 * Copyright 2016, 2017 - TDSystem Beratung & Training - Thomas Dausner (aka dausi)
 */
namespace Concrete\Package\TdsZIPGallery;

use Concrete\Core\Editor\Plugin;
use Core;
use Config;
use AssetList;
use Events;
use Page;
use View;
use Concrete\Core\Routing\Router;
use Concrete\Core\Support\Facade\Route;

class Controller extends \Concrete\Core\Package\Package
{

	protected $pkgHandle = 'tds_z_i_p_gallery';
	protected $appVersionRequired = '5.7.5.6';
	protected $pkgVersion = '0.9.3';
	protected $cked_plugin_key = 'site.sites.default.editor.ckeditor4.plugins.selected';

	public function getPackageDescription()
	{
		return t('Adds ZIP gallery button to the WYSIWYG editor');
	}

	public function getPackageName()
	{
		return t('ZIP Gallery');
	}

	public function concreteV8orAbove()
	{
		// compare current c5 version to v8.x
		// return < 0  on no v8
		//        >= 0 on yes, its v8.x or above
		return version_compare(Config::get('concrete')['version_installed'], '8');
	}

 	public function install()
	{
		$pkg = parent::install();

		if ($this->concreteV8orAbove() >= 0)
		{
			$cked_plugins = Config::get($this->cked_plugin_key);
			array_push($cked_plugins, 'zipgallery');
			Config::save($this->cked_plugin_key, $cked_plugins);
		}
 	}

 	public function uninstall()
	{
		$pkg = parent::uninstall();

		if ($this->concreteV8orAbove() >= 0)
		{
			$cked_plugins = Config::get($this->cked_plugin_key);
			$cked_plugins = array_diff($cked_plugins, ['zipgallery']);
			Config::save($this->cked_plugin_key, $cked_plugins);
		}
 	}

	public function on_start()
	{
		$routes = [
			'getinfo'	=> 'getInfo',
			'getimage'	=> 'getImage',
			'getthumb'	=> 'getThumb'
		];
		foreach ($routes as $url => $method)
		{
			$route = Router::route(['galleries/' . $url, $this->pkgHandle]);
			Route::register($route, 'Concrete\Package\TdsZIPGallery\Controller\Galleries::' . $method);
		}

		$params = [
			'cache_size'	=> 10000,		// for 'db' cache method
			'cache_method'	=> 'db',		// use 'cache' for utilisation of c5 cache/expensive
			'expires'		=> '+2 days'	// for 'cache' cache method
		];
		foreach($params as $param => $value)
		{
			if (!$this->getConfig()->has('tds_zip_gallery.' . $param))
			{
				$this->getConfig()->save('tds_zip_gallery.' . $param, $value);
			}
		}

		$al = AssetList::getInstance();
		if ($this->concreteV8orAbove() < 0)
		{	// 5.x ==> redactor
			$assetGroup = 'editor/plugin/zipgallery';
			$al->register('javascript', $assetGroup, 'redactor/plugin.js',  [], $this->pkgHandle);
			$al->register('css',        $assetGroup, 'redactor/plugin.css', [], $this->pkgHandle);
		}
		else
		{	// 8.x ==> ckeditor
			$assetGroup = 'editor/ckeditor/zipgallery';
			$al->register('javascript', $assetGroup, 'cked/register.js', [], $this->pkgHandle);
			$al->register('css',        $assetGroup, 'cked/plugin.css',  [], $this->pkgHandle);
		}
		$al->registerGroup($assetGroup, [
			['javascript', $assetGroup],
			['css',        $assetGroup]
		]);
		$plugin = new Plugin();
		$plugin->setKey($this->pkgHandle);
		$plugin->setName(t('ZIP Gallery Plugin'));
		$plugin->requireAsset($assetGroup);
		$editor = Core::make('editor');
		$editor->getPluginManager()->register($plugin);
		$editor->getPluginManager()->select('tds_z_i_p_gallery');

		$filetypes = Config::get('concrete.upload.extensions');
		if (strpos($filetypes, '*.zip') === false)
		{
			$filetypes .= ';*.zip';
			Config::save('concrete.upload.extensions', $filetypes);
		}

		Events::addListener('on_before_render', function($event) {
			$cID = $event['view']->controller->c->cID;
			$c = Page::getByID($cID);
			if ($this->concreteV8orAbove() < 0 || $c->getPageTypeID() > 0)
			{
				// either version 5.x or page is content page
				$al = AssetList::getInstance();
				$al->register('javascript', 'zipgallery/sw', 'js/swiper.jquery.min.js', [], $this->pkgHandle);
				$al->register('javascript', 'zipgallery',    'js/zipGallery.js',        [], $this->pkgHandle);
				$al->register('css',        'zipgallery/sw', 'css/swiper.min.css',      [], $this->pkgHandle);
				$al->register('css',        'zipgallery',    'css/zipGallery.css',      [], $this->pkgHandle);
				$al->registerGroup('zipgallery', [
					['javascript', 'zipgallery/sw'],
					['javascript', 'zipgallery'],
					['css',        'zipgallery/sw'],
					['css',        'zipgallery']
				]);
				$v = View::getInstance();
				$v->requireAsset('zipgallery');

				$msgs = [
					'zg_add'				=> t('Insert ZIP gallery'),
					'zg_edit'				=> t('Edit link to ZIP gallery'),
					'zg_zipurl'				=> t('Link to ZIP file'),
					'zg_no_zip'				=> t('File is no ZIP archive'),
					'zg_url_non_empty'		=> t('Link must not be empty'),
					'zg_zipselect' 			=> t('Select ZIP file'),
					'zg_linktitle'			=> t('Title of gallery link'),
					'zg_title_non_empty'	=> t('Link title must not be empty'),
					'zg_resolve_err'		=> t('Error resolving URL <%s>'),
					'zg_no_images'			=> t('Gallery <%s> has no valid images'),
					'zg_load_err'			=> t('Error loading information from file <%s>')
				];
				$script_tag = '<script type="text/javascript">var zg_messages = ' . json_encode($msgs) . '</script>';
				$v->addFooterItem($script_tag);
			}
		});
	}
}
