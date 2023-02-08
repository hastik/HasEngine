<?php namespace ProcessWire;

/**
 * ProcessWire ProCache: Front-end to Wikimedia LESS compiler
 *
 * @method string|bool compile($file, array $options = array())
 *
 */

class ProCacheCompilerWikimediaLESS extends ProCacheCompiler {
	
	protected $compress = false;
	protected $baseUrl = '';

	/**
	 * Get new instance of LESS parser
	 * 
	 * @return \Less_Parser
	 *
	 */
	protected function compiler() {
		if(!class_exists("\\Less_Parser")) {
			require_once(dirname(__FILE__) . '/less-wikimedia-3.0.0/lib/Less/Autoloader.php');
		}
		\Less_Autoloader::register();
		$less = new \Less_Parser(array('compress' => $this->compress));
		return $less;
	}
	
	public function setCompress($compress) {
		$this->compress = $compress ? true : false;
	}
	
	public function setBaseUrl($url) {
		$this->baseUrl = $url;
	}
	
	public function getCompiler($reset = false) {
		/** @var \Less_Parser $compiler */
		$compiler = parent::getCompiler($reset);
		$dirs = $this->getImportDirs();
		if(count($dirs)) $compiler->SetImportDirs($dirs);
		return $compiler;
	}

	/**
	 * Compile LESS file
	 * 
	 * @param string $file
	 * @param array $options
	 * @return bool|string
	 * 
	 */
	public function ___compile($file, array $options = array()) {
		
		if(!parent::___compile($file, $options)) return false;
		if(!$this->needsCompile($file)) return true;
		
		$targetFile = $this->getTargetFile();
		$compiler = $this->getCompiler(); /** @var \Less_Parser $compiler */
		
		try {
			if($this->baseUrl) {
				$compiler->parseFile($file, $this->baseUrl);
			} else {
				$compiler->parseFile($file);
			}
			$css = $compiler->getCss();
			if($targetFile) $css = $this->filePutContents($this->targetFile, $css);
		} catch(\Exception $e) {
			$css = $this->compileError('LESS', $file, $e->getMessage());
		}
		
		if($compiler) $compiler->Reset(); // for next call
		
		return $css;
	}
	
}