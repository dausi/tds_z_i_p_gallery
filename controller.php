<?php
/**
 * ZIP Image Gallery add-on controller.
 *
 * Copyright 2016 - 2019 - TDSystem Beratung & Training - Thomas Dausner
 */

namespace Concrete\Package\TdsZIPGallery;

use Concrete\Core\Package\Package;
use Concrete\Core\Editor\Plugin;
use Concrete\Core\Asset\AssetList;
use Concrete\Core\Support\Facade\Events;
use Concrete\Core\View\View;
use Concrete\Core\Support\Facade\Route;
use Concrete\Core\Block\BlockType\BlockType;

class Controller extends Package
{

    protected $pkgHandle = 'tds_z_i_p_gallery';
    protected $appVersionRequired = '8.0';
    protected $pkgVersion = '0.9.9.2';
    protected $cked_plugin_key = 'site.sites.default.editor.ckeditor4.plugins.selected';

    public function getPackageDescription()
    {
        return t('Adds ZIP Image Gallery Block and ZIP Image Gallery button to the WYSIWYG editor');
    }

    public function getPackageName()
    {
        return t('TDS ZIP Image Gallery');
    }

    public function getMessages()
    {
        return [
            'zg_zig_file' => t('ZIP Image Gallery file'),
            'zg_single' => t('Single image'),
            'zg_selSingle' => t('Select initial / single image'),
            'zg_selectImage' => t('Select image by click'),
            'zg_flip_rate' => t('Gallery flipping rate [s] (0 - 20), set to 0 for no flipping'),
            'zg_sl_per_view' => t('Slides per view (1 - 20)'),
            'zg_sl_space' => t('Space between slides [px] (0 - 200)'),
            'zg_default_caption' => '{%localised%&ensp;}{<b class="dim">&copy;%copyright% - }%index% - %filename%</b>',
            'zg_caption' => t('Image caption code (see <a target="_blank" href="http://tdsystem.eu/en/projects/zip-image-gallery/zip-image-gallery-general-application#caption">documentation</a>) - get <a href="#" class="def">default</a>'),
            'zg_unique' => t('Keep images unique over pages (for global ZIP Image Galleries)'),
            'zg_pagination' => t('Show pagination'),
            'zg_navigation' => t('Navigation'),
            'zg_singleWidth' => t('Max. image width (set to 0 for full width)'),
            'zg_img_title' => t('Image title'),
            'zg_img_alt' => t('Alternate image title'),
            'zg_nav_none' => t('none'),
            'zg_nav_black' => t('black'),
            'zg_nav_white' => t('white'),
            'zg_show_gall' => t('Show ZIP Image Gallery on click'),
            'zg_sub_caption' => t('Image caption code (same as above) - get <a href="#" class="def">default</a>'),
            'zg_add' => t('Insert ZIP Image Gallery'),
            'zg_edit' => t('Edit link to ZIP Image Gallery'),
            'zg_zipurl' => t('Link to ZIP file'),
            'zg_no_zip' => t('File is no ZIP archive'),
            'zg_url_non_empty' => t('Link must not be empty'),
            'zg_zipselect' => t('Select ZIP Image Gallery file'),
            'zg_linktitle' => t('Title of ZIP Image Gallery link'),
            'zg_title_non_empty' => t('Link title must not be empty'),
            'zg_indextitle' => t('Index of first image to show'),
            'zg_index_gt_zero' => t('Index must by greater equal 1'),
            'zg_thumbs_msg' => t('ZIP Image Gallery thumbnail size (WxH)'),
            'zg_thumbs_err' => t('Thumbnail sizes (WxH) must be in (10 ... 200)'),
            'zg_inhibitDownload' => t('Inhibit download of images'),
            'zg_resolve_err' => t('Error resolving URL &lt;%s&gt;'),
            'zg_no_images' => t('ZIP Image Gallery &lt;%s&gt; has no valid images'),
            'zg_load_err' => t('Error loading information from file &lt;%s&gt;'),
        ];
    }

    public function install()
    {
        $pkg = parent::install();

        $cked_plugins = $this->getConfig()->get($this->cked_plugin_key);
        if ($cked_plugins == null)
        {
            $cked_plugins = [ 'zipgallery' ];
        }
        else
        {
            array_push($cked_plugins, 'zipgallery');
        }
        $this->getConfig()->save($this->cked_plugin_key, $cked_plugins);

        $blk = BlockType::getByHandle($this->pkgHandle);
        if (!is_object($blk))
        {
            BlockType::installBlockType($this->pkgHandle, $pkg);
        }
    }

    public function uninstall()
    {
        parent::uninstall();

        $cked_plugins = $this->getConfig()($this->cked_plugin_key);
        if ($cked_plugins != null)
        {
            $cked_plugins = array_diff($cked_plugins, ['zipgallery']);
            $this->getConfig()->save($this->cked_plugin_key, $cked_plugins);
        }
    }

    public function on_start()
    {
        $routes = [
            'getinfo' => 'getInfo',
            'getimage' => 'getImage',
            'getthumb' => 'getThumb',
            'geturl' => 'getUrl'
        ];
        foreach ($routes as $url => $method)
        {
            Route::register('/ccm/galleries/' . $url, 'Concrete\Package\TdsZIPGallery\Controller\Gallery::' . $method);
        }

        $params = [
            'cache_size' => 100000,        // for 'db' cache method
            'expires' => '+2 days'    // for 'cache' cache method
        ];
        foreach ($params as $param => $value)
        {
            if (!$this->getConfig()->has('tds_zip_gallery.' . $param))
            {
                $this->getConfig()->save('tds_zip_gallery.' . $param, $value);
            }
        }
        $al = AssetList::getInstance();
        $assetGroup = 'editor/ckeditor/zipgallery';
        $al->register('javascript', $assetGroup, 'cked/register.js', [], $this->pkgHandle);
        $al->register('css', $assetGroup, 'cked/plugin.css', [], $this->pkgHandle);
        $al->registerGroup($assetGroup, [
            ['javascript', $assetGroup],
            ['css', $assetGroup]
        ]);
        $plugin = new Plugin();
        $plugin->setKey($this->pkgHandle);
        $plugin->setName(t('ZIP Gallery Plugin'));
        $plugin->requireAsset($assetGroup);
        $editor = $this->app->make('editor');
        $editor->getPluginManager()->register($plugin);
        $editor->getPluginManager()->select('tds_z_i_p_gallery');

        $filetypes = $this->getConfig()->get('concrete.upload.extensions');
        if (strpos($filetypes, '*.zip') === false)
        {
            $filetypes .= ';*.zip';
            $this->getConfig()->save('concrete.upload.extensions', $filetypes);
        }

        Events::addListener('on_before_render', function ($event) {

            $al = AssetList::getInstance();
            $al->register('javascript', 'zipgallery/sw', 'js/swiper.min.js', [], $this->pkgHandle);
            $al->register('javascript', 'zipgallery', 'js/zipGallery.js', [], $this->pkgHandle);
            $al->register('css', 'zipgallery/sw', 'css/swiper.min.css', [], $this->pkgHandle);
            $al->register('css', 'zipgallery', 'css/zipGallery.css', [], $this->pkgHandle);
            $al->register('css', 'zipgallery/block', 'blocks/' . $this->pkgHandle . '/css/form.css',
                [], $this->pkgHandle);
            $al->registerGroup('zipgallery', [
                ['javascript', 'zipgallery/sw'],
                ['javascript', 'zipgallery'],
                ['css', 'zipgallery/sw'],
                ['css', 'zipgallery'],
                ['css', 'zipgallery/block'],
            ]);
            $v = View::getInstance();
            $v->requireAsset('javascript', 'jquery');
            $v->requireAsset('font-awesome');
            $v->requireAsset('zipgallery');
            $script_tag = '<script type="text/javascript"> if (typeof ZIPGallery === "undefined") ZIPGallery = {}; ZIPGallery.messages = ' . json_encode($this->getMessages()) . '</script>';
            $v->addFooterItem($script_tag);
        });
    }
}
