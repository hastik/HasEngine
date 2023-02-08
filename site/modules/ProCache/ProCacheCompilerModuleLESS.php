<?php namespace ProcessWire;

/**
 * ProcessWire ProCache: Front-end to ProcessWire Less module (also Wikimedia based)
 *
 */

require_once(__DIR__ . '/ProCacheCompilerWikimediaLESS.php');

class ProCacheCompilerModuleLESS extends ProCacheCompilerWikimediaLESS {

	/**
	 * Get new instance of LESS parser
	 *
	 * @return \Less_Parser
	 *
	 */
	protected function compiler() {
		$modules = $this->wire()->modules;
		if($modules->isInstalled('Less')) {
			// use version installed with Less module when present
			$less = $modules->get('Less');
			$parser = $less->parser(array('compress' => $this->compress));
		} else {
			$parser = parent::compiler();
		}
		return $parser;
	}
}