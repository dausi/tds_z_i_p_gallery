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
use Core;
use Config;
use Database;
use DateTime;

defined('C5_EXECUTE') or die("Access Denied.");

class ZipGalleryCache
{
	// general parameter
	private $ignorePatterns = [];

	// DB based parameters
	private $maxEntries = 10000;
	private $table = 'TdsZIPGalleryCache';
	private $db = null;

	// c5 cache/expensive bases parameters
	private $chId =  'TdsZIPGalleryCache/';
	private $expires = '+2 days';

	public function __construct()
	{
		$this->db = Database::connection();
	}

	public function __destruct()
	{
	}

	public function setIgnorePatterns($ignorePatterns)
	{
		$this->ignorePatterns = $ignorePatterns;
	}

	/*
	 * get entry from c5 cache/expensive.
	 *
	 * @return null or content
	 */
	public function getEntryCacheExp($cacheName)
	{
		$cacheEntry = str_replace('/', '#', $cacheName);
		$data = null;
		$expCache = Core::make('cache/expensive');
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
	public function setEntryCacheExp($cacheName, $data)
	{

		$cacheEntry = str_replace('/', '#', $cacheName);
		$expCache = Core::make('cache/expensive');
		$cache = $expCache->getItem($this->chId	. $cacheEntry);
		if ($cache->isMiss())
		{
			//$cache->lock();
			$exp = new DateTime($this->expires);
			if (version_compare(Config::get('concrete')['version_installed'], '8') >= 0)
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
	public function getEntryDB($oldest, $cacheName)
	{
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
				if (empty($data))
				{
					$data = null;
					$this->db->delete($this->table, ['cacheEntry' => $cacheName]);
				}
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
	public function setEntryDB($cacheName, $data)
	{
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
