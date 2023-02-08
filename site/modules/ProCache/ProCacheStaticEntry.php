<?php namespace ProcessWire;
/**
 * ProcessWire Pro Cache: Static Entry
 *
 * Copyright (C) 2020 by Ryan Cramer
 *
 * This is a commercially licensed and supported module
 * DO NOT DISTRIBUTE
 * 
 * @property string $path
 * @property int $created
 * @property int $pages_id
 * @property int $parent_id
 * @property array $parent_ids
 * @property int $templates_id
 * @property int $language_id
 * @property array $urlSegments
 * @property string $urlSegmentStr
 * @property int $pageNum
 * @property string $keywords
 * 
 * // read-only properties
 * @property-read string $cachePath Full cache disk path
 * @property-read array $cacheFiles All cache files that exist in cache path (filenames with full disk paths)
 * 
 * // aliases 
 * @property int $parentID
 * @property int $languageID
 * @property int $templatesID
 * 
 * // object properties
 * @property Page|NullPage $page
 * @property Page|NullPage $parent
 * @property Template|null $template
 * @property Language|null $language
 * 
 * 
 *
 */
class ProCacheStaticEntry extends WireData {

	/**
	 * @var ProCache
	 * 
	 */
	protected $procache;

	/**
	 * @var Page|null
	 * 
	 */
	protected $page = null;

	/**
	 * @var array
	 * 
	 */
	protected $altKeys = array(
		'id' => 'pages_id',
		'pageID' => 'pages_id',
		'parentID' => 'parent_id',
		'templateID' => 'templates_id',
		'languageID' => 'language_id',
		'data' => 'keywords', 
	);

	/**
	 * @var array
	 * 
	 */
	protected $objKeys = array(
		'page' => 'pages_id',
		'parent' => 'parent_id',
		'template' => 'templates_id',
		'language' => 'language_id',
	);
	

	/**
	 * Construct
	 * 
	 * @param ProCache $procache
	 * 
	 */
	public function __construct(ProCache $procache) {
		$this->procache = $procache;
		$procache->wire($this);
		parent::__construct();
		parent::setArray(array(
			// native values
			'path' => '',
			'pages_id' => 0, 
			'parent_id' => 0,
			'templates_id' => 0,
			'created' => time(),
			// values derived from data/keywords
			'pageNum' => 0, 
			'urlSegments' => array(),
			'language_id' => 0,
			'parent_ids' => array(),
		));
	}

	/**
	 * @param string $key
	 * @return string|int|Page|Language|array
	 * @throws WireException
	 * 
	 */
	public function get($key) {
		if(isset($this->altKeys[$key])) $key = $this->altKeys[$key];
		
		if($key === 'urlSegmentStr') {
			return $this->urlSegmentStr();
			
		} else if($key === 'flags') {
			return $this->getFlags();

		} else if($key === 'page') {
			return $this->getPage();
			
		} else if($key === 'parent') {
			return $this->getParent();

		} else if($key === 'template') {
			$id = (int) parent::get('templates_id');
			return $id ? $this->wire()->templates->get($id) : null;

		} else if($key === 'language') {
			$id = (int) parent::get('language_id');
			$languages = $this->wire()->languages;
			return $id && $languages ? $languages->get($id) : null;
			
		} else if($key === 'cachePath') {
			return $this->cachePath();

		} else if($key === 'cacheFiles') {	
			return $this->cacheFiles();
			
		} else if($key === 'keywords') {
			return $this->getKeywordsData();
		}
		
		return parent::get($key);
	}
	
	/**
	 * @param string $key
	 * @param int|string|Wire $value
	 * @return WireData|ProCacheStaticEntry
	 * 
	 */
	public function set($key, $value) {
		
		if(isset($this->altKeys[$key])) {
			$key = $this->altKeys[$key];
		} else if($key === 'page') {
			return $value instanceof Page ? $this->setPage($value) : $this;
		} else if(isset($this->objKeys[$key])) {
			$key = $this->objKeys[$key];
			/** @var Page|Template|Language $value */
			if(is_object($value)) $value = $value->id;
			$value = (int) $value;
		}
		
		if(substr($key, -3) === '_id') {
			$value = (int) $value;
		} else if($key === 'flags') {
			$value = (int) $value;
		} if($key === 'created') {
			if(!is_int($value)) $value = empty($value) ? 0 : strtotime($value);
		} else if($key === 'keywords') {
			return $this->setKeywordsData($value);
		} else if($key === 'urlSegmentStr') {
			$key = 'urlSegments';
			$value = explode('/', trim($value, '/'));
		} else if($key === 'urlSegments') {
			if(is_string($value) && $value) $value = explode('/', trim($value, '/'));
			if(!is_array($value)) $value = array();
		}
		
		return parent::set($key, $value);
	}

	/**
	 * Get Page object
	 * 
	 * @return NullPage|Page
	 * 
	 */
	public function getPage() {
		if($this->page) return $this->page;
		$id = (int) parent::get('pages_id');
		$page = $id ? $this->wire()->pages->get((int) $id) : new NullPage();
		if($page->id) $this->setPage($page);
		return $page;
	}

	/**
	 * Get parent Page
	 * 
	 * @return NullPage|Page
	 * 
	 */
	public function getParent() {
		if($this->page && $this->page->id) return $this->page->parent();
		$id = (int) parent::get('parent_id');
		$parent = $id ? $this->wire()->pages->get((int) $id) : new NullPage();
		return $parent;
	}

	/**
	 * Set Page object
	 * 
	 * Note: a Page is only set in certain instances
	 * 
	 * @param Page $page
	 * @return $this
	 * 
	 */
	public function setPage(Page $page) {
		$this->page = $page;
		$parentIDs = array();
		foreach($page->parents() as $parent) {
			if($parent->id > 1) $parentIDs[] = $parent->id;
		}
		parent::setArray(array(
			'parent_id' => $page->parent_id, 
			'parent_ids' => $parentIDs, 
			'templates_id' => $page->templates_id, 
		));
		if(!parent::get('path')) $this->path = $page->path();
		return $this;
	}

	/**
	 * @return int
	 * 
	 */
	public function getFlags() {
		$flags = 0;
		if(count($this->urlSegments)) $flags = $flags | ProCacheStatic::flagsUrlSegments;
		if($this->language_id > 0) $flags = $flags | ProCacheStatic::flagsLanguage;
		if($this->pageNum > 1) $flags = $flags | ProCacheStatic::flagsPageNum;
		return $flags;
	}

	/**
	 * @return string
	 * 
	 */
	public function urlSegmentStr() {
		$urlSegments = $this->urlSegments;
		return count($urlSegments) ? implode('/', $urlSegments) : '';
	}

	/**
	 * @return string
	 *
	 */
	protected function getKeywordsData() {
		$data = array();
		foreach($this->getParentIDs() as $id) $data[] = "p$id";
		if($this->pageNum > 1) $data[] = "n$this->pageNum";
		foreach($this->urlSegments as $s) $data[] = "u$s";
		if($this->language_id) $data[] = "l$this->language_id";
		return implode(' ', $data);
	}

	/**
	 * @param string $data
	 * @return $this
	 * 
	 */
	protected function setKeywordsData($data) {
		if(!is_string($data)) return $this;
		$parentIDs = array();
		$urlSegments = array();
		$pageNum = 0;
		$languageID = 0;
		$types = array();
		
		foreach(explode(' ', $data) as $item) {
			if(empty($item)) continue;
			$type = $item[0];
			$val = substr($item, 1);
			$types[$type] = $type;
			switch($type) {
				case 'p': $parentIDs[] = (int) $val; break;
				case 'u': $urlSegments[] = $val; break;
				case 'n': $pageNum = (int) $val; break;
				case 'l': $languageID = (int) $val; break;
			}
		}
		
		if(isset($types['p'])) parent::set('parent_ids', $parentIDs); 
		if(isset($types['u'])) parent::set('urlSegments', $urlSegments);
		if(isset($types['n'])) parent::set('pageNum', $pageNum);
		if(isset($types['l'])) parent::set('language_id', $languageID);
		
		return $this;
	}

	/**
	 * @return array
	 * 
	 */
	protected function getParentIDs() {
		$parentIDs = $this->parent_ids;
		if(!count($parentIDs)) {
			$parentIDs = array();
			$page = $this->page;
			if($page->id) {
				foreach($page->parents as $parent) {
					if($parent->id > 1) $parentIDs[] = $parent->id;
				}
			}
		}
		return $parentIDs;
	}

	/**
	 * Get full disk path 
	 * 
	 * @return string
	 * 
	 */
	public function cachePath() {
		$cachePath = $this->procache->getStatic()->getCachePath();
		$cachePath .= ltrim($this->path, '/'); 
		return $cachePath;
	}

	/**
	 * Get all cache index files (full paths) 
	 * 
	 * @param bool $exists Get only those that exist? (default=true)
	 * @return array
	 * 
	 */
	public function cacheFiles($exists = true) {
		$template = $this->wire()->templates->get($this->templates_id); 
		if(!$template) return array();
		$ext = $this->procache->getStatic()->getContentTypeExt($template); 
		$cacheFiles = array();
		$cachePath = $this->cachePath();
		$indexNames = $this->procache->getStatic()->cacheIndexBasenames($ext); 
		foreach($indexNames as $indexName) {
			$cacheFile = $cachePath . $indexName;
			if($exists && !file_exists($cacheFile)) continue;
			$cacheFiles[] = $cacheFile;
		}
		return $cacheFiles;
	}
}