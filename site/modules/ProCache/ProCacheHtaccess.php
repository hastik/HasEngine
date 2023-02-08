<?php namespace ProcessWire;

/**
 * ProcessWire Pro Cache: .htaccess file manager
 *
 * Copyright (C) 2020 by Ryan Cramer
 *
 * This is a commercially licensed and supported module
 * DO NOT DISTRIBUTE
 *
 */

class ProCacheHtaccess extends ProCacheClass {
	
	protected $requiredVersion = ''; // cached requiredVersion
	protected $getHtaccessRulesV2 = ''; // cached result of getHtaccessRulesV2() method
	protected $liveHtaccessFile = ''; // full path/file to live .htaccess file
	protected $liveFileContents = ''; // contents of live .htaccess file
	protected $hasProCacheTags = false; // does existing .htaccess file already have ProCache tags?

	/**
	 * Allow writing to example htaccess files?
	 * 
	 */
	public function useExampleFiles() {
		return ((int) $this->procache->htNoEx) !== 1;
	}

	/**
	 * Set full path/file to .htaccess file
	 * 
	 * Use this for testing/debugging only. 
	 * 
	 * @param string $htaccessFile
	 * 
	 */
	public function setLiveHtaccessFile($htaccessFile) {
		if(!file_exists($htaccessFile)) throw new WireException("File does not exist: $htaccessFile"); 
		$this->liveHtaccessFile = $htaccessFile;
	}
	
	/**
	 * Get htaccess file
	 *
	 * @param bool $getUrl
	 * @return string
	 *
	 */
	public function getLiveHtaccessFile($getUrl = false) {
		$rootPath = $this->wire()->config->paths->root;
		if($this->liveHtaccessFile) {
			$file = $this->liveHtaccessFile;
		} else {
			$file = $rootPath . '.htaccess';
		}
		return ($getUrl ? str_replace($rootPath, '/', $file) : $file);
	}

	/**
	 * Get contents of current live .htaccess file
	 * 
	 * @return string
	 * 
	 */
	public function getLiveHtaccessContents() {
		if($this->liveFileContents) return $this->liveFileContents;
		$file = $this->getLiveHtaccessFile();
		if(file_exists($file)) $this->liveFileContents = file_get_contents($this->getLiveHtaccessFile()); 
		$this->liveFileContents = str_replace("\r\n", "\n", $this->liveFileContents); 
		return $this->liveFileContents;
	}

	/**
	 * Get ProCache version hash found in current live .htaccess file 
	 * 
	 * @return string
	 * 
	 */
	public function getLiveHtaccessVersion() {
		$contents = $this->getLiveHtaccessContents();
		if(strpos($contents, 'PROCACHE') === false) return '';
		if(preg_match('!#\s+PROCACHE\s+v/?([^-\s]+)!', $contents, $matches)) return $matches[1];
		return '';
	}

	/**
	 * Get required htaccess version
	 * 
	 * @return string
	 * 
	 */
	public function getRequiredHtaccessVersion() {
		$this->getHtaccessRulesV2();
		return $this->requiredVersion;
	}

	/**
	 * Check if current .htaccess file needs any updates and return them if so
	 * 
	 * Also maintains an example completed file
	 * 
	 * @return string Return blank string if no updates needed or return string of data needed for manual update
	 * 
	 */
	public function checkHtaccessFile() {
		
		$fileContents = $this->getLiveHtaccessContents();
		$fileContentsOriginal = $fileContents;
		$newData = $this->populateHtaccessContents($fileContents); // updates $fileContents
		
		// if no updates are needed return a blank string
		if($newData === true) return '';

		$oldRules = $this->extractProCacheRules($fileContentsOriginal, 'RewriteCond %{REQUEST_URI}');
		$newRules = $this->extractProCacheRules($fileContents, 'RewriteCond %{REQUEST_URI}'); 
		if($oldRules === $newRules) return '';

		if($this->useExampleFiles()) {
			$exampleFile = $this->getExampleHtaccessFile();
			if(!file_exists($exampleFile) || file_get_contents($exampleFile) !== $fileContents) {
				$this->wire()->files->filePutContents($exampleFile, $fileContents);
				$fileUrl = $this->getExampleHtaccessFile(true);
				$this->message(sprintf($this->_('Your .htaccess file with an example of completed updates can be found in: %s'), $fileUrl));
			}
		}
		
		return $newData;
	}

	/**
	 * Get path/filename of example .htaccess file
	 * 
	 * @param bool $getUrl Get URL rather than disk path?
	 * @return string
	 * @throws WireException
	 * 
	 */
	public function getExampleHtaccessFile($getUrl = false) {
		
		$rootPath = $this->wire()->config->paths->root;
		$writable = is_writable($rootPath);
		$file = $rootPath . '.htaccess-procache';
		
		if($writable && is_file($file)) $writable = is_writable($file);
		
		if(!$writable) {
			$cachePath = $this->wire()->config->paths->cache; 
			$file = $cachePath . 'htaccess-procache.txt';
		}
		
		if($getUrl) {
			$rootUrl = $this->wire()->config->urls->root;
			$file = str_replace($rootPath, $rootUrl, $file);
		}
		
		return $file;
	}

	/**
	 * Populate .htaccess rules into given $fileContents and return what was added
	 * 
	 * @param $fileContents
	 * @return bool|int
	 *  - true: Content needs no updates
	 *  - string: Content to add (and/or that was added)
	 * 
	 */
	protected function populateHtaccessContents(&$fileContents) {
	
		if(((int) $this->procache->htVersion) === 1) {
			$newData = $this->getHtaccessRulesV1();
		} else {
			$newData = $this->getHtaccessRulesV2();
		}
		
		// Section landmarks
		$openLandmark = '# PW-PAGENAME';
		$closeLandmark = '# END-PW-PAGENAME';
		$hasLandmark = strpos($fileContents, $openLandmark) !== false && strpos($fileContents, $closeLandmark) !== false;
		$oldLandmark = '  RewriteCond %{REQUEST_URI} "^/~?[-_.a-zA-Z0-9/]*$"';
		$hasOldLandmark = strpos($fileContents, $oldLandmark) !== false;

		// ProCache tags 
		$openTag = '# PROCACHE v';
		$closeTag = '# /PROCACHE';
		$hasOpenTag = strpos($fileContents, $openTag) !== false;
		$hasCloseTag = strpos($fileContents, $closeTag) !== false;
		$hasTags = $hasOpenTag && $hasCloseTag;
		$this->hasProCacheTags = $hasTags;

		/*
		if($hasOpenTag > 1 || $hasCloseTag > 1) {
			$this->warning(sprintf($this->_('Multiple PROCACHE rulesets found in %s, please remove those not in use'), $fileUrl));
		}
		*/

		if($hasTags) {
			// htaccess file data already has ProCache tags in it
			// check if htaccess is already up-to-date
			$hasGoodVersion = false;
			foreach($this->getAcceptedHtaccessVersions() as $version) {
				if($hasTags && strpos($fileContents, $version)) $hasGoodVersion = true;
			}
			
			// return now if found version is good
			if($hasGoodVersion) return true;
			
			// htaccess needs update, remove old ProCache rules
			// $fileContents = preg_replace('! *# *PROCACHE *v.+?/PROCACHE[- ]*!s', '  ', $fileContents);
			$fileContents = $this->removeHtaccessRules($fileContents, array(
				'removeLinesHaving' => array('ProCache Manual Directives'),
				'replaceWith' => $newData
			));
			
		} else if($hasLandmark) {
			// htaccess needs to be written for first time
			// has PW-PAGENAME … END-PW-PAGENAME landmarks, append new section below it
			$fileContents = str_replace($closeLandmark, "$closeLandmark\n$newData\n", $fileContents);

		} else if($hasOldLandmark) {
			// has alternate/old landmark: RewriteCond %{REQUEST_URI} "^/~?[-_.a-zA-Z0-9/]*$"
			$fileContents = str_replace($oldLandmark, $oldLandmark . "\n$newData\n$oldLandmark", $fileContents);
			
		} else {
			// $this->warning(sprintf($this->_('Unable to find any landmarks in %s file for suggested placement of ProCache rules'), $fileUrl));
			// return false;
		}

		return $newData;
	}
	/**
	 * Extract and return ProCache rules from given .htaccess file contents
	 *
	 * Returned rules are stripped of comments
	 *
	 * @param string $contents
	 * @param array|string $exclusions Exclude any lines that contain any of these strings
	 * @return string
	 *
	 */
	protected function extractProCacheRules($contents, $exclusions = array()) {
		if(strpos($contents, 'PROCACHE') === false) return '';
		if(!is_array($exclusions)) $exclusions = empty($exclusions) ? array() : array($exclusions);
		if(!preg_match('!(#\s+PROCACHE\s+v[^-\s]+.*?#\s+/PROCACHE)!s', $contents, $matches)) return '';
		$a = explode("\n", $matches[1]);
		$lines = array();
		foreach($a as $line) {
			$line = trim($line);
			if(strpos($line, '#') === 0) continue;
			$exclude = false;
			foreach($exclusions as $xs) {
				if(stripos($line, $xs) !== false) $exclude = true;
			}
			if($exclude) continue;
			$lines[] = $line;
		}
		return implode("\n", $lines);
	}


	/**
	 * Remove ProCache .htaccess rules from given fileContents and return it
	 * 
	 * @param string $fileContents
	 * @param array $options
	 *  - `replaceWith` (string):  Optionally replace rules with this string
	 *  - `returnRemoved` (bool): Return removed rules rather than .htaccess contents without rules?
	 *  - `removeComments` (bool): Remove comments from returned rules?
	 *  - `removeBlankLines` (bool): Remove blank lines from returned rules?
	 *  - `removeLinesHaving` (array): Also remove lines having any of these strings (not case sensitive).
	 * @return string
	 * 
	 */
	public function removeHtaccessRules($fileContents, array $options = array()) {
		
		$defaults = array(
			'exclusions' => array(),
			'replaceWith' => '', 
			'returnRules' => false,
			'removeComments' => false,
			'removeBlankLines' => false,
			'removeLinesHaving' => array(), 
		);	
	
		$options = array_merge($defaults, $options);
		$lines = array();
		$rules = array();
		$removing = false;
		
		foreach(explode("\n", $fileContents) as $key => $line) {
			
			if(!empty($options['removeLinesHaving'])) {
				$skipLine = false;
				foreach($options['removeLinesHaving'] as $str) {
					if(stripos($line, $str) !== false) $skipLine = true;
					if($skipLine) break;
				}
				if($skipLine) continue;
			}

			$trimline = ltrim($line);
			
			if($removing) {
				if(strpos($trimline, '# /PROCACHE') === 0) $removing = false;
				$rules[] = $line;
			} else if(strpos($trimline, '# PROCACHE v') === 0) {
				$removing = true;
				if($options['replaceWith']) {
					$lines[] = $options['replaceWith'];
					$options['replaceWith'] = '';
				}
				
			} else {
				$lines[] = $line;
			}
		}
	
		if($options['returnRules']) $lines = $rules;
		
		if($options['removeComments'] || $options['removeBlankLines']) {
			foreach($lines as $key => $line) {
				if($options['removeComments'] && strpos(ltrim($line), '#') === 0) {
					unset($lines[$key]);
				} else if($options['removeBlankLines'] && !strlen(trim($line))) {
					unset($lines[$key]); 
				}
			}
		}
		
		return implode("\n", $lines);
	}

	/**
	 * Remove htaccess comments from given data
	 * 
	 * @param string $data
	 * @param string $match Only return lines that match this string
	 * @return string
	 * 
	 */
	protected function removeCommentLines($data, $match = '') {
		$lines = explode("\n", $data);
		foreach($lines as $key => $line) {
			$line = trim($line); 
			if(strpos($line, '#') === 0 || !strlen($line)) {
				unset($lines[$key]);
			} else if($match && stripos($line, $match) === false) {
				unset($lines[$key]);
			}
		}
		return implode("\n", $lines);
	}

	/**
	 * @return array
	 * 
	 */
	public function getAcceptedHtaccessVersions() {
		if(((int) $this->procache->htVersion) === 1) {
			$versions = array($this->getHtaccessRulesV1(true));
		} else {
			$versions = array($this->getRequiredHtaccessVersion());
		}
		return $versions;
	}

	/**
	 * Return a string of ProCache htaccess data consistent with module settings
	 *
	 * @param bool $noComments Exclude comments?
	 * @return string
	 *
	 */
	protected function getHtaccessRulesV2($noComments = true) {

		if($this->getHtaccessRulesV2) return $this->getHtaccessRulesV2;

		$a = array();
		$module = $this->procache;
		$config = $this->wire()->config;
		$templates = $this->wire()->templates;
		$sanitizer = $this->wire()->sanitizer;
		$noCacheGetVars = trim($module->noCacheGetVars);
		$extensions = array();
		$pwPageNameRule = ltrim($this->getPwPageNameSection(1), ' ');
		$slashRule = $this->getSlashRule();
		$replacements = array(
			'valid_request' => 'pwpcstep',
			'index_name' => 'pwpcname',
			'index_host' => 'pwpchost',
			'index_ext' => 'pwpcext',
			'index_file' => 'pwpcfile',
			'cache_path' => 'pwpcpath',
			'cache_exists' => 'pwpcgo', 
			', E=' => ',E=', 
			//'}.' => '}\\.',
		);
		
		if($module->cacheTemplates) {
			foreach($module->cacheTemplates as $templateID) {
				$template = $templates->get((int) $templateID);
				if(!$template) continue;
				$ext = $module->getStatic()->getContentTypeExt($template);
				$extensions[$ext] = $ext;
			}
		}

		if(!count($extensions)) $extensions = array('html');

		$a[] = '# check for valid request';
		// $a[] = $pwPageNameRule;
		$a[] = 'RewriteCond %{REQUEST_METHOD} !=POST';
			
		if($noCacheGetVars === '*') {
			// skip cache for all GET vars
			$a[] = "RewriteCond %{QUERY_STRING} !.*=.*"; 

		} else if(!empty($noCacheGetVars)) {
			// skip cache for certain GET vars
			$values = array();
			foreach(explode("\n", $noCacheGetVars) as $var) {
				$var = $sanitizer->name(trim($var)); 
				if(!empty($var)) $values[] = $var;
			}
			if(count($values)) {
				$values = implode('|', $values);
				$a[] = "RewriteCond %{QUERY_STRING} !.*($values)=.*";
			}
		}
		
		$a[] = "RewriteRule $slashRule - [E=valid_request:pour, E=index_name:index, E=index_host:X]";
		$a[] = "# exclude logged-in users";
		$a[] = 'RewriteCond %{ENV:valid_request} "=pour"';
		
		if(strlen(trim($module->noCacheCookies))) {
			// skip cache for certain cookies
			$values = array();
			foreach(explode("\n", $module->noCacheCookies) as $cookie) {
				$cookie = $sanitizer->name(trim($cookie));
				if(!empty($cookie)) $values[] = $cookie;
			}
			if(count($values)) {
				$values = implode('|', $values);
				$a[] = "RewriteCond %{HTTP_COOKIE} !^.*($values).*$";
			}
		}

		//$path = '%{DOCUMENT_ROOT}' . $config->urls->assets . $module->cacheDir;
		$path = $config->urls->assets . $module->cacheDir;
		$a[] = "RewriteRule $slashRule - [E=valid_request:stir, E=cache_path:$path]";
	
		// https
		if($module->https) {
			// use 'https' as file rather than 'index' when https
			if(empty($_SERVER['HTTPS']) && !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
				// AWS load balancer
				$a[] = "# https on AWS load balancer";
				$a[] = "RewriteCond %{HTTP:X-Forwarded-Proto} =https";
				$a[] = "RewriteRule ^.*$ - [E=index_name:https]";
			} else {
				// regular https
				$a[] = "# https";
				$a[] = "RewriteCond %{HTTPS} on";
				$a[] = "RewriteRule ^.*$ - [E=index_name:https]";
			}
		}
	
		// hosts
		$numHosts = 0;
		foreach($module->cacheHosts as $host) {
			if(!strlen($host)) continue;
			if(!in_array($host, $config->httpHosts) && $config->httpHost != $host) continue;
			$host = strtolower($host);
			//$hostName = str_replace('.', '\.', $host);
			$hostEnvName = str_replace(array(':', '.'), '_', $host);
			if(!$numHosts) $a[] = "# host names";
			$a[] = 'RewriteCond %{ENV:valid_request} "=stir"';
			$a[] = "RewriteCond %{HTTP_HOST} ^$host$ [NC]";
			$a[] = "RewriteRule ^.*$ - [E=index_host:$hostEnvName, E=valid_request:shake]";
			$numHosts++;
		}
		if(!$numHosts) $replacements[',E=' . $replacements['index_host'] . ':X'] = '';
	
		// extensions
		foreach($extensions as $ext) {
			$index = $numHosts ? "%{ENV:index_name}-%{ENV:index_host}.$ext" : "%{ENV:index_name}.$ext";
			$a[] = '# extension: ' . $ext;
			$a[] = 'RewriteCond %{ENV:valid_request} "=' . ($numHosts ? 'shake' : 'stir') . '"';
			//$a[] = "RewriteCond %{ENV:cache_path}/$0/$index -f"; // does index file exist?
			$a[] = "RewriteCond %{DOCUMENT_ROOT}%{ENV:cache_path}/$0/$index -f"; // does index file exist?
			$a[] = "RewriteRule ^.*$ - [E=valid_request:drink, E=index_file:$0/$index]";
		}

		// delivery
		$a[] = "# deliver from cache";
		$a[] = 'RewriteCond %{ENV:valid_request} "=drink"';
		$a[] = 'RewriteRule ^(.*) %{ENV:cache_path}/%{ENV:index_file} [L]';
	
		$a[] = '<ifModule mod_headers.c>';
		$a[] = 'Header set X-PWPC "ProCache" env=valid_request';
		$a[] = 'Header set X-Powered-By "ProcessWire CMS, ProCache" env=valid_request';
		$a[] = '</ifModule>';
		
		if($module->busterUrlType === 'name') {
			$a[] = "# BUSTER";
			$a[] = 'RewriteCond %{REQUEST_FILENAME} !-f';
			$a[] = "RewriteRule ^(.+)\.([a-zA-Z0-9]+)\.([^.]+)$ " . '$1.' . '$3' . " [L]";
		}

		$a[] = $pwPageNameRule;
		
		if($noComments) foreach($a as $k => $v) if(strpos($v, '#') === 0) unset($a[$k]); 
		$out = '  ' . implode("\n  ", $a); 
		
		if(strlen($module->docRootPath)) {
			$find = '%{DOCUMENT_ROOT}';
			$replace = rtrim($module->docRootPath, "/\\");
			if($replace === '-') {
				// optional blank (likely not useful)
				$replace = '';
				$find .= $config->urls->root;
			}
			$replacements[$find] = $replace;
		}
		
		$out = str_replace(array_keys($replacements), array_values($replacements), $out); 
		$this->requiredVersion = md5($out);
		$openTag = "# PROCACHE v2/$this->requiredVersion ";
		$closeTag = "# /PROCACHE ";
		while(strlen($openTag) < 97) $openTag .= '-';
		while(strlen($closeTag) < 97) $closeTag .= '-';
		$out = "\n  $openTag\n$out\n  $closeTag";
		$this->getHtaccessRulesV2 = $out;
		
		return $out; 
	}

	/**
	 * Get rule for how to handle trailing slashes
	 * 
	 * @return string
	 * 
	 */
	protected function getSlashRule() {
		if($this->procache->slashUrls > 0) {
			// consider cache only if trailing slash is present
			$slashRule = '^.*/$';
		} else if($this->procache->slashUrls < 0) {
			// consider cache only if trailing slash is NOT present
			$slashRule = '^.*[^/]$';
		} else {
			// disregard trailing slash
			$slashRule = '^.*$';
		}
		return $slashRule;
	}

	/**
	 * Get everything between PW-PAGENAME and END-PW-PAGENAME in the live .htaccess file
	 * 
	 * This is most likely only 1 line
	 * 
	 * @param int $maxLines
	 * @return string
	 * 
	 */
	protected function getPwPageNameSection($maxLines = 0) {
		// htaccess needs update and we can make it here
		$oldRewriteCond = '  RewriteCond %{REQUEST_URI} "^/~?[-_.a-zA-Z0-9/]*$"';
		$fileContents = $this->getLiveHtaccessContents();
		$openLandmark = '# PW-PAGENAME';
		$closeLandmark = '# END-PW-PAGENAME';
		$hasLandmarks = strpos($fileContents, $openLandmark) && strpos($fileContents, $closeLandmark);
		if(!$hasLandmarks) return $oldRewriteCond;
		list(,$foot) = explode($openLandmark, $fileContents, 2);
		list($data,) = explode($closeLandmark, $foot, 2);
		$data = $this->removeCommentLines($data, 'RewriteCond');
		if(empty($data)) $data = $oldRewriteCond;
		if($maxLines) {
			$lines = explode("\n", $data);
			$lines = array_slice($lines, 0, $maxLines);
			$data = implode("\n", $lines);
		}
			
		return $data;
	}

	/**
	 * Get instruction about where to insert ProCache rules in live .htaccess file
	 * 
	 * @return string
	 * 
	 */
	protected function getInstruction() {
		$fileContents = $this->getLiveHtaccessContents();
		$landmark1 = '# END-PW-PAGENAME';
		$landmark2 = 'RewriteCond %{REQUEST_FILENAME} !-f';
		if(strpos($fileContents, $landmark1)) {
			return sprintf($this->_('Insert it immediately after the line that says: %s.'), "`$landmark1`");
		}
		return sprintf($this->_('Insert it immediately before the line that says: %s.'), "`$landmark2`");
	}

	/**
	 * Get Fieldset for ProcessProCache
	 * 
	 * @return bool|InputfieldFieldset Returns false if no action required
	 * 
	 */
	public function getFieldset() {
		
		$htaccess = $this->checkHtaccessFile();
		if($htaccess === true || !strlen($htaccess)) return false;
		
		$exampleUrl = $this->useExampleFiles() ? $this->getExampleHtaccessFile(true) : '';
	
		if(!$this->wire()->input->requestMethod('POST')) {
			$this->warning($this->_('Your .htaccess file may require an update (see Status tab).'));
		}
		
		/** @var InputfieldFieldset $fs */
		$fs = $this->wire()->modules->get('InputfieldFieldset');
		$fs->attr('id+name', '_htaccessData');
		$fs->icon = 'warning';
		$fs->label = 
			$this->_('Action required') . ' ' . 
			'(' . $this->_('your .htaccess file needs an update') . ')';
		$fs->description = 
			$this->_('Once you have finished configuring ProCache (and saved your settings) please copy and paste the text below into your .htaccess file.') . ' ' .
			$this->getInstruction();
		
		if($this->hasProCacheTags) $fs->description .= ' ' . 
			$this->_('You should remove and replace your older ProCache .htaccess directives.');
		
		$note = $this->_('(see the “Tweaks” tab to turn .htaccess example files on or off.)');
	
		if($exampleUrl) {
			$fs->description .= ' ' . sprintf(
					$this->_('For your reference, we have also prepared an already-updated copy of your .htaccess in: %s'),
					"[u]{$exampleUrl}[/u]"
				) . ' ' . 
				$note;
		}
			
		/** @var InputfieldMarkup $f */
		$f = $this->wire()->modules->get('InputfieldMarkup');
		$f->label = $this->_('Text to copy/paste for .htaccess file');
		$f->attr('value', '<pre>' . $this->wire()->sanitizer->entities($htaccess) . '</pre>');
		if($exampleUrl) {
			$f->notes = sprintf($this->_('There is a copy of your updated .htaccess file in: %s'), "[u]{$exampleUrl}[/u]") . ' ' . $note;
			$f->detail = '';
		} else {
			$f->detail = '';
				
		}
		$fs->add($f);
	
		return $fs;
	}

	/**
	 * Backup live htaccess file (currently not used)
	 *
	 * @return string
	 *
	private function backupLiveFile() {
		$file = $this->getLiveHtaccessFile();
		$rootPath = $this->wire()->config->paths->root;
		$backupPath = $this->getBackupPath();
		$backupUrl = str_replace($rootPath, '/', $backupPath);
		// backup existing .htaccess file in to site/assets/backups/procache/htaccess-[n].txt
		$n = 1;
		do {
		$backupFile = $backupPath . "htaccess-$n.txt";
		} while(is_file($backupFile) && ++$n);
		$this->wire()->files->copy($file, $backupFile);
		$this->message(sprintf($this->_('Backed up existing .htaccess file to: %s'), $backupUrl));
		return $backupFile;
	}
	 */
	
	/**
	 * Allow writing to live .htaccess file?
	 *
	 * Returns boolean true if specifically allowed, false if specifically disallowed.
	 * Returns integer 0 if disallowed because file isn't readable/writable.
	 * Returns blank string if disallowed because pageNameCharset==UTF8.
	 *
	 * @return bool|int
	 *
	public function allowWriteLive() {
		if(!$this->procache->htAllow) return false;
		$file = $this->getHtaccessFile();
		if(!is_writable($file) || !is_readable($file)) return 0;
		if($this->wire()->config->pageNameCharset === 'UTF8') return '';
		return true;
	}
	 */

	/*
	protected function putHtaccessContents($fileContents) {
		$this->fileContents = $fileContents;
		return file_put_contents($this->getHtaccessFile(), $fileContents); 
	}
	*/

	/**
	 * Are any updates needed for live .htaccess file?
	 *
	public function updatesNeeded() {
		$versions = $this->getAcceptedHtaccessVersions();
		$fileContents = $this->getLiveHtaccessContents();
		$hasVersion = false;
		foreach($versions as $version) {
			if(strpos($fileContents, $version) !== false) $hasVersion = true;
		}
		return !$hasVersion;
	}
	 */


	/**
	 * Return old htaccess version (v1) which is still considered valid
	 * 
	 * This is used only for comparison purposes so that we don't advise them to change something unnecessarily.
	 *
	 * @param bool $getVersion Get version rather than rules?
	 * @return string
	 *
	 */
	public function getHtaccessRulesV1($getVersion = false) {

		$module = $this->procache;
		$getVars = trim($module->noCacheGetVars);
		$extensions = array();

		if($module->cacheTemplates) {
			foreach($module->cacheTemplates as $templateID) {
				$template = $this->wire('templates')->get($templateID);
				if(!$template) continue;
				$ext = $module->getStatic()->getContentTypeExt($template);
				$extensions[$ext] = $ext;
			}
		}
		if(!count($extensions)) $extensions = array('html');

		if(empty($getVars)) {
			$getVarsCond = '';

		} else if($getVars == '*') {
			$getVarsCond = "\n  RewriteCond %{QUERY_STRING} !.*=.*"; // passthru all GET vars

		} else {
			$str = '';
			foreach(explode("\n", $getVars) as $var) $str .= $this->sanitizer->name(trim($var)) . '|';
			$getVarsCond = "\n  RewriteCond %{QUERY_STRING} !.*(" . rtrim($str, '|') . ")=.*";
		}

		if(strlen(trim($module->noCacheCookies))) {
			$cookies = '';
			foreach(explode("\n", $module->noCacheCookies) as $cookie) $cookies .= $this->sanitizer->name(trim($cookie)) . '|';
			$cookies = rtrim($cookies, '|');
			$cookiesCond = "\n  RewriteCond %{HTTP_COOKIE} !^.*($cookies).*$";
		} else {
			$cookiesCond = '';
		}

		$dir = $module->cacheDir;
		$cacheHosts = $module->cacheHosts;
		if(!count($cacheHosts)) $cacheHosts = array('');
		$out = '';

		foreach($cacheHosts as $host) {

			if(strlen($host)) {
				if(!in_array($host, $this->config->httpHosts) && $this->config->httpHost != $host) continue;
				$host = strtolower($host);
				$hostName = str_replace('.', '\.', $host);
				$hostCond = "\n  RewriteCond %{HTTP_HOST} ^$hostName [NC]";
			} else {
				$hostCond  = '';
			}

			foreach($extensions as $ext) {

				$o = $hostCond;
				$index = $module->getStatic()->cacheIndexBasename($host, false, $ext);

				$o .=
					"\n  RewriteCond %{REQUEST_METHOD} !=POST" . $getVarsCond . $cookiesCond .
					"\n  RewriteCond %{DOCUMENT_ROOT}{$this->config->urls->assets}$dir/" . '$1/' . $index . ' -f' .
					"\n  RewriteRule ^(.*) %{DOCUMENT_ROOT}{$this->config->urls->assets}$dir/" . '$1/' . $index . ' [L]';

				if($module->https) {
					$index2 = $module->getStatic()->cacheIndexBasename($host, true, $ext);
					if(empty($_SERVER['HTTPS']) && !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
						// AWS load balancer
						$o =
							"\n  RewriteCond %{HTTP:X-Forwarded-Proto} =http # AWS load balancer only" . $o .
							"\n  RewriteCond %{HTTP:X-Forwarded-Proto} =https # AWS load balancer only" .
							str_replace($index, $index2, $o);
					} else {
						$o =
							"\n  RewriteCond %{HTTPS} off" . $o .
							"\n  RewriteCond %{HTTPS} on" . str_replace($index, $index2, $o);
					}
				}

				if(strlen($host)) {
					if($ext != 'html') {
						$o = "\n  # $host (*.$ext)" . $o;
					} else {
						$o = "\n  # $host" . $o;
					}
				}

				$out .= $o;
			}
		}

		if($module->busterUrlType === "name") {
			$busterOut =
				"\n  # BUSTER" .
				"\n  RewriteCond %{REQUEST_FILENAME} !-f" .
				"\n  RewriteRule ^(.+)\.([a-zA-Z0-9]+)\.([^.]+)$ " . '$1.' . '$3' . " [L]";
			$out .= $busterOut;
		}

		$out =
			"\n  # PROCACHE v/VERSION/" . $out .
			"\n  # /PROCACHE";

		$docRootPath = $module->docRootPath;
		if(strlen($docRootPath)) {
			$find = '%{DOCUMENT_ROOT}';
			$replace = rtrim($docRootPath, "/\\");
			if($replace === '-') {
				// optional blank (likely not useful)
				$replace = '';
				$find .= $this->wire('config')->urls->root;
			}
			$out = str_replace($find, $replace, $out);
		}

		$version = md5($out);
		if($getVersion) return $version;
		$out = str_replace('/VERSION/', "$version", $out);

		return $out;
	}


}
	