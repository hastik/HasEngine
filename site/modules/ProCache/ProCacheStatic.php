<?php namespace ProcessWire;

/**
 * ProcessWire Pro Cache: Static cache management
 *
 * Copyright (C) 2020 by Ryan Cramer
 *
 * This is a commercially licensed and supported module
 * DO NOT DISTRIBUTE
 * 
 * @method string writeCacheFileReady(Page $page, $content, $file, array $options = array())
 *
 */

class ProCacheStatic extends ProCacheClass {

	/**
	 * Flags used in pages_procache.flags column
	 * 
	 */
	const flagsUrlSegments = 2; // indicates 1+ URL segments
	const flagsPageNum = 4;  // indicates URL with pagination/pageNum
	const flagsLanguage = 8; // indicates URL in non-default language

	/**
	 * @var ProCacheStaticBehaviors|null
	 * 
	 */
	protected $behaviors = null;

	/**
	 * @var ProCacheStaticClear|null
	 * 
	 */
	protected $clear = null;

	/**
	 * @var array
	 * 
	 */
	protected $testData = array();

	/**
	 * @var bool
	 * 
	 */
	protected $testMode = false;
	
	/**
	 * Construct
	 *
	 * @param ProCache $procache
	 *
	 */
	public function __construct(ProCache $procache) {
		parent::__construct($procache);
		require_once(__DIR__ . '/ProCacheStaticEntry.php');
		require_once(__DIR__ . '/ProCacheStaticClear.php');
		require_once(__DIR__ . '/ProCacheStaticBehaviors.php');
	}

	/**
	 * Get ProCacheStaticBehaviors instance
	 * 
	 * @return ProCacheStaticBehaviors
	 * 
	 */
	protected function behaviors() {
		if($this->behaviors !== null) return $this->behaviors;
		$this->behaviors = new ProCacheStaticBehaviors($this->procache);
		return $this->behaviors;
	}
	
	/**
	 * Get ProCacheStaticClear instance
	 * 
	 * @return ProCacheStaticClear
	 *
	 */
	protected function clear() {
		if($this->clear !== null) return $this->clear;
		$this->clear = new ProCacheStaticClear($this->procache, $this);
		return $this->clear;
	}
	
	/**
	 * Create and return a new ProCacheStaticEntry
	 *
	 * @param array $data Optional associative array of data to populate
	 * @return ProCacheStaticEntry
	 *
	 */
	public function newEntry(array $data = array()) {
		$entry = new ProCacheStaticEntry($this->procache);
		if(!empty($data)) $entry->setArray($data);
		return $entry;
	}


	/************************************************************************************************
	 * Cache information
	 *
	 */

	/**
	 * Return the number of pages in the cache
	 *
	 * @return int
	 *
	 */
	public function numCachedPages() {
		$table = $this->procache->getTable();
		$query = $this->wire('database')->prepare("SELECT COUNT(*) FROM $table");
		$query->execute();
		list($cnt) = $query->fetch(\PDO::FETCH_NUM);
		$query->closeCursor();
		return (int) $cnt;
	}

	/**
	 * Return an info array about the given page’s ProCache info or false if not cached
	 *
	 * Returned info array is array("path of cached page" => "date created");
	 * An empty array just indicates the page is enabled for caching, but no cache file exists.
	 *
	 * @param Page $page
	 * @return array|bool
	 *
	 */
	public function pageInfo(Page $page) {

		$procache = $this->procache;
		if(!in_array($page->template->id, $procache->cacheTemplates)) return false;

		$sanitizer = $this->wire('sanitizer'); /** @var Sanitizer $sanitizer */
		$charset = $this->wire('config')->pageNameCharset;
		$cachePath = $this->getCachePath();
		$hosts = $procache->cacheHosts;
		$ext = $this->getContentTypeExt($page);
		$table = $this->procache->getTable();
		$info = array();

		if(!count($hosts)) $hosts = array('');

		$sql = "SELECT * FROM $table WHERE pages_id=:pages_id";
		$query = $this->wire('database')->prepare($sql);
		$query->bindValue(':pages_id', $page->id, \PDO::PARAM_INT);
		$query->execute();

		while($row = $query->fetch(\PDO::FETCH_ASSOC)) {
			if($charset === 'UTF8') {
				$row['path'] = $sanitizer->pagePathName($row['path'], 8); // 8=toUTF8
			}
			foreach($hosts as $host) {
				foreach(array(false, true) as $https) {
					$index = $this->cacheIndexBasename($host, $https, $ext);
					$filename = $cachePath . $row['path'] . $index;
					if(is_file($filename)) {
						$path = $row['path'] . $index;
						$info[$path] = $row['created'];
					}
				}
			}
		}

		$query->closeCursor();
		ksort($info);

		return $info;
	}
	
	/**
	 * Find cache database rows of cache URLs for page matching given criteria
	 *
	 * @param array $options
	 *  - `page` (Page|null): Specify Page object to limit results to that Page.
	 *  - `path` (string): Specify wildcard path to find matching entries, i.e. "/tours/*" or "*boat*" or "*-bike/"
	 *  - `language` (Language|int|string): Match entries in this language, omit to match all, 
	 *     boolean false to match only default language.
	 *  - `urlSegmentStr` (string): Match entries having this URL segment string, wildcards or regex is OK. 
	 *  - `urlSegments` (array|bool): Match entries having any of these URL segments, omit to get all, empty array to allow inclusion of any. 
	 *     boolean false to exclude all URL segments, or boolean true to get only URL segments. 
	 *  - `pageNum` (int|bool): Get only this pageNum, true to get only pageNum>1, false to get no pageNum>1, or int 0 to also include any paginations. 
	 *  - `hasParent` (Page|int): Find entries having this parent page somewhere in their parents
	 * @return ProCacheStaticEntry[]
	 *
	 */
	public function findCacheEntries(array $options = array()) {

		$defaults = array(
			'page' => null, 
			'path' => '',
			'language' => null,
			'languageID' => '',
			'urlSegmentStr' => '',
			'urlSegments' => array(),
			'pageNum' => 0,
			'hasParent' => 0, 
		);

		$options = array_merge($defaults, $options);
		$charset = $this->wire()->config->pageNameCharset;
		$sanitizer = $this->wire()->sanitizer;
		$database = $this->wire()->database;

		$page = $options['page'] instanceof Page ? $options['page'] : new NullPage();
		$path = empty($options['path']) ? '' : $options['path'];

		$entries = array();
		$wheres = array();
		$binds = array();
		$urlSegmentRegex = '';
		$table = $this->procache->getTable();
		
		if($page->id) {
			$wheres[] = "pages_id=:pages_id";
			$binds[':pages_id'] = $page->id;
		}
		
		if($path) {
			if(strpos($path, '*') === false) {
				$wheres[] = "path=:path";
				$binds[':path'] = $path;
			} else {
				$path = str_replace('*', '%', $path);
				$wheres[] = "path LIKE :path";	
				$binds[':path'] = $path;
			}
		}

		if(empty($options['languageID']) && !empty($options['language'])) {
			$options['languageID'] = $this->language($options['language'], 'id'); 
		}
		
		if(strlen($options['urlSegmentStr'])) {
			if(strpos($options['urlSegmentStr'], '*') !== false) {
				$s = trim($options['urlSegmentStr'], '/');
				if($s[0] === '*' || ctype_alnum($s[0])) {
					$urlSegmentRegex = '!^' . str_replace('*', '.*', $s) . '$!';
				} else {
					$urlSegmentRegex = $s;
				}
			}
		}

		if(is_array($options['urlSegments'])) {
			// URL segments are allowed among other results
			if(!empty($options['urlSegments'])) {
				$wheres[] = "(flags & " . self::flagsUrlSegments . ')';
			}
		} else if($options['urlSegments'] === true || $urlSegmentRegex) {
			$wheres[] = "(flags & " . self::flagsUrlSegments . ')';
		} else if($options['urlSegments'] === false) {
			$wheres[] = "NOT(flags & " . self::flagsUrlSegments . ')';
		}
	
		if($options['pageNum'] === 0) {
			// page numbers are allowed among other results
		} else if($options['pageNum'] === false) {
			$wheres[] = "NOT(flags & " . self::flagsPageNum . ')';
		} else if($options['pageNum'] === true || $options['pageNum'] > 1) {
			$wheres[] = "(flags & " . self::flagsPageNum . ')';
		}
		
		if($options['language'] === false) {
			$wheres[] = "NOT(flags & " . self::flagsLanguage . ')';
		} else if($options['language'] === true) {
			$wheres[] = "(flags & " . self::flagsLanguage . ')';
		}
		
		if($options['hasParent']) {
			$hasParent = (int) "$options[hasParent]";
			if($hasParent > 99) {
				$wheres[] = "MATCH(data) AGAINST(:hasParent)";
				$binds[':hasParent'] = "p$hasParent";
			}
		}
		
		if($options['languageID']) {
			$languageID = (int) "$options[languageID]";
			if($languageID) {
				$wheres[] = "MATCH(data) AGAINST(:languageID)";
				$binds[':languageID'] = "l$languageID";
			}
		}
		
		$sql = "SELECT path, flags, data, templates_id, parent_id, created FROM $table ";
		if(count($wheres)) $sql .= "WHERE " . implode(' AND ', $wheres); 
		$query = $database->prepare($sql);
		foreach($binds as $bindKey => $bindValue) $query->bindValue($bindKey, $bindValue);
		$query->execute();

		while($row = $query->fetch(\PDO::FETCH_ASSOC)) {

			/** @var ProCacheStaticEntry $entry */
			$entry = $this->newEntry($row);

			if($urlSegmentRegex) {
				if(!preg_match($urlSegmentRegex, $entry->urlSegmentStr())) continue;
			} else if($options['urlSegmentStr']) {
				if($options['urlSegmentStr'] !== $entry->urlSegmentStr()) continue;
			}

			if(is_array($options['urlSegments']) && count($options['urlSegments'])) {
				$hasUrlSegment = false;
				$urlSegments = $entry->urlSegments;
				foreach($options['urlSegments'] as $s) {
					$hasUrlSegment = in_array($s, $urlSegments);
					if($hasUrlSegment) break;
				}
				if(!$hasUrlSegment) continue;
			}

			if($options['pageNum'] !== 0) {
				if($options['pageNum'] === true) {
					// only pageNum 2+ allowed
					if($entry->pageNum < 2) continue;
				} else if($options['pageNum'] === false) {
					// no pageNum allowed other than 1
					if($entry->pageNum > 1) continue;
				} else {
					// only specific pageNum allowed
					if((int) $options['pageNum'] !== $entry->pageNum) continue;
				}
			}

			if($options['languageID'] && $entry->language_id != $options['languageID']) continue;

			$path = $entry->path;
			if($charset === 'UTF8') {
				$path = $sanitizer->pagePathName($path, 8); // 8=toUTF8
				if(empty($path)) continue;
				$entry->path = $path;
			}

			$entries[] = $entry;
		}

		$query->closeCursor();

		return $entries;
	}


	/**
	 * Get the cache time for the given Template, or for all templates
	 *
	 * @param null|string|int|Template|bool $template Template id, name or object,
	 *   omit to return cache times for all templates
	 *   specify `-1` for only templates with custom cache times
	 *   specify boolean `true` to return as newline-separated "template=time" string for all templates. 
	 *   specify boolean `false` to return as newline-separated "template=time" string for only templates with custom cache times.
	 * @return array|int|string
	 * 	- If not given an argument, then returns an array of all cache times, indexed by template name.
	 * 	- If given a $template, returns cache time or 0 if template is not cached.
	 *  - If given a boolean, returns a newline-separated "template=time" string.
	 *
	 */
	public function getCacheTime($template = null) {

		$procache = $this->procache;
		$cacheTimes = array();
		$cacheTimeTemplate = 0; // used only if $template argument specified
		$cacheTimeCustom = $procache->cacheTimeCustom;
		$cacheTimeDefault = $procache->cacheTime;
		$templates = $this->wire('templates'); /** @var Templates $templates */
		$getString = is_bool($template);
		$getOnlyCustom = $template === -1 || $template === false;
		$templateSetCacheTimes = array(); // cache times set in Setup > Templates > template
		
		if($getOnlyCustom || $getString) $template = null;

		if($template !== null) {
			if(!is_object($template)) $template = $templates->get($template);
			if(!$template instanceof Template) return 0;
			if(!in_array($template->id, $procache->cacheTemplates)) return 0;
			// cache time defined with template settings, quick exit, return now
			if($template->cache_time < 0) return abs($template->cache_time);
		}

		/** @var Template|null $template */

		if(strlen($cacheTimeCustom)) {
			if(($template === null ||
				(strpos($cacheTimeCustom, $template->name) !== false) ||
				(strpos($cacheTimeCustom, "($template->id)") !== false))) {

				foreach(explode("\n", $cacheTimeCustom) as $line) {
					if(!strpos($line, '=')) continue;
					// i.e. basic-page=3600 OR basic-page(10)=3600
					list($templateName, $cacheTime) = explode('=', $line, 2);
					$cacheTime = (int) $cacheTime;
					if($cacheTime < 1) continue;

					if(strpos($templateName, '(') !== false) {
						// definition string also contains template ID
						list($templateName, $templateID) = explode('(', $templateName, 2);
						$templateID = (int) trim($templateID, ')');
					} else {
						$templateID = 0;
					}

					if($template === null) {
						// times for all templates requested
						$t = $templates->get(($templateID ? $templateID : $templateName));
						if($t && in_array($t->id, $procache->cacheTemplates)) {
							$cacheTimes[$templateName] = $cacheTime;
						}
					} else if($templateName === $template->name || $templateID === $template->id) {
						// just one template
						$cacheTimeTemplate = $cacheTime;
						break;
					}
				}
			}
		}

		if($getOnlyCustom) {
			// return only templates with custom cache times
			ksort($cacheTimes);
			
		} else if($template !== null) {
			// return cache time for one template
			return ($cacheTimeTemplate ? $cacheTimeTemplate : (int) $procache->cacheTime);
			
		} else {
			// return cache time for all templates
			// check if there are any additional cache times specified with individual templates
			foreach($procache->cacheTemplates as $id) {
				$t = $templates->get((int) $id);
				if(!$t) continue;
				if($t->cache_time < 0) {
					$cacheTimes[$t->name] = abs($t->cache_time);
					$templateSetCacheTimes[$t->name] = $cacheTimes[$t->name];
				} else if(!isset($cacheTimes[$t->name])) {
					$cacheTimes[$t->name] = $cacheTimeDefault; // default
				}
			}
			ksort($cacheTimes);
		}
		
		if(is_array($cacheTimes) && $getString) {
			$out1 = '';
			$out2 = '';
			foreach($cacheTimes as $templateName => $cacheTime) {
				$commented = false;
				$custom = false;
				if($cacheTime === $cacheTimeDefault) {
					$commented = true;
				} else if(isset($templateSetCacheTimes[$templateName])) {
					$commented = true;
					$custom = true;
				}
				if($commented) {
					$out2 .= "\n# $templateName=$cacheTime";
					if($custom) $out2 .= " (@template)";
				} else {
					$out1 .= "\n$templateName=$cacheTime";
				}
			}
			$cacheTimes = trim($out1) . "\n\n" . trim($out2); 
			$cacheTimes = trim($cacheTimes);
		}
		
		return $cacheTimes;
	}

	/**
	 * Get a string with all custom cache times
	 * 
	 * @return string
	 * 
	 */
	public function getCacheTimesStr() {
		return $this->getCacheTime(true); 
	}
	
	/**
	 * Allow given page to be cached? - call via $procache->allowCacheForPage($page)
	 *
	 * Please call ProCache::allowCacheForPage() rather than this one, since the one
	 * in the ProCache is hookable and delegates to this method.
	 *
	 * #pw-internal
	 *
	 * @param Page $page
	 * @return bool
	 *
	 */
	public function allowCacheForPage(Page $page) {

		$cache = true;
		$user = $this->wire()->user;
		$procache = $this->procache;

		if($user && $user->isLoggedin()) {
			// if user is logged in, abort
			$cache = false;

		} else if(count($_GET) || count($_POST)) {
			// if any GET or POST vars are present, then abort
			$cache = false;

		} else if($procache->noCacheCookies) {
			// check if any disallowed cookies are present
			$hasCookie = false;
			foreach(explode("\n", $procache->noCacheCookies) as $name) {
				if(isset($_COOKIE[trim($name)])) $hasCookie = true;
			}
			if($hasCookie) $cache = false;
		}

		if($cache) {
			if($page->id != $procache->renderPageID) {
				// if page is something other than the one we started with, don't attempt to operate on it
				$cache = false;

			} else if(!in_array($page->template->id, $procache->cacheTemplates)) {
				// if page's template is not in the list, then abort
				$cache = false;
			}

			// duplicate the option from PageRender
			if($cache) {
				$pageID = (int) $this->session->get('PageRenderNoCachePage');
				if($pageID && $pageID === $page->id) $cache = false;
			}
		}

		return $cache;
	}

	/************************************************************************************************
	 * Cache paths and files
	 *
	 */
	
	/**
	 * Get root of path for storing cache files
	 *
	 * It’s preferable to use getCachePath() with no arguments instead, since it
	 * will create the directory if it does not yet exist.
	 *
	 * @return string
	 *
	 */
	public function getCachePathRoot() {
		$cacheDir = $this->wire()->sanitizer->name($this->procache->cacheDir);
		if(!strlen($cacheDir)) $cacheDir = 'ProCache';
		return $this->wire()->config->paths->assets . $cacheDir . '/';
	}

	/**
	 * Return the path where we store cache files, optionally for a page
	 *
	 * @param Page|null $page
	 * @param array $options
	 *  - `urlSegments` (array): Array of URL segments (default=[])
	 *  - 'urlSegmentStr' (string): URL segment string, as alternative to urlSegments array (default='')
	 *  - `language` (Language|null): Language to use (default=null)
	 *  - `pageNum` (int): Page/pagination number (default=1)
	 *  - `create` (bool|null): Create if it does not exist? true, false or null for auto-detect (default=null)
	 *  - `convertUTF8` (bool): Convert UTF-8 paths to encoded plain text? (default=false)
	 * @return string
	 *
	 */
	public function getCachePath(Page $page = null, array $options = array()) {

		$defaults = array(
			'pageNum' => 1,
			'urlSegments' => array(),
			'urlSegmentStr' => '',
			'language' => null,
			'create' => $this->procache->cacheOn,
		);

		$hasOptions = count($options) > 0;
		$options = $hasOptions ? array_merge($defaults, $options) : $defaults;
		$path = $this->getCachePathRoot();
		$config = $this->wire('config'); /** @var Config $config */
		

		// create root cache directory if not already present
		if($options['create'] && !is_dir($path)) $this->files()->mkdir($path);

		// if root cache directory requested, return it now
		if($page === null) return $path;

		// check for custom language setting
		$setLanguage = $options['language'] ? $this->language($options['language']) : null;
		if($setLanguage && $setLanguage->id != $this->language()->id) {
			$this->wire()->languages->setLanguage($setLanguage);
		} else if($setLanguage) {
			$setLanguage = null;
		}

		// convert string URL segments to array
		if($options['urlSegmentStr'] && empty($options['urlSegments'])) {
			$options['urlSegments'] = $options['urlSegmentStr'];
		}
		if(is_string($options['urlSegments'])) {
			$options['urlSegments'] = explode('/', trim($options['urlSegments'], '/'));
		}

		// add $page path and URL segments to path
		$segments = explode('/', $page->path());

		// ensure URL segments are in correct format
		if(!empty($options['urlSegments'])) {
			foreach($options['urlSegments'] as $key => $segment) {
				$segments[] = $this->wire('sanitizer')->pagePathNameUTF8($segment);
			}
		}

		// add page number index to path
		if($options['pageNum'] > 1) {
			$segments[] = $config->pageNumUrlPrefix . $options['pageNum'];
		}

		// add all segments to page and create directories to ensure it exists
		foreach($segments as $segment) {
			if(!strlen($segment)) continue;
			$path .= $segment . '/';
			if($options['create'] && !is_dir($path)) $this->files()->mkdir($path);
		}

		if($setLanguage) $this->wire()->languages->unsetLanguage();

		return $path;
	}

	/**
	 * Get cache paths for Page in all languages that it is published in
	 *
	 * @param Page $page
	 * @param array $options
	 *  - `pageNum` (int): Page/pagination number (default=1)
	 *  - `urlSegments` (array|string): Array or string of URL segments (default=[])
	 *  - `create` (bool|null): Create if it does not exist? true, false or null for auto-detect (default=null)
	 *  - `language` (Language|null): Limit to just this language (default=null)
	 * @return array Returned array of cache paths is indexed by language name
	 *
	 */
	public function getPageCachePathsLanguages(Page $page, array $options = array()) {
		
		$defaults = array(
			'pageNum' => 1, 
			'urlSegments' => array(), 
			'create' => null, 
			'language' => null, 
		);
		
		$options = array_merge($defaults, $options);
		$languages = $this->wire()->languages;
		$paths = array();
	
		if($languages) {
			$languageID = $options['language'] ? $this->language($options['language'], 'id') : 0;
			foreach($languages as $language) {
				if($languageID && $languageID != $language->id) continue;
				$property = $language->isDefault() ? "status" : "status$language";
				$status = $page->get($property);
				if(!$status) continue;
				$o = $options;
				$o['language'] = $language;
				$paths[$language->name] = $this->getCachePath($page, $o);
			}
		} else {
			$paths = array('default' => $this->getCachePath($page, $options));
		}
		
		return $paths;
	}

	/**
	 * Get cache file for given Page and options
	 *
	 * @param Page $page
	 * @param array $options
	 *  - `ext` (string): File extension (omit to determine automatically).
	 *  - `host` (string): Hostname or omit for current hostname
	 *  - `https` (bool|null): True for HTTPS-only file, false for HTTP-only file, or omit for current scheme.
	 *  - `create` (bool): Create dirs if they don't exist? (default=false)
	 *  - `pageNum` (int): Pagination number or omit for first pagination or pagination not applicable.
	 *  - `language` (Language|string|int|null): Language or omit for current language or if not applicable.
	 *  - `urlSegments` (array): URL segments to include or omit if not applicable.
	 *  - `urlSegmentStr` (string): Alternative to URL segments array, option to specify as string
	 * @return string
	 *
	 */
	public function getCacheFile(Page $page, array $options = array()) {

		$defaults = array(
			'ext' => $this->getContentTypeExt($page),
			'host' => '',  // blank for current host
			'https' => null,  // null for current scheme
			'create' => false, // create directories if they don't exist?
			'pageNum' => 1,
			'language' => null, // can be Language, string, int
			'urlSegments' => array(),
			'urlSegmentStr' => '',
		);

		$options = array_merge($defaults, $options);

		if(empty($options['urlSegments']) && !empty($options['urlSegmentStr'])) {
			$options['urlSegments'] = explode('/', trim($options['urlSegmentStr'], '/'));
		}

		$path = $this->getCachePath($page, array(
			'pageNum' => $options['pageNum'],
			'urlSegments' => $options['urlSegments'],
			'create' => $options['create'],
			'language' => $options['language'],
		));

		$index = $this->cacheIndexBasename($options['host'], $options['https'], $options['ext']);
		$file = $path . $index;

		return $file;
	}

	/**
	 * Get cache file for given page and options if it exists, or false if it does not
	 *
	 * This is very similar to getCacheFile() but more geared towards the public API.
	 *
	 * @param Page $page
	 * @param array $options
	 *  - `host` (string): Hostname or omit for current hostname
	 *  - `https` (bool|null): True for HTTPS-only file, false for HTTP-only file, or omit for current scheme.
	 *  - `getFile` (bool): Specify true to return the filename whether it exists or not (default=false).
	 *  - `pageNum` (int): Pagination number or omit for first pagination or pagination not applicable.
	 *  - `language` (Language|string|int|null): Language or omit for current language or if not applicable.
	 *  - `urlSegments` (array): URL segments to include or omit if not applicable.
	 *  - `urlSegmentStr` (string): Optionally specify URL segments here as "seg1/seg2/etc" as alternative to above.
	 * @return bool|string Returns false if not cached, or returns string with cache filename if cached.
	 *
	 */
	public function hasCacheFile(Page $page, array $options = array()) {

		$defaults = array(
			'host' => '',
			'https' => null,
			'pageNum' => 1,
			'getFile' => false,
			'language' => null,
			'urlSegments' => array(),
			'urlSegmentStr' => '',
		);

		$options = array_merge($defaults, $options);

		if(!empty($options['urlSegmentStr']) && empty($options['urlSegments'])) {
			$options['urlSegments'] = explode('/', trim($options['urlSegmentStr'], '/'));
		}

		$cacheFile = $this->getCacheFile($page, $options);

		if($options['getFile']) return $cacheFile;

		return $cacheFile && file_exists($cacheFile) ? $cacheFile : false;
	}

	/**
	 * Return the index filename relative to the given host and https state
	 *
	 * If hostname and/or https aren't supplied, they will be determined automatically
	 * 
	 * Examples of return value:
	 * 
	 * - index.html - HTML cache file
	 * - https.html - HTML cache file for HTTPS used only when HTTPS and HTTP are cached separately.
	 * - index.xml - File extension represents the content-type, in this case XML
	 * - processwire_com-index.html - HTML cache file when hosts cached separately for processwire.com
	 * - www_processwire_com-index.html - Same as above, but for www.processwire.com hostname
	 *
	 * @param string $hostname
	 * @param bool|string $https
	 * @param string $ext
	 * @return string
	 *
	 */
	public function cacheIndexBasename($hostname = '', $https = null, $ext = 'html') {

		$config = $this->wire('config'); /** @var Config $config */
		$procache = $this->procache;

		if(!$procache->https) {
			// procache configured not to cache https requests separately
			$https = false;

		} else if($https === null) {
			// auto-detect from current request scheme
			$https = $config->https ? true : false;

		} else if(is_string($https)) {
			// auto-detect from given string, which may be a URL, scheme, or number-string
			if(strpos($https, 'https') === 0 || $https === 'https' || $https === '1') {
				$https = true;
			} else {
				$https = false;
			}

		} else {
			// int or bool 
			$https = $https ? true : false;
		}

		$basename = $https ? 'https' : 'index';
		$cacheHosts = $procache->cacheHosts;

		if($cacheHosts && count($cacheHosts)) {
			if(!$hostname) $hostname = $config->httpHost;
			$hostname = strtolower($hostname);
			$hostname = str_replace('.', 'P', $hostname);
			if(ctype_alnum($hostname)) {
				$hostname = str_replace('P', '_', $hostname);
			} else {
				$hostname = preg_replace('/[^-_a-z0-9]/', '_', $hostname);
			}
			$basename = "$basename-$hostname";
		}

		$basename .= '.' . $ext;

		return $basename;
	}
	
	/**
	 * Get all possible index file basenames for cache files
	 *
	 * @param string|Page $ext File extension or Page
	 * @param bool $getVerbose Get verbose array of info with array indexed by basenames?
	 * @return array
	 *
	 */
	public function cacheIndexBasenames($ext = 'html', $getVerbose = false) {

		if(is_object($ext)) $ext = $this->getContentTypeExt($ext);
		
		$hosts = count($this->procache->cacheHosts) ? $this->procache->cacheHosts : array('');
		$schemes = $this->procache->https ? array('http', 'https') : array('http');
		$basenames = array();

		foreach($hosts as $host) {
			foreach($schemes as $scheme) {
				$https = $scheme === 'https';
				$basename = $this->cacheIndexBasename($host, $https, $ext);
				if($getVerbose) {
					$basenames[$basename] = array(
						'name' => $basename,
						'host' => $host,
						'scheme' => $scheme,
					);
				} else {
					$basenames[] = $basename;
				}
			}
		}

		return $basenames;
	}



	/**
	 * Get the content-type extension for the given Page or Template
	 *
	 * @param Template|Page $item
	 * @return string
	 *
	 */
	public function getContentTypeExt($item) {

		$contentTypes = $this->wire('config')->contentTypes;
		if(!is_array($contentTypes)) return 'html'; // core versions before 2.5.25

		if($item instanceof Template) {
			$contentType = $item->contentType;
		} else if($item instanceof Page && $item->template) {
			$contentType = $item->template->contentType;
		} else {
			$contentType = 'html';
		}

		if(strpos($contentType, '/') !== false) {
			list($ignore, $contentType) = explode('/', $contentType);
			if($ignore) {} // ignore
		}

		return isset($contentTypes[$contentType]) ? $contentType : 'html';
	}


	/************************************************************************************************
	 * Cache rendering and writing
	 *
	 */
	
	/**
	 * Save a new static cache file
	 *
	 * @param Page $page
	 * @param string $out
	 * @return bool True if static cache file was rendered, false if not
	 *
	 */
	public function renderCache(Page $page, &$out) {

		/** @var WireInput $input */
		$input = $this->wire('input');
		$procache = $this->procache;
		$flags = 0;

		// determine current cache file name based on page and page number
		$pageNum = $input->pageNum();
		if($pageNum > 1 && $pageNum < ProCache::MAX_PAGE_NUM && $page->template->allowPageNum) {
			// keep pageNum
			$flags = $flags | self::flagsPageNum;
		} else {
			$pageNum = 1;
		}

		// determine any extra URL segments
		$urlSegments = array();
		$maxUrlSegments = (int) $procache->urlSegments;
		$curUrlSegments = $input->urlSegments();
		$numUrlSegments = count($curUrlSegments);

		// don't cache if more URL segments are present than are allowed
		if($numUrlSegments > $maxUrlSegments) return false;
		if($numUrlSegments && !$page->template->urlSegments) return false;

		if($numUrlSegments) {
			$n = 0;
			foreach($curUrlSegments as $s) {
				if(++$n > $maxUrlSegments) break;
				$urlSegments[] = $s;
			}
			$flags = $flags | self::flagsUrlSegments;
		}

		/** @var Language|null $language */
		$language = $this->wire()->languages ? $this->wire()->user->language : null;
		
		if($language && !$language->isDefault()) {
			$flags = $flags | self::flagsLanguage;
		}

		// get the cache path and intended cache files
		$pathOptions = array('pageNum' => $pageNum, 'urlSegments' => $urlSegments);
		$path = $this->getCachePath($page, $pathOptions);
		$ext = $this->getContentTypeExt($page);
		$index = $this->cacheIndexBasename('', null, $ext);
		$file = $path . $index;
		$tmp = $file . ".tmp";

		// if tmp file is already being written & less than 1 min old, abort writing cache
		if(is_file($tmp) && filemtime($tmp) > (time() - 60)) return false;

		// we first write to a tmp file to ensure the cache file can't be read until it has been fully written
		if($procache->bodyClass) {
			$o = $out;
			$procache->getTweaks()->renderOutputTweaksCacheOnly($o);
			$o = $this->writeCacheFileReady($page, $o, $file, $pathOptions);
			$result = empty($o) ? 0 : $this->files->filePutContents($tmp, $o);
			unset($o);
		} else {
			$out = $this->writeCacheFileReady($page, $out, $file, $pathOptions);
			$result = empty($out) ? 0 : $this->files->filePutContents($tmp, $out);
		}

		if($result === false) {
			$this->error("Error saving tmp ProCache file: $tmp");
			return false;
		} else if($result === 0) {
			// cache write was aborted by writeCacheFileReady hook
			return false;
		}

		// place the new cache file
		if(is_file($file)) $this->files->unlink($file);
		$this->files->rename($tmp, $file);

		// keep a record of this cache file in the DB
		$pagePath = substr($path, strlen($this->getCachePath())-1);
		$created = date('Y-m-d H:i:s');
		$table = $this->procache->getTable();
		$data = array();

		foreach($page->parents() as $parent) {
			if($parent->id < 100) continue;
			// p1234, p4567, p7890, etc.
			$data[] = "p$parent->id";
		}
	
		if(count($urlSegments)) {
			// path/to/page/foo/bar => ufoo ubar
			foreach($urlSegments as $s) {
				$data[] = "u$s";
			}
		}

		if($pageNum > 1) {
			// path/to/page/page2 => n2
			$data[] = "n$pageNum";
		}

		if($language && !$language->isDefault()) {
			// non-default language i.e. "l4567"
			$data[] = "l$language->id";
		}

		if($this->wire()->config->pageNameCharset === 'UTF8') {
			$pagePath = $this->wire()->sanitizer->pagePathName($pagePath, 4); // 4=toAscii
		}

		$sql =
			"INSERT INTO $table " .
			"(pages_id, parent_id, templates_id, path, created, flags, `data`) " .
			"VALUES(:pages_id, :parent_id, :templates_id, :pagePath, :created, :flags, :data) " .
			"ON DUPLICATE KEY UPDATE " .
			"pages_id=VALUES(pages_id), parent_id=VALUES(parent_id), templates_id=VALUES(templates_id), " .
			"path=VALUES(path), created=VALUES(created), flags=VALUES(flags), `data`=VALUES(`data`) ";

		$query = $this->wire('database')->prepare($sql);
		$query->bindValue(':pages_id', (int) $page->id, \PDO::PARAM_INT);
		$query->bindValue(':parent_id', (int) $page->parent_id, \PDO::PARAM_INT);
		$query->bindValue(':templates_id', (int) $page->templates_id, \PDO::PARAM_INT);
		$query->bindValue(':pagePath', $pagePath, \PDO::PARAM_STR);
		$query->bindValue(':created', $created);
		$query->bindValue(':flags', $flags, \PDO::PARAM_INT);
		$query->bindValue(':data', implode(' ', $data));

		return $query->execute();
	}


	/**
	 * Content ready to write to cache file (hooks can optionally modify content)
	 *
	 * #pw-hooker
	 *
	 * @param Page $page
	 * @param string $content
	 * @param string $file
	 * @param array $options
	 *  - `pageNum` (int): Pagination number
	 *  - `urlSegments` (array): URL segments
	 * @return string
	 *
	 */
	protected function ___writeCacheFileReady(Page $page, $content, $file, $options = array()) {
		if($page || $file && $options) {} // for hooks
		return $content;
	}


	/************************************************************************************************
	 * Cache clearing delegated to ProCacheStaticClear
	 *
	 */
	
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
		return $this->clear()->clearPages($items, $options);
	}

	/**
	 * Clear the cache for a specific page, including pageNum and urlSegment versions
	 *
	 * @param Page $page
	 * @param array $options
	 *  - `language` (string|int|Language|bool): Clear only this language (default='')
	 *  - `urlSegmentStr` (string): Clear only entries matching this URL segment string, wildcards OR regex OK (default='')
	 *  - `urlSegments` (array|bool): Clear only entries having any of these URL segments, 
	 *     boolean false to clear no URL segments, 
	 *     omit (or empty array) to clear all (default=[])
	 *  - `pageNum` (int|bool): Clear only pagination number (i.e. 2 or higher), 
	 *     true to clear all pageNum>1
	 *     false to clear no pageNum>1, 
	 *     omit (int 0) to clear all (default=0)
	 *  - `clearRoot` (bool|null): Clear root index of page path? (default=false when specific URL segments or paginations requested, true otherwise)
	 *  - `rmdir` (bool): Remove directories rather than index files? (default=false)
	 *  - `getFiles` (bool): Get array of files that were cleared, rather than a count? (default=false)
	 * @return int|array Quantity or array of files and/or directories that were removed
	 *
	 */
	public function clearPage(Page $page, $options = array()) {
		return $this->clear()->clearPage($page, $options);
	}

	/**
	 * Clear only root index file for given Page
	 * 
	 * @param Page $page
	 * @param array $options
	 *  - `language` (string|int|Language|bool): Clear only this language (default='')
	 *  - `getFiles` (bool): Get array of files that were cleared, rather than a count? (default=false)
	 * @return int|array Quantity or array of files and/or directories that were removed
	 */
	public function clearPageRoot(Page $page, array $options = array()) {
		$defaults = array(
			'urlSegments' => false, 
			'pageNum' => false, 
			'clearRoot' => true, 
			'rmdir' => false, 
		);
		return $this->clear()->clearPage($page, array_merge($defaults, $options));
	}

	/**
	 * Clear only URL segment(s) for page (optionally specifying which ones)
	 * 
	 * @param Page $page
	 * @param string|array|bool $urlSegments Specify any one of the following:
	 *  - Boolean true to clear all URL segments (default behavior if not specified)
	 *  - String with 'segment1' or 'segment1/segment2' or 'segment1/segment2/segment3', etc. 
	 *  - String with wildcard to clear all matching wildcard, i.e. 'boat-*' or 'boats/boat-*' or '*-boat' or '*boat*'
	 *  - String with PCRE regular expression to match 
	 *  - Array of URL segments (strings) to clear
	 * @param array $options
	 *  - `language` (string|int|Language|bool): Clear only this language (default='')
	 *  - `pageNum` (bool): Specify boolean true to also clear pagination URL segments (default=false)
	 *  - `rmdir` (bool): Remove directories rather than index files? (default=false)
	 *  - `getFiles` (bool): Get array of files that were cleared, rather than a count? (default=false)
	 * @return int|array Quantity or array of files and/or directories that were removed
	 * 
	 */
	public function clearPageUrlSegments(Page $page, $urlSegments = true, array $options = array()) {
		$defaults = array(
			'urlSegmentStr' => is_string($urlSegments) ? $urlSegments : '', 
			'urlSegments' => is_array($urlSegments) || is_bool($urlSegments) ? $urlSegments : array(),
			'pageNum' => 0, 
			'clearRoot' => false, 
		);
		return $this->clear()->clearPage($page, array_merge($defaults, $options));
	}

	/**
	 * Clear page pagination(s)
	 * 
	 * @param Page $page
	 * @param bool|int $pageNum Specify one of the following:
	 *  - Integer (greater than 1) of pagination number to clear.
	 *  - Boolean true to clear all paginations greater than 1.
	 * @param array $options
	 *  - `urlSegments` (array|bool): Specify URL segments to use, true to clear only URL segments, 
	 *     false to exclude any URL segments, or omit (blank array) to allow inclusion of any URL segments. 
	 *  - `language` (string|int|Language|bool): Clear only this language (default='')
	 *  - `pageNum` (bool): Specify boolean true to also clear pagination URL segments (default=false)
	 *  - `rmdir` (bool): Remove directories rather than index files? (default=false)
	 *  - `getFiles` (bool): Get array of files that were cleared, rather than a count? (default=false)
	 * @return int|array Quantity or array of files and/or directories that were removed
	 * 
	 */
	public function clearPageNum(Page $page, $pageNum = true, array $options = array()) {
		$defaults = array(
			'urlSegmentStr' => '', 
			'urlSegments' => array(), 
			'clearRoot' => false, 
			'pageNum' => $pageNum, 
		);
		return $this->clear()->clearPage($page, array_merge($defaults, $options));
	}

	/**
	 * Clear entire Page branch (Page and everything below it)
	 *
	 * @param Page $page
	 * @return int
	 *
	 */
	public function clearBranch(Page $page) {
		return $this->clear()->clearBranch($page);
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
		return $this->clear()->clearPath($path, $options);
	}

	/**
	 * Clear entire cache
	 *
	 * @return int Number of pages cleared
	 *
	 */
	public function clearAll() {
		return $this->clear()->clearAll();
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
		return $this->clear()->cacheMaintenance();
	}

	/************************************************************************************************
	 * Cache clearing behaviors delegated to ProCacheStaticBehaviors
	 *
	 */
	
	/**
	 * Get the cache clearing behaviors for all templates or a given template
	 *
	 * If given no arguments, returns array in this format:
	 * ~~~~~
	 * $returnValue = [
	 *   'template-name' => [
	 * 	    // one or more of these:
	 * 	    CACHE_CLEAR_[BEHAVIOR] => CACHE_CLEAR_[BEHAVIOR],
	 *      CACHE_CLEAR_PAGES => array(123,456,789),
	 * 	    CACHE_CLEAR_SELECTOR => "selector string",
	 *   ],
	 *   'template-name' => [
	 *      // behaviors
	 *   ],
	 * ];
	 *
	 * If given a template, then just the single dimensional array is returned containing
	 * only the behvaiors.
	 *
	 * @param null|Template|string|int|array $options Specify any of the following options:
	 *  - `page` (Page): Page to get cache behaviors for. You can use either this or template option (no need for both).
	 *  - `template` (Template): Page template to retrieve behaviors for. Omit to retrieve for all cached templates.
	 *  - `cacheClearCustom` (string): Value to use for cacheClearCustom setting rather than default.
	 *  - `onlyCustom` (bool): Return behaviors for templates that are customized differently fron the defaults? (default=false)
	 *  - `verbose` (bool): Get verbose data array? (default=false)
	 *  - `indexType` (string): Type of index to use on returned array, 'value', 'abbr', 'name' (default='value')
	 * @return array
	 *
	 */
	public function getCacheClearBehaviors($options = array()) {
		if(!is_array($options)) $options = array('template' => $options);
		return $this->behaviors()->getCacheClearBehaviors($options);
	}

	/**
	 * Get cache clear behaviors as string
	 *
	 * @param array $options
	 *  - `useAbbrs` (bool): Use behavior abbreviations rather than numbers? (default=true)
	 *  - `useTemplateIDs` (bool): Include template IDs in definition? i.e. "basic-page(10)=behaviors" (default=false)
	 *  - `useComments` (bool): Use commented lines to indicate templates without custom definitions? (default=false)
	 *  - `cacheClearCustom` (string|null): Cache custom clear behaviors string to use rather than default (default=null)
	 * @return string
	 *
	 */
	public function getCacheClearBehaviorsStr(array $options = array()) {
		return $this->behaviors()->getCacheClearBehaviorsStr($options);
	}

	/**
	 * Execute cache clear behaviors for given Page (to be called after Page has been modified and saved)
	 *
	 * @param Page $page
	 * @return array Returns array indexed by behavior name each with a count of files cleared
	 *
	 */
	public function executeCacheClearBehaviors(Page $page) {
		$behaviors = $this->behaviors()->getCacheClearBehaviors(array('page' => $page));
		return $this->behaviors()->executeCacheClearBehaviors($page, $behaviors);
	}

	/**
	 * Get array of [ ProCache::CACHE_CLEAR_* => 'C' ] where 'C' is 1 char behavior abbreviation
	 *
	 * @return array
	 *
	 */
	public function getBehaviorAbbrs() {
		return $this->behaviors()->getBehaviorAbbrs();
	}


	/************************************************************************************************
     * Test mode and test data
	 * 
	 */

	/**
	 * Enable or disable test mode
	 *
	 * @param bool $testMode
	 *
	 */
	public function setTestMode($testMode) {
		$this->testMode = $testMode ? true : false;
	}

	/**
	 * @return bool
	 * 
	 */
	public function getTestMode() {
		return $this->testMode;
	}

	/**
	 * Get test mode data
	 *
	 * @param bool $reset
	 * @return array
	 *
	 */
	public function getTestData($reset = true) {
		$testData = $this->testData;
		$cachePath = $this->getCachePath();
		foreach($testData as $key => $value) {
			$testData[$key] = str_replace($cachePath, '/', $value);
		}
		if($reset) $this->testData = array();
		return $testData;
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
		if(!$this->testMode) return;
		if($file) $file = str_replace($this->getCachePathRoot(), '/', $file);
		$this->testData[] = array(
			'page' => $page,
			'action' => $action, 
			'file' => $file,
		);
	}

	/**
	 * Find all page entries in cache matching given selector
	 *
	 * WORK IN PROGRESS / NOT YET FUNCTIONAL
	 *
	 * - id=123 (find entries for page ID 123)
	 * - parent=456 (find all cache entries for pages having parent 456)
	 * - path=/path/to/page (find entry for /path/to/page/)
	 * - path^=/path/to/page (find entries beginning with /path/to/page)
	 * - path*=/path/to/page (find entries containing /path/to/page anywhere in path or URL segment string)
	 * - urlSegments=foo (find entries containing URL segment "foo")
	 * - urlSegmentStr=foo/bar (find entries containing URL segment string "foo/bar")
	 * - language=default (limit results to entries for default language)
	 * - https=1 (limit entries to those for https only)
	 * - https=0 (limit entries to those for not https only)
	 * - flags=urlSegments (limit entries to those having URL segments)
	 * - flags=pageNum (limit entries to those for paginations 2+)
	 * - flags=language (limit entries to those for non-default languages)
	 *
	 * @param string|array $selector
	 * @return array
	 *
	private function find($selector) {
		$sanitizer = $this->wire()->sanitizer;
		$items = array();
		$binds = array();
		$wheres = array();
		$data = array();

		foreach(new Selectors($selector) as $s) {
			$operator = $s->operator();
			$values = array();
			$col = '';
			$exact = true;

			$name = strtolower($s->field());

			if($name === 'id' || $name === 'pages_id') {
				$col = 'pages_id';
				$values = $s->values();
			} else if($name === 'path') {
				$col = 'path';
				$values = $s->values();
			} else if($name === 'urlsegments') {
				foreach($s->values as $v) {
					$data[] = "u" . $sanitizer->pageName($v);
				}
			} else if($name === 'urlsegmentstr') {
				$col = 'path';
			} else if($name === 'language') {
			} else if($name === 'https') {
			} else if($name === 'flags') {
			} else {
				// unrecognized
			}
		}

		return $items;
	}
	 */

	
	/************************************************************************************************
	 * Table admin
	 *
	 */

	/**
	 * Does given column exist?
	 *
	 * @param string $column
	 * @param string $table
	 * @return bool
	 *
	 */
	public function columnExists($column, $table = '') {
		$table = $table ? $this->database->escapeTable($table) : ProCache::DB_TABLE;
		$query = $this->database->prepare("SHOW COLUMNS FROM `$table` WHERE Field=:column");
		$query->bindValue(':column', $column, \PDO::PARAM_STR);
		$query->execute();
		$exists = (int) $query->rowCount() > 0;
		$query->closeCursor();
		return $exists;
	}

	/**
	 * Get primary ProCache DB table name while also checking that schema is up-to-date
	 *
	 * @return string
	 *
	 */
	public function getTable() {

		$table = ProCache::DB_TABLE;
		$requiredVersion = 3;

		if($this->procache->schemaVersion >= $requiredVersion) return $table;

		$schemaVersion = 0; // 0=not updated, 1 or more means version updated to
		$database = $this->wire()->database;
		$exception = null;

		if($this->procache->schemaVersion < 1) {
			// schema version 1: add parent_id column
			if($this->columnExists('parent_id')) {
				$schemaVersion = 1; // column already exists
			} else try {
				$database->exec("ALTER TABLE $table ADD parent_id int(10) unsigned AFTER pages_id");
				$database->exec("ALTER TABLE $table ADD INDEX parent_id (parent_id, pages_id)");
				$schemaVersion = 1; // version updated
			} catch(\Exception $e) {
				$exception = $e;
			}
		}
		
		if($this->procache->schemaVersion < 2) {
			// schema version 2: add data column
			if($this->columnExists('data')) {
				$schemaVersion = 2; // column already exists
			} else try {
				$database->exec("ALTER TABLE $table ADD `data` TEXT");
				$database->exec("ALTER TABLE $table ADD FULLTEXT `data` (`data`)");
				$schemaVersion = 2; // version updated
			} catch(\Exception $e) {
				$exception = $e;
			}
		}
		
		if($this->procache->schemaVersion < 3) {
			// schema version 3: add flags column
			if($this->columnExists('flags')) {
				$schemaVersion = 3; // column already exists
			} else try {
				$database->exec("ALTER TABLE $table ADD `flags` INT UNSIGNED NOT NULL DEFAULT 0 AFTER created");
				$database->exec("ALTER TABLE $table DROP INDEX `pages_id`");
				$database->exec("ALTER TABLE $table ADD INDEX `pages_id` (`pages_id`, `flags`)");
				$schemaVersion = 3; // version updated
			} catch(\Exception $e) {
				$exception = $e;
			}
		}

		if($exception) {
			$flags = $this->wire()->user->isSuperuser() ? Notice::log : Notice::logOnly;
			$this->error($exception->getMessage(), $flags);
		} else if($schemaVersion) {
			$this->message("ProCache schema version updated from {$this->procache->schemaVersion} to $schemaVersion");
			$this->procache->schemaVersion = $schemaVersion;
			$this->modules->saveConfig('ProCache', 'schemaVersion', $schemaVersion);
		}

		return $table;
	}

	/**
	 * Install
	 * 
	 * @throws WireException
	 * 
	 */
	public function install() {
		$config = $this->wire()->config;
		$engine = $config->dbEngine;
		$sql =
			"CREATE TABLE " . ProCache::DB_TABLE . " (" .
			"path varchar(500) CHARACTER SET ascii NOT NULL, " .
			"pages_id int(10) unsigned NOT NULL, " .
			"parent_id int(10) unsigned NOT NULL, " . // v1
			"templates_id int(10) unsigned NOT NULL, " .
			"created datetime NOT NULL, " .
			"flags int unsigned NOT NULL DEFAULT 0, " . // v3
			"`data` TEXT, " . // v2
			"PRIMARY KEY (path), " .
			"INDEX created (created, templates_id), " .
			"INDEX pages_id (pages_id, flags), " .
			"INDEX parent_id (parent_id), " . // v1
			"FULLTEXT `data` (`data`)" . // v2
			") ENGINE=$engine DEFAULT CHARSET=utf8";

		$this->wire()->database->exec($sql);
	}

	/**
	 * Uninstall
	 * 
	 */
	public function uninstall() {
		$this->clearAll();
		$this->wire()->database->exec("DROP TABLE " . ProCache::DB_TABLE); 
	}
	
	

}