<?php
/**
 * Created by PhpStorm.
 * User: digitalhigh
 * Date: 11/13/2018
 * Time: 9:49 PM
 */

namespace digitalhigh\widget\curl;


class curlGet {
	/**
	 * curlGet constructor.
	 * @param string $url
	 * @param bool | array $headers
	 * @param int $timeout
	 * @param bool $decode
	 * @param bool $log
	 */
	function __construct($url, $headers = false, $timeout = 4, $decode = true, $log = false) {
		$cert = getCert();
		if ($log) write_log("GET url $url", "INFO", "curlGet");
		$url = filter_var($url, FILTER_SANITIZE_URL);
		if (!filter_var($url, FILTER_VALIDATE_URL)) {
			write_log("URL $url is not valid.", "ERROR");
			return false;
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_CAINFO, $cert);
		if ($headers) {
			if (is_string($headers)) $headers = [$headers];
			if (is_array($headers)) {
				if ($log) write_log("Setting headers: ".json_encode($headers), "INFO", false, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			}
		}
		$contentType = false;
		$result = curl_exec($ch);
		if (!curl_errno($ch)) {
			switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
				case 200:
					$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
					break;
				default:
					write_log('Unexpected HTTP code: ' . $http_code . ', URL: ' . $url, "ERROR");
					$result = false;
			}
		}
		curl_close($ch);

		if ($result) {
			if ($decode) {
				$result = decodeResult($result, $contentType, $log);
			} else {
				if ($log) write_log("Curl result(RAW): " . json_encode($result));
			}
		}
		return $result;
	}
}