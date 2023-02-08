<?php namespace ProcessWire;

/**
 * ProcessWire Pro Cache: File tools
 *
 * Copyright (C) 2020 by Ryan Cramer
 *
 * This is a commercially licensed and supported module
 * DO NOT DISTRIBUTE
 *
 */ 

class ProCacheFiles extends ProCacheClass {
	
	/**
	 * Create a directory
	 *
	 * @param string $path
	 * @param bool $recursive
	 * @return bool
	 *
	 */
	public function mkdir($path, $recursive = false) {
		$files = $this->wire('files'); /** WireFileTools */
		return $files->mkdir($path, $recursive);
	}

	/**
	 * Remove a directory, optionally recursively
	 *
	 * @param string $path
	 * @param bool $recursive
	 * @return bool
	 *
	 */
	public function rmdir($path, $recursive) {
		$files = $this->wire('files'); /** WireFileTools */
		return $files->rmdir($path, $recursive);
	}

	/**
	 * 2-step rmdir: removes a path by first renaming it and then removing it
	 *
	 * Unlike wireRmdir (function), this one renames the directory first so that they can't be
	 * displayed by Apache or written by PW while in the process of being removed.
	 *
	 * @param string $path
	 * @param bool $recursive
	 * @return bool
	 *
	 */
	public function rmdir2($path, $recursive) {
		if(!is_dir($path)) return false;
		$n = 1;
		$tmpPath = $this->wire('config')->paths->assets . 'ProCache-tmp';
		while(is_dir($tmpPath . $n)) $n++;
		$tmpPath .= $n . '/';
		if(!$this->rename($path, $tmpPath)) return false;
		return $this->rmdir($tmpPath, $recursive);
	}

	/**
	 * Rename file
	 *
	 * @param string $oldName
	 * @param string $newName
	 * @return bool
	 *
	 */
	public function rename($oldName, $newName) {
		$files = $this->wire('files'); /** WireFileTools */
		return $files->rename($oldName, $newName);
	}

	/**
	 * Remove/unlink a file
	 *
	 * @param string $filename
	 * @return bool
	 *
	 */
	public function unlink($filename) {
		return $this->wire()->files->unlink($filename);
	}

	/**
	 * Write contents to file
	 *
	 * @param string $filename
	 * @param string $contents
	 * @return bool
	 *
	 */
	public function filePutContents($filename, $contents) {
		$flags = LOCK_EX;
		$files = $this->wire('files'); /** WireFileTools */
		return $files->filePutContents($filename, $contents, $flags);
	}

	/**
	 * Cleanup any ProCache-tmp dirs that remain in /site/assets/
	 *
	 * This is here to be called from a CRON job, on really large installations where the server
	 * doesn't allow enough time/resources to complete full cache clears in a single http request.
	 *
	 * @return int Number of directories removed
	 *
	 */
	public function tmpDirCleanup() {

		$n = 0;
		$assetsPath = $tmpPath = $this->wire('config')->paths->assets;

		foreach(new \DirectoryIterator($assetsPath) as $file) {
			if($file->isDot() || !$file->isDir()) continue;

			// skip anything that does not start with 'ProCache-tmp'
			if(strpos($file->getBasename(), 'ProCache-tmp') !== 0) continue;

			// if modified within the last hour leave it alone
			if($file->getMTime() > (time() - 3600)) continue;

			$tmpPath = $file->getPathname();
			if(!strpos($tmpPath, 'ProCache-tmp')) continue; // not necessary but just being overly careful
			$this->rmdir($tmpPath, true);
			$n++;
		}

		return $n - 1;
	}

	
}