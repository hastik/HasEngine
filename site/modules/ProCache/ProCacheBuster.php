<?php namespace ProcessWire;
/**
 * ProcessWire Pro Cache: File asset browser cache buster
 *
 * Copyright (C) 2018 by Ryan Cramer
 *
 * This is a commercially licensed and supported module
 * DO NOT DISTRIBUTE
 *
 * @property string $urlType One of "qstr" (?version), "qvar" (?v=version) or "name" (file.version.ext)
 *
 */
class ProCacheBuster extends WireData {
	
	public function __construct() {
		$this->set('urlType', 'qstr'); // qstr, qvar or name
		parent::__construct();
	}
	/**
	 * Get a cache busting URL for given asset/file URL
	 *
	 * If given a URL including scheme+hostname then it will be retained in the returned URL.
	 *
	 * @param string $url Full URL to file (hostname optional)
	 * @param array $options
	 *  - `mtime` (int): Modification time to use for file as unix timestamp. (default=0, which means auto-detect)
	 *  - `file` (string): Filename with disk path to determine mtime from. (default=auto detect from URL)
	 *  - `str` (string): Version string to fall-back to if URL from file cannot be located and no mtime available. (default=none)
	 *  - `of` (bool): Output formatting, TRUE if URL to be encoded for use in HTML, only matters if "&" comes into play. (default=true)
	 * @return string URL modified with cache busting string in query string
	 *
	 */
	public function url($url, array $options = array()) {

		$defaults = array(
			'mtime' => 0,
			'file' => '',
			'str' => '',
			'of' => true,
		);

		/** @var Config $config */
		$config = $this->wire('config');
		$rootPath = $config->paths->root;
		$rootURL = $config->urls->root;
		$queryString = '';
		$host = '';
		$_url = $url; // original unmodified
		$options = array_merge($defaults, $options);
		$urlType = $this->urlType;
		
		if(strpos($url, '/pwpc/')) return $url;

		if(strpos($url, '?')) {
			// separate url from query string
			list($url, $queryString) = explode('?', $url);
			// if query string is just for buster, we remove it
			if($queryString === "buster" || $queryString === "buster=1") $queryString = '';
		}

		if(strpos($url, '//') !== false) {
			// url that includes hostname
			// example: http://domain.com/site/assets/files/123/file.jpg
			// example: //domain.com/site/assets/files/123/file.jpg
			list($scheme, $url) = explode('//', $url, 2);
			if(strpos($url, '/') === false) return $_url; // fail, no file asset
			list($host, $url) = explode('/', $url, 2);
			if($host !== $config->httpHost) {
				// validate that host for file is not external
				$validHost = false;
				foreach($config->httpHosts as $h) {
					if($h === $host) {
						$validHost = true;
						break;
					}
				}
				// if external host, we cannot version this file
				if(!$validHost) return $_url;
			}
			$host = "$scheme//$host";
			$url = "/$url";
		}

		// if disk path to file provided, use that, otherwise attempt auto-detect from URL
		if($options['file']) {
			$file = $options['file'];
		} else {
			$file = strpos($url, $rootURL) === 0 ? substr($url, strlen($rootURL)) : $url;
			$file = $rootPath . ltrim($file, '/');
		}

		$xtime = ''; // minified buster version string based on mtime
		$mtime = 0; // file mtime

		if($options['mtime']) {
			$mtime = $options['mtime'];
		} else if(file_exists($file)) {
			$mtime = filemtime($file);
		} else if($options['str']) {
			$xtime = $options['str'];
		} else {
			return $_url; // fail, cannot find file and no alternative provided
		}

		if(empty($xtime)) {
			if(empty($mtime)) return $_url; // no mtime provided, return original URL
			$xtime = $this->timeStr($mtime); // create buster version string (xtime)
		}

		if($urlType === 'name') {
			// version string is part of file name, before extension
			// example: /url/to/file.123.jpg
			$parts = explode('.', $url);
			$ext = array_pop($parts);
			$url = implode('.', $parts);
			$url .= ".$xtime.$ext";

		} else if(strlen($queryString)) {
			// version string will be part of existing query string
			$amp = $options['of'] ? "&amp;" : "&";
			$queryString .= $urlType == 'qvar' ? $amp . "v=$xtime" : "$amp$xtime";

		} else {
			// version string will be entire query string
			$queryString = $urlType == 'qvar' ? "v=$xtime" : $xtime;
		}

		if(strlen($queryString)) {
			// append query string to url
			$url .= "?$queryString";
		}
		
		return $host . $url;
	}

	/**
	 * Get a cache busting URL (including scheme and hostname) for given asset/file URL
	 *
	 * @param string $url
	 * @param array $options See busterURL() method for options.
	 * @return string
	 *
	 */
	public function httpUrl($url, array $options = array()) {
		$url = $this->url($url, $options);
		if(strpos($url, '//') !== false) return $url;
		/** @var Config $config */
		$config = $this->wire('config');
		return ($config->https ? "https://" : "http://") . $config->httpHost . $url;
	}
	
	/**
	 * Get relative base-36 buster time for given time (or request time, if omitted)
	 *
	 * @param int $mtime Optionally specify modification time to use
	 * @return int|null|string
	 *
	 */
	public function timeStr($mtime = 0) {

		static $stime = null; // start base time to make times relative from
		static $ztime = null; // request time
		static $ctime = null; // cached time for when mtime==0

		if($ztime === null) {
			$ztime = time();
		}

		if(!$mtime) {
			if($ctime !== null) return $ctime;
			$mtime = $ztime;
			$ctime = 0;
		}

		if($stime === null) {
			// get time of file created when ProcessWire was originally installed
			$stime = (int) $this->wire('config')->installed; 
			if(!$stime) {
				$file = $this->wire('config')->paths->assets . 'installed.php';
				if(is_readable($file)) $stime = filemtime($file);
				if(!$stime) $stime = 1286709071; // 2010-10-10 11:11:11 date of first PW2
			}
		}

		if($mtime > $stime) {
			$xtime = $mtime - $stime;
			$older = false;
		} else {
			$xtime = $stime - $mtime;
			$older = true;
		}

		$xtime = base_convert($xtime, 10, 36); // convert to base-36

		if($older) {
			$xtime = ctype_digit("$xtime") ? "$xtime" : strtoupper($xtime);
		}

		if($ctime === 0) $ctime = $xtime; // cache for mtime==0

		return $xtime;
	}

	/**
	 * Cache bust any assets linked in the given HTML
	 * 
	 * This uses the TextformatterBuster module
	 * 
	 * @param string $html HTML to bust
	 * @param array $options Optionally modify defaults for TextformatterBuster: 
	 *  - `exts` (array): File extensions to bust 
	 *  - `attrs` (array): HTML attributes to look for files in 
	 *  - Note the defaults for these options are those configured with the TextformatterBuster module. 
	 * @return string
	 * 
	 */
	public function html($html, array $options = array()) {
		$module = $this->wire('modules')->get('TextformatterBuster');
		if(!$module) return $html;
		$module->format($html);
		foreach($options as $key => $value) {
			$module->set($key, $value);
		}
		return $html;
	}
	
}

