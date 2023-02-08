<?php namespace ProcessWire;

/**
 * ProcessWire Pro Cache: CDN 
 *
 * Copyright (C) 2020 by Ryan Cramer
 *
 * This is a commercially licensed and supported module
 * DO NOT DISTRIBUTE
 *
 */ 

class ProCacheCDN extends ProCacheClass {
	
	/**
	 * @var array
	 * 
	 */
	protected $cdnHosts = array();

	/**
	 * @var array
	 *
	 */
	protected $cdnPathsToHosts = array();

	/**
	 * @var array
	 * 
	 */
	protected $cdnExts = array();

	/**
	 * @var array
	 * 
	 */
	protected $cdnAttrs = array();

	/**
	 * @var int
	 * 
	 */
	protected $cdnStatus;

	/**
	 * @var array
	 * 
	 */
	protected $cdnTemplates;

	/**
	 * @var bool
	 * 
	 */
	protected $debug = false;

	/**
	 * Becomes a boolean if there is a forced allow from an allowCDN(bool) call
	 * 
	 * @var bool|null
	 * 
	 */
	protected $allow = null;
	
	/**
	 * Construct
	 *
	 * @param ProCache $procache
	 * 
	 */
	public function __construct(ProCache $procache) {
	
		parent::__construct($procache);
		
		$this->cdnStatus = (int) $procache->cdnStatus;
		$this->cdnTemplates = $procache->cdnTemplates;
		
		$cdnHosts = $procache->cdnHosts;
		$this->cdnHosts = $cdnHosts ? explode("\n", $cdnHosts) : array();
		
		$cdnExts = strtolower($procache->cdnExts);
		$cdnExts = $cdnExts ? explode(' ', trim($cdnExts)) : array();
		foreach($cdnExts as $ext) if(!empty($ext)) $this->cdnExts[$ext] = $ext;
		
		$cdnAttrs = strtolower($procache->cdnAttrs);
		$cdnAttrs = $cdnAttrs ? explode(' ', trim($cdnAttrs)) : array(); // for type=html only
		foreach($cdnAttrs as $attr) if(!empty($attr)) $this->cdnAttrs[$attr] = $attr;

		$this->debug = $this->procache->debug || $this->procache->wire('config')->debug;
	}

	/**
	 * Get or set runtime CDN HTML attributes 
	 * 
	 * @param array $attrs Attributes to add or omit to get current attributes
	 * @param bool $replace Replace all existing attributes (when setting/adding)? Omit to add more
	 * @return array
	 * 
	 */
	public function attrs(array $attrs = array(), $replace = false) {
		if($replace) $this->cdnAttrs = array();
		if(empty($attrs)) return $this->cdnAttrs;
		foreach($attrs as $attr) {
			$attr = strtolower($attr);
			$this->cdnAttrs[$attr] = $attr;
		}
		return $this->cdnAttrs;		
	}

	/**
	 * Get or set runtime CDN file extensions
	 *
	 * @param array $exts Extensions to add or omit to get current extensions
	 * @param bool $replace Replace all existing extensions (when setting/adding)? Omit to add more
	 * @return array
	 *
	 */
	public function exts(array $exts = array(), $replace = false) {
		if($replace) $this->cdnExts = array();
		if(empty($exts)) return $this->cdnExts;
		foreach($exts as $ext) {
			$ext = strtolower($ext);
			$this->cdnExts[$ext] = $ext;
		}
		return $this->cdnExts;
	}

	/**
	 * Allow CDN to be used for this Page and request?
	 *
	 * @param Page|null|bool $page Omit for current Page, specify Page, or specify boolean to force allow/disallow
	 * @return bool
	 *
	 */
	public function allowCDN($page = null) {
		
		if(is_bool($page)) $this->allow = $page;
		if(is_bool($this->allow)) return $this->allow;

		$cdnStatus = (int) $this->cdnStatus;
		
		if($cdnStatus === ProCache::CDN_STATUS_OFF) return false;

		$user = $this->wire()->user;
		
		if($cdnStatus === ProCache::CDN_STATUS_GUEST) {
			if($user && $user->isLoggedin()) return false;
		} else if($cdnStatus == ProCache::CDN_STATUS_USERS) {
			if(!$user || !$user->isLoggedin()) return false;
		} else {
			// CDN_STATUS_ALL always active
		}

		if(empty($this->cdnHosts)) return false;
		
		if(count($this->cdnTemplates)) {
			if($page === null) $page = $this->wire()->page;
			if(!$page || !$page instanceof Page) return false;
			if(!in_array($page->template->id, $this->cdnTemplates)) return false;
		}

		return true;
	}


	/**
	 * Get array of CDN path to CDN host, i.e. [ '/site/' => 'cdn.domain.com/' ]
	 * 
	 * @param bool|null $https include only HTTPS hosts? Or omit to auto-detect from $config->https
	 * @return array
	 * 
	 */
	public function pathsToHosts($https = null) {
		
		$config = $this->wire()->config;
		$rootURL = $config->urls->root;
		$pathsHosts = array();
		$pathsLengths = array();
		$https = $https === null ? (int) $config->https : (int) $https;
		
		if(isset($this->cdnPathsToHosts[$https])) {
			return $this->cdnPathsToHosts[$https];
		}
		
		foreach($this->cdnHosts as $line) {
			// example of $line: /site/ = cdn.host.com
			if(strpos($line, '=') === false) continue;

			$line = trim($line);
			list($path, $host) = explode('=', $line, 2);

			// prepare the path
			$path = trim($path);
			if($rootURL != '/' && strpos($path, $rootURL) === 0) {
				// path specifies root subdirectory already
			} else {
				$path = $rootURL . ltrim($path, '/');
			}
			// make sure path ends with a slash
			$path = rtrim($path, '/') . '/';

			// if path doesn't start with a slash, fix that
			if(strpos($path, '/') !== 0) $path = "/$path";

			// prepare the host
			$host = trim($host);
			$host = rtrim($host, '/') . '/';

			if(strpos($host, '//') === false) {
				// host does not specify a scheme/protocol, so add "//" to account for http and https
				$host = '//' . $host;
			} else if($https && stripos($host, 'http://') === 0) {
				// don't use http-only CDNs when request is https and host only designates http
				continue;
			}
			
			$pathsHosts[$path] = $host;
			$pathsLengths[$path] = strlen($path);
		}
	
		// sort with longest paths first
		arsort($pathsLengths); 
		
		$this->cdnPathsToHosts[$https] = array();
		
		foreach(array_keys($pathsLengths) as $path) {
			$this->cdnPathsToHosts[$https][$path] = $pathsHosts[$path];
		}
		
		return $this->cdnPathsToHosts[$https];
	}

	/**
	 * Given a local URL convert to a CDN URL (if given URL is in CDN’s path space)
	 * 
	 * @param string $url
	 * @param bool $requireExt Require valid CDN file extension as defined in configuration? (default=false)
	 * @return string
	 * 
	 */
	public function url($url, $requireExt = false) {
		$cdnUrl = $url;
		
		if($requireExt) {
			$ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
			if(!isset($this->cdnExts[$ext])) return $cdnUrl;
		}
		
		if(strpos($url, '://')) {
			// we’ve been given an httpUrl, convert to regular
			list($scheme, $url) = explode('://', $url, 2);
			list($host, $url) = explode('/', $url, 2);
			if(!in_array($host, $this->wire()->config->httpHosts)) return $cdnUrl;
			$url = "/$url";
		} else if(strpos($url, '/') !== 0) {
			// unrecognized URL format
			return $url;
		} else {
			$scheme = $this->wire()->config->https ? 'https' : 'http';
		}
		
		$https = strtolower($scheme) === 'https' ? true : null;
		
		foreach($this->pathsToHosts($https) as $path => $host) {
			if(strpos($url, $path) !== 0) continue;
			$cdnUrl = $host . substr($url, strlen($path)); 
			break;
		}
		
		return $cdnUrl;
	}

	/**
	 * Populate CDN replacements
	 *
	 * @param $out
	 * @param string $type Either 'html' or 'css'
	 * @return bool Returns true if replacements were made, false if not
	 *
	 */
	public function populateCDN(&$out, $type = 'html') {

		$timer = $this->debug ? Debug::timer('ProCacheCDN') : null;
		$replacements = array();
		$exts = count($this->cdnExts) ? '\.(?:' . implode('|', $this->cdnExts) . ')' : '\.[a-z0-9]+';
		$attrs = count($this->cdnAttrs) ? implode('|', $this->cdnAttrs) : '[a-z]+';
		
		foreach($this->pathsToHosts() as $path => $host) {
			
			// if the path is not referenced anywhere in the output, we can early exit
			if(strpos($out, $path) === false) continue;

			if($type == 'html') {
				// html replacement of paths
				//        1:attr          2:quote1       3:path                                 4:extra (srcset or quote2)
				$re = '!\b(' . $attrs . ')(\s*=\s*["\']?)(' . $path . '[^"\'\s>]*?' . $exts . ')\b([^"\'>]*)!i';

				// $re = '!\b(' . $attrs . ')(\s*=\s*["\']?)(' . $path . '[^"\'\s>]*?' . $exts . ')([\'"]|\s|>)!i';
			} else if($type == 'css') {
				// css replacement of paths
				//     1:attr      2:quote1   3:path                            4:quote2
				$re = '!(:\s*url\()(["\']?)(' . $path . '[^"\')]*?' . $exts . ')([\'"]?\))!i';
			} else {
				// replace any paths that are in single or double quotes
				//     1:attr 2:quote1  3:path                         4:quote2
				$re = '!(.?)(["\'])(' . $path . '[^"\']+?' . $exts . ')([\'"])!i';
			}

			// locate all paths in output
			if(!preg_match_all($re, $out, $matches)) continue;

			foreach($matches[0] as $key => $fullMatch) {
				$attr = $matches[1][$key];
				$quote1 = $matches[2][$key];
				$url = $matches[3][$key];
				$extra = $matches[4][$key];
				$url = str_replace($path, '/', $url);
				$at = strtolower($attr);
			
				if(($at === 'srcset' || strpos($at, 'data-') === 0) && strlen($extra) > 1 && strpos($extra, '/') !== false) {
					// srcset or other attribute with more references
					$extra = str_replace(dirname($matches[3][$key]) . '/', $host . ltrim(dirname($url), '/') . '/', $extra);
				}

				$replacements[$fullMatch] = $attr . $quote1 . $host . ltrim($url, '/') . $extra;
			}
		}

		if($timer) {
			if($type == 'html') {
				$this->procache->debugInfo(
					"     ProCache CDN: " . count($replacements) . " URLs updated" . "\n" .
					"PopulateCDN timer: " . Debug::timer($timer) . "s"
				);
			} else if($type == 'css') {
				$out .= '/* ProCache CDN ' . count($replacements) . ' URLs (debug mode) ' . Debug::timer($timer) . 's */';
			}
		}

		if(count($replacements)) {
			$out = str_replace(array_keys($replacements), array_values($replacements), $out);
			return true;
		} else {
			return false;
		}
	}

}