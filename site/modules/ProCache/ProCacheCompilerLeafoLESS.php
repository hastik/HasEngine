<?php namespace ProcessWire;

/**
 * ProcessWire ProCache: Front-end to Leafo LESS compiler
 *
 * @method string|bool compile($file, array $options = array())
 *
 */

class ProCacheCompilerLeafoLESS extends ProCacheCompiler {
	
	protected $formatter = null;
	
	public function setFormatter($formatter) {
		$this->formatter = $formatter;
	}

	/**
	 * Get new instance of LESS parser
	 *
	 * @return \lessc
	 *
	 */
	protected function compiler() {
		if(!class_exists("\\lessc", false)) {
			require_once(dirname(__FILE__) . '/less-leafo-0.5.0/lessify.inc.php');
		}
		$less = new \lessc;
		return $less;
	}

	/**
	 * @param bool $reset
	 * @return \lessc|mixed
	 * 
	 */
	public function getCompiler($reset = false) {
		/** @var \lessc $compiler */
		$compiler = parent::getCompiler($reset);
		$importPaths = $this->getImportPaths();
		if(count($importPaths)) $compiler->setImportDir($importPaths);
		if($this->formatter) $compiler->setFormatter($this->formatter);
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
		
		$this->addImportDir(dirname($file) . '/');

		if(!empty($options['formatter'])) $this->formatter = $options['formatter'];
		if(!parent::___compile($file, $options)) return false;
		if(!$this->needsCompile($file)) return true;
		
		$compiler = $this->getCompiler();
		$targetFile = $this->getTargetFile();
		
		try {
			$css = $compiler->compileFile($file);
			if($targetFile) $css = $this->filePutContents($targetFile, $css);
		} catch(\Exception $e) {
			$css = $this->compileError('LESS', $file, $e->getMessage());
		}

		return $css;
	}

}