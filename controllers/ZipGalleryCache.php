<?php
namespace Concrete\Package\TdsZIPGallery\Controller;

defined('C5_EXECUTE') or die("Access Denied.");
/**
 * Class ZipGalleryCache
 *
 * implemantation of a simple cache.
 *
 * Copyright 2017 - TDSystem Beratung & Training - Thomas Dausner
 *
 * All cache entries are kept in a database table. Cache entry names are set up in caller.
 *
 * On running into $this->maxEntries number of cache entries the oldest entry is discarded.
 *
 */

use Database;
use DateTime;
use Punic\Data;


class ZipGalleryCache
{
	/** general parameter
     *
     * @var $ignorePatterns mixed
     */
	private $ignorePatterns = [];

	/** DB based parameters
     *
     * @var $maxEntries integer
     * @var $table string
     * @var $db Database
     */
	private $maxEntries = 100000;
	private $table = 'TdsZIPGalleryCache';
	private $db = null;

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

    /**
     * get entry from DB cache table.
     * entry found but older than 'oldest' is deleted.
     *
     * @param DateTime $oldest
     * @param string $cacheName
     *
     * @return Data|null
     * @throws \Doctrine\DBAL\Exception\InvalidArgumentException
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

	/**
	 * set entry to DB cache table
     *
     * @param string $cacheName
     * @param Data $data
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
