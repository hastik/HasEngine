<?php namespace ProcessWire;

/**
 * ProcessWire Pro Cache: Static cache management behaviors
 *
 * Copyright (C) 2020 by Ryan Cramer
 *
 * This is a commercially licensed and supported module
 * DO NOT DISTRIBUTE
 * 
 * @method array executeCacheClearBehaviors(Page $page, array $behaviors)
 *
 */

class ProCacheStaticBehaviors extends ProCacheClass {

	/**
	 * Cache clear behavior abbreviations
	 *
	 * @var array
	 *
	 */
	protected $behaviorAbbrs = array(
		ProCache::CACHE_CLEAR_FAMILY => 'F',
		ProCache::CACHE_CLEAR_CHILDREN => 'C',
		ProCache::CACHE_CLEAR_PARENTS => 'P',
		ProCache::CACHE_CLEAR_HOME => 'H',
		ProCache::CACHE_CLEAR_SITE => 'A',
		ProCache::CACHE_CLEAR_PAGES => 'G',
		ProCache::CACHE_CLEAR_SELECTOR => 'S',
		ProCache::CACHE_CLEAR_REFERENCES => 'R',
		ProCache::CACHE_CLEAR_NOSELF => 'X',
	);

	/**
	 * Cache clear behavior names
	 *
	 * @var array
	 *
	 */
	protected $behaviorNames = array(
		ProCache::CACHE_CLEAR_FAMILY => 'family',
		ProCache::CACHE_CLEAR_CHILDREN => 'children',
		ProCache::CACHE_CLEAR_PARENTS => 'parents',
		ProCache::CACHE_CLEAR_HOME => 'home',
		ProCache::CACHE_CLEAR_SITE => 'all',
		ProCache::CACHE_CLEAR_PAGES => 'pages',
		ProCache::CACHE_CLEAR_SELECTOR => 'selector',
		ProCache::CACHE_CLEAR_REFERENCES => 'references',
		ProCache::CACHE_CLEAR_NOSELF => 'noself',
	);

	/**
	 * @return array
	 * 
	 */
	public function getBehaviorAbbrs() {
		return $this->behaviorAbbrs;
	}

	/*
	public function kjbehaviorsPossibleForPage(Page $page) {
		$template = $page->template;
		if(!$template) return false;
		if(in_array($template->id, $this->procache->cacheTemplates)) return true;
	}
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
	 * Specifying one or more options can change the return value. 
	 *
	 * When providing the `template` option please note that just the single dimensional array is returned containing
	 * only the behaviors for that template.
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
	public function getCacheClearBehaviors(array $options = array()) {
		
		$defaults = array(
			'page' => null, 
			'template' => null,
			'cacheClearCustom' => null, 
			'onlyCustom' => false,
			'verbose' => false, 
			'indexType' => 'value', 
		);
	
		$options = array_merge($defaults, $options);
		$templates = $this->wire('templates'); /** @var Templates $templates */
		$template = $options['template']; /** @var Template|null $template */
		$procache = $this->procache;
		$page = $this->wire('page'); /** @var Page $page */
		$pageTemplateName = $page && $page->template ? $page->template->name : '';  // current page template name
		$cacheClears = array();
		$templateHasRules = false; // does template have custom rules?
		$cacheClearCustom = $options['cacheClearCustom'] === null ? $procache->cacheClearCustom : $options['cacheClearCustom'];
		$defaultCacheClear = array(ProCache::CACHE_CLEAR_SELF => ProCache::CACHE_CLEAR_SELF);
		
		if(!$template && $options['page']) {
			$template = $options['page']->template;
		} else if($template && !is_object($template)) {
			$template = $templates->get($template);
		}

		/** @var array $cacheClearOff Bypasses of cache clear behaviors, one or more CACHE_CLEAR_OFF_* constants */
		$cacheClearOff = $procache->cacheClearOff;
		// Disable cache clear behaviors for templates in $cacheClears when...
		// ...saved page uses template that is not configured to be cached in procache:
		$cacheClearOffTemplate = in_array(ProCache::CACHE_CLEAR_OFF_TEMPLATE, $cacheClearOff);
		// ...saved page uses a system template (admin, user, role, permission, etc.):
		$cacheClearOffSystem = in_array(ProCache::CACHE_CLEAR_OFF_SYSTEM, $cacheClearOff);
		// ...page is saved from site API rather than interactive/admin:
		$cacheClearOffSite = in_array(ProCache::CACHE_CLEAR_OFF_SITE, $cacheClearOff);

		if($template !== null) {
			// check if there are custom rules for this template
			if(!$template) return array();
			if(strlen($cacheClearCustom)) {
				if(strpos($cacheClearCustom, "($template->id)")) {
					$templateHasRules = preg_match('!\(' . $template->id . '\)\s*=!', $cacheClearCustom);
				} else if(strpos($cacheClearCustom, $template->name) !== false) {
					$templateHasRules = preg_match('!\b' . $template->name . '\s*[(=]!', $cacheClearCustom);
				}
			}
		}

		/** @var Template|null $template */

		if(strlen($cacheClearCustom) && ($template === null || $templateHasRules)) {
			// populate $cacheClears with just custom template overrides
			$cacheClears = $this->getCacheClearCustomBehaviors($cacheClearCustom, $template);
		}
		
		if($options['onlyCustom']) return $cacheClears;
		
		$cacheTemplates = $template === null ? $procache->cacheTemplates : array($template);

		// iternate through all cached templates
		foreach($cacheTemplates as $t) {
			/** @var int|Template $t */
			if(!is_object($t)) $t = $templates->get((int) $t);
			if(!$t || !$t instanceof Template) continue;
			
			$source = 'default';
			$skip = false;

			/** @var Template $t */
			
			if($cacheClearOffTemplate && !in_array($t->id, $procache->cacheTemplates)) {
				// skip clear when saved page not configured for caching
				$cacheClear = array();
				$skip = 'not-cached';

			} else if($cacheClearOffSystem && ($t->flags & Template::flagSystem) && $t->name != 'home') {
				// skip clear when page being saved is a system page
				$cacheClear = array();
				$skip = 'system-page';

			} else if($cacheClearOffSite && $pageTemplateName !== 'admin') {
				// skip clear when page is saved somewhere in in the site (rather than admin)
				$cacheClear = array();
				$skip = 'api-save';

			} else if($t->cache_time < 0) {
				// expiration settings defined with template
				$cacheClear = isset($cacheClears[$t->name]) ? $cacheClears[$t->name] : $defaultCacheClear;
				$source = 'template';

				if($t->cacheExpire == Template::cacheExpireSite) {
					// expire entire site
					$cacheClear[ProCache::CACHE_CLEAR_SITE] = ProCache::CACHE_CLEAR_SITE;

				} else if($t->cacheExpire == Template::cacheExpireParents) {
					// expire parents of page and homepage
					$cacheClear[ProCache::CACHE_CLEAR_PARENTS] = ProCache::CACHE_CLEAR_PARENTS;
					$cacheClear[ProCache::CACHE_CLEAR_HOME] = ProCache::CACHE_CLEAR_HOME;

				} else if($t->cacheExpire == Template::cacheExpireSpecific) {
					// expire specific pages indicated in $template->cacheExpirePages
					$cacheClear[ProCache::CACHE_CLEAR_PAGES] = $t->cacheExpirePages;

				} else if($t->cacheExpire == Template::cacheExpireSelector) {
					// expire pages matching selector
					$cacheClear[ProCache::CACHE_CLEAR_SELECTOR] = $t->cacheExpireSelector;
				}

			} else if(isset($cacheClears[$t->name])) {
				// use already-defined settings
				$cacheClear = $cacheClears[$t->name];
				$source = 'custom';

			} else {
				// USE DEFAULTS
				// defined in ProCache, no custom setting override, use default cacheClear behaviors
				$cacheClear = $defaultCacheClear;
				foreach($procache->cacheClear as $behavior) {
					$cacheClear[(int) $behavior] = (int) $behavior;
				}
			}
			
			if(isset($cacheClear[ProCache::CACHE_CLEAR_NOSELF])) unset($cacheClear[ProCache::CACHE_CLEAR_SELF]);

			if($options['indexType'] !== 'value') {
				// return value with alternate index type
				$a = array();
				foreach($cacheClear as $key => $value) {
					if($options['indexType'] === 'abbr') $key = $this->behaviorAbbrs[$key];
					if($options['indexType'] === 'name') $key = $this->behaviorNames[$key];
					$a[$key] = $value;
				}
				$cacheClear = $a;
			}
			
			if($options['verbose']) {
				// verbose array return values
				$clearAbbrs = '';
				$clearNames = array();
				foreach($cacheClear as $key => $val) {
					$clearAbbrs .= $this->behaviorAbbrs[$key];
					$clearNames[$key] = $this->behaviorNames[$key];
				}
				$cacheClear = array(
					'source' => $source, 
					'skip' => $skip === false ? "no" : "yes:$skip",
					'value' => $cacheClear,
					'abbrs' => $clearAbbrs,
					'names' => $clearNames,
					'pages' => isset($cacheClear[ProCache::CACHE_CLEAR_PAGES]) ? $cacheClear[ProCache::CACHE_CLEAR_PAGES] : array(),
					'selector' => isset($cacheClear[ProCache::CACHE_CLEAR_SELECTOR]) ? $cacheClear[ProCache::CACHE_CLEAR_SELECTOR] : '',
				);
			} 
			
			$cacheClears[$t->name] = $cacheClear;
		}
		
		if($template === null) {
			// return all
			$result = $cacheClears;

		} else if(isset($cacheClears[$template->name])) {
			// return for one template
			$result = $cacheClears[$template->name];

		} else {
			// fallback for one template
			$cacheClears[$template->name][ProCache::CACHE_CLEAR_SELF] = ProCache::CACHE_CLEAR_SELF;
			$result = $cacheClears;
		}
	
		if($template !== null && isset($result[ProCache::CACHE_CLEAR_NOSELF])) {
			unset($result[ProCache::CACHE_CLEAR_SELF]);
		}

		return $result;
	}

	/**
	 * Given cache clear custom definition string, convert to array of cache clear behaviors
	 *
	 * @param string $cacheClearCustom String that defines custom cache clearing settings
	 * @param Template|null $template
	 * @return array Array indexed by template name
	 *
	 */
	protected function getCacheClearCustomBehaviors($cacheClearCustom, Template $template = null) {

		$cacheClears = array();

		foreach(explode("\n", $cacheClearCustom) as $line) {
			// line in format: "basic-page=1,2,3" or "basic-page(10)=1,2,3"
			if(!strpos($line, '=')) continue;
			$line = trim($line);
			if(strpos($line, '#') === 0) continue; // skip comments

			// find current template in rules, if it's there
			if($template !== null) {
				if(strpos($line, $template->name) !== 0 && strpos($line, "($template->id)") === false) continue;
			}

			list($name, $behaviorStr) = explode('=', $line, 2);

			$name = trim($name);
			$behaviorStr = trim($behaviorStr);
			$behaviorPageIDs = array();
			$behaviorSelector = '';
			$behaviors = array();

			if(strpos($name, '(') !== false) {
				// template and id, i.e. "basic-page(10)"
				list($name, $id) = explode('(', $name, 2);
				$id = (int) trim($id, ')');
				if($template !== null && $id != $template->id) continue;
				$t = $this->wire()->templates->get($id);
				if($t && $name !== $t->name) $name = $t->name;
			}

			while(strpos($behaviorStr, 'pages:') !== false) {
				// i.e. basic-page=pages:123,456,789
				// i.e. basic-page=pages:template=product,categories.count>0
				list($behaviorStr, $behaviorData) = explode('pages:', $behaviorStr, 2);
				$behaviorData = trim($behaviorData);
				if(!strlen($behaviorData)) continue;
				if(ctype_digit(str_replace(array(',', ' '), '', $behaviorData))) {
					// CSV list of page IDs
					foreach(explode(',', $behaviorData) as $pageID) {
						$pageID = (int) trim($pageID);
						if($pageID) $behaviorPageIDs[$pageID] = $pageID;
					}
					if(count($behaviorPageIDs)) $behaviors[] = ProCache::CACHE_CLEAR_PAGES;
				} else {
					$behaviorSelector = $behaviorData;
					$behaviors[] = ProCache::CACHE_CLEAR_SELECTOR;
				}
			}

			if($template !== null && trim($name) !== $template->name) continue;

			// found template, override behaviors with one specific to this template
			if(strlen($behaviorStr)) {
				$behaviorStr = str_replace(array(' ', ','), '', $behaviorStr);
				if(strpos($behaviorStr, ',') !== false) {
					// format: "1,2,3" or "C,P,H"
					foreach(explode(',', $behaviorStr) as $c) {
						$c = trim($c);
						if(strlen($c) > 1) $c = $c[0];
						$behaviors[] = $c;
					}
				} else {
					// format: "CPH" or "123" 
					for($n = 0; $n < strlen($behaviorStr); $n++) {
						$behaviors[] = $behaviorStr[$n];
					}
				}
			}

			$cacheClear = array(ProCache::CACHE_CLEAR_SELF => ProCache::CACHE_CLEAR_SELF);
			
			foreach($behaviors as $behavior) {
				if(ctype_digit("$behavior")) {
					// i.e. 1
					$behavior = (int) $behavior;
				} else if(ctype_alpha($behavior)) {
					// i.e. C
					$behavior = array_search(strtoupper($behavior), $this->behaviorAbbrs);
					if($behavior === false) continue;
				} else {
					// unknown behavior
					continue;
				}
				$behavior = (int) $behavior;
				if($behavior === ProCache::CACHE_CLEAR_PAGES) {
					$cacheClear[$behavior] = $behaviorPageIDs;
				} else if($behavior === ProCache::CACHE_CLEAR_SELECTOR) {
					$cacheClear[$behavior] = $behaviorSelector;
					//$cacheClear[$behavior] = $behavior;
				} else if($behavior === ProCache::CACHE_CLEAR_NOSELF) {
					unset($cacheClear[ProCache::CACHE_CLEAR_SELF]);
					$cacheClear[$behavior] = $behavior;
				} else {
					$cacheClear[$behavior] = $behavior;
				}
			}

			$cacheClears[$name] = $cacheClear;
		}
		
		return $cacheClears;
	}

	/**
	 * Get cache clear behaviors as string
	 *
	 * @param array $options
	 *  - `useAbbrs` (bool): Use behavior abbreviations rather than numbers? (default=true)
	 *  - `useTemplateIDs` (bool): Include template IDs in definition? i.e. "basic-page(10)=behaviors" (default=false)
	 *  - `useComments` (bool): Use commented lines to indicate templates without custom definitions? (default=false)
	 *  - `cacheClearCustom` (string|null): Cache custom clear behaviors string to parse as source rather than default (default=null)
	 * @return string
	 *
	 */
	public function getCacheClearBehaviorsStr(array $options = array()) {
		
		$defaults = array(
			'useAbbrs' => true,
			'useTemplateIDs' => false,
			'useComments' => false, 
			'cacheClearCustom' => null,
			'onlyCustom' => true,
		);
	
		$options = array_merge($defaults, $options);
		$strs = array();
		$templates = $this->wire()->templates;
		$data = $this->getCacheClearBehaviors(array(
			'onlyCustom' => $options['onlyCustom'], 
			'cacheClearCustom' => $options['cacheClearCustom'],
		));

		foreach($data as $templateName => $behaviors) {
			
			$template = $templates->get($templateName);
			if(!$template) continue;
		
			// skip templates with settings defined on template
			if($template->cache_time < 0) continue;

			list($str1, $str2, $str3) = array($templateName, '', '');

			if($options['useTemplateIDs']) {
				$str1 .= "($template->id)=";
			} else {
				$str1 .= "=";
			}

			unset($behaviors[ProCache::CACHE_CLEAR_SELF]); // default assumed
			
			if(!empty($behaviors[ProCache::CACHE_CLEAR_SELECTOR])) {
				$str3 = "pages:" . $behaviors[ProCache::CACHE_CLEAR_SELECTOR];
				unset($behaviors[ProCache::CACHE_CLEAR_SELECTOR]);
			} else if(!empty($behaviors[ProCache::CACHE_CLEAR_PAGES])) {
				$str3 = "pages:" . implode(',', $behaviors[ProCache::CACHE_CLEAR_PAGES]);
				unset($behaviors[ProCache::CACHE_CLEAR_PAGES]);
			}
			
			if($options['useAbbrs']) {
				$abbrs = array();
				foreach($behaviors as $behavior) {
					$behavior = (int) $behavior;
					$abbrs[] = isset($this->behaviorAbbrs[$behavior]) ? $this->behaviorAbbrs[$behavior] : $behavior;
				}
				$str2 .= implode('', $abbrs);
			} else if(count($behaviors)) {
				$str2 .= implode(',', $behaviors);
			}
			
			$str = $str1 . $str2; 
			if($str3) $str .= ($str2 ? " $str3" : $str3);

			$strs[$templateName] = $str;
		}

		ksort($strs);
		$behaviorsStr = implode("\n", $strs);
	
		if($options['useComments']) {
			// include templates without custom definitions as commented
			$seeTemplate = '(@template)'; 
			$strs2 = array();
			foreach($this->procache->cacheTemplates as $tid) {
				$template = $templates->get((int) $tid);
				if(!$template || isset($strs[$template->name])) continue;
				$strs2[$template->name] = "# $template->name=" . ($template->cache_time < 0 ? $seeTemplate : '');
			}
			ksort($strs2);
			$behaviorsStr .= "\n\n" . implode("\n", $strs2); 
		}

		return $behaviorsStr;
	}

	/**
	 * Get pages that should be cleared when given Page is modified
	 * 
	 * @param Page $page
	 * @param bool $getIDs
	 * @return PageArray|array
	 * 
	public function getCacheClearPages(Page $page, $getIDs = false) {
		$behaviors = $this->getCacheClearBehaviors(array(
			'page' => $page, 
		));
		$clearPages = new PageArray();
		$clearIDs = array();
		foreach($behaviors as $key => $value) {
		}
		
		return $getIDs ? $clearIDs : $clearPages;
	}
	 */

	/**
	 * Execute cache clear behaviors for given Page
	 * 
	 * #pw-hooker
	 * 
	 * @param Page $page
	 * @param array $behaviors
	 * @return array Returns array indexed by behavior name each with a count of files cleared
	 * 
	 */
	public function ___executeCacheClearBehaviors(Page $page, array $behaviors) {

		// $behaviors = $this->getCacheClearBehaviors(array('page' => $page));
		$cleared = array();
		$static = $this->procache->getStatic();

		if(isset($behaviors[ProCache::CACHE_CLEAR_SITE])) {
			// clear all of cache
			$cleared['site'] = $static->clearAll();
			return $cleared;
		}
			
		if(isset($behaviors[ProCache::CACHE_CLEAR_FAMILY])) {
			// clear page, parent, siblings, children, grandchildren, etc. 
			$parent = $page->parent_id > 0 ? $page->parent : $page;
			$cleared['family'] = $static->clearBranch($parent);
			
		} else if(isset($behaviors[ProCache::CACHE_CLEAR_CHILDREN])) {
			// clears page and children
			$cleared['children'] = $static->clearBranch($page);
		} 
		
		if(!isset($behaviors[ProCache::CACHE_CLEAR_NOSELF])) {
			$cleared['self'] = $static->clearPage($page);
		}

		if(isset($behaviors[ProCache::CACHE_CLEAR_PARENTS])) {
			// clear parent pages
			$qty = 0;
			foreach($page->parents as $parent) {
				if(!$parent->id) break;
				if($parent->id < 2) continue;
				$qty += $static->clearPage($parent);
			}
			$cleared['parents'] = $qty;
		}

		if(isset($behaviors[ProCache::CACHE_CLEAR_HOME])) {
			// clear homepage
			$homepage = $this->pages->get(1); /** @var Page $homepage */
			$cleared['home'] = $static->clearPage($homepage);
		}

		if(!empty($behaviors[ProCache::CACHE_CLEAR_PAGES])) {
			// clear specific pages
			$ids = array();
			foreach($behaviors[ProCache::CACHE_CLEAR_PAGES] as $id) {
				$ids[(int) $id] = (int) $id;
			}
			$items = $this->wire()->pages->getById($ids);
			if($items->count()) {
				$cleared['pages'] = $static->clearPages($items);
			} else {
				$cleared['pages'] = 0;
			}
		}

		if(!empty($behaviors[ProCache::CACHE_CLEAR_SELECTOR])) {
			// clear pages via selector
			$selector = $behaviors[ProCache::CACHE_CLEAR_SELECTOR];
			$items = $this->wire()->pages->find($selector);
			if($items->count()) {
				$cleared['selector'] = $static->clearPages($items);
			} else {
				$cleared['selector'] = 0;
			}
		}
		
		if(!empty($behaviors[ProCache::CACHE_CLEAR_REFERENCES])) {
			// page references
			$items = $page->references();
			if($items->count()) {
				$cleared['references'] = $static->clearPages($items);
			} else {
				$cleared['references'] = 0;
			}
		}

		//$this->warning($behaviors);
		//$this->warning($cleared); 
		
		return $cleared;
	}
}
	