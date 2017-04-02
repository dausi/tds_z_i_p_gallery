<?php
/**
 * Class ZipGalleryCache
 *
 * implemantation of a simple cache.
 *
 * Copyright 2017 - TDSystem Beratung & Training - Thomas Dausner (aka dausi)
 *
 * All cache entries are kept in a database table. Cache entry names are set up in caller.
 *
 * On running into $this->maxEntries number of cache entries the oldest entry is discarded.
 *
 */
namespace Concrete\Package\TdsZIPGallery\Src;
use Package;
use Database;
use DateTime;

defined('C5_EXECUTE') or die(_("Access Denied."));

class ZipGalleryCache
{
	// general parameter
	private $ignorePatterns = [];

	// DB based parameters
	private $maxEntries = 0;
	private $table = 'TdsZIPGalleryCache';
	private $db = null;

	// c5 cache bases parameters
	private $chId =  'TdsZIPGalleryCache/';
	private $expires = '+2 days';

	private $method = 'db';

	public function __construct()
	{
		$pkg = Package::getByHandle('tds_z_i_p_gallery');
		$this->maxEntries = $pkg->getConfig()->get('tds_zip_gallery.cache_size');
		$this->expires    = $pkg->getConfig()->get('tds_zip_gallery.expires');
		$this->method	  = $pkg->getConfig()->get('tds_zip_gallery.cache_method');
		$this->db = Database::connection();
	}

	public function __destruct()
	{
	}

	public function setIgnorePatterns($ignorePatterns)
	{
		$this->ignorePatterns = $ignorePatterns;
	}

	public function getEntry($oldest, $cacheName)
	{
		switch ($this->method)
		{
			case 'cache':	return $this->getEntry_cache($oldest, $cacheName); break;
			case 'db':		return $this->getEntry_db($oldest, $cacheName); break;
		}
	}
	public function setEntry($cacheName, $data)
	{
		switch ($this->method)
		{
			case 'cache':	$this->setEntry_cache($cacheName, $data); break;
			case 'db':		$this->setEntry_db($cacheName, $data); break;
		}
	}

	/*
	 * get entry from c5 cache/expensive.
	 *
	 * @return null or content
	 */
	public function getEntry_cache($oldest, $cacheName)
	{
		$cacheEntry = str_replace('/', '#', $cacheName);
		$data = null;
		$expCache = \Core::make('cache/expensive');
		$cache = $expCache->getItem($this->chId	. $cacheEntry);
		if (!$cache->isMiss())
		{
			$data = $cache->get();
		}
		return $data;
	}

	/*
	 * set entry to c5 cache/expensive
	 */
	public function setEntry_cache($cacheName, $data)
	{

		$cacheEntry = str_replace('/', '#', $cacheName);
		$expCache = \Core::make('cache/expensive');
		$cache = $expCache->getItem($this->chId	. $cacheEntry);
		if ($cache->isMiss())
		{
			$cache->lock();
			$exp = new DateTime($this->expires);
			if (substr(Config::get('concrete')['version_installed'], 0, 1) == '8')
			{
				$cache->save($cache->set($data)->expiresAt($exp));
			}
			else
			{
				$cache->set($data, $exp->getTimestamp());
			}
		}
	}

	/*
	 * get entry from DB cache table.
	 * entry found but older than 'oldest' is deleted.
	 *
	 * @return null or content
	 */
	public function getEntry_db($oldest, $cacheName)
	{
		$cacheEntry = $this->cacheFolder . '/' . $cacheName;
		$data = null;
		$sql = ' FROM '. $this->table . ' WHERE cacheEntry = ?';
		$cStat = $this->db->fetchColumn('SELECT timeStamp' . $sql, [$cacheName]);
		if ($cStat != '')
		{
			// cached file exists
			if ($oldest <= $cStat)
			{
				// cached entry is newer or same as $oldest
				$data = $this->db->fetchColumn('SELECT content' . $sql, [$cacheName]);
			}
			else
			{
				// cached entry is older than $oldest
				$this->db->delete($this->table, ['cacheEntry' => $cacheName]);
			}
		}
		return $data;
	}

	/*
	 * set entry to DB cache table
	 */
	public function setEntry_db($cacheName, $data)
	{
		$cacheEntry = $this->cacheFolder . '/' . $cacheName;
		if (preg_match($this->ignorePatterns['re'], $cacheName) === 0)
		{
			$entries = $this->db->fetchColumn(
					'SELECT COUNT(*) FROM '. $this->table
					. ' WHERE cacheEntry NOT LIKE "' . $this->ignorePatterns['db'] . '"');
			if ($entries >= $this->maxEntries)
			{
				// must delete oldest
				$oldest = $this->db->fetchColumn('SELECT MIN(timeStamp) FROM '. $this->table);
				$this->db->delete($this->table, ['timeStamp' => $oldest]);
			}
		}
		$this->db->insert($this->table, [
			'cacheEntry'	=> $cacheName,
			'timeStamp'		=> time(),
			'content'		=> $data
		]);
	}

}
