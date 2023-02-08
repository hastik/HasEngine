<?php namespace ProcessWire;

/**
 * ProcessWire Pro Cache: File Merger
 *
 * Copyright (C) 2018 by Ryan Cramer
 *
 * This is a commercially licensed and supported module
 * DO NOT DISTRIBUTE
 *
 * @todo background-image: none, url(../image/img.png); causes "browser tries to load an image called 'none' and results in a 404".
 * @todo the fill='url(%23b)' in this gets removed somehow: background-image: url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' width='800' height='57' viewBox='0 0 800 57'%3E%3Cdefs%3E%3Cpath id='a' d='M0 0v57h787.49L800 28.5 787.008 0z'/%3E%3C/defs%3E%3CclipPath id='b'%3E%3Cuse xlink:href='%23a' overflow='visible'/%3E%3C/clipPath%3E%3ClinearGradient id='c' gradientUnits='userSpaceOnUse' x1='-1205.676' y1='24.198' x2='-1204.676' y2='24.198' gradientTransform='matrix(0 -57 57 0 -979.313 -68666.563)' fill='url(%23b)' d='M0 0h800v57H0V0z'/%3E\a %3C/svg%3E");
 * 
 * @method string getFileContents($file)
 *
 */

class ProCacheFileMerger extends Wire {
	
	protected $prefix = 'pwpc';
	protected $ext = '';
	protected $source = '';
	protected $destination = '';
	protected $refreshSeconds = 43200;
	protected $expiredSeconds = 86400; 
	protected $hashLength = 40;
	protected $mergedFilename = '';
	protected $maxImportSizeCSS = 5; 
	protected $hashSRI = '';
	protected $scss = null;
	protected $less = null;
	protected $procache = null;
	
	const debug = false;  // when true, files are re-created on every merge

	/**
	 * Construct
	 * 
	 * @param array $files
	 * @param string $destination
	 * @throws WireException
	 * 
	 */
	public function __construct(array $files = array(), $destination = '') {
		$this->source = $this->wire('config')->paths->templates;
		if($destination) {
			$this->setDestination($destination);
		} else {
			$destination = $this->wire('config')->paths->assets . $this->prefix . '/';
			if(!is_dir($destination)) wireMkdir($destination);
			$this->setDestination($destination);
		}
		$src = dirname(__FILE__) . '/minify/src/';
		foreach(array('Exception', 'Minify', 'Converter', 'CSS', 'JS') as $f) {
			require_once("$src$f.php");
		}
		if(count($files)) $this->merge($files);
		return $this; 
	}

	/**
	 * Get or set ProCache instance
	 * 
	 * @param ProCache|null $procache
	 * @return ProCache
	 * 
	 */
	public function procache(ProCache $procache = null) {
		if($procache) $this->procache = $procache;
		if(!$this->procache) $this->procache = $this->wire('procache');
		return $this->procache;
	}

	/**
	 * Set the file extension
	 * 
	 * @param $ext
	 * @return $this
	 * 
	 */
	public function setExtension($ext) {
		$this->ext = strtolower(trim($ext, '.'));
		return $this; 
	}

	/**
	 * Set the max CSS file import size (for background images, etc.)
	 *
	 * @param int $size
	 * @return $this
	 *
	 */
	public function setMaxImportSizeCSS($size) {
		$this->maxImportSizeCSS = (int) $size;
		return $this; 
	}

	/**
	 * Get a property or API var
	 * 
	 * @param $key
	 * @return mixed|string
	 * 
	 */
	public function __get($key) {
		if($key == 'url') return $this->url();
		if($key == 'path') return $this->path();
		return parent::__get($key);
	}

	/**
	 * Set the destination for merged files
	 * 
	 * @param $destination
	 * @return $this
	 * @throws WireException
	 * 
	 */
	public function setDestination($destination) {
		if(!is_dir($destination)) throw new WireException("Please specify a destination dir that exists"); 
		if(!is_writable($destination)) throw new WireException("Destination dir is not writable");
		$this->destination = rtrim($destination, '/') . '/';
		return $this;
	}
	
	public function getDestination($url = false) {
		if(!$url) return $this->destination;
		return str_replace($this->wire('config')->paths->root, $this->wire('config')->urls->root, $this->destination);
	}

	/**
	 * Set the max age of a cached CSS/JS file
	 * 
	 * @param $seconds
	 * @return $this
	 * 
	 */
	public function setExpiration($seconds) {
		$seconds = (int) $seconds;
		$this->expiredSeconds = $seconds; 
		$this->refreshSeconds = ($seconds ? floor($seconds / 2) : 0);
		return $this; 
	}

	/**
	 * Set the relative source directory 
	 * 
	 * Not necessary to set this usually, as the default is /path/to/pw/site/templates/
	 * 
	 * @param $source
	 * @return $this
	 * 
	 */
	public function setSource($source) {
		$this->source = rtrim($source, '/') . '/';	
		return $this;
	}

	/**
	 * Set the file prefix  (default=pwpc)
	 * 
	 * @param $prefix
	 * @return $this
	 * 
	 */
	public function setPrefix($prefix) {
		if($prefix) $this->prefix = $this->wire('sanitizer')->name($prefix);
		return $this;
	}

	/**
	 * Given an array of filenames, determine what the target filename should be
	 * 
	 * This considers the file mtime and size, so that changes to the file should be picked up immediately.
	 * 
	 * @param array $files
	 * @param bool $verbose Makes it return array of all properties rather than string
	 * @return string|array
	 * 
	 */
	protected function getTargetFilename($files, $verbose = false) {
		
		$hash = '';
		$ext = $this->ext;
		
		foreach($files as $file) {
			
			$x = pathinfo($file, PATHINFO_EXTENSION); 
			$mtime = filemtime($file);
			$importFiles = array();
			
			if($x === 'scss' || $x === 'sass' || $x === 'less') {
				// include SCSS/LESS @import statements in the mtime calculation
				$importFiles = $this->getImportFiles($file); 
				$x = 'css';
			}
			
			foreach($importFiles as $importFile) {
				$mt = filemtime($importFile);
				if($mt > $mtime) $mtime = $mt;
			}
			
			$hash .= $file . ',' . $mtime . ',' . filesize($file) . ',';
			if(!$ext) $ext = $x;
		}
		
		$hash = sha1($hash);
		$file = "$this->destination$this->prefix-$hash.$ext";
		
		if($verbose) {
			return array(
				'file' => $file,
				'path' => $this->destination, 
				'prefix' => $this->prefix . '-', 
				'hash' => $hash, 
				'ext' => $ext,
			);
		}
		
		return $file;
	}

	/**
	 * Get the URL for the merged file
	 * 
	 * @return string
	 * 
	 */
	public function url() {
		/** @var Config $config */
		$config = $this->wire('config');
		$cnt = 0;
		$url = str_replace($config->paths->assets, $config->urls->assets, $this->mergedFilename, $cnt); 
		if(!$cnt) $url = str_replace($config->paths->root, $config->urls->root, $url);
		return $url;
	}

	/**
	 * Get the path for the merged file
	 * 
	 * @return string
	 * 
	 */
	public function path() {
		return $this->mergedFilename;
	}

	/**
	 * Given an array of file URLs, convert them to file system paths
	 * 
	 * @param string|array|WireArray $files Can be array or CSV string
	 * @return array
	 * 
	 */
	protected function fileURLsToPaths($files) {
		if(is_string($files)) {
			// convert CSV string to $files array
			$files = explode(',', $files);
			foreach($files as $key => $file) {
				if(strpos($file, '?') !== false) {
					list($file, $unused) = explode('?', $file);
					if($unused) { } // ignore
				}
				$files[$key] = trim($file); 
				if(!strlen($file)) unset($files[$key]); 
			}
		} else if($files instanceof WireArray) {
			$files = $files->getArray();
		}
		if(!is_array($files)) return array();
		$rootPath = $this->wire('config')->paths->root;
		$rootURL = $this->wire('config')->urls->root; 
		foreach($files as $key => $file) {
			if(strpos($file, '/') !== 0) $file = $this->source . $file;
			if(strpos($file, $rootPath) !== 0) {
				if($rootURL != '/' && strpos($file, $rootURL) === 0) {
					$file = substr($file, strlen($rootURL) - 1);
				}
				$file = $rootPath . ltrim($file, '/');
			}
			$files[$key] = $file;
		}
		return $files;
	}
	
	public function getMinifierJS() {
		return new \MatthiasMullie\Minify\JS();
	}
	
	public function getMinifierCSS() {
		$minifier = new \MatthiasMullie\Minify\CSS();
		$minifier->setMaxImportSize($this->maxImportSizeCSS);
		return $minifier;
	}
	
	/**
	 * Given a list of file URLs, merge them into a single file
	 * 
	 * To retrieve the result, call the $this->url() method or append ->url() to your merge call.
	 * 
	 * @param string|array|WireArray $files Array or CSV string of file URLs
	 * @param bool $minify Also minify the files? (default=false)
	 * @param bool $sri Return file that is named with its subresource integrity hash? (excludes leading "sha256-")
	 *  - Warning: the $sri option can add significant overhead, do not call on every request
	 * @return $this
	 * 
	 */
	public function merge($files, $minify = false, $sri = false) {
		
		$files = $this->fileURLsToPaths($files);	
		$targetData = $this->getTargetFilename($files, true);
		$targetFilename = $targetData['file'];
		$targetExt = $targetData['ext'];
		
		if(is_file($targetFilename) && !self::debug) {
			// use existing merge
			if($this->refreshSeconds && filemtime($targetFilename) < (time() - $this->refreshSeconds)) {
				// update mtime to prevent it from being cleaned out
				touch($targetFilename);
			}
			
		} else if($minify) {
			
			if(is_file($targetFilename)) unlink($targetFilename); 
			$minifier = null;
			
			if($targetExt === 'css') {
				$minifier = $this->getMinifierCSS();
			} else if($targetExt === 'js') {
				$minifier = $this->getMinifierJS();
			}
			
			if($minifier) {
				foreach($files as $file) {
					if($targetExt === 'css') {
						$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
						if($ext === 'scss' || $ext === 'sass') {
							$minifier->addString($this->compileSCSS($file), $file);
						} else if($ext === 'less') {
							$minifier->addString($this->compileLESS($file), $file);
						} else {
							$minifier->addFile($file);
						}
						
					} else {
						$minifier->addFile($file);
					}
				}
				$minifier->minify($targetFilename);
				wireChmod($targetFilename);
				$this->clean();
			}

		} else {
			// create new non-minified merge
			$fp = fopen($targetFilename, "w");
			foreach($files as $file) {
				if($targetExt === 'css') {
					$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
					if($ext === 'scss' || $ext === 'sass') {
						$css = $this->compileSCSS($file);
						$this->updateCSS($css, $file);
						fwrite($fp, $css);
					} else if($ext === 'less') {
						$css = $this->compileLESS($file);
						$this->updateCSS($css, $file);
						fwrite($fp, $css);
					} else {
						fwrite($fp, $this->getFileContents($file));
					}
				} else {
					fwrite($fp, $this->getFileContents($file));
				}
			}
			fclose($fp);
			wireChmod($targetFilename);
			$this->clean();
		}
	
		if($sri) {
			$data = file_get_contents($targetFilename);
			$hash = hash('sha256', $data, true);
			$hash = base64_encode($hash);
			$this->hashSRI = "sha256-$hash";
			$hashFilename = "$this->destination$this->prefix-$hash.$targetData[ext]";
			if(is_file($hashFilename) && $hashFilename != $targetFilename) {
				unlink($hashFilename);
				rename($targetFilename, $hashFilename);
				wireChmod($hashFilename);
				$targetFilename = $hashFilename;
			}
		}
		
		$this->mergedFilename = $targetFilename;
		
		return $this;
	}

	/**
	 * Get the contents of a file
	 * 
	 * Same as PHP file_get_contents() except that it updates CSS files to convert
	 * relative paths to absolute paths where appropriate. 
	 * 
	 * @param $file
	 * @return string
	 * 
	 */
	public function ___getFileContents($file) {
		if(is_file($file)) {
			$out = file_get_contents($file) . "\n";
		} else {
			$this->error("File does not exist: $file"); 
			$out = '';
		}
	
		if($this->ext == 'css' || strtolower(substr($file, -4)) == '.css') {
			$this->updateCSS($out, $file);
		}
		
		return $out; 
	}

	/**
	 * Clean up the merged file cache, removing old/expired files
	 * 
	 * @param string $ext Clean only files with this extension
	 * @param int $expiredSeconds Clear only files older than this many seconds (default=$this->expiredSeconds)
	 * @return int Number of files cleaned out
	 * 
	 */
	public function clean($ext = '', $expiredSeconds = 0) {
		static $cleaned = '';

		// don't allow this to run more than once per request
		if($ext && strpos($cleaned, ",$ext,") !== false) return 0; 
		if($cleaned === "*") return 0;
		
		if(!$expiredSeconds) $expiredSeconds = $this->expiredSeconds;
		if(!$expiredSeconds) return 0;
	
		$numRemoved = 0;
		$files = $this->wire()->files;
		$newestFile = '';
		$newestMtime = 0;
		$removeFiles = array();
		
		foreach($this->getFiles($ext) as $file) {
			$mtime = filemtime($file);
			if($mtime >= $newestMtime) list($newestFile, $newestMtime) = array($file, $mtime);
			if($mtime >= time() - $expiredSeconds) continue;
			$removeFiles[] = $file;
		}
		
		foreach($removeFiles as $file) {
			if($file === $newestFile) continue; // never remove newest version
			if($files->unlink($file)) $numRemoved++;
		}
	
		$cleaned = ($ext ? "$cleaned,$ext," : "*");
		
		return $numRemoved; 
	}

	/**
	 * Clear files by basename
	 * 
	 * @param array|string $basenames
	 * @return int
	 * 
	 */
	public function clearFiles($basenames) {
		if(!is_array($basenames)) $basenames = array($basenames);
		$numRemoved = 0;
		$files = $this->wire()->files;
		foreach($this->getFiles() as $file) {
			$basename = basename($file);
			foreach($basenames as $key => $name) {
				if($name === $basename) {
					if($files->unlink($file)) $numRemoved++;
					unset($basenames[$key]); 
				}
			}
			if(!count($basenames)) break;
		}
		return $numRemoved; 
	}

	/**
	 * Clear out all cached files
	 * 
	 * @param string $ext Optional extension to clear
	 * @return int Number of files removed
	 * 
	 */
	public function clear($ext = '') {
		$numRemoved = 0;
		$files = $this->wire()->files;
		foreach($this->getFiles($ext) as $file) {
			if($files->unlink($file)) $numRemoved++;
		}
		return $numRemoved; 
	}

	/**
	 * Get all files in the merge cache (both css and js)
	 * 
	 * @param string $ext Optionally specify an extension
	 * @return array
	 * 
	 */
	public function getFiles($ext = '') {
		$files = array();
		$prefix = $this->prefix . '-';
		$expectedLength = $this->hashLength + strlen($prefix); 
		foreach(new \DirectoryIterator($this->destination) as $file) {
			if($file->isDot() || $file->isDir()) continue;
			$basename = $file->getBasename("." . $file->getExtension());
			if(strlen($basename) != $expectedLength) continue;
			if(strpos($basename, $prefix) !== 0) continue;
			if($ext && $file->getExtension() != $ext) continue; 
			$files[$file->getBasename()] = $file->getPathname();
		}
		return $files;
	}

	/**
	 * Get the number of files in the merge cache
	 * 
	 * @param string $ext Optionally specify a file extension
	 * @return int
	 * 
	 */
	public function getNumFiles($ext = '') {
		return count($this->getFiles($ext));
	}

	/**
	 * Adjustments to CSS file contents
	 * 
	 * Converts relative paths to absolute
	 * 
	 * @param $out
	 * @param $file
	 * 
	 */
	public function updateCSS(&$out, $file) {
		if(strpos($out, 'url(') === false) return;

		$urls = array();
		$dirname = dirname($file) . '/';
		$cssRootURL = str_replace($this->wire('config')->paths->root, '/', $dirname);
		$siteRootURL = $this->wire('config')->urls->root;
		if($siteRootURL != '/') $cssRootURL = $siteRootURL . ltrim($cssRootURL, '/');
		
		preg_match_all('!\burl\(([^)]+)\)!', $out, $matches);

		foreach($matches[1] as $key => $url) {
			// skip over absolute, possibly external URLs.
			$url = trim($url, ' \'"');
			if(strpos($url, '//') === 0 || strpos($url, ':') !== false) continue;
			
			if(strpos($url, '../') === 0) {
				$cssRootParts = explode('/', trim($cssRootURL, '/'));
				while(strpos($url, '../') === 0) {
					array_pop($cssRootParts);
					$url = substr($url, 3);
				}
				$url = '/' . implode('/', $cssRootParts) . '/' . ltrim($url, '/');
			} else if(strpos($url, './') === 0) {
				$url = substr($url, 2);
				$url = $cssRootURL . ltrim($url, '/');
			} else {
				$url = $cssRootURL . ltrim($url, '/');
			}

			$url = '/' . ltrim($url, '/');
			$out = str_replace($matches[0][$key], "url($url)", $out);
			
			if(self::debug) $urls[] = $url;
		}
		
		if(self::debug) {
			$out = "/* PWPC " . wireDate('Y-m-d H:i:s') . "\n" . implode("\n", $urls) . "\n*/" . $out;
		}
	}

	/**
	 * Given contents of an HTML document merge and minify <link rel='stylesheet'> tags 
	 * 
	 * If any of the <link> tags have an href attribute that contains "?NoMinify" it will be skipped. 
	 * 
	 * @param string $html HTML document markup
	 * @param bool $minify Minfiy also? (default=true)
	 * @return string Updated HTML markup
	 * 
	 */
	public function mergeCSSFilesInMarkup($html, $minify = true) {
		
		$pos = stripos($html, '</head>');
		$head = substr($html, 0, $pos);
		$filesByGroup = array();
		$groups = array();

		$this->findAndRemovePlaceholders($head);
		$re = '!<link\s[^>]*?href\s*=\s*["\']?(/[^/][^"\'\s]+?\.(?:css|scss|sass|less)(?:\?[^"\'\s]+)?)["\'\s][^>]*>!i';
		if(!preg_match_all($re, $head, $matches)) return $html;
		
		$timer = $this->wire('config')->debug ? Debug::timer() : null;
		
		foreach($matches[0] as $key => $fullMatch) {
			// skip references that have "?NoMinify" in them	
			if(stripos($fullMatch, '?NoMinify')) {
				$fullReplacement = str_ireplace(array('?NoMinify=1', '?NoMinify'), '', $fullMatch); 
				$html = str_replace($fullMatch, $fullReplacement, $html);
				continue; 
			}
			// skip link tags that don't contain a rel='stylesheet'
			if(stripos($fullMatch, 'stylesheet') === false) continue;

			$file = $matches[1][$key];
			$group = str_replace($file, '{url}', $fullMatch); // remove file from the picture
			$group = str_replace("'", '"', $group); // normalize quotes to double
			
			$pos = strpos($file, '?');
			if($pos !== false) $file = substr($file, 0, $pos); // remove nocache variables, if present
			
			if(strpos($group, "\t") !== false) $group = str_replace("\t", " ", $group); // remove tabs
			while(strpos($group, '  ') !== false) $group = str_replace('  ', ' ', $group); // remove redundant whitespaace

			$hash = str_replace(array(' />', '/>', ' >'), '>', strtolower($group)); // tail doesn't matter for hash
			$hash = str_replace(array('"', ' '), '', $hash); // quotes and whitespace don't need to matter for the hash
			if(strpos($hash, 'text/css')) $hash = str_replace('type=text/css', '', $hash); // not needed in hash
			$hash = str_split($hash); // convert to array to generate hash
			sort($hash); // make order of attributes not matter for our group hash
			$hash = implode('', $hash); // convert sorted parts back to group hash
			
			if(isset($groups[$hash])) {
				$group = $groups[$hash];
			} else {
				$groups[$hash] = $group;
			}
			
			if(!isset($filesByGroup[$group])) $filesByGroup[$group] = array();
			$filesByGroup[$group][$fullMatch] = $file;
		}

		foreach($filesByGroup as $group => $files) {
			$mergedFile = $this->merge(array_values($files), $minify)->url();
			$mergedLink = '';
			$n = 0;
			foreach($files as $fullMatch => $file) {
				if(!$n) {
					// replace first <link> tag in the group with merged version
					$mergedLink = str_replace('{url}', $mergedFile, $group);
					$html = str_replace($fullMatch, $mergedLink, $html);
				} else {
					// replace all other <link> tags with blank
					$html = str_replace($fullMatch, '', $html); 
				}
				$n++;
			}
			if($mergedLink) {
				// remove whitespace that was created 
				$re = '/' . preg_quote($mergedLink, '/') . '\s*([\r\n]{1,2}[ \t]*)/s'; 
				$debugTimer = $timer ? "<!--PWPC" . Debug::timer($timer) . "-->": '';
				$html = preg_replace($re, $mergedLink . $debugTimer . '$1', $html);
			}
		}
		
		return $html;
	}

	/**
	 * Given contents of an HTML document merge and minify <script src='...'></script> tags
	 *
	 * If any of the <script> tags have a src attribute that contains "?NoMinify" it will be skipped.
	 *
	 * @param string $html HTML document markup
	 * @param bool $head Focus on document <head>? If false, it will focus on document <body>
	 * @param bool $minify Minfiy also? (default=true)
	 * @return string Updated HTML markup
	 *
	 */
	public function mergeJSFilesInMarkup($html, $head = true, $minify = true) {

		if($head) {
			$pos = stripos($html, '</head>');
			$content = $pos ? substr($html, 0, $pos) : $html;
		} else {
			$pos = stripos($html, '<body'); 
			$content = $pos ? substr($html, $pos) : $html; 
		}
		
		$filesByGroup = array();
		$groups = array();
		$timer = Debug::timer();

		$this->findAndRemovePlaceholders($content);
		$re = '!<script[^>]*?\s*src\s*=\s*["\']?(/[^/][^"\'\s>]+?\.js(?:\?[^"\'\s]+)?)["\'\s][^>]*>.*?<\s*/script\s*>!is';
		if(!preg_match_all($re, $content, $matches)) return $html;
		

		foreach($matches[0] as $key => $fullMatch) {
			// skip references that have "?NoMinify" in them	
			if(stripos($fullMatch, '?NoMinify')) {
				$fullReplacement = str_ireplace(array('?NoMinify=1', '?NoMinify'), '', $fullMatch);
				$html = str_replace($fullMatch, $fullReplacement, $html);
				continue;
			}

			$file = $matches[1][$key];
			$group = str_replace($file, '{url}', $fullMatch); // remove file from the picture
			$group = str_replace("'", '"', $group); // normalize quotes to double

			$pos = strpos($file, '?');
			if($pos !== false) $file = substr($file, 0, $pos); // remove nocache variables, if present

			if(strpos($group, "\t") !== false) $group = str_replace("\t", " ", $group); // remove tabs
			while(strpos($group, '  ') !== false) $group = str_replace('  ', ' ', $group); // remove redundant whitespace

			$hash = str_replace(array('"', ' '), '', $group); // quotes and whitespace don't need to matter for the hash
			if(strpos($hash, 'text/javascript')) $hash = str_replace('type=text/javascript', '', $hash); // not needed in hash
			$hash = str_split($hash); // convert to array to generate hash
			sort($hash); // make order of attributes not matter for our group hash
			$hash = implode('', $hash); // convert sorted parts back to group hash

			if(isset($groups[$hash])) {
				$group = $groups[$hash];
			} else {
				$groups[$hash] = $group;
			}

			if(!isset($filesByGroup[$group])) $filesByGroup[$group] = array();
			$filesByGroup[$group][$fullMatch] = $file;
		}

		foreach($filesByGroup as $group => $files) {
			$mergedFile = $this->merge(array_values($files), $minify)->url();
			$mergedScript = '';
			$n = 0;
			foreach($files as $fullMatch => $file) {
				if(!$n) {
					// replace first <script src> tag in the group with merged version
					$mergedScript = str_replace('{url}', $mergedFile, $group);
					$html = str_replace($fullMatch, $mergedScript, $html);
				} else {
					// replace all other <script src> tags with blank
					$html = str_replace($fullMatch, '', $html);
				}
				$n++;
			}
			if($mergedScript) {
				// remove whitespace that was created 
				$re = '/' . preg_quote($mergedScript, '/') . '\s*([\r\n]{1,2}[ \t]*)/s'; 
				$debugTimer = $timer ? "<!--PWPC" . Debug::timer($timer) . "-->" : '';
				$html = preg_replace($re, '$1' . $mergedScript . $debugTimer, $html);
			}
		}

		return $html;
	}

	/**
	 * Locates sections like conditional comments, replaces them with placeholders, and returns them
	 * 
	 * @param $html
	 * @return array Sections that were removed, indexed by placeholder name
	 * 
	 */
	protected function findAndRemovePlaceholders(&$html) {
		$placeholders = array();
		if(!strpos($html, '<!--[') || !strpos($html, ']-->')) return array();
		if(!preg_match_all('/(<!--\[.+?\]-->)/s', $html, $matches)) return array();
		foreach($matches[1] as $key => $value) {
			$placeholder = "<!--" . md5($value) . "-->";
			$placeholders[$placeholder] = $value;
		}
		$html = str_replace(array_values($placeholders), array_keys($placeholders), $html);
		return $placeholders;	
	}

	/**
	 * Restores placeholders that were found and removed by findAndRemovePlaceholders() method
	 * 
	 * @param $html
	 * @param array $placeholders
	 * 
	 */
	protected function restorePlaceholders(&$html, array $placeholders) {
		$html = str_replace(array_keys($placeholders), array_values($placeholders), $html);
	}

	/**
	 * Minify inline JS in the given HTML string
	 * 
	 * To skip a particular <script> place a "NoMinify" comment somewhere within it.
	 * 
	 * @param string $out Modified directly
	 * @return int Number of minifications that took place
	 * 
	 */
	public function minifyInlineJS(&$out) {
		if(stripos($out, '<script') == false) return 0;
		if(!preg_match_all('!(<script[^>]*>\s*)([^<].+?)</script>!is', $out, $matches)) return 0;
		$n = 0;
		foreach($matches[1] as $key => $tag) {
			if(stripos($tag, 'src')) continue;
			$code = $matches[2][$key];
			if(strpos($code, 'NoMinify') !== false) continue; 
			$minifier = $this->getMinifierJS();
			$minifier->add($code);
			$minCode = $minifier->minify();
			if($minCode && strlen($minCode) < strlen($code)) {
				$out = str_replace($tag . $code, trim($tag) . $minCode, $out);
				$n++;
			}
		}
		return $n;
	}

	/**
	 * Minify inline CSS in the given HTML string
	 * 
	 * To skip a particular inline <style> section, place a "NoMinify" CSS comment somewhere in it.
	 *
	 * @param string $out Modified directly
	 * @return int Number of minifications that took place
	 *
	 */
	public function minifyInlineCSS(&$out) {
		if(stripos($out, '<style') == false) return 0;
		if(!preg_match_all('!(<style[^>]*>\s*)([^<].+?)</style>!is', $out, $matches)) return 0;
		$n = 0;
		foreach($matches[1] as $key => $tag) {
			$code = $matches[2][$key];
			if(strpos($code, 'NoMinify') !== false) continue;
			$minifier = $this->getMinifierCSS();
			$minifier->setMaxImportSize(0); // don't encode images in inline CSS
			$minifier->add($code);
			$minCode = $minifier->minify();
			if($minCode && strlen($minCode) < strlen($code)) {
				$out = str_replace($tag . $code, trim($tag) . $minCode, $out);
				$n++;
			}
		}
		return $n;
	}

	/**
	 * SRI hash for inclusion in script tag, only available after a merge() call that specified use of $sri
	 * 
	 * @return string
	 * @throws WireException if you call this before merge() or after a merge() that didn't specify $sri
	 * 
	 */
	public function getHashSRI() {
		if(empty($this->hashSRI)) {
			throw new WireException('You must call merge() with the $sri option as true before calling getHashSRI()');
		}
		return $this->hashSRI;
	}

	/**
	 * Return instance of SCSS compiler
	 * 
	 * @param bool $cache Specify false to prevent cache and always return a new instance
	 * @return \ScssPhp\ScssPhp\Compiler
	 * @see http://leafo.net/scssphp/docs/
	 * @deprecated
	 * 
	 */
	public function getSCSS($cache = true) {
		if($cache) {}
		return $this->procache()->getSCSS();
		/*
		if($this->scss && $cache) return $this->scss;
		require_once(dirname(__FILE__) . '/scssphp/scss.inc.php');
		$scss = new \Leafo\ScssPhp\Compiler();
		if($cache) $this->scss = $scss;
		return $scss;
		*/
	}

	/**
	 * Return instance of LESS compiler
	 * 
	 * @param bool $cache Specify false to prevent cache and always return a new instance
	 * @return \lessc
	 * @see http://leafo.net/lessphp/docs/
	 *
	 */
	public function getLESS($cache = true) {
		if($cache) {}
		return $this->procache()->getLESS();
		/*
		if($this->less && $cache) return $this->less;
		require_once(dirname(__FILE__) . '/lessphp/lessify.inc.php');
		$less = new lessc;
		if($cache) $this->less = $less;
		return $less;
		*/
	}

	/**
	 * Compile an SCSS file
	 * 
	 * When using the `file` option the given file must include full server path and must be writable. The method will return 
	 * an error string if it cannot compile or write to the file, or boolean true if file does not need an update, or a positive
	 * integer of bytes written if file was updated. 
	 * 
	 * @param string $file SCSS file
	 * @param array $options
	 *  - `importPaths` (array): Paths to look for files referenced in @import, default is path of given $file. 
	 *  - `formatter` (string): "scss_formatter", "scss_formatter_nested" (default), or "scss_formatter_compressed".
	 *  - `file` (string): Save to this CSS file, rather than returning the CSS. See notes in method desc. (default='')
	 *  - `force` (bool): When using “file” option, force compilation even if existing CSS file has an up-to-date modified time. (default=false)
	 * @return string|bool|int
	 * @see http://leafo.net/scssphp/docs/
	 * 
	 */
	public function compileSCSS($file, array $options = array()) {
		return $this->procache()->getCompilerSCSS()->compile($file, $options);
		/*
		$defaults = array(
			'compiler' => null, // \ScssPhp\ScssPhp\Compiler
			'importPaths' => array(dirname($file) . '/'), 
			'formatter' => '', 
			'fileCSS' => '', 
			'force' => false, 
		);
		$options = array_merge($defaults, $options);
		if($options['fileCSS'] && !$options['force'] && is_file($options['fileCSS'])) {
			if(filemtime($file) <= filemtime($options['fileCSS'])) return true;
		}
		$compiler = $this->getSCSS();
		$compiler->setImportPaths($options['importPaths']); 
		if($options['formatter']) $compiler->setFormatter($options['formatter']); 
		try {
			$css = $compiler->compile(file_get_contents($file));
			if($options['fileCSS']) $css = $this->filePutContents($options['fileCSS'], $css); 
		} catch(\Exception $e) {
			$css = $this->compileError('SCSS', $file, $e->getMessage()); 
		}
		return $css;
		*/
	}
	
	/**
	 * Compile a LESS file
	 *
	 * @param string $file
	 * @param array $options
	 *  - `compiler` (\lessc|\Less_Parser): LESS compiler if you already have an instance you want to use
	 *  - `includePaths` (array): Paths to look for files referenced in @import statements, default is path of given $file.
	 *  - `formatter` (string): "lessjs" (default), "compressed" or "classic".
	 *  - `file` (string): Save to this CSS file, rather than returning the CSS. See notes in method desc. (default='')
	 *  - `url` (string): Root URL for CSS file, used only by Wikimedia parser (default='')
	 *  - `force` (bool): When using “file” option, force compilation even if existing CSS file has an up-to-date modified time. (default=false)
	 *  - `compress` (bool): Compress result when using Wikimedia parser? (default=true)
	 * @return string
	 * @see http://leafo.net/lessphp/docs/
	 *
	 */
	public function compileLESS($file, array $options = array()) {
		return $this->procache()->getCompilerLESS()->compile($file, $options);
		/*
		$defaults = array(
			'importPaths' => array(dirname($file) . '/'), 
			'formatter' => '',
			'fileCSS' => '',
			'force' => false, 
		);
		$options = array_merge($defaults, $options);
		if($options['fileCSS'] && !$options['force'] && is_file($options['fileCSS'])) {
			if(filemtime($file) <= filemtime($options['fileCSS'])) return true;
		}
		$compiler = $this->getLESS();
		$compiler->setImportDir($options['importPaths']);
		if($options['formatter']) $compiler->setFormatter($options['formatter']); 
		try {
			$css = $compiler->compileFile($file);
			if($options['fileCSS']) $css = $this->filePutContents($options['fileCSS'], $css); 
		} catch(\Exception $e) {
			$css = $this->compileError('LESS', $file, $e->getMessage()); 
		}
		return $css;
		*/
	}

	/**
	 * Return all files referenced in SCSS/LESS @import statements in the given SCSS/LESS file
	 * 
	 * @param string $file SCSS file
	 * @return array Array of file paths/names
	 * 
	 */
	protected function getImportFiles($file) {
		
		$files = array();
		$data = file_get_contents($file);
		if(strpos($data, '@import') === false) return $files;
		$extension = pathinfo($file, PATHINFO_EXTENSION); 
		$dirname = dirname($file);
		
		if(!preg_match_all('!@import\s+["\']([^/][^"\']+)["\']!', $data, $matches)) return $files;
		
		foreach($matches[1] as $key => $importFile) {
			if(strpos($importFile, '//') !== false) continue; // skip URLs
			$x = substr($importFile, -5); 
			if(strpos($x, '.css')) continue; // @import in .css file does not apply
			$tests = array();
			if($x == ".$extension") { 
				// matches extension of parent file 
				$tests[] = $importFile; 
			} else {
				// has no extension, so append extension of parent file
				$tests[] = "$importFile.$extension"; // example.scss
				if($x != 'less') {
					if(strrpos($importFile, '/')) {
						// file has path
						$parts = explode('/', $importFile);
						$importFile = array_pop($parts); // file without path
						$tests[] = implode('/', $parts) . "/_$importFile.$extension"; 
					} else {
						$tests[] = "_$importFile.$extension"; // _example.scss
					}
				}
			}
			foreach($tests as $f) {
				$importFile = realpath("$dirname/$f");
				if(file_exists($importFile)) {
					$files[] = $importFile;
					break;
				}
			}
		}
	
		// recursively check for other imports in found files
		foreach($files as $importFile) {
			$files2 = $this->getImportFiles($importFile); 
			if(count($files2)) $files = array_merge($files, $files2); 
		}
		
		return $files;
	}

	/**
	 * Typecasting to a string reveals the URL to a merged file
	 * 
	 * @return string
	 * 
	 */
	public function __toString() {
		return $this->url();
	}
	
}