<?php namespace ProcessWire;

/**
 * ProcessWire Pro Cache: Tests
 *
 * Copyright (C) 2020 by Ryan Cramer
 *
 * This is a commercially licensed and supported module
 * DO NOT DISTRIBUTE
 *
 *
 */
class ProCacheTests extends ProCacheClass {
	
	/**
	 * Test URL for non-cached vs. cached performance
	 * 
	 * Returns verbose array of timer data
	 *
	 * @param string $url
	 * @param int $testNum Iteration number, if running multiple times
	 * @return array
	 *
	 */
	public function urlCacheTest($url, $testNum = 1) {

		$urlResult = array(
			'url' => '',
			'success' => false,
			'message' => '',
			'qty' => 0,
			'code' => 0,
			'head' => array(),
			'content' => '',
			'timer' => 0.0,
			'size' => 0,
			'type' => '',
		);

		$result = array(
			'success' => false,
			'message' => $this->_('Invalid URL'),
			'normal' => $urlResult,
			'primed' => $urlResult,
			'cached' => $urlResult,
			'timeSaved' => 0.0,
			'timeSavedPct' => 0,
			'when' => date('Y/m/d H:i:s'),
		);

		if($testNum < 1) $testNum = 1;
		if(strpos($url, '://') === false) return $result;
		list($scheme,) = explode('://', $url);
		$scheme = strtolower($scheme);
		if($scheme !== 'http' && $scheme !== 'https') return $result;
		if(strpos($url, '?') !== false) list($url,) = explode('?', $url); // strip query string
		if(strpos($url, '#') !== false) list($url,) = explode('#', $url); // strip fragment

		$url = $this->wire()->sanitizer->url($url, array(
			'allowRelative' => false,
			'allowIDN' => true,
			'allowQueryString' => false,
			'allowSchemes' => array('http', 'https'),
		));

		if(empty($url)) return $result;

		$tests = array(
			'normal' => "$url?nocache=1",
			'primed' => $url,
			'cached' => $url,
		);

		if($testNum > 1) unset($tests['primed']);

		foreach($tests as $name => $testUrl) {
			if($testNum === 1 && $url === $testUrl) sleep(1);
			$result[$name] = $this->urlTest($testUrl, true);
			$result['message'] = $result[$name]['message'];
			$result['success'] = $result[$name]['success'];
			if(!$result['success']) break;
		}

		if($result['success']) {
			$result['message'] = $this->_('Success');
			$result['timeSaved'] = $result['normal']['timer'] - $result['cached']['timer'];
			$result['timeSavedPct'] = ($result['timeSaved'] / $result['normal']['timer']) * 100;
			
		} else if(empty($result['message'])) {
			$result['message'] = $this->_('Unknown error') . ' ' . $result['code'];
		}
		
		$result['qty'] = $testNum;

		return $result;
	}

	/**
	 * Run tests for given quantity of times, compiling the average
	 * 
	 * This is the same as the urlCacheTimer method except that it runs multiple times
	 * andn compiles the average. 
	 *
	 * @param string $url
	 * @param int $qty
	 * @return array
	 *
	 */
	public function urlCacheTestQty($url, $qty) {

		if($qty < 2) return $this->urlCacheTest($url);

		$num = 1;
		$totalNormalTime = 0.0;
		$totalCachedTime = 0.0;
		$totalTimeSaved = 0.0;
		$totalTimeSavedPct = 0.0;
		$result = array(
			'normal' => array(),
			'cached' => array(),
		);

		for($n = 1; $n <= $qty; $n++) {
			$num = $n;
			$a = $this->urlCacheTest($url, $n);
			$a['normal'] = array_merge($result['normal'], $a['normal']);
			$a['cached'] = array_merge($result['cached'], $a['cached']);
			$a['primed'] = $n > 1 ? $result['primed'] : $a['primed'];
			$result = array_merge($result, $a);
			$result['iterations'] = $n;
			if(!$result['success']) break;
			$totalNormalTime += $result['normal']['timer'];
			$totalCachedTime += $result['cached']['timer'];
			$totalTimeSaved += $result['timeSaved'];
			$totalTimeSavedPct += $result['timeSavedPct'];
			$result['normal']["timer$n"] = $result['normal']['timer'];
			$result['cached']["timer$n"] = $result['cached']['timer'];
			$result["timeSaved$n"] = $result['timeSaved'];
		}

		if(!$result['success']) return $result;

		$avgNormalTime = $totalNormalTime / $qty;
		$avgCachedTime = $totalCachedTime / $qty;
		$avgTimeSavedPct = $totalTimeSavedPct / $qty;

		$result['normal']['timer'] = $avgNormalTime;
		$result['normal']['timerTotal'] = $totalNormalTime;
		$result['cached']['timer'] = $avgCachedTime;
		$result['cached']['timerTotal'] = $totalCachedTime;
		$result['timeSaved'] = $avgNormalTime - $avgCachedTime;
		$result['timeSavedTotal'] = $totalNormalTime - $totalCachedTime;
		$result['timeSavedPct'] = $avgTimeSavedPct;
		$result['message'] .= ' - ' . sprintf($this->_('%d iterations'), $num);

		ksort($result['normal']);
		ksort($result['cached']);
		ksort($result);

		return $result;
	}

	/**
	 * Test given URL and return verbose array 
	 * 
	 * @param string $url
	 * @param bool $head
	 * @return array
	 * 
	 */
	public function urlTest($url, $head = false) {

		static $useCurl = null;

		$result = array(
			'url' => $url,
			'success' => false,
			'message' => '',
			'code' => 0,
			'head' => array(),
			'content' => '',
			'timer' => 0.0,
			'size' => 0,
			'type' => '',
		);

		$http = new WireHttp();
		$response = false;
		$userAgent = $this->className();

		if($useCurl === null) $useCurl = function_exists("\\curl_init");

		// time non-cached
		if($useCurl) {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10.0);
			curl_setopt($curl, CURLOPT_TIMEOUT, 10.0);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
			curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, ($head ? CURLOPT_NOBODY : CURLOPT_HTTPGET), true); // HEAD
			$timer = Debug::timer();
			$response = curl_exec($curl);
			$result['timer'] = (float) Debug::timer($timer);
			$result['type'] = 'curl';
			$result['code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			$result['content'] = $response;
			if($response === false) {
				$result['message'] = curl_error($curl);
			} else {
				$result['success'] = true;
			}
			curl_close($curl);
		}

		if($result['success'] === false) {
			$useCurl = false;
			$http->setHeader('user-agent', $userAgent);
			$timer = Debug::timer();
			$response = $head ? $http->head($url) : $http->get($url);
			$result['timer'] = (float) Debug::timer($timer);
			$result['size'] = strlen($response);
			$result['code'] = $http->getHttpCode(false);
			$result['type'] = $http->getLastSendType();
			$result['head'] = $http->getResponseHeaders();
			$result['content'] = $response;
			if($response === false) {
				$result['success'] = false;
				$result['message'] = $http->getError();
			} else {
				$result['message'] = $http->getHttpCode(true);
			}
		}

		$result['size'] = is_string($response) ? strlen($response) : 0;

		return $result;
	}

	/**
	 * Return all response headers for given URL
	 * 
	 * @param string $url
	 * @param bool $requireCURL 
	 * @return array
	 * 
	 */
	public function urlHeaders($url, $requireCURL = false) {

		$hasCURL = function_exists('\curl_init');
		if($requireCURL && !$hasCURL) {
			return array('error' => $this->_('Error: This test requires CURL')); 
		}
		
		$options = array();
		if($hasCURL) $options['use'] = 'curl';
		
		$http = new WireHttp();
		$http->setHeader('user-agent', $this->className());
		$http->setHeader('Accept-Encoding', 'gzip, deflate, br, compress');
		$http->get($url, array(), $options); 
		$headers = $http->getResponseHeaderValues();
		
		foreach($headers as $key => $value) {
			$key = strtolower($key);
			if(is_array($value)) {
				if($key === 'set-cookie') {
					foreach($value as $k => $v) {
						list($v,) = explode('=', $v, 2);
						$value[$k] = $v;
					}
				}
				$value = implode(', ', $value);
				$headers[$key] = $value;
			}
		}
		
		return $headers;
	}

	/**
	 * Get value for specific header
	 * 
	 * @param string $url
	 * @param string $header
	 * @param bool $requireCURL
	 * @return string|bool Returns header value on success or boolean false if not present
	 * 
	 */
	public function urlHeader($url, $header, $requireCURL = false) {
		$headers = $this->urlHeaders($url, $requireCURL);
		if(count($headers) === 1 && isset($headers['error'])) return $headers['error'];
		$header = strtolower($header);
		return isset($headers[$header]) ? $headers[$header] : false;
	}

	/**
	 * Validate a testing URL
	 * 
	 * @param string $url
	 * @param array $options
	 * @return string
	 * 
	 */
	public function validateUrl($url, array $options = array()) {
		$options['allowRelative'] = false;
		$options['requireScheme'] = true;
		$options['allowSchemes'] = array('http', 'https'); 
		$url = trim((string)$url);
		if(empty($url)) return '';
		if(strpos($url, '://') === false) {
			$this->error($this->_('Please make sure your test URL starts with either http:// or https://'));
			$url = '';
		}
		$url = $this->wire()->sanitizer->url($url, $options);
		if(empty($url)) $this->error($this->_('Test URL did not validate, please try again.'));
		return $url;
	}
	
}
