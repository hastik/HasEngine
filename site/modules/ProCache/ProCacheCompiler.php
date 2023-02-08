<?php namespace ProcessWire;

/**
 * ProcessWire Pro Cache: CSS preprocessors
 *
 * Copyright (C) 2020 by Ryan Cramer
 *
 * This is a commercially licensed and supported module
 * DO NOT DISTRIBUTE
 * 
 * @method string|bool compile($file, array $options = array())
 *
 */

abstract class ProCacheCompiler extends ProCacheClass {

	/**
	 * Keys are disk paths, values are URLs
	 *
	 * @var array
	 *
	 */
	protected $importDirs = array();
	protected $compiler = null;
	protected $targetFile = '';
	protected $options = array();
	protected $force = false;
	
	public function setImportDirs(array $dirs, $reset = true) {
		if($reset) $this->importDirs = array();
		foreach($dirs as $path => $url) {
			if(is_int($path)) $path = $url;
			$this->addImportDir($path, $url);
		}
	}
	
	public function addImportDir($path, $url = '') {
		if(empty($url) || $url === $path) $url = $this->pathToUrl($path);
		$this->importDirs[$path] = $url;
	}
	
	public function getImportDirs() {
		return $this->importDirs;
	}
	
	public function getImportPaths() {
		return array_keys($this->importDirs); 
	}
	
	public function getImportUrls() {
		return array_values($this->importDirs); 
	}
	
	public function setTargetFile($file) {
		$this->targetFile = $file;
	}
	
	public function getTargetFile() {
		return $this->targetFile;
	}
	
	public function setForce($force) {
		$this->force = (bool) $force;
	}
	
	public function getForce() {
		return $this->force;
	}
	
	/**
	 * Get existing or new instance of compiler
	 *
	 * @param bool $reset Reset to new instance if there already is one? (default=false)
	 * @return mixed
	 *
	 */
	public function getCompiler($reset = false) {
		if($reset) $this->compiler = null;
		if($this->compiler) return $this->compiler;
		$this->compiler = $this->compiler();
		return $this->compiler;
	}

	/**
	 * Get new instance of compiler
	 * 
	 * @return object
	 * 
	 */
	abstract protected function compiler();

	/**
	 * Does given file need to be recompiled?
	 * 
	 * @param string $file
	 * @return bool
	 * 
	 */
	public function needsCompile($file) {
		$targetFile = $this->getTargetFile();
		if(!$targetFile) return true;
		if($this->getForce()) return true;
		if(!is_file($targetFile)) return true;
		if(filemtime($file) > filemtime($targetFile)) return true;
		return false;
	}

	/**
	 * @param string $file
	 * @param array $options
	 * @return bool|string
	 * 
	 */
	public function ___compile($file, array $options = array()) {
		$defaults = array(
			'importDirs' => array(),
			'importPaths' => array(), // alias of importDirs
			'targetFile' => '',
			'fileCSS' => '', // alias of targetFile
			'force' => false, 
		);
		$options = array_merge($defaults, $options);
		if(!is_file($file)) return false;
		if(!empty($options['fileCSS']) && empty($options['targetFile'])) {
			// compatibility with some versions that used fileCSS
			$options['targetFile'] = $options['fileCSS'];
			unset($options['fileCSS']);
		}
		if(!empty($options['targetFile'])) {
			$this->setTargetFile($options['targetFile']); 
		}
		if(isset($options['force'])) {
			$this->setForce($options['force']); 
		}
		if(!empty($options['importPaths']) && empty($options['importDirs'])) {
			// allowed alias
			$options['importDirs'] = $options['importPaths'];
		}
		if(!empty($options['importDirs'])) {
			if(is_array($options['importDirs'])) {
				$this->setImportDirs($options['importDirs'], false); 
			} else if(is_string($options['importDirs'])) {
				$this->addImportDir($options['importDirs']); 
			}
		}
		return true;
	}
	
	/**
	 * Log and return an SCSS/LESS error for storage in resulting CSS file
	 *
	 * @param string $source Specify either SCSS or LESS
	 * @param string $file
	 * @param string $error
	 * @return string
	 *
	 */
	protected function compileError($source, $file, $error) {
		$log = $this->wire('log');
		if($log) $log->save("procache-" . strtolower($source) . "-errors", $error);
		$error .= "\nCompiling file: $file";
		return "/*NoMinify*/\n\n" .
			"/*** ProCache Error ***************************************************************\n" .
			"$source compile error: $error\n" .
			"***********************************************************************************/\n\n";
	}
	
	/**
	 * Like PHP’s file_put_contents() but with custom return values for error handling
	 *
	 * Returns string with error message on fail, or integer with bytes written on success.
	 *
	 * @param string $file
	 * @param string $contents
	 * @return int|string
	 *
	 */
	protected function filePutContents($file, $contents) {
		if(!is_writable($file)) return "Path or file is not writable: $file";
		$bytes = @file_put_contents($file, $contents, LOCK_EX);
		if($bytes === false) return "Unable to write to: $file";
		return $bytes;
	}

	/**
	 * Convert disk path to URL
	 * 
	 * @param string $path
	 * @return string
	 * 
	 */
	protected function pathToUrl($path) {
		$config = $this->wire()->config;
		$url = '';
		if(DIRECTORY_SEPARATOR != '/') $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
		if(strpos($path, $config->paths->root) === 0) return str_replace($config->paths->root, '/', $path);
		foreach(array($config->urls->templates, $config->urls->site) as $loc) {
			if(strpos($path, $loc) === false) continue;
			$url = $config->urls->templates . $path;
			break;
		}
		return $url;
	}

	
}

