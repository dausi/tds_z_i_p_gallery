<?php
namespace Concrete\Package\TdsZIPGallery\Block\TdsZIPGallery;

use Concrete\Core\Block\BlockController;
use Concrete\Core\File\File;
use Concrete\Package\TdsZIPGallery\Controller as ZipController;

class Controller extends BlockController
{
    protected $btInterfaceWidth = 700;
    protected $btInterfaceHeight = 800;
    protected $btCacheBlockOutput = true;
    protected $btTable = 'btTdsZIPGallery';
    protected $btDefaultSet = 'basic';

    public function getBlockTypeDescription()
    {
        return t('Add ZIP Image Gallery on your pages.');
    }

    public function getBlockTypeName()
    {
        return t('ZIP Image Gallery');
    }

    public function add()
    {
		$this->set('info', '');
        $this->set('zipFileID', 0);
        $this->set('zipFilePath', '');
        $this->set('zipFileObject', null);
    	$this->set('flipRate', 20);
    	$this->set('single', 0);
    	$this->set('startImg', -1);
    	$this->set('singleName', '');
        $this->set('singleWidth', 0);
        $this->set('img_title', '');
        $this->set('img_alt', '');
    	$this->set('numSlides', 1);
        $this->set('pagination', 0);
    	$this->set('spaceBetween', 10);
 		$this->set('imgUnique', 0);
		$this->set('nav', 'none');
 		$this->set('showit', 0);
		$this->set('subCaption', '');
 		$this->set('subWidth', 50);
 		$this->set('subHeight', 50);
		$this->set('inhibitDownload', 0);
 		$this->set('messages', (new ZipController($this->app))->getMessages());

    }
	
    public function edit()
    {
        $zipFileObject = null;
		$zipFileID = $this->getFileID();
        if ($zipFileID > 0) {
            $zipFileObject = $this->getFileObject();
        }
        $this->set('zipFileID', $zipFileID);
		$this->set('zipFilePath', $zipFileObject->getRelativePath());
        $this->set('zipFileObject', $this->getFileObject());
        $this->set('messages', (new ZipController($this->app))->getMessages());
    }

    public function view()
    {
        $zipFileObject = $this->getFileObject();
        $this->set('zipFileID', $this->getFileID());
        $this->set('zipFilePath', $zipFileObject->getRelativePath());
		$messages = (new ZipController($this->app))->getMessages();
		$this->set('msg', $messages['zg_show_gall']);
    }

	public function save($args)
	{
		if (!isset($args['single']))
			$args['singleName'] = '';
        $args['pagination']	     = isset($args['pagination'])      ? 1 : 0;
		$args['imgUnique']	     = isset($args['imgUnique'])       ? 1 : 0;
		$args['showit']		     = isset($args['showit'])          ? 1 : 0;
		$args['inhibitDownload'] = isset($args['inhibitDownload']) ? 1 : 0;
		parent::save($args);
	}

    /**
     * @return int
     */
    public function getFileID()
    {
        return isset($this->record->zipFileID) ? $this->record->zipFileID : (isset($this->zipFileID) ? $this->zipFileID : 0);
    }

    /**
     * @return File
     */
    public function getFileObject()
    {
        return File::getByID($this->getFileID());
    }

}
