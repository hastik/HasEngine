<?php namespace ProcessWire;

/**
 * ProcessWire Pro Cache: Output Tweaks
 *
 * Copyright (C) 2020 by Ryan Cramer
 *
 * This is a commercially licensed and supported module
 * DO NOT DISTRIBUTE
 *
 */

abstract class ProCacheClass extends Wire {

	/**
	 * @var ProCache
	 *
	 */
	protected $procache;

	/**
	 * Construct
	 *
	 * @param ProCache $procache
	 * 
	 */
	public function __construct(ProCache $procache) {
		$procache->wire($this);
		$this->procache = $procache;
		parent::__construct();
	}

	/**
	 * @return ProCacheFiles
	 * 
	 */
	public function files() {
		return $this->procache->getFiles();
	}

	/**
	 * Convert given argument to Language if it isn’t already, or null if languages not active
	 * 
	 * @param Language|string|int|null $language
	 * @param string Get only this property: 'id' or 'name'
	 * @return Language|string|int|null
	 * 
	 */
	public function language($language = null, $property = '') {
		$languages = $this->wire()->languages;
	
		if(!$languages) {
			// languages not available
			$language = null;
			
		} else if($language === null) {
			// get current user language
			$language = $this->wire()->user->language;

		} else if(is_object($language)) { 
			// already what we want
			
		} else if($language === 'default') {
			// get default language
			$language = $languages->getDefault();
			
		} else if(ctype_digit("$language")) {
			// language ID
			$language = $languages->get((int) $language);
			
		} else {
			// language name
			$language = $languages->get($language);
		}
		
		if($language && !wireInstanceOf($language, 'Language')) {
			$language = null;
		}

		if($property) {
			if($language) {
				$value = $language->get($property);
			} else {
				$value = $property === 'id' ? 0 : '';
			}
		} else {
			$value = $language;
		}
		
		return $value;
	}
}
	
