<?php namespace ProcessWire;

/**
 * ProcessWire ProCache: Front-end to SCSSPHP SCSS compiler
 *
 * @method string|bool compile($file, array $options = array())
 *
 */

class ProCacheCompilerSCSS extends ProCacheCompiler {
	
	protected $formatter = '';

	public function setFormatter($formatter) {
		$this->formatter = $formatter;
	}

	/**
	 * Get new instance of LESS parser
	 *
	 * @return \ScssPhp\ScssPhp\Compiler
	 *
	 */
	protected function compiler() {
		
		if(!class_exists('\ScssPhp\ScssPhp\Compiler', false)) {
			require_once(dirname(__FILE__) . '/scss-leafo-1.1.1/scss.inc.php');
		}
		
		$scss = new \ScssPhp\ScssPhp\Compiler();
		
		return $scss;
	}

	/**
	 * @param bool $reset
	 * @return \ScssPhp\ScssPhp\Compiler|mixed
	 *
	 */
	public function getCompiler($reset = false) {
		/** @var \ScssPhp\ScssPhp\Compiler $compiler */
		$compiler = parent::getCompiler($reset);
		$importPaths = $this->getImportPaths();
		if(count($importPaths)) $compiler->setImportPaths($importPaths);
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
		if(!empty($options['formatter'])) $this->setFormatter($options['formatter']);
		if(!parent::___compile($file, $options)) return false;
		if(!$this->needsCompile($file)) return true;

		$compiler = $this->getCompiler();
		$targetFile = $this->getTargetFile();

		try {
			$css = $compiler->compile(file_get_contents($file));
			if($targetFile) $css = $this->filePutContents($targetFile, $css);
		} catch(\Exception $e) {
			$css = $this->compileError('SCSS', $file, $e->getMessage());
		}

		return $css;
	}

}