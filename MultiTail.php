<?php
namespace digitalhigh;


use Exception;
use SplFileObject;

class MultiTail {

	public $lineCounts = [];
	public $logs;
	private $reloadTime;
	private $maxLines = 5000;
	private $auth;
	private $noHeader;


	function __construct($logs, $noHeader) {
		$this->logs = $logs;
		$this->noHeader = $noHeader;
	}

	public function load() {

	}

	public function fetch() {
		debug("Fetching.");
		$logs = $this->logs;
		$contents = [];
		foreach ($logs as $name => $data) {
			$path = $data['path'];
			$lineNumber = $data['line'];
			$file = new SplFileObject($path);
			if (!$file->eof()) {
				$file->seek($lineNumber);
				while ($file->valid()) {
					$parsed = $this->parseLine($file->fgets(), $lineNumber);
					if ($parsed && trim($parsed['body'])) {
						$parsed['line'] = $lineNumber;
						$parsed['doc'] = $name;
						$contents[] = $parsed;
					}
					$lineNumber++;
				}
			}
			$data['line'] = $lineNumber;
			$logs[$name] = $data;
			$file = null;
		}
		$this->logs = $logs;
		usort($contents, function ($item1, $item2) {
			return $item1['stamp'] <=> $item2['stamp'];
		});

		return array_slice($contents, ($this->maxLines) * -1);
	}

	private function parseLine($line, $number) {
		if ($line == "; <?php die('Access denied'); ?>" . PHP_EOL) return false;
		$levels = [
			"DEBUG", "INFO", "WARN", "ERROR", "ALERT", "EMERGENCY", "CRITICAL", "NOTICE", "INFORMATIONAL", "PINK",
			"ORANGE", "GREEN"
		];
		$level = "DEBUG";
		$stamp = "";
		$opp = [];
		// Sort out things in brackets

		$sploded = explode("]", $line);
		foreach ($sploded as $param) {
			$param = trim($param);
			if ($param[0] !== "[") continue;
			$param = ltrim($param, "[");
			// Remove param from string
			$line = str_replace("[$param]", "", $line);
			// See if it's a log level
			$levelSet = false;
			foreach ($levels as $check) {
				if (preg_match("/$check/", strtoupper($param))) {
					$level = $check;
					$levelSet = true;
				}
			}
			if ($levelSet) continue;
			// Otherwise, put it to misc params
			$opp[] = $param;
		}

		// Check the remaining body for JSON
		$jsonItem = false;
		$ogText = "";
		debug("JSON Check.");
		if (preg_match('~\[\{.*\}\]~', $line, $json)) {
			foreach ($json as $check) {
				$result = json_decode($check, true);
				if (is_array($result)) {
					$jsonItem = $result;
					$ogText = $check;
				}
			}
		}

		if (preg_match('~\{.*\}~', $line, $json)) {
			debug("Found json.");
			foreach ($json as $check) {
				$result = json_decode($check, true);
				if (is_array($result)) {
					if (strlen($check) > strlen($ogText)) {
						$jsonItem = $result;
						$ogText = $check;
					}
				}
			}
		}

		if ($jsonItem) $line = str_replace($ogText, "[JSON]", $line);

		// Check the remaining body for a URL
		$link = false;
		debug("Url check.");
		if (preg_match('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $line, $urls)) {
			foreach($urls as $url) {
				debug("Found url.");
				$url = filter_var($url, FILTER_SANITIZE_URL);
				if (filter_var($url, FILTER_VALIDATE_URL)) {
					$line = str_replace($url, "[URL]", $line);
					$link = $url;
				}
				if ($link) break;
			}
		}

		// Check the remaining body for XML
		debug("Markup check.");
		$markups = [];
		if (preg_match("/<[\s\S]*?>/", $line, $markup)) {
			debug("Found meta.");
			foreach ($markup as $tagged) {
				if($tagged != strip_tags($tagged)){
					$markups["html"] = $tagged;
					$line = str_replace($tagged, "[HTML]", $line);
				} else {
					try {
						$xmlObj = simplexml_load_string($tagged);
						$line = str_replace($tagged, "[XML]", $line);
						$markups["xml"] = $xmlObj;
					} catch (Exception $e) {
						continue;
					}
				}
			}
		}

		$line = [
			'stamp' => $stamp,
			'level' => $level,
			'params' => $opp,
			'number' => $number,
			'body' => $line,
			'json' => $jsonItem,
			'url' => $link,
			'markup' => $markups
		];
		return $line;
	}
}

function debug($msg) {
	$debug = $_GET['debug'] ?? false;
	if ($debug) {
		//echo($msg) . PHP_EOL;
	}
}

