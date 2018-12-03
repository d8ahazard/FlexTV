<?php
namespace digitalhigh;


use Exception;
use SplFileObject;

class MultiTail {

	public $lineCounts = [];
	public $logs;
	private $maxLines;
	private $noHeader;


	function __construct($logs, $noHeader, $maxLines = 5000) {
		$this->logs = $logs;
		$this->noHeader = $noHeader;
		$this->maxLines = $maxLines;
	}

	public function fetch() {
		debug("Fetching.");
		$logs = $this->logs;
		$contents = [];
		foreach ($logs as $name => $data) {
			$lastStamp = false;
			$path = $data['path'];
			$lineNumber = $data['line'];
			$file = new SplFileObject($path);
			if (!$file->eof()) {
				$file->seek($lineNumber);
				while ($file->valid()) {
					$parsed = $this->parseLine($file->fgets(), $name, $lastStamp);
					if ($parsed && trim($parsed['body'])) {
						$parsed['line'] = $lineNumber;
						$parsed['doc'] = $name;
						$lastStamp = (($parsed['stamp'] ?? "") !== "") ? $parsed['stamp'] : false;
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
		//usort($contents, 'cmp');
		$stamps = array_column($contents, 'unix');
		array_multisort($stamps, SORT_ASC, $contents);

		return array_slice($contents, ($this->maxLines) * -1);
	}

	private function parseLine($line, $file, $lastStamp = false) {
		$dateFormat = '%m-%d-%Y %H:%M:%S';
		if ($line == "; <?php die('Access denied'); ?>" . PHP_EOL) return false;
		$level = preg_match("/error/", strtolower($file)) ? "ERROR" : "DEBUG";
		$app = explode("_",$file)[0];
		$ms = false;
		switch(true) {
			case preg_match("/nginx/", strtolower($file)):
				$regex = '~^(?P<stamp>[\d+/ :]+) \[(?P<level>.+)\] .*?: (?P<body>.+), client: (?P<user>.+), server: (?P<server>.+), request: (?P<doc>.+), host: (?P<host>.+)$~';
				$format = '%Y/%m/%d %H:%M:%S';
				break;
			case preg_match("/apache/", strtolower($file)):
				$regex = '~/^\[(?P<stamp>[^\]]+)\] \[(?P<level>[^\]]+)\] (?P<user>?:\[client ([^\]]+)\])?\s*(?P<body>.*)$/i~';
				$format = '%a %b %d %H:%M:%S %Y';
				break;
			case preg_match("/lighttpd/", strtolower($file)):
				$regex = '~(?P<stamp>.*)\: \((?P<func>.+)\) (?P<body>.*)~';
				$format = '%Y-%m-%d %H:%M:%S';
				// 2018-12-03 09:00:28: (connections-glue.c.166) SSL: -1 5 0 Success
				break;
			case preg_match("/flextv/", strtolower($file)):
			case preg_match("/phlex/", strtolower($file)):
				$regex = '~\[(?P<stamp>.*?)\].*\[(?P<level>.*?)\].*\[(?P<user>.*?)\].*\[(?P<func>.*?)\] - (?P<body>.*)$~';
				$format = '%m-%d-%Y %H:%M:%S';
				break;
			case preg_match("/php/", strtolower($file)):
			default:
				$regex = '~\[(?P<stamp>.*?)\] PHP (?P<level>.+?(?=:)):  (?P<body>.*)$~';
				$format = '%d-%b-%Y %H:%M:%S %Z';
		}
		//debug("Checking $app '$regex' against $line");
		//debug("");
		preg_match($regex, $line, $matches);
		$stamp = $lastStamp;
		try {
			$check = $matches['stamp'] ?? false;
			$check = $check ? $check : $stamp;
			if ($check) {
				//debug("Checking stamp: $check");
				$og = trim(explode(".", $check)[0]);
				$ms = explode(".", $check)[1] ?? false;
				$ftime = strptime($og, $format);
				if ($ftime) {
					$unix = mktime(
						$ftime['tm_hour'],
						$ftime['tm_min'],
						$ftime['tm_sec'],
						1 ,
						$ftime['tm_yday'] + 1,
						$ftime['tm_year'] + 1900
					);
					debug("UNIX: ".json_encode($unix));
					$stamp = strftime($dateFormat, $unix);
					$matches['unix'] = $unix;
					if ($ms) $stamp .= ".$ms";
					//debug("Stamp: $stamp");
				}
			}
		} catch (Exception $e) {}
		$keep = ['user', 'stamp', 'level', 'doc', 'body', 'func', 'unix'];
		foreach ($matches as $key => $check) if (!in_array($key, $keep)) unset($matches[$key]);
		unset($matches[0]);
		$tmp = $matches;
		unset($tmp['body']);
		//debug("Matches: ".json_encode($tmp, JSON_PRETTY_PRINT));
		$line = $matches['body'];
		// Check the remaining body for JSON
		$jsonItem = false;
		$ogText = "";
		if (preg_match('~(\[.*\])|(\{.*\})~', $line, $json)) {
			foreach ($json as $check) {
				$result = json_decode($check, true);
				if (is_array($result)) {
					$jsonItem = $result;
					$ogText = $check;
				}
			}
		}


		if ($jsonItem) $line = str_replace($ogText, "[JSON]", $line);

		// Check the remaining body for a URL
		$link = false;
		if (preg_match('#(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))#', $line, $urls)) {
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

		$level = strtoupper($matches['level'] ?? $level);
		$checks = ["ERROR", "WARN", "PARSE", "NOTICE", "STRICT", "FATAL", "DEBUG", "INFO", "ALERT", "ORANGE", "PINK", "CRITICAL"];
		foreach ($checks as $check) {
			if (preg_match("/$check/", $level)) {
				$level = $check;
				break;
			}
		}

		$line = [
			'stamp' => $stamp,
			'level' => $level,
			'body' => $line,
			'json' => $jsonItem,
			'url' => $link,
			'markup' => $markups,
			'user' => $matches['user'] ?? "",
			'func' => $matches['func'] ?? "",
			'unix' => $matches['unix'] ?? "",
			'doc' => $file
		];
		return $line;
	}
}

function debug($msg) {
	$debug = $_GET['debug'] ?? false;
	if ($debug) {
		echo($msg) . PHP_EOL;
	}
}

function cmp($a, $b)
{
	if ($a['unix'] == $b['unix']) return 0;
	if (!isset($a['unix']) | !isset($b['unix'])) return 0;
	return ($a['unix'] < $b['unix']) ? -1 : 1;
}

