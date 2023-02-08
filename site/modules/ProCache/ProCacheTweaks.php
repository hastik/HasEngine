<?php namespace ProcessWire;

/**
 * ProcessWire Pro Cache: Output Tweaks
 *
 * Copyright (C) 2020 by Ryan Cramer
 *
 * This is a commercially licensed and supported module
 * DO NOT DISTRIBUTE
 *
 */ 

class ProCacheTweaks extends ProCacheClass {
	
	/**
	 * Render output tweaks before other operations that might manipulate markup
	 *
	 * @param Page $page
	 * @param $html
	 * @return int Number of tweaks applied
	 *
	 */
	public function renderOutputTweaksEarly(Page $page, &$html) {
		
		if($page) {} // ignore
		$numTweaksApplied = 0;
		
		$useCanonical = $this->procache->canonical && $this->procache->canonical != "0" && stripos($html, '<head') !== false;
		
		if($useCanonical) {
			// make sure there isn't already a canonical tag present
			if(stripos($html, 'canonical') !== false) {
				// if the word 'canonical' appeared somewhere, check that it's not a link canonical tag
				if(preg_match('/\srel\s*=\s*["\']?canonical/i', $html)) $useCanonical = false;
			}
			if($useCanonical) {
				switch($this->procache->canonical) {
					case "1": $scheme = $this->wire('config')->https ? 'https://' : 'http://'; break;
					case "http": $scheme = "http://"; break;
					case "https": $scheme = "https://"; break;
					default: $scheme = "";
				}
				if($scheme) {
					$url = $this->wire('input')->httpUrl();
					$url = str_replace(array('http://', 'https://'), $scheme, $url);
					$tag = "<link rel=\"canonical\" href=\"$url\" />";
					if(strpos($html, '<head>') !== false) {
						$html = str_ireplace("<head>", "<head>\n\t$tag", $html);
					} else {
						$html = preg_replace('/(<head\s+[^>]*>)/i', '$1' . "\n\t$tag", $html);
					}
					$numTweaksApplied++;
				}
			}
		}
		
		return $numTweaksApplied;
	}

	/**
	 * Insert a ProCache class to the <body> tag, when present, as well as append debug info
	 *
	 * @param Page $page
	 * @param string $html
	 * @return int Number of tweaks applied
	 *
	 */
	public function renderOutputTweaks(Page $page, &$html) {

		if($page) {}
		$numTweaksApplied = 0;
		$info = ProCache::getModuleInfo();
		$version = $info['version'];
		$debug = $this->procache->debugMode();
		
		if($debug) {
			$timerStr =
				"         ProCache: v$version\n" .
				"      Render date: " . date('Y-m-d H:i:s') . "\n" .
				" Page render time: " . $this->procache->get('pageRenderTime') . "s\n" .
				" Total w/ProCache: " . Debug::timer($this->procache->timer) . "s";
		} else {
			$timerStr = '';
		}

		if($this->procache->debug) {
			$debugStr = "<p id='ProCacheDebug'>$timerStr</p>";
			if(stripos($html, '</body>')) {
				$html = str_ireplace("</body>", "$debugStr</body>", $html);
			} else {
				// no closing body tag
				$html .= $debugStr;
			}
			$numTweaksApplied++;
		}

		if($debug) {
			$this->procache->debugInfo($timerStr, true); 
			$hr = "=======================================";
			$debugInfo = "\nProcessWire Debug Mode: ProCache Info\n$hr\n" . implode("\n$hr\n", $this->procache->debugInfo());
			$debugStr = "<!--\n$debugInfo\n$hr\n-->";
			if(stripos($html, '</html>')) {
				$html = str_ireplace("</html>", "</html>$debugStr", $html);
			} else {
				$html .= $debugStr;
			}
			$numTweaksApplied++;
		}

		return $numTweaksApplied;
	}

	/**
	 * Output tweaks that apply only to the cached file (i.e. not visible except when cache in use)
	 *
	 * @param $html
	 * @return int Number of tweaks applied
	 *
	 */
	public function renderOutputTweaksCacheOnly(&$html) {
		$numTweaksApplied = 0;
		$bodyClass = $this->sanitizer->name($this->procache->bodyClass);

		if($bodyClass) {
			foreach(array('body', 'html') as $tag) {

				$hasTag1 = strpos($html, "<$tag>") !== false;
				$hasTag2 = $hasTag1 === false ? strpos($html, "<$tag ") !== false : false;

				if($hasTag1) {
					// has tag with no attributes present
					$html = str_replace("<$tag>", "<$tag class=$bodyClass>", $html);
					$numTweaksApplied++;
					break;

				} else if($hasTag2 && preg_match('/<' . $tag . '\s[^>]*>/', $html, $matches)) {
					// has tag and attributes are present
					$body = $matches[0];
					if(strpos($body, 'class=')) {
						// class attr already present
						if(strpos($body, 'class="') || strpos($body, "class='")) {
							// quoted class attribute, add new body class to quoted value
							$body = preg_replace('/( class=[\'"])/', '$1' . "$bodyClass ", $body);
						} else {
							// unquoted class attribute, merge existing class and add quotes
							$body = preg_replace('/ class=([^\s>]+)/', ' class="$1 ' . $bodyClass . '"', $body);
						}
					} else {
						// no class attr present, so add one
						$body = rtrim($body, '>') . " class=$bodyClass>";
					}
					$html = str_replace($matches[0], $body, $html);
					$numTweaksApplied++;
					break;
				}
			}
		}

		return $numTweaksApplied;
	}


}