<?php namespace ProcessWire;

/**
 * ProcessWire Pro Cache: Static cache management
 *
 * Copyright (C) 2020 by Ryan Cramer
 *
 * This is a commercially licensed and supported module
 * DO NOT DISTRIBUTE
 *
 * @method bool allowClearCacheEntry(Page $page, ProCacheStaticEntry $entry)
 * @method string writeCacheFileReady(Page $page, $content, $file, array $options = array())
 * @method bool clearPageReady(Page $page, array $options)
 * @method void clearedPage(Page $page, array $options)
 *
 */

class ProCacheStaticClear extends ProCacheClass {
	
	/**
	 * @var ProCacheStatic|null
	 * 
	 */
	protected $static = null;
	
	/**
	 * Names of hookable methods and whether hooked
	 *
	 * @var array
	 *
	 */
	protected $hookers = array();
	
	/**
	 * Construct
	 *
	 * @param ProCache $procache
	 * @param ProCacheStatic $static
	 *
	 */
	public function __construct(ProCache $procache, ProCacheStatic $static) {
		$this->static = $static;
		parent::__construct($procache);
	}

	/**
	 * Clear entire cache
	 *
	 * @return int Number of pages cleared
	 *
	 */
	public function clearAll() {
		$table = $this->procache->getTable();
		$cachePath = $this->static->getCachePath();
		if($this->testMode()) {
			$this->addTestData($this->_('Remove all cache files (full clear)'));
		} else {
			$this->files()->rmdir2($cachePath, true);
		}
		$sql = $this->testMode() ? "SELECT path FROM $table" : "DELETE FROM $table";
		$query = $this->wire()->database->prepare($sql);
		$query->execute();
		$qty = $query->rowCount();
		$query->closeCursor();
		if($this->wire()->config->debug) {
			$this->message($this->_('ProCache cleared all pages') . " ($qty)", Notice::debug);
		}
		return $qty;
	}
	
	/**
	 * Clear by path or wildcard path, regardless of page
	 *
	 * @param string $path Specify exact path or wildcard path to page or URL segment to clear
	 *  - Examples of wildcard paths: "/tours/*" or "*boat*" or "*-bike/"
	 * @param array $options
	 *  - `rmdir` (bool): Remove directories rather than index files? (default=false)
	 *  - `getFiles` (bool): Get array of files that were cleared, rather than a count? (default=false)
	 * @return int|array Quantity or array of files and/or directories that were removed
	 *
	 */
	public function clearPath($path, array $options = array()) {
		
		$defaults = array(
			'rmdir' => false, 
			'getFiles' => false,
		);
		
		$options = array_merge($defaults, $options);
		$entries = $this->static->findCacheEntries(array('path' => $path));
		$cachePath = $this->static->getCachePath();
		
		$filesCleared = array();
		$pathsCleared = array();
		$pageIdsCleared = array();
		$indexNamesByExt = array();
		
		foreach($entries as $entry) {
			/** @var ProCacheStaticEntry $entry */

			$indexNames = array();
			$path = $entry->path;
			$pageID = $entry->pages_id;
			
			if(!$options['rmdir']) {
				// index names only needed if clearing by file 
				$template = $entry->template;
				if(!$template) continue;
				$ext = $this->static->getContentTypeExt($template);
				if(!isset($indexNamesByExt[$ext])) {
					$indexNamesByExt[$ext] = $this->static->cacheIndexBasenames($ext, true);
				}
				$indexNames = $indexNamesByExt[$ext];
			}

			if($this->testMode()) {
				// in test mode we only identify what would be cleared
				if($options['rmdir']) {
					$this->addTestData($this->_('delete path'), null, $path);
				} else {
					foreach($indexNames as $indexName => $indexInfo) {
						$cacheFile = $path . $indexName;
						$this->addTestData($this->_('delete file'), null, $cacheFile);
						$filesCleared[] = $cacheFile;
					}
				}
				$pathsCleared[] = $entry->path;
				$pageIdsCleared[$pageID] = $pageID;
				
			} else {
				// live mode
				$path = $cachePath . trim($path, '/') . '/';
				if(!is_dir($path)) continue;
				$pathsCleared[] = $path;
				$pageIdsCleared[$pageID] = $pageID;

				if($options['rmdir']) {
					// remove a path entirely
					$this->files()->rmdir2($path, true);
					$pageIdsCleared[$pageID] = $pageID;
				} else {
					// remove index files 
					foreach($indexNames as $indexName => $indexInfo) {
						$cacheFile = $path . $indexName;
						if(!file_exists($cacheFile)) continue;
						if($this->files()->unlink($cacheFile)) $filesCleared[] = $cacheFile;
					}
				}
			}
		}
		
		$itemsCleared = empty($filesCleared) ? $pathsCleared : $filesCleared;
		$this->clearPageRows($pageIdsCleared, $pathsCleared);

		return $options['getFiles'] ? $itemsCleared : count($itemsCleared);
	}
	
	/**
	 * Clear entire Page branch (Page and everything below it)
	 *
	 * @param Page $page
	 * @return int
	 *
	 */
	public function clearBranch(Page $page) {

		$table = $this->procache->getTable();
		$cachePaths = $this->static->getPageCachePathsLanguages($page);

		foreach($cachePaths as $pageCachePath) {
			if($this->testMode()) {
				$this->addTestData($this->_('delete branch path'), $page, $pageCachePath);
			} else if(is_dir($pageCachePath)) {
				$this->files()->rmdir2($pageCachePath, true);
			}
		}

		if($this->testMode()) return 0;

		$sql = "DELETE FROM $table WHERE pages_id=:pages_id OR parent_id=:parent_id OR MATCH(data) AGAINST(:against)";
		$query = $this->wire()->database->prepare($sql);
		$query->bindValue(':pages_id', $page->id, \PDO::PARAM_INT);
		$query->bindValue(':parent_id', $page->id, \PDO::PARAM_INT);
		$query->bindValue(':against', "p$page->id", \PDO::PARAM_STR);
		$query->execute();
		$qty = $query->rowCount();
		$query->closeCursor();

		return $qty;
	}

	/**
	 * Clear the cache for multiple pages
	 *
	 * @param PageArray $items
	 * @param array $options
	 *  - `getFiles` (bool): Get array of files that were cleared, rather than a count? (default=false)
	 * @return int|array Quantity or array of files and/or directories that were removed
	 *
	 */
	public function clearPages(PageArray $items, $options = array()) {

		$defaults = array(
			'getFiles' => is_bool($options) ? $options : false,
		);

		$options = is_array($options) ? array_merge($defaults, $options) : $defaults;
		$itemsCleared = $options['getFiles'] ? array() : 0;

		foreach($items as $page) {
			/** @var Page $page */
			$result = $this->clearPage($page, $options);
			if($options['getFiles']) {
				$itemsCleared = array_merge($itemsCleared, $result);
			} else {
				$itemsCleared += $result;
			}
		}
		return $itemsCleared;
	}

	/**
	 * Clear the cache for a specific page 
	 *
	 * Default behavior is to clear for all languages, paginations and URL segment variations.  
	 * To clear only specific languages, paginations or URL segments, use the options. 
	 *
	 * @param Page $page
	 * @param array $options
	 *  - `language` (string|int|Language|bool): Clear only this language (default='')
	 *  - `urlSegmentStr` (string): Clear only entries matching this URL segment string, wildcards OR regex OK (default='')
	 *  - `urlSegments` (array|bool): Clear only entries having any of these URL segments, false to clear no URL segments, omit to clear all. (default=[])
	 *  - `pageNum` (int|bool): Clear only pagination number (i.e. 2), true to clear all pageNum>1, false to clear no pageNum>1, omit to clear all (default=0)
	 *  - `clearRoot` (bool|null): Clear root index of page path? (default=false when specific URL segments or paginations requested, true otherwise)
	 *  - `rmdir` (bool): Remove directories rather than index files? (default=false)
	 *  - `getFiles` (bool): Get array of files that were cleared, rather than a count? (default=false)
	 * @return int|array Quantity or array of files and/or directories that were removed
	 *
	 */
	public function clearPage(Page $page, $options = array()) {

		$defaults = array(
			'getFiles' => is_bool($options) ? $options : false,
			'language' => '', // clear only this language name
			'urlSegmentStr' => '', // clear only matching this URL segment string, wildcards or regex OK
			'urlSegments' => array(), // clear only those having any of these URL segments
			'pageNum' => 0, // clear only this pageNum, true to clear all pageNum, false to clear no pageNum
			'rmdir' => false, // remove directories rather than index files?
			'languageID' => 0, // set automatically by clearPageInit when 'language' option is specified
			'clearRoot' => null, // clear root index of page path, sans URL-segments, etc.?
			'flags' => -1, // clear only rows matching these flags or -1 to ignore
			'page' => $page, // used by findCacheEntries() call
		);

		$options = is_array($options) ? array_merge($defaults, $options) : $defaults;
		$this->clearPageInit($page, $options);
		if(!$this->clearPageReady($page, $options)) return $options['getFiles'] ? array() : 0;

		$config = $this->wire()->config;
		$procache = $this->procache;
		$cachePath = $this->static->getCachePath();
		$pageIsCacheable = in_array($page->template->id, $procache->cacheTemplates);
		$pagePath = $page->path();
		$ext = $this->static->getContentTypeExt($page);
		$indexNames = $this->static->cacheIndexBasenames($ext, true);

		$filesCleared = array();
		$pathsCleared = array();
		$pathsNotCleared = array(); // Paths we were disallowed from clearing

		if($options['clearRoot'] !== false) {
			// clear index files
			$filesCleared = $this->clearPageIndexFiles($page, $options);
		}

		// clear urlSegment, pageNum, language variations
		foreach($this->static->findCacheEntries($options) as $entry) {
			/** @var ProCacheStaticEntry $entry */

			$path = $entry->path;
			if($path === $pagePath) continue; // already cleared as part of clearPageIndexFiles()

			if($this->hookers['allowClearCacheEntry']) {
				if(!$this->allowClearCacheEntry($page, $entry)) {
					// hook indicated it may not be cleared
					$pathsNotCleared[] = $entry->path;
					continue;
				}
			}

			if($this->testMode()) {
				// in test mode we only identify what would be cleared
				if(!$pageIsCacheable) continue;
				if($options['rmdir']) {
					$this->addTestData($this->_('delete segment path'), $page, $path);
					$pathsCleared[] = $entry->path;
					continue;
				}
				foreach($indexNames as $indexName => $indexInfo) {
					$cacheFile = $path . $indexName;
					$indexInfo['languageID'] = $entry->language_id;
					$this->addTestData($this->_('delete segment file'), $page, $cacheFile);
					$filesCleared[] = $cacheFile;
				}
				continue;
			}
			
			$path = $cachePath . trim($path, '/') . '/';

			if(!is_dir($path)) {
				// if not there, skip, as its parent dir may have already been removed
				continue;
			}

			// remove a pageNum or urlSegment path entirely
			if($options['rmdir']) {
				$this->files()->rmdir2($path, true);
				$pathsCleared[] = $path;
				continue;
			}

			// remove index files in segment paths
			foreach($indexNames as $indexName => $indexInfo) {
				$cacheFile = $path . $indexName;
				if(!file_exists($cacheFile)) continue;
				if($this->files()->unlink($cacheFile)) $filesCleared[] = $cacheFile;
			}
		}

		$itemsCleared = array_merge($filesCleared, $pathsCleared);
		$numItemsCleared = count($filesCleared) + count($pathsCleared);
		$numRowsCleared = $this->clearPageRows($page, $pathsNotCleared, true);
		
		if(!empty($this->hookers['clearedPage'])) {
			$options['filesCleared'] = $filesCleared;
			$options['pathsCleared'] = $pathsCleared;
			$options['pathsNotCleared'] = $pathsNotCleared;
			$options['numRowsCleared'] = $numRowsCleared;
			$this->clearedPage($page, $options);
		}
		
		if($this->testMode()) {
			return $options['getFiles'] ? $itemsCleared : $numItemsCleared;
		}

		if($config->debug && $pageIsCacheable) {
			$this->message($this->_('ProCache cleared page:') . ' ' .
				$page->path . ' (' .
				sprintf($this->_n('1 dir/file', '%d dirs/files', $numItemsCleared), $numItemsCleared) . ' / ' .
				sprintf($this->_n('1 row', '%d rows', $numRowsCleared), $numRowsCleared) .
				')',
				Notice::debug
			);
		}

		return $options['getFiles'] ? $itemsCleared : $numItemsCleared;
	}

	
	/**
	 * Initialize page clear
	 *
	 * @param Page $page
	 * @param $options
	 *
	 */
	protected function clearPageInit(Page $page, &$options) {
		if($page) {}
		
		$options['flags'] = (int) $options['flags']; 

		if(!isset($this->hookers['clearPageReady'])) {
			$this->hookers['clearPageReady'] = $this->hasHook('clearPageReady()');
			$this->hookers['clearedPage'] = $this->hasHook('clearedPage()');
			$this->hookers['allowClearCacheEntry'] = $this->hasHook('allowClearCacheEntry()');
		}

		$lang = $options['language'];
		$options['language'] = '';
		$options['languageID'] = 0;
		
		if($lang && $this->wire()->languages) {
			if(!is_object($lang)) {
				$lang = ctype_digit($lang) ? (int) $lang : $this->wire()->sanitizer->pageName($lang);
				$lang = $this->wire()->languages->get($lang);
			}
			if(is_object($lang) && $lang->id) {
				$options['language'] = $lang->name;
				$options['languageID'] = $lang->id;
			}
		}
	
		$hasFilters = true;
		if(empty($options['pageNum']) && empty($options['urlSegmentStr']) && empty($options['urlSegments'])) {
			$hasFilters = false;
		}
	
		if($options['clearRoot'] === true) {
			// clear ONLY root, unless filters are present, in which case clear ALSO root
			if($hasFilters) $options['clearRoot'] = null;
			
		} else if($options['clearRoot'] === null) {
			// clearRoot==null means auto-detect
			// in this case we clear root only if no pageNum, language or urlSegmentStr was requested
			if($hasFilters) $options['clearRoot'] = false;
			
		} else if($options['clearRoot'] === false) {
			// forced skip of clear root index files
		}
	}
	
	/**
	 * Clear DB rows for Page
	 *
	 * @param Page|int|array|PageArray $page Page object, page ID or array of page IDs or objects
	 * @param array $paths Optional paths to clear or skip
	 * @param bool $skip Skip $paths? Or omit to limit clear to them
	 * @return int
	 *
	 */
	protected function clearPageRows($page, $paths = array(), $skip = false) {

		// clear from DB table
		$binds = array();
		$ands = array();
		$utf8 = $this->wire()->config->pageNameCharset === 'UTF8';
		$table = $this->procache->getTable();

		foreach($paths as $n => $path) {
			if($utf8) $path = $this->wire()->sanitizer->pagePathName($path, 4); // 4=toAscii
			if(empty($path)) continue;
			$key = 'path' . (int) $n;
			$ands[] = $skip ? "path!=:$key" : "path=:$key";
			$binds[":$key"] = $path;
		}

		if($this->testMode()) {
			$sql = "SELECT path FROM $table ";
		} else {
			$sql = "DELETE FROM $table ";
		}
	
		if(is_array($page) || $page instanceof PageArray) {
			$ids = array();
			foreach($page as $id) $ids[] = (int) "$id";
			if(!count($ids)) return 0;
			$sql .= "WHERE pages_id IN(" . implode(',', $ids) . ") ";
		} else {
			$sql .= "WHERE pages_id=:pages_id ";
			$binds[":pages_id"] = (int) "$page";
		}
		
		if(count($ands)) {
			$sql .= "AND (" . implode(' AND ', $ands) . ")";
		}

		$query = $this->database->prepare($sql);

		foreach($binds as $bindKey => $bindValue) {
			$query->bindValue($bindKey, $bindValue);
		}

		$query->execute();
		$qty = $query->rowCount();
		$query->closeCursor();

		return $qty;
	}

	/**
	 * Clear index files for page
	 *
	 * @param Page $page
	 * @param $options
	 *  - `language` (Language|int|string): Clear only this language 
	 * @return array Files that were cleared
	 *
	 */
	protected function clearPageIndexFiles(Page $page, array $options = array()) {
		
		$defaults = array(
			'language' => null, 
		);

		$options = array_merge($defaults, $options);
		$ext = $this->static->getContentTypeExt($page);
		$cachePathRoot = $this->static->getCachePath();
		$cachePaths = $this->static->getPageCachePathsLanguages($page, $options);
		$hosts = count($this->procache->cacheHosts) ? $this->procache->cacheHosts : array('');
		$schemes = $this->procache->https ? array('http', 'https') : array('http');
		$pageIsCacheable = in_array($page->template->id, $this->procache->cacheTemplates);
		$filesCleared = array();

		foreach($cachePaths as $languageName => $pageCachePath) {

			if(!$this->testMode() && !is_dir($pageCachePath)) continue;

			// clear cache for saved page, index files only
			// clear for all defined hosts and schemes (http/https)

			foreach($hosts as $host) {
				foreach($schemes as $scheme) {
					$https = $scheme === 'https';
					$indexName = $this->static->cacheIndexBasename($host, $https, $ext);
					$cacheFile = $pageCachePath . $indexName;
					if($this->testMode()) {
						if($pageIsCacheable) {
							$this->addTestData($this->_('delete file'), $page, $cacheFile);
							$filesCleared[] = $cacheFile;
						}
					} else if(is_file($cacheFile)) {
						if($this->files()->unlink($cacheFile)) {
							$filesCleared[] = $cacheFile;
						}
					}
				}
			}
		}
		
		// make returned files relative to root cache path
		foreach($filesCleared as $key => $file) {
			$filesCleared[$key] = str_replace($cachePathRoot, '/', $file); 
		}

		return $filesCleared;
	}
	
	/**
	 * Allow clearing given cache entry?
	 *
	 * #pw-hooker
	 *
	 * @param Page $page
	 * @param ProCacheStaticEntry $entry Cache entry row from DB
	 * @return bool
	 *
	 */
	protected function ___allowClearCacheEntry(Page $page, ProCacheStaticEntry $entry) {
		if($page && $entry) {}
		return true;
	}
	
	/**
	 * Hook called right before a Page is cleared from cache
	 *
	 * Hooks can make this return false to skip clearing the Page
	 *
	 * @param Page $page
	 * @param array $options
	 * @return bool
	 *
	 */
	protected function ___clearPageReady(Page $page, array $options) {
		if($options) {}
		return $page->id ? true : false;
	}

	/**
	 * Hook called after page has been cleared
	 *
	 * Hooks can make this return false to skip clearing the Page
	 *
	 * @param Page $page
	 * @param array $options
	 *
	 */
	protected function ___clearedPage(Page $page, array $options) {
		if($options && $page) {}
	}
	
	/**
	 * @return bool
	 *
	 */
	public function testMode() {
		return $this->static->getTestMode();
	}

	/**
	 * Add test mode data
	 *
	 * @param string $action
	 * @param Page $page
	 * @param string $file
	 *
	 */
	public function addTestData($action, Page $page = null, $file = '') {
		$this->static->addTestData($action, $page, $file); 
	}

	/**
	 * Cache maintenance to clear out cache files and DB entries have have expired
	 *
	 * This is run by ProCache every 30 seconds
	 *
	 * @todo Clear per template?
	 *     https://processwire.com/talk/topic/22628-feature-request-clear-cache-via-cronjob-and-selectors/
	 *
	 * @return int Number of cache files removed
	 *
	 */
	public function cacheMaintenance() {

		$templates = $this->wire('templates'); /** @var Templates $templates */
		$sanitizer = $this->wire('sanitizer'); /** @var Sanitizer $sanitizer */
		$database = $this->wire('database'); /** @var WireDatabasePDO $database */
		$cachePath = $this->static->getCachePath();
		$maintenanceFile = $cachePath . ".run-maintenance";
		$lastMaintenanceFile = $cachePath . ".last-maintenance";
		$time = time();

		// check if maintenance has already occurred within the last 30 seconds
		if(is_file($lastMaintenanceFile)) {
			if(filemtime($lastMaintenanceFile) + ProCache::MAINTENANCE_SECONDS > $time) return 0;
			$this->files()->unlink($lastMaintenanceFile);
		}

		// check if maintenance is already occurring under another request
		if(is_file($maintenanceFile) && filemtime($maintenanceFile) > ($time - 30)) return 0;

		$timeStr = date('Y-m-d H:i:s');

		// reserve the maintenance file
		$this->files()->filePutContents($maintenanceFile, "Started $timeStr");

		// handle cacheTimeCustom	
		$where = '';
		$templateIDs = array();

		foreach($this->static->getCacheTime() as $templateName => $cacheTime) {
			if($cacheTime < 1 || $cacheTime == $this->procache->cacheTime) continue;
			$template = $templates->get($sanitizer->name($templateName));
			if(!$template) continue;
			$maxAge = date('Y-m-d H:i:s', $time - $cacheTime);
			$templateID = (int) $template->id;
			$where .= ($where ? 'OR ' : '') . "(created<'$maxAge' AND templates_id=$templateID) ";
			$templateIDs[] = $templateID;
		}

		$table = $this->procache->getTable();
		$maxAge = date('Y-m-d H:i:s', $time - $this->procache->cacheTime);

		if($where) {
			$where .= " OR (created<'$maxAge' AND templates_id NOT IN(" . implode(',', $templateIDs) . "))";
		} else {
			$where .= "created<'$maxAge'";
		}

		$sql = "SELECT path, templates_id FROM $table WHERE $where ORDER BY created ASC";
		$query = $database->prepare($sql);
		$query->execute();
		$cnt = 0;
		$hosts = $this->procache->cacheHosts;
		$httpsOK = $this->procache->https;
		if(!count($hosts)) $hosts = array('');

		while($row = $query->fetch(\PDO::FETCH_NUM)) {
			list($path, $templateID) = $row;
			$template = $templates->get((int) $templateID);
			$ext = $template ? $this->static->getContentTypeExt($template) : 'html';
			foreach($hosts as $host) {
				foreach(array(false, true) as $https) {
					if($https && !$httpsOK) continue;
					$index = $this->static->cacheIndexBasename($host, $https, $ext);
					$file = $cachePath . ltrim($path, '/') . $index;
					if(!is_file($file)) continue;
					if($this->files()->unlink($file)) $cnt++;
				}
			}
		}

		$query->closeCursor();

		if($cnt) $database->exec("DELETE FROM $table WHERE $where");
		$this->files()->unlink($maintenanceFile);

		// record the last run time
		$this->files()->filePutContents($lastMaintenanceFile, "Maintenance last run on $timeStr and cleared $cnt cache files.");

		return $cnt;
	}


}	
