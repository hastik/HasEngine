<?php namespace ProcessWire;

/**
 * ProcessWire Pro Cache: Minify
 *
 * Copyright (C) 2020 by Ryan Cramer
 *
 * This is a commercially licensed and supported module
 * DO NOT DISTRIBUTE
 *
 */

class ProCacheMinify extends ProCacheClass {
	
	/**
	 * Merge and minify CSS and JS file references in output
	 *
	 * @param page $page
	 * @param string $out
	 * @return bool
	 *
	 */
	public function renderMerge(Page $page, &$out) {
		
		if($page) {}
		$n = 0;
		$debug = $this->procache->debugMode();
		$debugInfo = array();
		$_timer = $debug ? Debug::timer('ProCacheMerge') : null;
		$procache = $this->procache;

		if(in_array('cssHead', $procache->minifyOptions)) {
			$timer = $debug ? Debug::timer('ProCacheMergeCSSHead') : null;
			$merger = $procache->getFileMerger();
			$merger->setMaxImportSizeCSS($procache->minifyMaxImportCSS);
			$out = $merger->mergeCSSFilesInMarkup($out, true);
			if($timer) $debugInfo[] = '    Merge/min CSS: ' . Debug::timer($timer) . "s";
			$n++;
		}

		if(in_array('jsHead', $procache->minifyOptions)) {
			$timer = $debug ? Debug::timer('ProCacheMergeJSHead') : null;
			$merger = $procache->getFileMerger();
			$out = $merger->mergeJSFilesInMarkup($out, true, true);
			if($timer) $debugInfo[] = 'Merge/min JS head: ' . Debug::timer($timer) . "s";
			$n++;
		}

		if(in_array('jsBody', $procache->minifyOptions)) {
			$timer = $debug ? Debug::timer('ProCacheMergeJSBody') : null;
			$merger = $procache->getFileMerger();
			$out = $merger->mergeJSFilesInMarkup($out, false, true);
			if($timer) $debugInfo[] = 'Merge/min JS body: ' . Debug::timer($timer) . "s";
			$n++;
		}

		if(count($debugInfo)) {
			$debugInfo[] = '  Merge/min total: ' . Debug::timer($_timer) . "s";
			$procache->debugInfo(implode("\n", $debugInfo));
		}

		return $n > 0;
	}

	/**
	 * Minify HTML of output
	 *
	 * @param Page $page
	 * @param string $out
	 * @param bool $allowCache Whether or not content will be cached by ProCache
	 * @return bool
	 *
	 */
	public function renderMinify(Page $page, &$out, $allowCache) {

		$procache = $this->procache;
		$minifyOptions = $procache->minifyOptions;

		if($page) {}
		if(!$allowCache && in_array('htmlCache', $minifyOptions)) return false;
		
		if($this->wire('user')->isLoggedin()) {
			if(!in_array('htmlUsers', $minifyOptions)) return false;
		} else {
			if(!in_array('htmlGuest', $minifyOptions) && !in_array('htmlCache', $minifyOptions)) return false;
		}

		$timer = $procache->debugMode() ? Debug::timer('ProCacheMinifyHTML') : null;
		$originalSize = $timer ? strlen($out) : 0;

		$merger = null;
		$minifyHTMLOptions = $procache->minifyHTMLOptions;
		
		if(in_array('js', $minifyHTMLOptions)) {
			$merger = $procache->getFileMerger();
			$merger->minifyInlineJS($out);
		}
		if(in_array('css', $minifyHTMLOptions) && stripos($out, '<style') !== false) {
			if(is_null($merger)) $merger = $procache->getFileMerger();
			$merger->minifyInlineCSS($out);
		}

		require_once(dirname(__FILE__) . '/ProCacheMinifyHTML.php');
		$minifier = new ProCacheMinifyHTML();

		$minifier->setOption('removeComments', in_array('uncomment', $minifyHTMLOptions));
		$minifier->setOption('convertXHTML', in_array('noXHTML', $minifyHTMLOptions));
		$minifier->setOption('unquoteAttr', in_array('unquote', $minifyHTMLOptions));
		$minifier->setOption('removeBlankAttr', in_array('unblank', $minifyHTMLOptions));
		$minifier->setOption('removeUnnecessaryAttr', in_array('unattr', $minifyHTMLOptions));
		$minifier->setOption('removeUnnecessaryTags', in_array('untag', $minifyHTMLOptions));
		$minifier->setOption('collapseBooleanAttr', in_array('unbool', $minifyHTMLOptions));

		$minifier->setBlocks($procache->minifyBlocks);
		$minifier->setIgnoreTags($procache->minifyIgnoreTags);
		$minifier->setRemoveBlankAttrs($procache->minifyRemoveBlankAttr);

		/* FOR EXAMPLE PURPOSES ONLY
		$minifier->setJSMinifyFunction(function($js) {
			$jsmin = new MatthiasMullie\Minify\JS();
			$jsmin->add($js);
			return $jsmin->minify();	
		});
		$minifier->setCSSMinifyFunction(function($css) {
			$cssmin = new MatthiasMullie\Minify\CSS();
			$cssmin->add($css);	
			$cssmin->setMaxImportSize(0);
			return $cssmin->minify();
		});
		*/

		$size1 = 0;
		$size2 = 0;
		$size3 = 0;

		if($timer) $size1 = strlen($out);
		$out = $minifier->minify($out);
		if($timer) $size2 = strlen($out);
		if(in_array('hrefs', $minifyHTMLOptions)) {
			$minifier->convertURLs($out, $this->wire('config')->urls->root, $this->wire('input')->url());
		}
		if($timer) $size3 = strlen($out);

		if($timer) {
			$timer = Debug::timer($timer);
			$procache->debugInfo(
				"      Minify HTML: " . $timer . 's' . "\n" .
				"    Original size: " . $originalSize . "\n" .
				"      Minify size: " . strlen($out) . "\n" .
				"     Minify saved: " . ($size1-$size2) . "\n" .
				"ConvertURLs saved: " . ($size2-$size3) . "\n" .
				"    Total savings: " . ($size1-$size3) . ' (' . round(100 - (100 * ($size3 / $originalSize)), 1) . '%) '
			);
		}

		return true;
	}
	
	/**
	 * Get HTML minifier instance
	 *
	 * @param array $options
	 * @return ProCacheMinifyHTML
	 *
	 */
	public function getHtmlMinifier(array $options = array()) {
		$procache = $this->procache;
		if(count($options) < count($procache->minifyHTMLOptions)) {
			$options = array_merge($procache->minifyHTMLOptions, $options);
		}
		require_once(dirname(__FILE__) . '/ProCacheMinifyHTML.php');
		$minifier = new ProCacheMinifyHTML();
		$minifier->setOption('removeComments', in_array('uncomment', $options));
		$minifier->setOption('convertXHTML', in_array('noXHTML', $options));
		$minifier->setOption('unquoteAttr', in_array('unquote', $options));
		$minifier->setOption('removeBlankAttr', in_array('unblank', $options));
		$minifier->setOption('removeUnnecessaryAttr', in_array('unattr', $options));
		$minifier->setOption('removeUnnecessaryTags', in_array('untag', $options));
		$minifier->setOption('collapseBooleanAttr', in_array('unbool', $options));
		$minifier->setBlocks($procache->minifyBlocks);
		$minifier->setIgnoreTags($procache->minifyIgnoreTags);
		$minifier->setRemoveBlankAttrs($procache->minifyRemoveBlankAttr);
		return $minifier;
	}
	
	/**
	 * Minify HTML of output
	 *
	 * @param string $out
	 * @param array $options
	 * @param bool $debug
	 * @return bool
	 * @todo make the renderMinify() use this method instead, since this is basically a copy of it
	 *
	 */
	public function minifyHtml(&$out, array $options = array(), $debug = false) {

		$procache = $this->procache;
		$options = array_merge($procache->minifyHTMLOptions, $options);
		$timer = $debug ? Debug::timer('ProCacheMinifyHTML') : null;
		$originalSize = strlen($out);

		$merger = null;
		if(in_array('js', $options)) {
			$merger = $procache->getFileMerger();
			$merger->minifyInlineJS($out);
		}

		if(in_array('css', $options) && stripos($out, '<style') !== false) {
			if(is_null($merger)) $merger = $procache->getFileMerger();
			$merger->minifyInlineCSS($out);
		}

		$minifier = $this->getHtmlMinifier($options);

		/* FOR EXAMPLE PURPOSES ONLY
		$minifier->setJSMinifyFunction(function($js) {
			$jsmin = new MatthiasMullie\Minify\JS();
			$jsmin->add($js);
			return $jsmin->minify();	
		});
		$minifier->setCSSMinifyFunction(function($css) {
			$cssmin = new MatthiasMullie\Minify\CSS();
			$cssmin->add($css);	
			$cssmin->setMaxImportSize(0);
			return $cssmin->minify();
		});
		*/

		$out = $minifier->minify($out);

		if(in_array('hrefs', $options)) {
			$minifier->convertURLs($out, $this->wire('config')->urls->root, $this->wire('input')->url());
		}

		$minifiedSize = strlen($out);
		$totalBytesSaved = ($originalSize-$minifiedSize);
		$totalPercentSaved = round(100 - (100 * ($minifiedSize / $originalSize)), 1);

		if($timer) {
			$timer = Debug::timer($timer);
			$procache->debugInfo(
				" Minify HTML time: " . $timer . 's' . "\n" .
				"    Original size: " . $originalSize . "\n" .
				"      Minify size: " . $minifiedSize . "\n" .
				"     Minify saved: " . $totalBytesSaved . "\n" .
				"    Total savings: " . $totalPercentSaved . '% '
			);
		}

		return $totalPercentSaved;
	}



}