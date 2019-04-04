<?php

require_once dirname(__FILE__) . "/serverUtil.php";

function timeStamp() {
	$php_timestamp = time();
	$stamp = date(" h:i:s A - m/d/Y", $php_timestamp);
	return $stamp;
}

function dumpHeaders() {

	if (count($_GET)) {
		write_log("GET Array: " . json_encode($_GET), "INFO");
		$headers = [];
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$headers = apache_request_headers();
		foreach ($_SERVER as $key => $value) {
			if (strpos($key, 'HTTP_') === 0) {
				$chunks = explode('_', $key);
				$header = '';
				for ($i = 1; $y = sizeof($chunks) - 1, $i < $y; $i++) {
					$header .= ucfirst(strtolower($chunks[$i])) . '-';
				}
				$header .= ucfirst(strtolower($chunks[$i]));
				$headers["$header"] = $value;
			}
		}
		if (isset($headers['Referrer'])) {
			if (preg_match("/phlexchat.com/", $headers['Referrer'])) {
				write_log("Forcing server address for webapp.", "INFO");
				$_GET['serverAddress'] = "https://app.phlexchat.com";
			}
		}
	}
	$headers['uri'] = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];;
	write_log("Headers: " . json_encode(array_unique($headers, SORT_REGULAR)));
}

if (!function_exists('apache_request_headers')) {
	function apache_request_headers() {
		$arh = [];
		$rx_http = '/\AHTTP_/';
		foreach ($_SERVER as $key => $val) {
			if (preg_match($rx_http, $key)) {
				$arh_key = preg_replace($rx_http, '', $key);
				$rx_matches = explode('_', $arh_key);
				if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
					foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
					$arh_key = implode('-', $rx_matches);
				}
				$arh[$arh_key] = $val;
			}
		}
		return ($arh);
	}
}

// Recursively filter empty keys from an array
// Returns filtered array.
function array_filter_recursive($array, callable $callback = null) {
	if (is_string($array)) $array = json_decode($array, true);
	if ($array === null) return "";
	$array = is_callable($callback) ? array_filter($array, $callback) : array_filter($array);
	if (is_array($array)) {
		foreach ($array as &$value) {
			if (is_array($value)) {
				$value = call_user_func(__FUNCTION__, $value, $callback);
			}
		}
	}
	return $array;
}

//Get the current protocol of the server
function serverProtocol() {
	return (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://');
}

// Fetch data from a URL using CURL`
function curlGet($url, $headers = null, $timeout = 10) {
	write_log("Curling URL: $url");
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	if ($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	$result = curl_exec($ch);
	write_log("Curl Result: " . json_encode($result));
	if (!curl_errno($ch)) {
		switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
			case 200:
				break;
			default:
				write_log('Unexpected HTTP code: ' . $http_code . ', URL: ' . $url, "ERROR");
				$result = false;
		}
	}
	curl_close($ch);

	return $result;
}


function curlPost($url, $content = false, $JSON = false, Array $headers = null) {
	//$mc = JMathai\PhpMultiCurl\MultiCurl::getInstance();
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 4);
	curl_setopt($curl, CURLOPT_TIMEOUT, 3);

	if ($headers) {
		if ($JSON) {
			$headers = array_merge($headers, array("Content-type: application/json"));
		}
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	} else {
		if ($JSON) {
			$headers = array("Content-type: application/json");
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		}
	}
	if ($content) {
		write_log("Should have content: " . $content);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
	}
	$response = curl_exec($curl);
	curl_close($curl);
	return $response;
}


if (!function_exists('alexa_log')) {
	function alexa_log($text, $level = false, $filename = false, $caller = false) {
		$log = $filename ? $filename : "alexaLog.log";
		$pp = false;
		if (!file_exists($log)) {
			touch($log);
			chmod($log, 0666);
			$authString = "; <?php die('Access denied'); ?>" . PHP_EOL;
			file_put_contents($log, $authString);
		}
		if (filesize($log) > 1048576) {
			$oldLog = "alexaLog.old.log";
			if (file_exists($oldLog)) unlink($oldLog);
			rename($log, $oldLog);
			touch($log);
			chmod($log, 0666);
			$authString = "; <?php die('Access denied'); ?>" . PHP_EOL;
			file_put_contents($log, $authString);
		}

		$date = date(DATE_RFC2822);
		$level = $level ? $level : "DEBUG";
		$user = $_SESSION['plexUserName'] ?? $_SESSION['alexaEmail'] ?? false;
		$user = $user ? "[$user] " : "";
		$caller = $caller ? getCaller($caller) : getCaller();
		$text = trim($text);

		if (($text === "") || !file_exists($log)) return;

		$line = "[$date] [$level] " . $user . "[$caller] - $text" . PHP_EOL;

		if ($pp) $_SESSION['pollPlayer'] = true;
		if (!is_writable($log)) return;
		if (!$handle = fopen($log, 'a+')) return;
		if (fwrite($handle, $line) === FALSE) return;

		fclose($handle);
	}
}

// Get the name of the function calling write_log
if (!function_exists('getCaller')) {
	function getCaller($custom = "foo") {
		$trace = debug_backtrace();
		$useNext = false;
		$caller = false;
		//write_log("TRACE: ".print_r($trace,true),null,true);
		foreach ($trace as $event) {
			if ($useNext) {
				if (($event['function'] != 'require') && ($event['function'] != 'include')) {
					$caller .= "::" . $event['function'];
					break;
				}
			}
			if (($event['function'] == 'write_log') || ($event['function'] == 'alexa_log') || ($event['function'] == 'doRequest') || ($event['function'] == $custom)) {
				$useNext = true;
				// Set our caller as the calling file until we get a function
				$file = pathinfo($event['file']);
				$caller = $file['filename'] . "." . $file['extension'];
			}
		}
		return $caller;
	}
}

function build_url(array $parts) {
	return (isset($parts['scheme']) ? "{$parts['scheme']}://" : '') .
		(isset($parts['user']) ? "{$parts['user']}" : '') .
		(isset($parts['pass']) ? ":{$parts['pass']}" : '') .
		(isset($parts['user']) ? '@' : '') .
		(isset($parts['host']) ? "{$parts['host']}" : '') .
		(isset($parts['port']) ? ":{$parts['port']}" : '') .
		(isset($parts['path']) ? "{$parts['path']}" : '') .
		(isset($parts['query']) ? "?{$parts['query']}" : '') .
		(isset($parts['fragment']) ? "#{$parts['fragment']}" : '');
}

function bye($echo = false, $msg = false) {
	if ($echo) echo $echo;
	if ($msg) write_log($msg, "ALERT");
	write_log("-------END REQUEST (V2)-------", "ALERT");
	die();
}

function respondOK($text = null) {
	// check if fastcgi_finish_request is callable
	if (is_callable('fastcgi_finish_request')) {
		if ($text !== null) {
			echo $text;
		}
		/*
		 * http://stackoverflow.com/a/38918192
		 * This works in Nginx but the next approach not
		 */
		session_write_close();
		fastcgi_finish_request();

		return;
	}

	ignore_user_abort(true);

	ob_start();

	if ($text !== null) {
		echo $text;
	}

	$serverProtocol = filter_input(INPUT_SERVER, 'SERVER_PROTOCOL', FILTER_SANITIZE_STRING);
	header($serverProtocol . ' 200 OK');
	// Disable compression (in case content length is compressed).
	header('Content-Encoding: none');
	header('Content-Length: ' . ob_get_length());

	// Close the connection.
	header('Connection: close');

	ob_end_flush();
	ob_flush();
	flush();
}

function testClient($serverAddress, $apiToken) {

	if (substr($serverAddress, -1) == '/') {
		$serverAddress = substr($serverAddress, 0, -1);
	}
	write_log("Incoming server address: " . $serverAddress);
	$serverBlock = parse_url($serverAddress);
	$host = $serverBlock['host'];

	$isIp = filter_var($host, FILTER_VALIDATE_IP);

	if ($isIp) {
		$filtered = filter_var($host, FILTER_VALIDATE_IP,
			FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_IPV6
		);

	} else {
		$filtered = !preg_match("/localhost/", $host);
	}
	$good = ($filtered);
	if (!$good) {
		write_log("Dumbass alart.", "ERROR");
		return [false, "$host is not a publicly accessible address."];
	}
	if (isset($serverBlock['path'])) {
		$blockPath = $serverBlock['path'];
		if (preg_match("/htm/", $blockPath) || preg_match("/html/", $blockPath) || preg_match("/php/", $blockPath)) {
			$path = dirname($blockPath);
			write_log("Path is $path");
			$serverBlock['path'] = rtrim($path, '/\\');

		}
		write_log("Path is " . $serverBlock['path']);
	}

	$curlResult = false;
	$curlError = false;
	write_log("Current server block: " . json_encode($serverBlock));
	if (isset($serverBlock['scheme'])) {
		$testBlock = $serverBlock;
		$path = $testBlock['path'] ?? "";
		$testBlock['path'] = $path . '/api.php';
		$testBlock['query'] = 'testclient=true&apiToken=' . $apiToken;
		$serverAddress1 = build_url($serverBlock);
		$url = build_url($testBlock);

		try {
			write_log("Testing client connection url: " . $url);
			$curlResult = curlGet($url, false, 30);
		} catch (Exception $e) {
			write_log("CURL EXCEPTION: $e", "WARN");
			$curlError = $e;
		}
	} else {
		$serverBlock['scheme'] = "https";
		$testBlock = $serverBlock;
		$path = $testBlock['path'] ?? "";
		if (substr($path, -1) == '/') {
			$path = substr($path, 0, -1);
		}
		$testBlock['path'] = $path . '/api.php';
		$testBlock['query'] = 'testclient=true&apiToken=' . $apiToken;
		$serverAddress1 = build_url($serverBlock);
		$url = build_url($testBlock);

		try {
			write_log("Testing client connection url: " . $url);
			$curlResult = curlGet($url, false, 30);
		} catch (Exception $e) {
			write_log("CURL EXCEPTION: $e", "WARN");
			$curlError = $e;
		}
		if (!$curlResult) {
			$curlError = false;
			write_log("Testing client with https failed, attempting to use http.");
			$testBlock['scheme'] = 'http';
			$serverBlock['scheme'] = 'http';
			$url = build_url($testBlock);
			write_log("Testing client connection url: " . $url);
			try {
				write_log("Testing client connection url: " . $url);
				$curlResult = curlGet($url, false, 30);
			} catch (Exception $e) {
				write_log("CURL EXCEPTION: $e", "WARN");
				$curlError = $e;
			}
		}
	}
	if (strtolower($curlResult) == "success") {
		$result = [true, build_url($serverBlock)];
	} else {
		$serverAddress2 = build_url($serverBlock);
		$msg = "Unable to connect to Client at " .
			"<a href='$serverAddress1'>$serverAddress1</a> or <a href='$serverAddress2'>$serverAddress2.</a><BR>";
		$msg .= ($curlError ? $curlError : " Please check the setting for 'Public Address' in the Phlex UI.");
		$result = [false, $msg];
	}
	return $result;
}

function echoCurl($method = false, $body = false, $headers = false, $uri = false) {
	if (!$method) $method = $_SERVER['REQUEST_METHOD'];
	if (!$uri) {
		$actual_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	} else {
		$actual_link = $uri;
	}
	if ($method == 'POST') {
		$body = $body ? $body : file_get_contents('php://input');
	}
	if (!$headers) {
		$headers = [];
		foreach ($_SERVER as $key => $value) {
			if (strpos($key, 'HTTP_') === 0) {
				$chunks = explode('_', $key);
				$header = '';
				for ($i = 1; $y = sizeof($chunks) - 1, $i < $y; $i++) {
					$header .= ucfirst(strtolower($chunks[$i])) . '-';
				}
				$header .= ucfirst(strtolower($chunks[$i]));
				array_push($headers, "$header: $value");
			}
		}
	}
	$query = "curl -v '$actual_link'";
	foreach ($headers as $header) $query .= " -H '$header'";
	$query .= " -X $method";
	if ($body) $query .= " -d '$body'";
	$query = json_encode(['query' => $query]);
	write_log("Replay command: $query", "ALERT");
}


