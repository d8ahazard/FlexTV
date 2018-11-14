<?php
/**
 * Created by PhpStorm.
 * User: digitalhigh
 * Date: 11/13/2018
 * Time: 9:49 PM
 */

namespace digitalhigh\widget\curl;


class curlPost {
	function __construct($url, $content = false, $JSON = false, Array $headers = null, $timeOut = 3) {
		write_log("POST url $url", "INFO", "curlPost");
		$url = filter_var($url, FILTER_SANITIZE_URL);
		if (!filter_var($url, FILTER_VALIDATE_URL)) {
			write_log("URL $url is not valid.");
			return false;
		}

		$cert = getCert();
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CAINFO, $cert);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 4);
		curl_setopt($curl, CURLOPT_TIMEOUT, $timeOut);
		if ($headers) {
			if ($JSON) {
				$headers = array_merge($headers, ["Content-type: application/json"]);
			}

			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		} else {
			if ($JSON) {
				$headers = ["Content-type: application/json"];
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			}
		}
		if ($content) curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
		$response = curl_exec($curl);
		if (!curl_errno($curl)) {
			switch ($http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) {
				case 200:
					break;
				default:
					write_log('Unexpected HTTP code: ' . $http_code . ', URL: ' . $url, "ERROR", "curlPost");
					$response = false;
			}
		}
		curl_close($curl);
		return $response;
	}
}