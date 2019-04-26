<?php
require_once dirname(__FILE__) . '/php/vendor/autoload.php';
require_once dirname(__FILE__) . "/php/webApp.php";
require_once dirname(__FILE__) . '/php/util.php';
require_once dirname(__FILE__) . '/php/fetchers.php';
require_once dirname(__FILE__) . '/php/body.php';
require_once dirname(__FILE__) . '/php/JsonXmlElement.php';
require_once dirname(__FILE__) . '/php/digitalhigh/dialogFlow/DialogFlow.php';
require_once dirname(__FILE__) . '/php/multiCurl.php';
require_once dirname(__FILE__) . '/php/digitalhigh/widget/src/widget.php';

use digitalhigh\DialogFlow\DialogFlow;
use digitalhigh\multiCurl;
use digitalhigh\widget\widget;
use Kryptonit3\SickRage\SickRage;
use Kryptonit3\Sonarr\Sonarr;
use digitalhigh\widget\exception\widgetException;

scriptDefaults();

if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) analyzeRequest();

/**
 * Takes an incoming request and makes sure it's authorized and valid
 */
function analyzeRequest() {

	$json = file_get_contents('php://input');
	$post = $_POST['postData'] ?? false;
	if (!$post) write_log("-------NEW REQUEST RECEIVED-------", "ALERT");
	scriptDefaults();
	checkDefaults();
	if (isset($_GET['revision'])) {
		$rev = $GLOBALS['config']->get('general', 'revision', false);
		echo $rev ? substr($rev, 0, 8) : "unknown";
		die;
	}

	$token = false;
	if (isset($_SERVER['HTTP_APITOKEN'])) {
		$token = $_SERVER['HTTP_APITOKEN'];
	}

	if (isset($_POST['apiToken'])) {
		$token = $_POST['apiToken'];
	}

	if (isset($_GET['apiToken'])) {
		$token = $_GET['apiToken'];
	}

	if (isset($_SERVER['HTTP_METHOD'])) {
		$method = $_SERVER['HTTP_METHOD'];
		if ($method == 'google') $token = $_SERVER['HTTP_TOKEN'];
	}

	if (!$token && isset($_SESSION['apiToken'])) {
		$token = $_SESSION['apiToken'];
	}

	$user = $token ? verifyApiToken($token) : False;

	if ($user) {
		$apiToken = $user['apiToken'];
		$_SESSION['dologout'] = false;
		$_SESSION['v2'] = true;

		foreach ($user as $key => $value) $_SESSION[$key] = $value;
		if (!(isset($_SESSION['counter']))) {
			$_SESSION['counter'] = 0;
		}

		$apiTokenMatch = ($apiToken === $_SESSION['apiToken']);
		$loaded = $_SESSION['loaded'] ?? false;
		// DO NOT SET ANY SESSION VARIABLES MANUALLY AFTER THIS IS CALLED
		if (!$apiTokenMatch || !$loaded) {
			setSessionData(false);
		}
		initialize();
	} else {
		write_log("APIToken $token is not valid!", "ERROR");
		if (isset($_GET['testclient'])) {
			http_response_code(401);
			bye();
		}
	}
}

/**
 * Loads/ends session, figures out what to do
 */
function initialize() {
	$post = $_POST['postData'] ?? false;
	if ($post) {
		header('Content-Type: application/xml');
		plexApi();
		bye();
	}
	if (isset($_GET['fetchData'])) {
		$force = ($_GET['force'] === 'true');
		$result = getUiData($force);
		header('Content-Type: application/json');
		if (isset($result['widgets'])) write_log("Okay, really really echoing JSON here: ".json_encode($result),"ALERT",false,true);
		echo json_encode($result);
		bye();
	}
	if (isset($_GET['testclient'])) {
		write_log("API Link Test successful!", "INFO");
		echo 'success';
		bye();
	}

	if (isset($_GET['recommend'])) {
		header("Content-Type: application/json");
		echo json_encode(getRecommendations($_GET['recommend']));
		die();
	}

	if (isset($_GET['test'])) {
		$result = [];
		$status = testConnection($_GET['test']);
		header('Content-Type: application/json');
		$result['status'] = $status[0];
		$result['list'] = $status[1] ?? false;
		echo json_encode($result);
		bye();
	}
	if (isset($_GET['testFc'])) {
		$uri = $_GET['uri'] ?? '';
		$result = "Failure";
		write_log("We be testin $uri");
		if (trim($uri)) {
			$testUri = $uri . "/test";
			write_log("Curling: $testUri");
			$data = curlGet($testUri,false, 4,false,true);
			write_log("DATA: $data");
			$result = ($data === 'Success') ? $data : "Failure";
		}
		write_log("Result: $result");
		echo $result;
		bye();
	}

	if (isset($_GET['setFc'])) {
		$string = json_decode($_GET['setFc'], true);
		$result = "Failure";
		write_log("FC JSON: ".json_encode($string));

		if (is_array($string)) {
			write_log("Setting...");
			updateUserPreference('fcArray', $string);
			$result = "Success";
		}
		echo $result;
		bye();
	}


	if (isset($_GET['registerServer'])) {
		write_log("Registering server with flextv.us", "INFO");
		sendServerRegistration();
		echo "OK";
		bye();
	}
	if (isset($_GET['card'])) {
		popCommand($_GET['card']);
		bye();
	}
	if (isset($_GET['checkUpdates'])) {
		echo json_encode(checkUpdate());
		bye();
	}
	if (isset($_GET['installUpdates'])) {
		echo json_encode(installUpdate());
		bye();
	}
	if (isset($_GET['device'])) {
		$type = $_GET['device'];
		$id = $_GET['id'] ?? false;
		$data = $dev = false;
		if (!$id) {
			$name = $_GET['name'] ?? false;
			$uri = $_GET['uri'] ?? false;
			if ($name) $dev = findDevice("Name", $name, $type);
			if ($uri) $dev = findDevice("Uri", $name, $type);
			if ($dev) $id = $dev['id'] ?? false;
		}
		header('Content-Type: application/json');
		if ($id !== 'rescan' && $id !== false) {
			$data = setSelectedDevice($type, $id);
		} else if ($id == 'rescan') {
			$force = $_GET['passive'] ?? false;
			$data = selectDevices(scanDevices(!$force));
		}
		if ($data) {
			writeSession('deviceUpdated', true);
			if (!isset($_GET['say'])) echo json_encode($data);
		}
		if (!isset($_GET['say'])) bye();
	}

	if ((isset($_GET['id'])) && (!isset($_GET['device']))) {
		$valid = true;
		$id = $_GET['id'];
		$value = $_GET['value'];
		write_log("Setting Value changed: $id = $value", "INFO");
		$value = str_replace("?logout", "", $value);
		if ((preg_match("/IP/", $id) || preg_match("/Uri/", $id)) && !preg_match("/device/", $id)) {
			$value = cleanUri($value);
			if (!$value) $valid = false;
		}
		if (preg_match("/Path/", $id)) if ((substr($value, 0, 1) != "/") && (trim($value) !== "")) $value = "/" . $value;

		if ($valid) {
			if ($id === 'forceSSL' || $id === 'noNewUsers' || $id === 'cleanLogs') {
				$data = ['name' => $id, 'value' => $value];
				if ($_SESSION['masterUser']) {
					setPreference('general', $data, ["name" => $id]);
					writeSession($id, $value);
				} else {
					$user = $_SESSION['plexUserName'] ?? "no user";
					write_log("WARNING, UNAUTHENTICATED USER '$user' TRYING TO CHANGE SETTINGS.", "ERROR");
				}
			} else {
				updateUserPreference($id, $value);
				writeSession($id, $value);
				if (is_array($value)) $checkVal = json_encode($value); else $checkVal = $value;
				if (!isset($_SESSION['settings'])) $_SESSION['settings'] = [];
				$_SESSION['settings'][$id] = $checkVal;
			}
			if ((trim($id) === 'useCast') || (trim($id) === 'noLoop')) scanDevices(true);
			if ($id == "appLanguage") checkSetLanguage($value);
			if ($id === 'publicAddress') setStartUrl();
			if (preg_match("/Token/", $id) || preg_match("/Uri/", $id)) {
				$id = str_replace(["Token", "Uri"], "", $id);
				testConnection($id);
			}
		}

		echo($valid ? "valid" : "invalid");
		bye();
	}

	if (isset($_GET['suggestions'])) {
		write_log("Fetching suggestions!");
		$type=$_GET['suggestions'];
		$data = fetchSuggestedMedia(['type'=> strtolower($type)]);
		$count = count($data);
		$key = rand(0, ($count - 1));

		write_log("Final suggestion array out $key of $count items: ".json_encode($data));
		$rand = $data[$key];
		write_log("Rand: ".json_encode($rand));
		header('Content-Type: Application/JSON');
		echo(json_encode($rand));
		bye();
	}

	if (isset($_GET['jsonWidgetArray'])) {
		$widgetData = json_decode($_GET['jsonWidgetArray'], true);
		write_log("JSON WIDGET ARRAY: ".json_encode($widgetData), "ALERT", false, true, true);
		$widgets = buildWidgets($widgetData);
		updateUserPreference('jsonWidgetArray', $widgets);
	}

	if (isset($_GET['jsonAppArray'])) {
		$apps = json_decode($_GET['jsonAppArray'], true);
		write_log("JSON APP ARRAY: ".json_encode($apps));
		updateUserPreference('jsonAppArray', $apps);
	}

	if (isset($_GET['castLogs'])) {
		downloadCastLogs();
	}

	if (isset($_GET['fetchList'])) {
		$fetch = $_GET['fetchList'];
		$list = fetchList($fetch);
		echo $list;
		bye();
	}
	if (isset($_GET['notify'])) {
		header('Content-Type: application/json');
		$msg = false;
		$json = trim(file_get_contents('php://input'));
		write_log("Notify body: " . $json);
		// Couchpotato format
		if (preg_match("/message=/", $json)) {
			write_log("Got a hook command from couchpotato!");
			$var = explode("=", $json)[1] ?? "";
			if (trim($var)) {
				$var = urldecode($var);
				$msg = $var;
				write_log("We have a hook message from couchpotato: $msg");
			}
		}
		// Radarr event
		if (preg_match("/EventType/", $json)) {
			write_log("This looks like a Radarr or event!");
			$json = json_decode($json, true);
			if (isset($json['Movie']['Title'])) {
				write_log("Yeah, this is a Radarr event.");
				$media = $json['Movie']['Title'];
				$event = $json['EventType'];
				$msg = "The Movie $media has been $event on Radarr.";
			}
			if (isset($json['Episodes'][0]['Title'])) {
				write_log("Yeah, this is a Radarr event.");
				$media = $json['Episodes'][0]['Title'];
				$event = $json['EventType'];
				$msg = "The Movie $media has been $event on Sonarr.";
			}
		}
		// Get request
		if (isset($_GET['message'])) {
			$msg = $_GET['message'];
		}

		if ($msg) {
			write_log("Broadcasting audio: $msg");
			$result = castAudio($msg);
			$reply = ['status' => $result];
			echo json_encode($reply);
		}
		bye();
	}


	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$json = file_get_contents('php://input');
		$request = json_decode($json, true);
		$response = false;
		$amazonRequest = $_SERVER['HTTP_AMAZONREQUEST'] ?? false;
		if ($amazonRequest) {
			writeSession('amazonRequest', true);
			write_log("No, really, we have an amazonrequest: " . json_encode($request), "ALERT");
			writeSession('lastRequest', $request['result']['resolvedQuery']);
		}
		if ($request) {
			if (isset($request['result']['resolvedQuery']) || isset($request['type'])) {
				write_log("Request JSON: " . $json, "INFO");
				try {
					$df = new DialogFlow(fetchDirectory(3), $_SESSION['appLanguage']);
					$response = $df->process($request);
				} catch (Exception $e) {
					write_log("There was an exception! '$e'", "ERROR");
				}
				mapApiRequest($response);
				bye("Ending session.");
			}
		}

	}

	$command = $_GET['command'] ?? $_SERVER['HTTP_COMMAND'] ?? false;
	if ((isset($_GET['say'])) && $command) {
		write_log("Incoming API request detected: '$command'", "INFO");
		try {
			$request = fetchApiAiData($command);
			if ($request) {
				mapApiRequest($request);
				bye("Ending session.");
			}
		} catch (\Exception $error) {
			write_log("An excepton occurred mapping request. " . json_encode($error->getMessage()), "ERROR");
		}
	}

	if (isset($_GET['commands'])) {
		$limit = ($_GET['commands'] !== "all") ? $_GET['commands'] : false;
		$commands = fetchCommands($limit);
		header('Content-Type: application/json');
		echo json_encode($commands);
		bye();
	}

	if (isset($_GET['fireFallback'])) {
		sendFallback();
		echo "SUCCESS";
		bye();
	}
}

function plexApi() {
	$serverId = $_SESSION['plexServerId'] ?? false;
	$server = findDevice('Id', $serverId, 'Server');
	$plexUrl = $server['Uri'];
	$post = $_POST['postData'];
	$headers = headerRequestArray(plexHeaders($server));
	array_push($headers, "Accept: application/json");
	$url = $plexUrl . $post;
	$resultstr = curlGet($url, $headers, 4, false, false);
	echo $resultstr;

}

function pingMonitor($urls) {
	
}


function setSessionData($rescan = true) {
	$data = fetchUserData($rescan);
	if ($data) {
		foreach ($data as $key => $value) {
			writeSession($key, $value);
		}
		$clientId = trim($data['plexClientId'] ?? "");
		$serverId = trim($data['plexServerId'] ?? "");
		$check = [
			'Client' => $clientId,
			'Server' => $serverId
		];
		foreach ($check as $section => $value) {
			$sectionArray = $devices["$section"] ?? [];
			if (!$value && count($sectionArray)) {
				setSelectedDevice($section, $sectionArray[0]);
			}
		}
		$_SESSION['deviceID'] = checkSetDeviceID();
		$_SESSION['plexHeaderArray'] = plexHeaders();
		$_SESSION['plexHeader'] = headerQuery(plexHeaders());
		$_SESSION['loaded'] = true;
	}
	if (!$data) write_log("Error, could not find userdata!!", "ERROR");
}

function triggerRescan() {
	$servers = $_SESSION['deviceList']['Server'] ?? [];
	$urls = [];
	foreach ($servers as $server) {
		$name = $server['Name'];
		$uri = $server['Uri'];
		$token = $server['Token'];
		$urls[$name] = "$uri/library/sections/all/refresh?X-Plex-Token=$token";
	}
	$mc = new multiCurl($urls);
	$mc->process();
	return true;
}

function getUiData($force = false) {
	$result = [];
	$devices = selectDevices(scanDevices(false));
	$apps = fetchAppArray();
	if ($force) {
		$widgetData = fetchWidgetArray();
		$fcArray = getPreference('userdata', ['fcArray'],[],['apiToken'=>$_SESSION['apiToken']], true);
		$fetchers = getPreference('userdata', ['jsonFetcherArray'],[],['apiToken'=>$_SESSION['apiToken']], true);
		write_log("Sending forced data.","ALERT",false,true);
		$lang = checkSetLanguage();
		$result = [
			'devices' => $devices,
			'strings' => $lang['javaStrings'] ?? [],
			'widgets' => $widgetData,
			'apps' => $apps,
			'fcArray' => $fcArray,
			'fetchers' => $fetchers
		];
		$_SESSION['settings'] = [];
		return $result;
	} else {
		$widgetData = updateWidgets();
		$playerStatus = fetchPlayerStatus();
		$deviceText = json_encode($devices);
		$settingData = array_merge(fetchGeneralData(), fetchUserData());
		// Temporarily do this until we're sure nobody's got base64 lists anymore
		$settingData['jsonAppArray'] = $apps;
		$settingData['widgets'] = $widgetData;
		$updated = [];
		foreach ($settingData as $key => $value) {
			if (preg_match("/List/", $key) && $key !== 'deviceList') {
				$value = fetchList(str_replace("List", "", $key));
			}
			$staticBools = [
				'darkTheme',
				'forceSSL',
				'hasPlugin',
				'isWebApp',
				'noNewUsers',
				'plexDvrNewAirings',
				'plexDvrReplaceLower',
				'plexPassUser',
				'shortAnswers',
				'masterUser',
				'notifyUpdate',
				'alertPlugin',
				'autoUpdate'
			];
			if (preg_match("/Enabled/", $key) || preg_match("/Newtab/", $key) || preg_match("/Search/", $key) || in_array($key, $staticBools)) {
				$value = boolval($value);
				if ($value == "0") $value = false;
				if ($value == "1") $value = true;
			}
			$ogVal = $value;
			if (is_array($value)) {
				foreach($value as $checkKey => &$check) if (isset($check['lastUpdate'])) unset($check['lastUpdate']);
				$value = json_encode($value);
			}
			$oldValue = $_SESSION['settings'][$key] ?? "<NODATA>..";
			if ($oldValue !== $value) {
				if ($key === 'widgets') {
					write_log("Pushing widgets because value for $key updated: $value", "ALERT", false, true);
				}
				$updated[$key] = $ogVal;
				$_SESSION['settings'][$key] = $value;
			}
		}

		$removes = ['appArray', 'jsonAppArray', 'appList', 'jsonWidgetArray', 'commands', 'fetchers', 'widgets', 'fcArray'];
		$apps = $updated['jsonAppArray'] ?? [];
		$commands = $updated['commands'] ?? [];
		$fetchers = $updated['fetchers'] ?? [];
		$widgets = $updated['widgets'] ?? [];
		$fcArray = $updated['fcArray'] ?? [];

		if (count($apps)) $result['apps'] = $apps;
		if (count($widgets)) {
			$result['widgets'] = $widgets;
		}
		if (count($commands)) $result['commands'] = $commands;
		if (count($fetchers)) $result['fetchers'] = $commands;
		if (count($fcArray)) $results['fcArray'] = $fcArray;
		foreach ($removes as $remove) if (isset($updated[$remove])){
			unset($updated[$remove]);
		}
		if (count($updated)) {
			$result['userData'] = $updated;
			writeSession('updated', false, true);
		}

		$deviceUpdated = $_SESSION['devices'] !== $deviceText;

		if ($deviceUpdated) {
			$result['devices'] = $devices;
			writeSession('devices', $deviceText);
		}

		if ($playerStatus) {
			$lastStatus = $_SESSION['lastStatus'] ?? "<NODATA>..";
			if (json_encode($playerStatus) !== $lastStatus) {
				$results['playerStatus'] = $playerStatus;
				writeSession('lastStatus', json_encode($playerStatus));
			}
		}
	}

	$messages = $_SESSION['messages'] ?? false;
	if ($messages) {
		$result['messages'] = $_SESSION['messages'];
		writeSession('messages', false);
	}

	return $result;
}


function fetchMediaInfo(Array $params) {
	write_log("Function fired with params: " . json_encode($params));
	$suggestion = $track = $album = $subtype = $season = $mod = $episode = $title = $media = false;
	$action = $params['control'] ?? false;
	$shuffle = false;
	if ($action) {
		$check = explode(".", $action);
		$action = $check[0];
		$type = $check[1] ?? false;
		if ($type == 'shuffle') {
			$type = false;
			$shuffle = true;
		}

		if ($type === 'recommended') {
			$suggestion = true;
			$type = false;
		}
	}

	$request = $params['request'] ?? $params['music-artist'] ?? $params['movie-title'] ?? false;
	$year = $params['year']['amount'] ?? $params['age'] ?? false;
	$artist = $params['music-artist'] ?? false;
	if (!$artist) {
		$data = explode(" by ", $params['resolved']);
		$artist = $data[1] ?? false;
	}
	$type = $type ?? ((($artist) && ($artist == $request)) ? 'music.artist' : false);
	$type = $type ?? ((($artist) && ($artist != $request)) ? 'music' : false);
	if (isset($params['movie-title'])) $type = ['movie'];
	$castMember = $params['movie-actor'] ?? false;
	if ($castMember) write_log("This is a search by cast.", "ALERT");
	$time = $params['time'] ?? $params['duration'] ?? false;
	if (is_array($time)) {
		$unit = $time['unit'];
		if ($unit == 's') $unit = 'second';
		if ($unit == 'min') $unit = 'minute';
		$time = $time['amount'] . " " . $unit;
	}
	$time = ($time ? strtotime("+$time") : false);
	$time = ($time ? ($time - strtotime("now")) : 0) * 1000;
	// Associate number values and subtypes
	$types = $params['type'] ?? [$type] ?? false;
	$season = $episode = $album = $track = false;
	if ($types) {
		$i = 0;
		foreach ($types as $checkType) {
			$check = explode(".", $checkType);
			$type = $checkType;
			$subType = $check[1] ?? false;
			$subValue = ($subType ? ($params['number'][$i] ?? $params['mod'][$i] ?? false) : false);
			switch ($subType) {
				case 'season':
					$season = $subValue;
					$subType = 'show';
					break;
				case 'episode':
					$episode = $subValue;
					break;
				case 'artist':
					if (($subtype !== "track") && ($subtype !== "album")) $subtype = "artist";
					break;
				case 'album':
					$album = $subValue;
					break;
				case 'track':
				case 'song':
					if ($subType == 'song') $subType = 'track';
					$track = $subValue;
					break;
			}
			if ($subType && $subValue) {
				$data["$subType"] = $subValue;
				$data['subType'] = $subType;
			} else {
				if ($type) $data['subType'] = $type;
			}
			$i++;
		}
	}
	$playlist = ($type === 'playlist');
	if ($track && $request) {
		write_log("Ripping the word track out, because DF sucks!");
		$request = trim(str_replace("Track", "", $request));
		$request = trim(str_replace("track", "", $request));
	}
	$type = ($type ? $type : ($artist ? 'music' : false));
	$type = ($type ? $type : ($album ? 'music.album' : false));
	$type = ($type ? $type : ($track ? 'music.track' : false));
	$data = [
		'action'  => $action,
		'intent'  => $action,
		'shuffle' => $shuffle,
		'suggestion' => $suggestion,
		'request' => $request,
		'type'    => $type,
		'season'  => $season,
		'episode' => $episode,
		'artist'  => $artist,
		'album'   => $album,
		'track'   => $track,
		'offset'  => $time,
		'year'    => $year,
		'cast'    => $castMember
	];
	foreach ($data as $key => $value) if ($value === false) unset($data["$key"]);

	$result = $meta = $searches = [];
	$result['params'] = $data;
	$subType = $data['subType'] ?? false;
	$searchUrl = "https://search.flextv.media/search.php";
	$queryParams = ['key' => keyGen()];
	if ($castMember) {
		$type = $params['type'][0] ?? 'movie';
		$castMedia = fetchRandomMediaByCast($castMember, $type);
		if ($castMedia) {
			$mediaItem = mapDataPlex($castMedia);
			$query = urlencode($mediaItem['title']);
			$year = $mediaItem['year'];
			$queryParams['query'] = $query;
			$queryParams['type'] = $type;
			$queryParams['year'] = $year;
			$url = $searchUrl . "?" . http_build_query($queryParams);
			$metaData = curlGet($url);
			$meta = $metaData ? json_decode($metaData, true) : [];
			$meta = $meta[0] ?? false;
			$media = ($meta) ? array_merge($mediaItem, $meta) : $mediaItem;
			$data['control'] = 'play';
			$result = ['media' => [$media], 'meta' => [$meta], 'params' => $data];
			write_log("Returning: " . json_encode($result));
			return $result;
		} else {
			write_log("Something went wrong, random media by cast not found.", "ERROR");
			$media = [];
			$meta = [];
			$data['control'] = 'play';
			$result = ['media' => $media, 'meta' => $meta, 'params' => $data];
			return $result;
		}
	}

//	if ($podCast) {
//		write_log("Podcast request.");
//		//$result = fetchPodCast($data);
//		return $result;
//	}

	if ($playlist) {
		write_log("Playlist request here, stripping extraneous words.");
		$strippers = ['the', 'my', 'called', 'named', 'titled'];
		$newRequest = str_replace($strippers, "", $request);
		if ($request !== $newRequest) {
			write_log("Stripped out some strings: $newRequest");
			$request = $newRequest;
			$data['request'] = $newRequest;
		}
	}

	$queryParams['query'] = $request;
	$queued = false;
	$tmp = "";
	if ($artist) $queryParams['artist'] = $artist;
	if ($album) $queryParams['album'] = $album;
	if ($subType) $queryParams['type'] = $subType;
	if ($year) $queryParams['year'] = $year;

	if ($request) {
		$searches['meta'] = $searchUrl . "?" . http_build_query($queryParams);
		foreach ($_SESSION['deviceList']['Server'] as $server) {
			$id = $server['Id'];
			$searches["plex.$id"] = $server['Uri'] . "/hubs/search?query=" . urlencode($request) . "&limit=30&X-Plex-Token=" . $server['Token'];
		}
		write_log("Search url array: " . json_encode($searches));
		$dataCurl = new multiCurl($searches, 3);
		$dataArray = $dataCurl->process();
		$result = mapData($dataArray);
		$meta = $dataArray['meta'];
		$media = $result['media'];
	}

	if ($shuffle) {
		if ($type && !$request && !$artist) {
			write_log("Fetching/shuffling random media.");
			$media = [shuffleMedia($type)];
			$queued = true;
			$result['media'] = $media;
			$result['meta'] = [];
			$data['control'] = 'play';
			$result['params'] = $data;
		}

		if (($artist) && (count($media) > 1)) {
			foreach ($media as $item) {
				if ($item['type'] === 'artist') {
					$media = [$item];
					break;
				}
			}
		}
	}

	if ($suggestion) {
		$data['media'] = fetchSuggestedMedia($data);

	}

	$ep = $key = $parent = false;
	write_log("Mapped metadata array: " . json_encode($result));
	// If we have a season or episode number
	if (($season || $episode) && (count($meta) >= 1)) {
		write_log("We need a numbered TV item.");
		if (count($media)) {
			foreach ($media as $item) {
				$it = $item['title'];
				if (compareTitles($item['title'], $request, false, true)) {
					$key = $item['key'] ?? false;
					$source = $item['source'] ?? false;
					if ($source) $parent = findDevice("Id", $source, "Server");
				}
			}
		}
		$eps = [];
		foreach ($meta as $metaItem) {
			if ($metaItem['type'] == "show.episode") {
				if ($season && $episode) {
					if ($season != -1 && $episode != -1) {
						if ($metaItem['season'] == $season && $metaItem['episode'] == $episode) {
							write_log("Found a matching episode in metadata.");
							$meta = [$metaItem];
							$ep = $metaItem;
							$season = $metaItem['season'];
							$episode = $metaItem['episode'];
						}
					}
				} else {
					array_push($eps, $metaItem);
				}
			}
		}
		if ($episode && $eps) {
			if ($episode == -1) {
				write_log("We need the latest aired episode...");
				foreach ($eps as $epCheck) {
					write_log("Airdate is " . $epCheck['airdate']);
					if (new DateTime() >= new DateTime($epCheck['airdate'])) {
						$ep = $epCheck;
					}
				}
			} else {
				--$episode;
				$ep = $eps[$episode] ?? false;
			}
		}
		if ($ep) {
			$meta = [$ep];
			$data['request'] = $ep['title'];
			$data['type'] = "episode";
			$data['year'] = $ep['year'];
			$data['season'] = $ep['season'];
			$data['episode'] = $ep['episode'];
			$season = $ep['season'];
			$episode = $ep['episode'];
		}

		if ($key && $parent) {
			$item = fetchNumberedTVItem($key, $season, $episode, $parent);
			if ($item) {
				$item['source'] = $parent['Id'];
				$media = [$item];
				$data['request'] = $item['title'];
				$data['type'] = 'episode';
			}
		}
	}
	// If we have a track number
	if ($track && count($meta) >= 1) {
		if (count($media)) {
			$tracks = [];
			foreach ($media as $item) {
				if (strtolower($item['title']) == strtolower($params['request']) && $item['type'] == 'album') {
					write_log("Got an album: " . json_encode($item));
					$host = findDevice("Id", $item['source'], "Server");
					$album = false;
					if ($host) {
						$url = $host['Uri'] . $item['key'] . "?X-Plex-Token=" . $host['Token'];
						$album = curlGet($url);
					}
					if ($album) {
						$container = (new JsonXmlElement($album))->asArray();
						$trackArray = $container['MediaContainer']['Track'] ?? [];
						foreach ($trackArray as $trackItem) {
							if ($trackItem['index'] == $track) {
								$trackItem['source'] = $item['source'];
								$new = mapDataPlex($trackItem);
								if ($new) $tracks[] = $new;
								break;
							}
						}
					}
				}
			}
			if (count($tracks) == 1) {
				$media = $tracks;
				$data['request'] = $tracks[0]['title'];
				$data['type'] = 'track';
			}
		}
	}
	if (!$castMember && !$queued) {
		if ($action == 'fetchMedia' && !isset($data['type'])) {
			$fetchers = listFetchers();
			$types = ['music' => ['track,album,artist'], 'movie' => ['movie'], 'show' => ['show', 'season', 'episode']];
			$providers = [
				'music' => ['headphones', 'lidarr'], 'movie' => ['couch', 'radarr', 'watcher'],
				'show'  => ['sonarr', 'watcher']
			];
			$type = [];
			foreach ($providers as $section => $items) {
				foreach ($items as $check) {
					if (in_array($check, $fetchers)) {
						foreach ($types[$section] as $item) array_push($type, $item);
					}
				}
			}
			write_log("Possible types to fetch: " . json_encode($type));
			$data['type'] = $type;
		}
		$matched = mergeData($data, $media, $meta);
	} else {
		$matched = $result;
	}
	return $matched;
}


function scanDevices($force = false) {
	//Variables
	$clients = $devices = $dvrs = $results = $servers = [];
	// Check to see if our cache should be refreshed.
	$now = microtime(true);
	$rescanTime = $_SESSION['rescanTime'] ?? 8;
	$ls = $_SESSION['lastScan'];
	$lastCheck = $ls ?? ceil(round($now) / 60) - $rescanTime;
	$diffMinutes = ceil(round($now - $lastCheck) / 60);
	$timeOut = ($diffMinutes >= $rescanTime);
	$list = fetchDeviceCache();
	$noServers = (!count($list['Server']));
	// Log things
	$msg = "Re-caching because of ";
	if ($force) $msg .= "FORCE & ";
	if ($timeOut) $msg .= "TIMEOUT &";
	if ($noServers) $msg .= "NO SERVERS";
	$msg = rtrim($msg, " &");
	// Set things up to be recached
	if ($force || $timeOut || $noServers) {
		writeSession('lastScan', $now);
		if (isset($_SESSION['scanning'])) {
			if ($_SESSION['scanning']) {
				writeSession('scanning', false);
				write_log("Breaking scanning loop.", "WARN");
				return $list;
			}
		}
		write_log("$msg", "INFO");
		writeSession('scanning', true);
		$url = "https://plex.tv/api/resources?includeHttps=1&includeRelay=1&X-Plex-Token=" . $_SESSION['plexToken'];
		$data = curlGet($url);
		$devices = $data['MediaContainer']['Device'] ?? false;
		if ($devices) {
			foreach ($devices as $device) {
				if (($device['presence'] == "1" || $device['product'] == "Plex for Vizio") && count($device['Connection'])) {
					$out = [
						'Product'              => $device['product'],
						'Id'                   => $device['clientIdentifier'],
						'Name'                 => $device['name'],
						'Token'                => $device['accessToken'],
						'Connection'           => $device['Connection'],
						'Owned'                => $device['owned'],
						'publicAddressMatches' => $device['publicAddressMatches'],
						'Key'                  => ""
					];
					if (preg_match("/Server/", $device['product'])) {
						$out['Version'] = $device['productVersion'];
						array_push($servers, $out);
					} else {
						$conn = isset($device['Connection']['address']) ? $device['Connection'] : $device['Connection'][0];
						$out['Uri'] = $conn['address'] . ":" . $conn['port'];
						array_push($clients, $out);
					}
				}
			}
		} else {
			write_log("Plex.TV connection issue, breaking to save device list.", "WARN");
			writeSession('scanning', false);
			die();
		}
		write_log("Currently have " . count($servers) . " Servers and " . count($clients) . " clients.");
		// Check set URI and public URI for servers, testing both http and https variables
		if (count($servers)) {
			$result = [];
			foreach ($servers as $server) {
				$name = $server['Name'];
				write_log("Checking $name: " . json_encode($server), "INFO");
				$connections = $server['Connection'];
				$mainTest = [];
				$backupTest = [];
				if (isset($connections['protocol'])) $connections = [$connections];
				$i = 0;
				$secure = ($server['httpsRequired']);
				$proto = $secure ? "https://" : "http://";
				write_log("Backup proto is $proto");
				write_log("This server " . ($secure ? 'does' : 'does not') . ' require https.');
				foreach ($connections as $connection) {
					$query = '?X-Plex-Token=' . $server['Token'];
					$uri = $connection['uri'];
					$backup = $proto . $connection['address'] . ":" . $connection['port'];
					$secure = (($server['httpsRequired'] && $connection['protocol'] === 'https') || (!$server['httpsRequired']));
					$cloud = preg_match("/plex.services/", $connection['address']);
					if ($secure || $cloud) {
						$mainTest[$uri] = $uri . $query;
					}
					$backupTest[$backup] = $backup . $query;
					$i++;
				}
				$test = $mainTest + $backupTest;
				$data = new multiCurl($test, 3);
				$uri = $data->test();
				write_log("Server uri: $uri");
				if ($uri) {
					$parts = parse_url($uri);
					unset($parts['query']);
					$server['uri'] = rtrim(http_build_url($parts), "/");
					write_log("Adding $name to server list.", "INFO");
					array_push($result, $server);
				}
			}
			$servers = $result;
		}
		write_log("Currently have " . count($servers) . " Servers and " . count($clients) . " clients (pre-scrape).", "INFO");
		// Scrape servers for cast devices, local devices, DVR status
		if (count($servers)) {
			$check = [];
			foreach ($servers as $server) {
				$cloud = preg_match("/plex.services/", $server['uri']);
				if (!$cloud) {
					array_push($check, $server);
				}
			}
			$res = scrapeServers($check);
			write_log("Server scrapin' result: " . json_encode($res), "INFO");
			if ($res) {
				$castLocal = $res['Client'];
				$dvrs = $res['Dvr'];
				$servers = $res['Server'];
				//Push local devices first
				foreach ($castLocal as $client) {
					if ($client['Product'] !== 'Cast') {
						array_push($clients, $client);
					}
				}
				//Finally, cast devices
				foreach ($castLocal as $client) {
					if ($client['Product'] === 'Cast') {
						array_push($clients, $client);
					}
				}
			} else {
				write_log("NO PLUGIN DETECTED!!", "ERROR", false, true);
				writeSession('alertPlugin', true);
			}
			// If this has never been set before
			if (!isset($_SESSION['alertPlugin'])) updateUserPreference('alertPlugin', true);
			if ($_SESSION['alertPlugin'] && !$_SESSION['hasPlugin']) {
				write_log("Building message now!", "INFO");
				$message = lang('uiNoCastPlugin');
				$alert = [
					[
						'title'   => 'Cast Plugin Not Found!',
						'message' => $message,
						'url'     => "https://github.com/d8ahazard/Cast.bundle"
					]
				];
				writeSession('messages', $alert);
				// Once we've sent the alert once, don't show it again
				updateUserPreference('alertPlugin', false);
			}
		}
		$results['Server'] = $servers;
		$results['Client'] = $clients;
		$results['Dvr'] = $dvrs;
		$results = sortDevices($results);
		updateDeviceCache($results);
		write_log("Final device array: " . json_encode($results), "INFO");
		writeSession('scanning', false);
	} else {
		$results = $list;
	}
	return $results;
}

function scrapeServers($serverArray) {
	$clients = $dvrs = $responses = $urls = [];
	foreach ($serverArray as $device) {
		$serverUri = $device['uri'];
		$token = $device['Token'];
		$deviceId = $device['Id'];
		$urls["${deviceId}_cast"] = ["$serverUri/chromecast/devices?X-Plex-Token=$token", ['Accept: application/json']];
		$urls["${deviceId}_dvr"] = ["$serverUri/livetv/dvrs?X-Plex-Token=$token", ['Accept: application/json']];
	}

	$fc = $_SESSION['fcArray'] ?? [];
	$server = findDevice(false, false, 'Server');
	$token = $server['Token'];
	$defaultParent = $server['Id'];

	$i = 0;
	foreach ($fc as $plugin) {
		write_log("Adding $plugin");
		$deviceId = base64_encode($plugin);
		$urls["fc_${deviceId}_cast"] = ["$plugin/cast/clients?X-Plex-Token=$token", ['Accept: application/json']];
		$i++;
	}

	$results = false;
	if (count($urls)) {
		$results = (new multiCurl($urls))->process();
	}
	$version = false;
	$hasPlugin = $_SESSION['hasPlugin'] ?? false;
	if ($results) {
		write_log("Results: " . json_encode($results));
		foreach ($serverArray as &$device) {
			$name = $device["Name"];
			$id = $device['Id'];
			$token = $device['Token'];
			$cast = $results["${id}_cast"] ?? false;
			$dvr = $results["${id}_dvr"]['MediaContainer']['Dvr'] ?? false;
			if ($cast) unset($results["${id}_cast"]);
			if ($dvr) unset($results["${id}_dvr"]);
			if ($cast) {
				$device['hasPlugin'] = true;
				write_log("Cast devices found for $name: " . json_encode($cast));
				$devVersion = $cast['version'] ?? false;
				if ($devVersion) {
					if (!$version || $devVersion > $version) $version = $devVersion;
				}

				if (!$hasPlugin) updateUserPreference('hasPlugin', true);
				$castDevices = $cast['MediaContainer']['Device'] ?? [];
				foreach ($castDevices as $castDevice) {
					if (isset($castDevice['name'])) {
						$type = $castDevice['type'];
						$type = ($type == 'audio' || $type == 'group' || $type == 'cast') ? 'Cast' : $type;
						$newDevice = [
							'Name' => $castDevice['name'],
							'Id' => $castDevice['id'],
							'Product' => $type,
							'Type' => $castDevice['type'],
							'Token' => $token,
							'Parent' => $id,
							'Uri' => $castDevice['uri']
						];
						array_push($clients, $newDevice);
					}
				}
			}

			if ($dvr) {
				write_log("DVR for $name: " . json_encode($dvr));
				$key = $dvr[0]['key'] ?? false;
				if ($key) {
					$device['key'] = $key;
					$lineup = explode("-", $dvr[0]['lineup'])[1] ?? false;
					$lineup = $lineup ? str_replace("OTA", "", explode("#", $lineup)[0]) : $lineup;
					$settings = $dvr[0]['Settings'];
					foreach ($settings as $setting) {
						switch ($setting['id']) {
							case 'startOffsetMinutes':
							case 'endOffsetMinutes':
							case 'comskipEnabled':
								$name = $setting['id'];
								$device["$name"] = intval($setting['value']);
						}
					}
					$device['zip'] = $lineup;
					array_push($dvrs, $device);
				}
			}
		}
	}

	if (count($results)) {
		write_log("We got our leftovers...");
		$cast = [];
		foreach($results as $name => $man) {
			if (preg_match("/fc_/", $name)) $cast = $man;
			$parent = base64_decode(explode("_", $name)[1]);
			if (count($cast)) {
				$device['hasPlugin'] = true;
				$devVersion = $cast['version'] ?? false;
				if ($devVersion) {
					if (!$version || $devVersion > $version) $version = $devVersion;
				}

				if (!$hasPlugin) updateUserPreference('hasPlugin', true);
				$castDevices = $cast['MediaContainer']['Device'] ?? [];
				foreach ($castDevices as $castDevice) {
					if (isset($castDevice['name'])) {
						$type = $castDevice['type'];
						$type = ($type == 'audio' || $type == 'group' || $type == 'cast') ? 'Cast' : $type;
						$newDevice = [
							'Name'    => $castDevice['name'],
							'Id'      => $castDevice['id'],
							'Product' => $type,
							'Type'    => $castDevice['type'],
							'Token'   => $token,
							'Parent'  => $parent,
							'Uri'     => $castDevice['uri']
						];
						array_push($clients, $newDevice);
					}
				}
			}
		}
	}

	if ($hasPlugin && $version <"1.1.105") {
		write_log("No version number detected!");
		$message = "Your cast plugin is out of date. Please install the latest version for proper functionality.";
		$alert = [
			[
				'title'   => 'Cast Plugin out-of-date!',
				'message' => $message,
				'url'     => "https://github.com/d8ahazard/Cast.bundle"
			]
		];
		writeSession('messages', $alert);
	} else {
		write_log("We have a version number - '$version'");
	}

	if (count($clients) || count($dvrs)) {
		$returns = [
			'Client' => $clients,
			'Dvr'    => $dvrs,
			'Server' => $serverArray
		];
	} else $returns = false;
	$clientCount = count($clients);
	$dvrCount = count($dvrs);
	$serverCount = count($serverArray);
	write_log("Found $clientCount clients and $dvrCount dvrs from $serverCount servers: " . json_encode($returns));

	return $returns;
}

function selectDevices($results) {
	$output = [];
	foreach ($results as $class => $devices) {
		$classOutput = [];
		$sessionId = $_SESSION["plex" . $class . "Id"] ?? false;
		$clientName = false;
		if ($class === 'client') $clientName = $_SESSION['plexClientName'] ?? false;
		$i = 0;
		$selected = false;
		foreach ($devices as $device) {
			if ($sessionId) {
				if ($device['Id'] == $sessionId) {
					$device['Selected'] = true;
					$selected = true;
				} else {
					$device['Selected'] = false;
				}
			} else {
				if ($i === 0) {
					write_log("No selected $class currently, picking one.", "WARN", false, true);
					setSelectedDevice($class, $device['Id']);
				}
				$device['Selected'] = (($i === 0) ? true : false);
			}
			array_push($classOutput, $device);
			$i++;
		}
		if ($clientName && !$selected) {
			write_log("No selected device, trying to select by client name.", "INFO", false, true);
			foreach ($classOutput as &$device) {
				if ($device['Name'] === $clientName) {
					$id = $device['Id'];
					$device['Selected'] = true;
					setSelectedDevice($class, $id);
				}
			}
		}
		$output[$class] = $classOutput;
	}
	return $output;
}

function setSelectedDevice($type, $id) {
	write_log("Function fired.");
	$list = $_SESSION['deviceList'] ?? [];
	$selected = false;

	if ($type === 'Broadcast') {
		write_log("Setting broadcast device.");
		updateUserPreference('broadcastDevice', $id);
		return [$id];
	}

	foreach ($list[$type] as $device) {
		write_log("Comparing $id to " . $device['Id']);
		if (trim($id) === trim($device['Id'])) {
			$selected = $device;
			if (!isset($selected['Parent']) && $type == 'Client') {
				$selected['Parent'] = $_SESSION['plexServerId'];
			}
		}
	}

	if (!$selected && count($list[$type])) {
		write_log("Unable to find selected device in list, defaulting to first item.", "WARN");
		$selected = $list[$type][0];
	}

	if (is_array($selected)) {
		$new = $push = [];
		foreach ($list[$type] as $device) {
			$device['Selected'] = ($device['Id'] === $id) ? true : false;
			array_push($new, $device);
		}
		$list[$type] = $new;
		write_log("Going to select " . $selected['Name']);
		writeSessionArray([['deviceList' => $list, 'deviceUpdated' => true]]);
		if ($type == 'Client' && isset($_SESSION['volume'])) writeSession('volume', "", true);
		$push['jsonDeviceArray'] = json_encode($list);
		$push["plex$type" . "Id"] = $selected['Id'];
		updateUserPreferenceArray($push);
	} else {
		if ($type == 'Dvr') {
			$dvr = $_SESSION['plexDvrId'] ?? false;
			if ($dvr) updateUserPreference('plexDvrId', false);
		}
	}
	return $list;
}

function sortDevices($input) {
	write_log("Input list: " . json_encode($input));
	$results = [];
	foreach ($input as $class => $devices) {
		$names = $output = [];
		foreach ($devices as $device) {
			$push = true;
			$devName = $device['Name'];
			$devId = $device['Id'];
			$devProduct = $device['Product'];
			$devType = $device['Type'] ?? false;
			$devUri = $device['Uri'] ?? false;
			$devIp = true;
			if ($devUri) {
				$devIp = parse_url($devUri)['host'] ?? true;
			}
			foreach ($output as $existing) {
				$exIp = parse_url($existing['Uri'])['host'] ?? false;
				$exId = $existing['Id'];
				$exName = $existing['Name'];
				$exProduct = $existing['Product'];
				$exType = $existing['Type'] ?? false;

				$isCast = ($devProduct === 'Cast');

				$idMatch = ($devId === $exId);
				$ipMatch = ($devIp === $exIp);
				$prodMatch = ($devProduct === $exProduct);
				$typeMatch = ($devType === $exType);

				if ($ipMatch) {
					if ($exProduct !== "Cast" && $isCast) {
						write_log("Skipping $devName ($devType) because native client beats Cast.");
						$push = false;
					}
					if ($prodMatch && $typeMatch) {
						write_log("Skipping $devName ($devType) because matching product.");
						$push = false;
					}
				}
				if ($idMatch) {
					write_log("Skipping $devName ($devType) because ID matches with $exName ($exType).", "INFO");
					$push = false;
				}
			}
			$duplicateCount = array_count_values($names)[$devName] ?? false;
			$displayName = $duplicateCount ? "$devName ($duplicateCount)" : $devName;
			if ($push) {
				if ($class == 'Client') {
					$new = [
						'Name'         => $devName,
						'FriendlyName' => $displayName,
						'Id'           => $device['Id'],
						'Product'      => $device['Product'],
						'Type'         => 'Client',
						'Token'        => $device['Token'] ?? $_SESSION['plexToken']
					];
					if (isset($device['Uri'])) $new['Uri'] = $device['Uri'];
					if (isset($device['Parent'])) $new['Parent'] = $device['Parent'];
				} else {
					$new = [
						'Name'         => $devName,
						'FriendlyName' => $displayName,
						'Id'           => $device['Id'],
						'Uri'          => $device['uri'],
						'Token'        => $device['Token'],
						'Product'      => $device['Product'],
						'Type'         => $class,
						'Key'          => $device['key'] ?? false,
						'Owned'        => $device['Owned'] ?? false,
						'Version'      => $device['Version']
					];
					if ($class === 'Server') {
						$extras = fetchServerData($new);
						$new['Sections'] = json_encode($extras['Sections']);
						if (is_array($extras['Stations'])) {
							$new['Stations'] = json_encode($extras['Stations']);
						}
						$new['HasPlugin'] = $device['hasPlugin'] ?? false;
					}
					if (($class !== "Dvr") && (isset($device['localUri']))) $new['localUri'] = $device['localUri'];
				}
				array_push($names, $devName);
				array_push($output, $new);
			}
		}
		$results[$class] = $output;
	}
	return $results;
}

function updateDeviceCache($data) {
	$now = microtime(true);
	$list = $_SESSION['deviceList'] ?? [];
	$updated = [];
	foreach ($data as $section => $devices) {
		$removeCurrent = true;
		$selected = $_SESSION["plex" . $section . "Id"];
		$sectionOut = [];
		$existing = $list[$section] ?? [];
		foreach ($devices as $device) {
			$device['last_seen'] = $now;
			$out = $device;
			$merged = false;
			$push = true;
			foreach ($existing as $check) {
				if ($device['Id'] === $check['Id'] && $device['Product'] === $check['Product']) {
					$out = array_merge($check, $device);
					$merged = true;
				}
			}
			$out['presence'] = $merged;
			if (!$merged) {
				$last = $out['last_seen'];
				$diff = ($now - $last) / 60 / 60;
				if ($diff >= 24) {
					write_log("Device hasn't been seen in 24 hours, dropping from cache.", "WARN");
					$push = false;
				}
			}
			if ($push) {
				if ($selected == $out['Id']) $removeCurrent = false;
				$sectionOut[] = $out;
			}
		}
		if ($removeCurrent) setSelectedDevice($section, "foo");
		$updated["$section"] = $sectionOut;
	}
	$devices = $updated;
	write_log("Diff: " . json_encode(array_diff_assoc_recursive($list, $devices)));
	writeSession('deviceList', $devices);
	writeSession('deviceUpdated', true);
	$string = json_encode($devices);
	$prefs = [
		'lastScan' => $now,
		'jsonDeviceArray'    => $string
	];
	updateUserPreferenceArray($prefs);
}


//**
// GRAB DATA FROM VARIOUS ENDPOINTS
// */

function fetchAirings($params) {
	write_log("Function fired!");
	$list = [];
	$startDate = new DateTime('today');
	$endDate = new DateTime('tomorrow');
	$date = $params['date'] ?? false;
	$times = $params['time-period'] ?? false;
	if ($date) {
		$startDate = new DateTime("$date");
		$endDate = new DateTime("$date +1 day");
	}
	if ($times) {
		$times = explode("/", $times);
		$startTime = DateInterval::createFromDateString($times[0]);
		$endTime = DateInterval::createFromDateString($times[1]);
		$startDate = $startDate->add($startTime);
		$endDate = $endDate->add($endTime);
	}
	$date2 = $endDate->format('Y-m-d');
	$date1 = $startDate->format('Y-m-d');
	write_log("StartDate: $date1 EndDate: " . $date2);
	if ($_SESSION['sickEnabled'] ?? false) {
		write_log("Checking sickrage for episodes...");
		$sick = new SickRage($_SESSION['sickUri'], $_SESSION['sickToken']);
		try {
			$scheduled = json_decode($sick->future('date', 'today|soon'), true);
		} catch (\Kryptonit3\SickRage\Exceptions\InvalidException $e) {
			write_log("There was an exception - '$e'", "ERROR");
			$scheduled = false;
		}
		if ($scheduled) {
			$shows = $shows2 = [];
			if (isset($scheduled['data']['soon'])) {
				$shows = $scheduled['data']['soon'];
			}
			if (isset($scheduled['data']['today'])) {
				$shows2 = $scheduled['data']['today'];
			}
			$shows = array_merge($shows, $shows2);
			if (is_array($shows)) {
				foreach ($shows as $show) {
					$airDate = DateTime::createFromFormat('Y-m-d', $show['airdate']);
					if ($airDate >= $startDate && $airDate <= $endDate) {
						$showName = $show['show_name'];
						$showName = preg_replace("/\ \(\d{4}\)/", "", $showName);
						$item = [
							'title'     => $showName,
							'epnum'     => $show['episode'],
							'seasonnum' => $show['season'],
							'summary'   => $show['ep_plot'],
							'type'      => 'episode',
							'source'    => 'sick'
						];
						write_log("Found a show on sick: " . json_encode($item), "INFO");
						array_push($list, $item);
					}
				}
			}
		}
	}
	if ($_SESSION['sonarrEnabled'] ?? false) {
		write_log("Checking Sonarr for episodes...");
		$sonarr = new Sonarr($_SESSION['sonarrUri'], $_SESSION['sonarrAuth']);
		$scheduled = json_decode($sonarr->getCalendar($date1, $date2), true);
		if ($scheduled) {
			foreach ($scheduled as $show) {
				$item = [
					'title'     => $show['series']['title'],
					'epnum'     => $show['episodeNumber'],
					'seasonnum' => $show['seasonNumber'],
					'summary'   => $show['overview'] ?? $show['series']['overview'],
					'type'      => 'episode',
					'source'    => 'sonarr'
				];
				write_log("Found a show on Sonarr: " . json_encode($item), "INFO");
				array_push($list, $item);
			}
		}
	}
	if ($_SESSION['plexDvrId'] ?? false) {
		write_log("Checking DVR for episodes...");
		$dvr = findDevice(false, false, 'Dvr');
		$data = doRequest([
			'uri'   => $dvr['Uri'],
			'path'  => "/media/subscriptions/scheduled",
			'query' => "?X-Plex-Token=" . $dvr['Token']
		], 5);
		$schedule = (new JsonXmlElement($data))->asArray();
		$items = $schedule['MediaContainer']['MediaGrabOperation'] ?? [];

		foreach ($items as $showItem) {
			$show = false;
			write_log("Looping for a show item: " . json_encode($showItem));
			if ($showItem['status'] === 'scheduled') {
				$series = $showItem['Video'][0]['Media'] ?? [];
				foreach ($series as $recording) {
					$date = $recording['beginsAt'];
					$airDate = new DateTime("@$date");
					if ($airDate >= $startDate && $airDate <= $endDate) {
						$show = $showItem['Video'][0];
						break;
					}
				}
				if ($show) {
					write_log("This item is airing: " . json_encode($show));
					$item = [
						'title'     => $show['grandparentTitle'],
						'epnum'     => intval($show['index']),
						'seasonnum' => intval($show['parentIndex']),
						'summary'   => $show['summary'],
						'source'    => $_SESSION['plexDvrId'],
						'type'      => 'episode'
					];
					write_log("Found a show on Plex DVR: " . json_encode($item), "INFO");
					array_push($list, $item);
				} else {
					write_log("This item doesn't appear to be airing...");
				}
			}
		}

	}
	foreach ($list as &$item) {
		$data = curlGet("https://search.flextv.media/search.php?query=" . urlencode($item['title']) . "&type=show&key=" . keyGen());
		if ($data) {
			$data = json_decode($data, true)[0] ?? false;
		}
		if ($data) write_log("Item data? : " . json_encode($data));
		$item = $data;
	}
	$list = (count($list) ? $list : false);
	write_log("Final airings list: " . json_encode($list));
	return $list;
}

function fetchApiAiData($command) {
	$context = $_SESSION['context'] ?? false;
	$d = fetchDirectory(3);
	$sessionId = $_SESSION['sessionId'] ?? rand(10000, 100000);
	writeSession('sessionId', $sessionId);
	try {
		$dialogFlow = new dialogFlow($d, getLocale(), 1, "$sessionId");
		$response = $dialogFlow->query($command, null, $context);
		$json = json_decode($response, true);
		if (is_null($json)) {
			write_log("Error parsing API.ai response.", "ERROR");
			return false;
		}
		$request = $dialogFlow->process($json);
	} catch (Exception $e) {
		write_log("There was an exception - '$e'", 'ERROR');
		$request = false;
	}


	return $request;
}

function fetchFirstUnwatchedEpisode($key, $parent = false) {
	$server = $parent ? $parent : findDevice(false, false, "Server");
	$uri = $server['Uri'];
	$token = $server['Token'];
	$mediaDir = preg_replace('/children$/', 'allLeaves', $key);
	$result = doRequest([
		'uri'   => $uri,
		'path'  => $mediaDir,
		'query' => "?X-Plex-Token=$token"
	]);
	if ($result) {
		$container = (new JsonXmlElement($result))->asArray();
		write_log("Container: " . json_encode($container));
		$videos = $container['MediaContainer']['Video'];
		foreach ($videos as $video) {
			$count = $video['viewcount'] ?? 0;
			if ($count == 0) {
				$video['art'] = $container['art'];
				$video['librarySectionID'] = $container['librarySectionID'];
				return $video;
			}
		}
		// If no unwatched episodes, return the first episode
		if (!$videos) {
			$video = $videos[0];
			$video['art'] = $container['art'];
			$video['librarySectionID'] = $container['librarySectionID'];
			return $video;
		}
	}
	return false;
}

function fetchHubList($section, $type = false) {
	$path = $results = false;
	write_log("Section is $section, type is $type.");
	$query = [];
	$serverId = $_SESSION['plexServerId'];
	$count = $_SESSION['returnItems'] ?? 6;
	$host = findDevice("Id", $serverId, "Server");
	if ($section == 'recent') {
		$path = '/hubs';
		if ($type) {
			$type = explode(".", $type)[0];
			$types = [
				"show"  => '2',
				'movie' => '1',
				'music' => '8'
			];
			$type = $types["$type"] ?? false;
			$path = $type ? '/hubs/home/recentlyAdded' : $path;
			$query = ['type' => $type];
		}
	}
	if ($section == 'ondeck') $path = '/hubs/home/onDeck';
	if ($path) {
		$query = array_merge($query, [
			'X-Plex-Token'           => $host['Token'],
			'X-Plex-Container-Start' => '0',
			'X-Plex-Container-Size'  => $_SESSION['returnItems'] ?? '6'
		]);
		$result = doRequest([
			'uri'   => $host['Uri'],
			'path'  => $path,
			'query' => $query
		]);
		if ($result) {
			$container = (new JsonXmlElement($result))->asArray();
			write_log("Hub response: " . json_encode($container));
			if ($section == 'recent' && !$type) {
				$hubs = $container['MediaContainer']['Hub'];
				$results = [];
				foreach ($hubs as $hub) {
					if ($hub['hubIdentifier'] == 'home.movies.recent' ||
						$hub['hubIdentifier'] == 'home.television.recent' ||
						$hub['hubIdentifier'] == 'home.music.recent') {
						write_log("Merging videos from " . $hub['hubIdentifier']);
						foreach ($hub['Video'] as $video) array_push($results, $video);
					}
				}
				write_log("Results: " . json_encode($results));
				$results = shuffle_assoc($results);
				write_log("Results: " . json_encode($results));
			} else {
				$results = $container['MediaContainer']['Video'] ?? $container['MediaContainer']['Directory'] ?? false;
			}
			if (is_array($results) && count($results) > $count) $results = array_slice($results, 0, $count);
		} else {
			write_log("Error retrieving result.", "ERROR");
		}
	} else {
		write_log("Unable to create path.", "ERROR");
	}
	$out = [];

	foreach ($results as $result) {
		$result['source'] = $serverId;
		$out[] = $result;
	}
	write_log("Final output: " . json_encode($out));
	return $out;
}

function fetchLatestEpisode($key) {
	$last = false;
	$mediaDir = preg_replace('/children$/', 'allLeaves', $key);
	$server = findDevice(false, false, 'Server');
	$result = doRequest([
		'uri'   => $server['Uri'],
		'path'  => $mediaDir,
		'query' => '?X-Plex-Token=' . $server['Token']
	]);
	if ($result) {
		$container = new SimpleXMLElement($result);
		if (isset($container->Video)) foreach ($container->Video as $episode) {
			$last = $episode;
		}
	}
	return $last;
}

function fetchMediaExtra($ratingKey, $returnAll = false) {
	$server = findDevice(false, false, 'Server');
	$result = doRequest(['path' => "/library/metadata/$ratingKey?X-Plex-Token=" . $server['Token']]);
	if ($result) {
		$extras = json_decode(json_encode(new SimpleXMLElement($result)), true);
		if ($returnAll) return $extras;
		$extra = $extras['Video']['@attributes'] ?? $extras['Directory']['@attributes'];
		return $extra;
	} else {
		write_log("No media extra found.", "WARN");
	}
	return false;
}

function fetchNowPlaying($server=false) {
	$result = $urls = [];
	$servers = $_SESSION['deviceList']['Server'] ?? [];
	foreach ($servers as $server) {
		if ($server['Owned']) $urls[] = $server['Uri'] . "/status/sessions?X-Plex-Token=" . $server['Token'];
	}
	#TODO: Finish writing this
//    $sessions = count($urls) ? new multiCurl($urls) : false;
//    if ($sessions) $sessions = $sessions->process();
//    foreach ($sessions as $serverSession) {
//        $serverSession = new JsonXmlElement($serverSession);
//        $serverSession = $serverSession->asArray();
//        if (is_array($serverSession)) $sessionList = $serverSession['MediaContainer'] ?? false;
//    }
	return $result;
}

function fetchTtsFile($text) {

	$headers = [
		"Pragma: no-cache",
		"Access-Control-Request-Method: POST",
		"Origin: https://soundoftext.com",
		"Accept-Encoding: gzip, deflate, br",
		"Accept-Language: en-US,en;q=0.9",
		"User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36",
		"Accept: */*",
		"Cache-Control: no-cache",
		"Referer: https://soundoftext.com/",
		"Connection: keep-alive",
		"Access-Control-Request-Headers: content-type"
	];

	$words = substr($text, 0, 2000);

	write_log("Building speech for '$words'");
	$payload = [
		"engine" => "Google",
		"data"   => [
			"text"  => $text,
			"voice" => "en-US"
		]
	];

	// Request OPTIONS
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.soundoftext.com/sounds");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "OPTIONS");
	curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_exec($ch);
	curl_close($ch);

	// Get ID for generated speech file
	$headers[] = "Content-Type: application/json";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.soundoftext.com/sounds");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$result = curl_exec($ch);
	curl_close($ch);

	$data = $result ? json_decode($result, true) : [];
	write_log("Raw result: $result");
	if (isset($data['success'])) {
		if ($data['id'] ?? false) {
			$id = $data['id'];
			$location = "https://soundoftext.nyc3.digitaloceanspaces.com/$id.mp3";
			return $location;
		}
	}

	return false;
}

function fetchNumberedTVItem($seriesKey, $num, $epNum = false, $parent = false) {
	$selector = $epNum ? 'season' : 'episode';
	$match = false;
	write_log("Searching for " . $selector . " number " . $num . ($epNum ? ' and episode number ' . $epNum : ''), "INFO");
	$mediaDir = preg_replace('/children$/', 'allLeaves', $seriesKey);
	$server = findDevice(false, false, 'Server');
	$host = $parent ? $parent['Uri'] : $server['Uri'];
	$token = $parent ? $parent['Token'] : $server['Token'];
	$url = "$host$mediaDir?X-Plex-Token=$token";
	$result = curlGet($url);
	if ($result) {
		$episodes = $result['MediaContainer']['Video'];
		if ($selector == "season") {
			foreach ($episodes as $episode) {
				if ($epNum) {
					if (($episode['parentIndex'] == $num) && ($episode['index'] == $epNum)) {
						$match = $episode;
						break;
					}
				} else {
					if ($episode['parentIndex'] == $num) {
						$match['index'] = $episode['parentIndex'];
						break;
					}
				}
			}
		} else {
			$episode = $episodes[intval($num) - 1] ?? false;
			if ($episode) {
				$match = $episode;
			}
		}
	}
	if ($match) {
		$match = mapDataPlex($match);
		write_log("Found match: " . json_encode($match), "INFO");
	} else write_log("NO MATCH FOUND.");
	return $match;
}

function fetchPlayerState($wait = false) {
	$result = false;
	$timeout = $wait ? 5 : 1;
	$status = ['status' => 'idle'];
	$client = findDevice(false, false, 'Client');
	$serverId = $client['Parent'] ?? $_SESSION['plexServerId'];
	$server = findDevice("Id", $serverId, "Server");
	$serverUri = $server['Uri'] ?? false;
	if ($serverUri) {
		$count = $_SESSION['counter'] ?? 1;
		$headers = clientHeaders($server, $client);
		$headers = array_unique($headers);
		$params = headerQuery($headers);
		if ($client['Product'] == 'Cast') {
			$uri = urlencode($_SESSION['plexClientId']);
			$url = "$serverUri/chromecast/status?X-Plex-Clienturi=$uri&X-Plex-Token=" . $client['Token'];
		} else {
			$url = "$serverUri/player/timeline/poll?wait=1&commandID=$count$params";
		}
		write_log("URL is '$url'", "INFO", false, true);
		$result = doRequest($url, $timeout);
	} else {
		write_log("Error fetching Server info: " . json_encode($server), "ERROR", false, true);
	}
	if ($result) {
		$result = (new JsonXmlElement($result))->asArray();
		write_log("Player JSON: " . json_encode($result), "INFO", false, true);
		if (isset($result['Timeline'])) {
			foreach ($result['Timeline'] as $timeline) {
				$id = $timeline['machineIdentifier'];
				$sessionId = $_SESSION['plexClientId'];
				if ($id == $sessionId) {
					$state = strtolower($timeline['state']);
					$volume = $timeline['volume'];
					$status = [
						'state'  => $state,
						'volume' => intval($volume) / 100
					];
				}
			}
		} else {
			$status = [
				'state'  => strtolower($result['state']),
				'volume' => $result['volume'],
				'muted'  => $result['muted']
			];
		}
	}
	write_log("Returning player status: " . json_encode($status), "INFO", false, true);
	return $status;
}

function fetchPlayerStatus() {
	$client = findDevice(false, false, 'Client');
	$addresses = parse_url($client['Uri']);
	$server = findDevice(false, false, 'Server');
	$host = $client['Parent'] ?? $server['Id'];
	$clientIp = $addresses['host'] ?? true;
	$clientId = $client['Id'];
	$state = 'idle';
	$status = ['status' => $state];
	$host = findDevice("Id", $host, "Server");
	$url = $host['Uri'] . '/status/sessions?X-Plex-Token=' . $host['Token'];
	$result = ($host['Owned'] ?? false) ? curlGet($url) : false;
	if ($result) {
		$mc = $result['MediaContainer'] ?? false;
		if ($mc) {
			$track = $mc['Track'] ?? [];
			$video = $mc['Video'] ?? [];
			$obj = array_merge($track, $video);
			foreach ($obj as $media) {
				$player = $media['Player'][0];
				$product = $player['product'] ?? false;
				$playerId = $player['machineIdentifier'] ?? false;
				$playerIp = $player['address'] ?? true;
				$streams = $media['Media'][0]['Part'][0]['Stream'];
				$castDevice = (($product === 'Plex Chromecast' || $product === 'Plex Cast') && ($clientProduct = "cast"));
				$isCast = ($castDevice && ($clientIp == $playerIp));
				$isPlayer = (($clientId && $playerId) && ($clientId == $playerId));
				$state = 'idle';
				if ($isPlayer || $isCast) {
					$state = strtolower($player['state']);
					$time = $media['viewOffset'];
					$duration = $media['duration'];
					$type = $media['type'];
					$summary = $media['summary'] ?? $media['parentTitle'] ?? "";
					$title = $media['title'] ?? "";
					$year = $media['year'] ?? false;
					$tagline = $media['tagline'] ?? "";
					$parentTitle = $media['parentTitle'] ?? "";
					$grandParentTitle = $media['grandparentTitle'] ?? "";
					$parentIndex = $media['parentIndex'] ?? "";
					$index = $media['index'] ?? "";
					$thumb = (($media['type'] == 'movie') ? $media['thumb'] : $media['parentThumb']);
					$thumb = transcodeImage($thumb, $host);
					$art = transcodeImage($media['art'], $host, true);
					$mediaResult = [
						'title'            => $title,
						'parentTitle'      => $parentTitle,
						'grandParentTitle' => $grandParentTitle,
						'parentIndex'      => $parentIndex,
						'index'            => $index,
						'tagline'          => $tagline,
						'duration'         => $duration,
						'time'             => $time,
						'summary'          => $summary,
						'year'             => $year,
						'art'              => $art,
						'thumb'            => $thumb,
						'type'             => $type,
						'streams'          => $streams
					];
					$status = [
						'status'      => $state,
						'time'        => $time,
						'mediaResult' => $mediaResult,
						'volume'      => $_SESSION['volume'] ?? false
					];
				}
			}
		}
	}
	$currentState = $_SESSION['playerStatus'] ?? 'idle';
	if ($currentState !== $state || !(isset($_SESSION['volume']))) {
		writeSession('playerStatus', $state);
		$playerData = fetchPlayerState(true);
		$volume = 100;
		if (isset($playerData['volume'])) {
			$volume = $playerData['volume'] * 100;
			$status['volume'] = $volume;
		}
		writeSession("volume", $volume);
	}
	return $status;
}

function fetchPlayQueue($media, $shuffle = false, $returnQueue = false, $mediaId = false) {
	$key = $media['key'] ?? false;
	$queueID = $media['queueID'] ?? false;
	$isAudio = ($media['type'] == 'album' || $media['type'] == 'artist' || $media['type'] == 'track');
	$host = findDevice("Id", $media['source'], "Server");
	$sections = $host['Sections'] ?? false;
	$sections = $sections ? json_decode($sections, true) : false;
	$sectionId = $media['librarySectionID'] ?? false;
	$uuid = false;
	$typeCheck = ($isAudio ? 'artist' : ($media['type'] == 'movie' ? 'movie' : 'show'));
	$playlist = ($media['type'] === 'playlist');
	foreach ($sections as $section) {
		if ($sectionId) {
			if ($section['Id'] == $sectionId) $uuid = $section['uuid'];
		} else {
			if ($section['type'] == $typeCheck) $uuid = $section['uuid'];
		}
	}
	if ($mediaId) {
		$uri = urlencode("library://$uuid/directory//library/sections/$mediaId/all?type=$mediaId");
	} else {
		$uri = urlencode("library://$uuid/item/" . urlencode($key));
	}

	$query = [
		'type'                     => ($isAudio ? 'audio' : 'video'),
		'uri'                      => $uri,
		'continuous'               => 1,
		'shuffle'                  => $shuffle ? '1' : '0',
		'repeat'                   => 0,
		'own'                      => 1,
		'includeChapters'          => 1,
		'includeGeolocation'       => 1,
		'X-Plex-Client-Identifier' => $_SESSION['plexClientId']
	];

	if ($playlist) {
		unset($query['uri']);
		$query['playlistID'] = $media['ratingKey'];
	}
	$headers = clientHeaders($host);
	$result = doRequest([
		'uri'     => $host['Uri'],
		'path'    => '/playQueues' . ($queueID ? '/' . $queueID : ''),
		'query'   => array_merge($query, plexHeaders($host)),
		'type'    => 'post',
		'headers' => $headers
	]);
	if ($result) {
		$container = (new JsonXmlElement($result))->asArray();
		write_log("Response container from queue: " . json_encode($container));
		if ($returnQueue) return $container;
		$queueID = $container['playQueueID'] ?? false;
	} else {
		write_log("Error fetching queue ID!", "ERROR");
	}
	return $queueID;
}

function fetchPlayQueueAudio($media) {
	$response = $result = $sections = $song = $url = $uuid = false;
	$host = findDevice("Id", $media['source'], "Server");
	if ($host) $sections = json_decode($host['Sections'], true);
	if (is_array($sections)) foreach ($sections as $section) if ($section['type'] == "artist") $uuid = $section['uuid'];
	$ratingKey = $media['ratingKey'] ?? false;
	$ratingKey = $ratingKey ? urlencode($ratingKey) : false;
	$type = $media['type'] ?? false;
	if (($ratingKey) && ($type) && ($uuid)) {
		$url = $host['Uri'] . "/playQueues?type=audio&uri=library%3A%2F%2F" . $uuid . "%2F";
		switch ($type) {
			case 'album':
				$url .= "item%2F%252Flibrary%252Fmetadata%252F" . $ratingKey . "&shuffle=0";
				break;
			case 'artist':
				$url .= "item%2F%252Flibrary%252Fmetadata%252F" . $ratingKey . "&shuffle=1";
				break;
			case 'track':
				$url .= "directory%2F%252Fservices%252Fgracenote%252FsimilarPlaylist%253Fid%253D" . $ratingKey . "&shuffle=0";
				break;
			default:
				write_log("NOT A VALID AUDIO ITEM!", "ERROR");
				return false;
		}
	}
	if ($url) {
		$header = headerQuery(plexHeaders($host));
		$url .= "&repeat=0&includeChapters=1&includeRelated=1" . $header;
		write_log("URL: $url");
		$result = curlPost($url);
	}
	if ($result) {
		$container = (new JsonXmlElement($result))->asArray();
		if (isset($container['playQueueID'])) {
			write_log("Playqueue container: " . json_encode($container));
			$selectedOffset = intval($container['playQueueSelectedOffset'] ?? 0);
			$track = $container['MediaContainer']['Track'][$selectedOffset];
			$response = [
				'title'   => $track['title'],
				'artist'  => $track['grandparentTitle'],
				'album'   => $track['parentTitle'],
				'key'     => "library/metadata/" . $container['playQueueSelectedMetadataItemID'],
				'queueID' => $container['playQueueID']
			];
		} else {
			write_log("Error fetching play queue ID!!", "ERROR");
		}
	}
	return $response;
}

function fetchRandomMediaByKey($key) {
	$winner = false;
	$server = findDevice(false, false, 'Server');
	$result = doRequest([
		'path'  => $key,
		'query' => '&limit=30&X-Plex-Token=' . $server['Token']
	]);
	if ($result) {
		$matches = [];
		$container = new SimpleXMLElement($result);
		foreach ($container->children() as $video) {
			array_push($matches, $video);
		}
		$size = sizeof($matches);
		if ($size > 0) {
			$winner = rand(0, $size);
			write_log("Selecting random item $winner / $size.", "INFO");
			$winner = $matches[$winner];
			if ($winner['type'] == 'show') {
				$winner = fetchFirstUnwatchedEpisode($winner['key']);
			}
		}
	}
	if ($winner) {
		$item = json_decode(json_encode($winner), true)['@attributes'];
		return $item;
	}
	return false;
}

function shuffleShow($item) {
	write_log("Shuffling item: " . json_encode($item));
	$parent = findDevice('Id', $item['parent'], 'Server');
	$ratingKey = $item['ratingKey'];
	$sectionKey = false;
	$parentUri = $parent['Uri'];
	$sections = json_decode($parent['Sections'], true);
	foreach ($sections as $section) if ("/library/sections/" . $section['id'] === $item['sectionKey']) $sectionKey = $section['uuid'];
	if ($sectionKey) {
		$uri = urlencode("library://$sectionKey/item/" . urlencode("/library/metadata/$ratingKey"));
		$url = "$parentUri/playQueues?type=video&shuffle=1&continuous=1&uri=$uri";
		$url .= "&repeat=0&own=1&includeChapters=1&includeGeolocation=1" . headerQuery(plexHeaders($parent));
		write_log("URL: $url");
		$data = curlPost($url);

		if ($data) {
			$container = (new JsonXmlElement($data))->asArray();
			write_log("Container: " . json_encode($container));
			$queueId = $container['playQueueID'] ?? false;
			if ($queueId) {
				$item['queueID'] = $queueId;
				return $item;
			}
		} else {
			write_log("Error shuffling show item!", "ERROR");
		}
	}
	return false;
}

function fetchRandomMediaByCast($actor, $type = 'movie') {
	$section = false;
	$server = findDevice(false, false, 'Server');
	$sections = json_decode($server['Sections'], true);
	foreach ($sections as $check) if ($check['type'] === $type) $section = $check['id'];

	$actorKey = $result = false;
	if ($section) {
		$result = doRequest([
			'path'  => '/library/sections/' . $section . '/actor',
			'query' => '?X-Plex-Token=' . $server['Token']
		]);
	}

	if ($result) {
		$actors = (new JsonXmlElement($result))->asArray();
		write_log("MediaArray: " . json_encode($actors));
		foreach ($actors['MediaContainer']['Directory'] as $actors) {
			if ($actors['title'] == ucwords(trim($actor))) {
				$actorKey = $actors['fastKey'];
			}
		}
		if (!($actorKey)) {
			write_log("No actor key found, I should be done now.", "WARN");
			return false;
		}
	} else {
		write_log("No result found, I should be done now.", "WARN");
		return false;
	}
	$result = doRequest(['query' => $actorKey . '&X-Plex-Token=' . $server['Token']]);
	if ($result) {
		$container = (new JsonXmlElement($result))->asArray();
		$videos = $container['MediaContainer']['Video'] ?? $container['MediaContainer']['Directory'] ?? false;
		if (is_array($videos)) {
			write_log("Results: " . json_encode($videos));
			$winner = $videos[array_rand($videos)];
			write_log("Matching result: " . json_encode($winner), "INFO");
			$winner['source'] = $server['Id'];
			return $winner;
		} else {
			write_log("Error fetching random cast media.");
		}
	}
	return false;
}

function fetchRandomMediaByGenre($fastKey, $type = false) {
	$server = findDevice(false, false, 'Server');
	$result = doRequest(['path' => $fastKey . '&X-Plex-Token=' . $server['Token']]);
	if ($result) {
		$container = new SimpleXMLElement($result);
		$winners = [];
		foreach ($container->children() as $directory) {
			if (($directory['type'] == 'movie') && ($type != 'show')) {
				array_push($winners, $directory);
			}
			if (($directory['type'] == 'show') && ($type != 'movie')) {
				$media = fetchLatestEpisode($directory['title']);
				if ($media) array_push($winners, $media);
			}
		}
		$size = sizeof($winners);
		if ($size > 0) {
			$winner = rand(0, $size);
			$winner = $winners[$winner];
			write_log("Matching result: " . $winner['title'], "INFO");
			return $winner;
		}
	}
	return false;
}

function fetchRandomNewMedia($type) {
	$winner = false;
	$server = findDevice(false, false, 'Server');
	$result = doRequest(['path' => '/library/recentlyAdded' . '?X-Plex-Token=' . $server['Token']]);
	if ($result) {
		$matches = [];
		#TODO: This one too
		$container = new SimpleXMLElement($result);
		foreach ($container->children() as $video) {
			if ($video['type'] == $type) {
				array_push($matches, $video);
			}
			if (($video['type'] == 'season') && ($type == 'show')) {
				array_push($matches, $video);
			}
		}
		$size = sizeof($matches);
		if ($size > 0) {
			$winner = rand(0, $size - 1);
			$winner = $matches[$winner];
			if ($winner['type'] == 'season') {
				$result = fetchFirstUnwatchedEpisode($winner['parentKey'] . '/children');
				$winner = $result;
			}
		} else {
			write_log("Can't seem to find any random ${type}.", "WARN");
		}
	}
	if ($winner) {
		$item = json_decode(json_encode($winner), true)['@attributes'];
		$item['thumb'] = $server['Uri'] . $winner['thumb'] . "?X-Plex-Token=" . $server['Token'];
		$item['art'] = $server['Uri'] . $winner['art'] . "?X-Plex-Token=" . $server['Token'];
		$winner = [$item];
	}
	return $winner;
}


function shuffleMedia($type = false) {
	$server = findDevice(false, false, "Server");
	$sections = json_decode($server['Sections'], true);
	$queue = [];
	switch ($type) {
		case 'movie':
		case 'show':
			$id = $uuid = false;
			foreach ($sections as $section) if ($section['type'] === $type) {
				$id = $section['id'];
				$uuid = $section['uuid'];
			}
			if ($id && $uuid) {
				$media = [
					'type'             => $type,
					'librarySectionID' => $uuid,
					'source'           => $server['Id']
				];
				$queue = fetchPlayQueue($media, true, true, $id);
			}

			break;
		case 'music':
			break;
		case 'playlist':
			break;
		case 'podcast':
			break;
		case 'news':
			break;
		default:
			break;
	}
	if (count($queue)) $queue['type'] = 'queue';
	return $queue;
}

function fetchServerData($server = false) {
	$server = $server ? $server : findDevice(false, false, 'Server');
	$sections = [];
	$stations = false;
	$uri = $server['Uri'];
	$token = $server['Token'];
	$url = "$uri/library/sections?includeStations=1&includeRelated=1&X-Plex-Token=$token";
	$results = curlGet($url, ['Accept: application/json']);
	if ($results) {
		$container = $results['MediaContainer']['Directory'] ?? false;
		if ($container) {
			foreach ($container as $section) {
				array_push($sections, [
					"id"   => (string)$section['key'],
					"uuid" => (string)$section['uuid'],
					"type" => (string)$section['type']
				]);
				if ($section['Stations'] ?? false) $stations = $section['Stations'];
			}
		} else {
			write_log("Error retrieving section data!", "ERROR");
		}
	}

	if (count($sections)) {
		writeSession('sections', $sections);
	}

	if ($stations) {
		$items = [];
		foreach ($stations['Metadata'] as $station) {
			$items[] = [
				'title' => $station['title'],
				'type'  => $station['type'],
				'key'   => $station['key'],
				'thumb' => transcodeImage($stations['thumb'], $server),
				'art'   => transcodeImage($stations['art'], $server)
			];
		}
		$stations = $items;
	}
	return ['Sections' => $sections, 'Stations' => $stations];

}


function fetchSuggestedMedia($data) {
	$results = $topMedia = $userId = false;
	$types = [
		'movie' => 1,
		'episode' => 2,
		'track' => 8
	];

	write_log("Fetching suggested media: ".json_encode($data));
	$type = $data['type'] ?? 'movie';
	$typeInt = $types[$type] ?? 1;
	$server = findDevice(false, false, "Server");
	$uri = $server['Uri'];
	$token = $server['Token'];
	$userData = curlGet("$uri/accounts?X-Plex-Token=$token");
	$currentUser = fetchUserData();
	if ($userData) {
		$users = $userData['MediaContainer']['Account'];
		foreach($users as $user) {
			if ($user['name'] === $currentUser['plexUserName']) {
				$userId = $user['id'];
			}
		}
	}

	if ($userId) {
		$int = strtotime("-1 week");
		$topUrl = "$uri/library/all/top?accountID=$userId&type=$typeInt&viewedAt%3E=$int&limit=10&X-Plex-Token=$token";
		$topMedia = curlGet($topUrl);
	}
	$ids = [];
	if ($topMedia) {
		write_log("We have top media: ".json_encode($topMedia));
		$mc = $topMedia['MediaContainer'];
		$topMedia = $mc['Video'] ?? $mc['Directory'] ?? $mc['Audio'];
		foreach($topMedia as $media) {
			$title = $media['title'];
			$key = $media['ratingKey'];
			$url = "$uri/hubs/metadata/$key/related?includeExternalMetadata=1&asyncAugmentMetadata=1&X-Plex-Token=$token";
			write_log("URL: $url");
			$ids[$title] = $url;
		}
	}

	if (count($ids)) {
		write_log("We have url's, getting some recommendations: ".json_encode($ids));
		$mc = (new multiCurl($ids))->process();
		write_log("MC Result: ".json_encode($mc));
		foreach($mc as $mTitle => $data) {
			$top = false;
			$hubs = $data['MediaContainer']['Hub'];
			foreach($hubs as $hub) {
				if ($hub['hubIdentifier'] === 'external.movie.similar') {
					$top = $hub;
				}
			}

			if ($top) {
				foreach($top as $cat => $items) {
					foreach($items as $item) {
						$item['reason'] = $mTitle;
						write_log("Pushing item: ".json_encode($item));
						$results[] = $item;
					}
				}
			}
		}
	}

	return $results;
}


function fetchTransientToken($host = false, $type = false) {
	$host = $host ? $host : findDevice(false, false, "Server");
	$header = headerQuery(plexHeaders($host));

	$url = $host['Uri'] . '/security/token?type=delegation&scope=all' . $header;
	$result = curlGet($url);
	if ($result) {
		$ttoken = (string)$result['token'];
		if ($ttoken) {
			return $ttoken;
		} else {
			write_log("Error fetching transient token.", "ERROR");
		}
	}
	return false;
}

function fetchTracks($ratingKey) {
	$playlist = $queue = false;
	$server = findDevice(false, false, 'Server');
	$result = doRequest(['path' => '/library/metadata/' . $ratingKey . '/allLeaves?X-Plex-Token=' . $server['Token']]);
	$data = [];
	if ($result) {
		$container = new SimpleXMLElement($result);
		foreach ($container->children() as $track) {
			$trackJSON = json_decode(json_encode($track), true);
			if (isset($track['ratingCount'])) {
				if ($track['ratingCount'] >= 1700000) array_push($data, $trackJSON['@attributes']);
			}
		}
	}
	usort($data, "cmp");
	foreach ($data as $track) {
		if (!$queue) {
			$queue = fetchPlayQueue($track);
		} else {
			$track['queueId'] = $queue;
			fetchPlayQueue($track);
		}
	}
	return $playlist;
}


//**
// Send data out
//  */

function sendCommand($cmd, $value = false) {
	if (preg_match("/stop/", $cmd)) sendWebHook(false, "Stop");
	if (preg_match("/pause/", $cmd)) sendWebHook(false, "Paused");
	$client = findDevice(false, false, 'Client');
	$id = $client['Parent'] ?? $_SESSION['plexServerId'];
	$server = findDevice('Id', $id, 'Server');

	write_log("Sending command $cmd to client: " . json_encode($client));
	if (preg_match("/Cast/", $client['Product'])) {
		$cmd = strtolower($cmd);
		$result = sendCommandCast($cmd, $value);
	} else {
		if ($cmd === 'next') $cmd = 'skipNext';
		//TODO: Volume command for non-cast devices
		$url = $server['Uri'] . '/player/playback/' . $cmd . '?type=video&commandID=' . $_SESSION['counter'] . headerQuery(clientHeaders($server, $client));
		$result = doRequest($url);
		writeSession('counter', $_SESSION['counter'] + 1);
	}
	if ($cmd === 'volume') writeSession("volume", $value);
	$result['cmd'] = $cmd;
	if ($value) $result['value'] = $value;
	return $result;
}

function sendCommandCast($cmd, $value = false) {
	write_log("Sending cast command '$cmd'");
	// Set up our cast device
	if ("volume" == $cmd) {
		$value = $value ? intval($value) : filter_var($cmd, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
	}
	$valid = true;
	switch ($cmd) {
		case "play":
		case "pause":
		case "stepforward":
		case "stop":
		case "previous":
		case "skipforward":
		case "next":
		case "volume":
		case "volume.down":
		case "volume.up":
		case "volume.mute":
		case "volume.unmute":
		case "seek":
			break;
		default:
			$return['status'] = 'error';
			$valid = false;
	}
	if ($valid) {
		$client = findDevice(false, false, 'Client');
		$host = findDevice("Id", $client['Parent'], 'Server');
		$url = $host['Uri'] . "/chromecast/cmd?X-Plex-Token=" . $host['Token'];
		$headers = [
			'Uri' => $client['Id'],
			'Cmd' => $cmd
		];
		if ($value) $headers['Val'] = $value;
		$header = headerRequestArray($headers);
		write_log("Headers: " . json_encode($headers));
		$result = curlGet($url, $header);
		if ($result) {
			$return['url'] = "No URL";
			$return['status'] = 'success';
			return $return;
		}
	}
	$return['status'] = 'error';
	return $return;
}

function sendCommandRecord($command) {
	write_log("Function fired.");
	$server = findDevice(false, false, 'Dvr');
	$ip = $server['Uri'];
	$token = $server['Token'];
	$key = $server['Key'];
	$query = urlencode($command);
	$url = "$ip/tv.plex.providers.epg.onconnect:${key}/hubs/search?sectionId=&query=$query&X-Plex-Token=$token";
	write_log("Url is: " . $url);
	$data = curlGet($url);
	$results = [];
	if ($data) {
		$container = (new JsonXmlElement($data))->asArray()['MediaContainer']['Hub'] ?? false;
		write_log("Result is: " . json_encode($container));
		if ($container) foreach ($container as $hub) {
			write_log("Hubba hubba: " . json_encode($hub));
			$type = $hub['type'];
			if (($type == 'show') || ($type == 'movie')) {
				$items = $hub['Directory'];
				foreach ($items as $item) {
					$return = [
						'title'   => $item['title'],
						'year'    => $item['year'],
						'type'    => $item['type'],
						'thumb'   => $item['thumb'],
						'art'     => $item['thumb'],
						'addedAt' => $item['addedAt'] ?? false,
						'guid'    => $item['guid']
					];

					array_push($results, $return);
				}
			}
		}
	}

	if (count($results) === 1) {
		$guid = $results[0]['guid'];
		$title = $results[0]['title'];
		$year = $results[0]['year'];
		$thumb = $results[0]['thumb'];
		$sectionId = $results[0]['librarySectionID'];
		if ($results[0]['addedAt']) {
			$template = false;
		} else {
			write_log("We have but one result...adding $title.");
			$url = "$ip/media/subscriptions/template?guid=$guid&X-Plex-Token=$token";
			$template = curlGet($url);
		}
		if ($template) {
			$container = (new JsonXmlElement($template))->asArray();
			$hintString = $container['MediaContainer']['SubscriptionTemplate'][0]['MediaSubscription'][0]['parameters'];
			$hintItems = explode("&", $hintString);
			//$paramString = urlencode($paramString);
			$hints = [];
			$hintParams = [];
			foreach ($hintItems as $hint) {
				$hint = explode("=", $hint);
				$key = $hint[0];
				if (preg_match("/params/", $key)) {
					$key = str_replace(["params", "[", "]"], "", $key);
					$hintParams[$key] = urldecode($hint[1]);
				} else {
					$key = str_replace(["hints", "[", "]"], "", $key);
					$hints[$key] = urldecode($hint[1]);
				}
			}

			$plexSettings = $container['MediaContainer']['SubscriptionTemplate'][0]['MediaSubscription'][0]['Setting'];
			$plexPrefs = [];
			foreach ($plexSettings as $setting) {
				$plexPrefs[$setting['id']] = $setting['value'];
			}
			$appPrefs = getSessionData('plexDvr', true);
			if (isset($appPrefs['newAirings'])) {
				$appPrefs['onlyNewAirings'] = $appPrefs['newAirings'];
				unset($appPrefs['newAirings']);
			}
			if (isset($appPrefs['replaceLower'])) {
				$appPrefs['replaceLowerQuality'] = $appPrefs['replaceLower'];
				unset($appPrefs['replaceLower']);
			}
			if (isset($appPrefs['resolution'])) {
				$appPrefs['minVideoQuality'] = $appPrefs['resolution'];
				unset($appPrefs['resolution']);
			}
			$prefs = array_merge($plexPrefs, $appPrefs);
			unset($prefs['id']);
			unset($prefs['dvrEnabled']);
			$params['prefs'] = $prefs;
			if (count($hints)) $params['hints'] = $hints;
			if (count($hintParams)) $params['params'] = $hintParams;
			$params['targetLibrarySectionID'] = $sectionId;
			$params['targetSectionLocationID'] = $sectionId;
			$params['includeGrabs'] = 1;
			$params['type'] = $sectionId;
			$params = array_merge($params, plexHeaders($server));
			$paramString = http_build_query($params);
			$url = "$ip/media/subscriptions?$paramString";
			$result = curlPost($url);
			if ($result) {
				write_log("Add result: " . json_encode($result));
				$results = [];
				$container = (new JsonXmlElement($result))->asArray();
				$subscriptions = $container['MediaContainer']['MediaSubscription'] ?? false;
				write_log("Container: " . json_encode($container));
				foreach ($subscriptions as $show) {
					$added = $show['Directory'][0]['title'];
					if (cleanCommandString($title) == cleanCommandString($added)) {
						write_log("Show added to record successfully: " . json_encode($show));
						$return = [
							'title'   => $title,
							'year'    => $year,
							'type'    => $show['Directory'][0]['type'],
							'thumb'   => $thumb,
							'art'     => $thumb,
							'addedAt' => "NOW"
						];
						array_push($results, $return);
					}
				}
			}
		} else {
			write_log("Error fetching result!", "ERROR");
		}
	}

	return $results;
}

function sendFallback() {
	write_log("Function fired!!", "WARN");
	write_log("Session vars: " . getSessionData());
	$fallBackMedia = $_SESSION['fallBackMedia'] ?? false;
	$fallBackAction = $_SESSION['fallBackAction'] ?? false;
	if ($fallBackAction && is_array($fallBackMedia)) {
		write_log("We have an action of $fallBackAction and media: " . json_encode($fallBackMedia));
		if ($fallBackAction == 'play') {
			write_log("Sending media.");
			sendMedia($fallBackMedia);
		} else {
			downloadMedia($fallBackMedia, scanFetchers($fallBackMedia['type']));
		}
	} else {
		write_log("Missing " . ($fallBackMedia ? " action." : " media."));
	}
	writeSessionArray(['fallBackMedia' => false, 'fallBackAction' => false], true);
}

function sendMedia($media, $shuffle = false) {
	write_log("Incoming media: " . json_encode($media));
	$playUrl = false;
	$client = findDevice(false, false, 'Client');
	$id = $client['Parent'] ?? $_SESSION['plexServerId'];
	$parent = findDevice("Id", $id, "Server");
	$hostId = $media['source'] ?? $_SESSION['plexServerId'];
	$host = findDevice("Id", $hostId, 'Server');
	if (!$host) {
		write_log("Couldn't find a host!", "ERROR");
		return false;
	}
	$server = parse_url($host['Uri']);
	$serverProtocol = $server['scheme'];
	$serverIP = $server['host'];
	$serverPort = $server['port'];
	$serverID = $host['Id'];
	$queueID = (isset($media['queueID']) ? $media['queueID'] : fetchPlayQueue($media, $shuffle));
	$isAudio = ($media['type'] == 'album' || $media['type'] == 'artist' || $media['type'] == 'track');
	$type = $isAudio ? 'music' : 'video';
	$key = urlencode($media['key']);
	$offset = ($media['viewOffset'] ?? 0);
	$commandId = $_SESSION['counter'];
	$token = $parent['Token'];
	$transientToken = fetchTransientToken($host, $media['type']);
	if ($queueID && $transientToken) {
		$client = findDevice("Id", $_SESSION['plexClientId'], "Client");
		if (!$client) {
			write_log("Error fetching client, you should work on that.", "ERROR");
			return false;
		}
		if ($client['Product'] === 'Cast') {
			$clientUri = $client['Parent'];
			$fc = $_SESSION['fcArray'] ?? [];
			foreach($fc as $check) {
				write_log("Checking $clientUri against ". $check);
				if ($check === $clientUri) {
					write_log("Sending command to flexConnect standalone: ".json_encode($client));
					$parent['Uri'] = $check;
					$token = $_SESSION['plexToken'];
				}
			}
			$isAudio = ($media['type'] == 'album' || $media['type'] == 'artist' || $media['type'] == 'track');
			$userName = $_SESSION['plexUserName'];
			$version = explode("-", $parent['Version'])[0];
			$headers = [
				'X-Plex-Clienturi'      => $_SESSION['plexClientId'],
				'X-Plex-Contentid'      => $media['key'],
				'X-Plex-Contenttype'    => $isAudio ? 'audio' : 'video',
				'X-Plex-Offset'         => $media['viewOffset'] ?? 0,
				'X-Plex-Serverid'       => $host['Id'],
				'X-Plex-Serveruri'      => $host['Uri'],
				'X-Plex-Serverversion'  => $version,
				'X-Plex-Username'       => $userName,
				'X-Plex-Queueid'        => $queueID,
				'X-Plex-Transienttoken' => $transientToken
			];

			write_log("Header array: " . json_encode($headers));
			$headers = headerQuery($headers);
			$url = $parent['Uri'] . "/chromecast/play?X-Plex-Token=" . $token;
			//$headers = headerRequestArray($headers);

			curlGet($url . $headers);
			$status = "FOO";
		} else {
			writeSession('counter', $_SESSION['counter']++);
			fetchPlayerState(false);
			$playUrl = $parent['Uri'] . "/player/playback/playMedia" .
				"?type=$type" .
				"&containerKey=%2FplayQueues%2F$queueID%3Fown%3D1" .
				"&key=$key" .
				"&offset=$offset" .
				"&machineIdentifier=$serverID" .
				"&protocol=$serverProtocol" .
				"&address=$serverIP" .
				"&port=$serverPort" .
				"&token=$transientToken" .
				"&commandID=$commandId";
			$headers = clientHeaders($parent, $client);
			$playUrl .= headerQuery($headers);
			$result = curlGet($playUrl, false, 6);
			$status = (((preg_match("/200/", $result) && (preg_match("/OK/", $result)))) ? 'success' : 'error');
		}
	} else {
		$status = "ERROR: can't fetch queue or Token.";
		write_log("Error queueing or fetching Ttoken!", "ERROR");
	}
	$return['url'] = $playUrl;
	$return['status'] = $status;
	return $return;
}

function fetchPlayItem($media, $shuffle = false) {
	write_log("Function fired: " . json_encode($media));
	$item = false;
	$type = $media['type'];
	$source = $media['source'];
	$parent = findDevice("Id", $source, "Server");
	if ($parent) {
		switch ($type) {
			case 'movie':
			case 'playlist':
			case 'episode':
				write_log("Movie/playlist playback, just start it.");
				$item = $media;
				break;
			case 'show':
				if ($shuffle) {
					$item = shuffleShow($media);
				} else {
					write_log("Play the latest on-deck item, or first unwatched episode.");
					$item = $media['ondeck'][0] ?? fetchFirstUnwatchedEpisode($media['key'], $parent);
				}
				$item['source'] = $media['source'];
				write_log("Show item: " . json_encode($item));
				break;
			case 'season':
				write_log("Start a season from the beginning.");
				break;
			case 'artist':
			case 'album':
			case 'track':
				write_log("Queueing $type");
				$item = fetchPlayQueueAudio($media);
				$item['source'] = $media['source'];
				$item['art'] = $media['art'];
				$item['thumb'] = $media['thumb'];
				$item['summary'] = $media['summary'];
				$item['type'] = $type;
				write_log("Queued item: " . json_encode($item));
				break;
			case 'queue':
				$item = mapDataPlex($media['MediaContainer']['Video'][0]);
				$item['queueId'] = $media['playQueueID'];
				break;
			default:
				$item = false;
		}
	}
	write_log("Returning playback item: " . json_encode($item));
	return $item;
}

function sendSpeech($data) {
	$speech = $data['speech'];
	$cards = $data['cards'] ?? false;
	$contextName = $data['contextName'] ?? "end";
	$waitForResponse = $data['waitForResponse'] ?? $data['wait'] ?? false;
	$suggestions = $data['suggestions'] ?? false;
	write_log("My reply is going to be '$speech'.", "INFO");
	if (isset($_GET['say'])) return;
	$amazonRequest = $_SESSION['amazonRequest'] ?? false;
	if ($amazonRequest) {
		sendSpeechAlexa($speech, $contextName, $cards, $waitForResponse, $suggestions);
	} else {
		sendSpeechAssistant($speech, $contextName, $cards, $waitForResponse, $suggestions);
	}
	if (function_exists('fastcgi_finish_request')) {
		fastcgi_finish_request();
	}
}

function sendSpeechAssistant($speech, $contextName, $cards, $waitForResponse, $suggestions) {
	if (count($cards)) write_log("Card array: " . json_encode($cards));
	ob_start();
	$data = [];
	$items = $richResponse = $sugs = [];
	if (!trim($speech)) $speech = "There was an error building this speech response, please inform the developer.";
	$data["google"]["expectUserResponse"] = boolval($waitForResponse);
	$data["google"]["isSsml"] = false;
	$data["google"]["noInputPrompts"] = [];
	$items[0] = [
		'simpleResponse' => [
			'textToSpeech' => $speech,
			'displayText'  => $speech
		]
	];
	if (is_array($cards)) {
		$count = count($cards);
		if ($count == 1) {
			$cardTitle = $cards[0]['title'];
			$cards[0]['image']['accessibilityText'] = "Image for $cardTitle.";
			if (preg_match("/https/", $cards[0]['image']['url'])) {
				array_push($items, ['basicCard' => $cards[0]]);
			} else {
				write_log("Not displaying card for $cardTitle because image is not https.", "INFO");
			}
		} else {
			if ($count >= 2 && $count <= 30) {
				$carousel = [];
				$titles = [];
				foreach ($cards as $card) {
					$cardTitle = substr($card['title'], 0, 50);
					$item = [];
					$img = $card['image']['url'];
					if (!preg_match("/http/", $img)) $img = proxyImage($img);
					if (preg_match("/https/", $img) && !arrayContains($cardTitle, $titles)) {
						$item['image']['url'] = $img;
						$item['image']['accessibilityText'] = $card['title'];
						$item['title'] = $cardTitle;
						$item['description'] = $card['description'];
						$item['optionInfo']['key'] = "play " . ($card['key'] ?? $cardTitle);
						array_push($carousel, $item);
						array_push($titles, $cardTitle);
					} else {
						write_log("Not displaying card for $cardTitle because image is not https.", "INFO");
					}
				}
				if (count($carousel) >= 2 && count($carousel) <= 30) {
					$data['google']['systemIntent']['intent'] = 'actions.intent.OPTION';
					$data['google']['systemIntent']['data']['@type'] = 'type.googleapis.com/google.actions.v2.OptionValueSpec';
					$data['google']['systemIntent']['data']['listSelect']['items'] = $carousel;
					$data['google']['expectedInputs'][0]['possibleIntents'][0]['inputValueData']['@type'] = "type.googleapis.com/google.actions.v2.OptionValueSpec";
					$data['google']['expectedInputs'][0]['possibleIntents'][0]['intent'] = "actions.intent.OPTION";
				}
			}
		}
	}
	$data['google']['richResponse']['items'] = $items;
	if (is_array($suggestions)) {
		$sugs = [];
		foreach ($suggestions as $suggestion) {
			if (trim($suggestion) and strlen($suggestion) <= 25) array_push($sugs, ["title" => $suggestion]);
		}
		if (count($sugs)) $data['google']['richResponse']['suggestions'] = $sugs;
	}
	$output["speech"] = $speech;
	$output['displayText'] = $speech;
	$output['data'] = $data;
	$output["contextOut"][0] = [
		"name"       => $contextName,
		"lifespan"   => 2,
		"parameters" => []
	];
	$output['v2'] = true;
	$output['source'] = "PhlexChat";
	ob_end_clean();
	echo json_encode($output);
	write_log("JSON out: " . json_encode($output));
}

function sendSpeechAlexa($speech, $contextName, $cards, $waitForResponse, $suggestions) {
	write_log("Function fired!");
	ob_start();
	$endSession = !$waitForResponse;
	write_log("ContextName, Suggestions: $contextName $suggestions");
	write_log("I " . ($endSession ? "should " : "shouldn't ") . "end the session.");
	$response = [
		"version"  => "1.0",
		"response" => [
			"outputSpeech"     => [
				"type" => "PlainText",
				"text" => $speech
			],
			"shouldEndSession" => $endSession
		],
		"reprompt" => [
			"outputSpeech" => [
				"type" => "PlainText",
				"text" => "I'm sorry, I didn't catch that."
			]
		]
	];
	if ($cards) {
		$cardTitle = $cards[0]['title'];
		if (preg_match('/https/', $cards[0]['image']['url'])) {
			$response['response']['card'] = [
				"type"  => "Standard",
				"title" => $cardTitle,
				"text"  => $cards[0]['summary'] ?? $cards[0]['formattedText'] ?? $cards[0]['description'] ?? $cards[0]['tagline'] ?? $cards[0]['subtitle'] ?? '',
				"image" => [
					"smallImageUrl" => $cards[0]['image']['url'],
					"largeImageUrl" => $cards[0]['image']['url']
				]
			];
		} else {
			write_log("Not displaying card for $cardTitle because image is not https.", "WARN");
		}
	}
	$response['originalRequest'] = $_SESSION['lastRequest'];
	ob_end_clean();
	header('Content-Type: application/json');
	echo json_encode($response);
	write_log("JSON out is " . json_encode($response));
}

function sendServerRegistration() {
	$address = $_SESSION['appAddress'] ?? $_SESSION['publicAddress'];
	$registerUrl = "https://api.flextv.us" . "?apiToken=" . $_SESSION['apiToken'] . "&serverAddress=" . htmlentities($address);
	write_log("Server registration URL: $registerUrl ", 'INFO');
	$result = curlGet($registerUrl);
	if ($result == "OK") {
		write_log("Successfully registered with server.", "INFO");
	} else {
		write_log("Server registration failed.  Response: " . $result, "ERROR");
	}
}

function sendWebHook($param = false, $type = false) {
	if ($_SESSION['hookEnabled'] == "true") {
		write_log("Webhooks are enabled.", "INFO");
		if ($type && ($_SESSION['hookSplit'] == "true")) {
			$url = $_SESSION['hook' . $type . 'Url'];
		} else {
			$url = $_SESSION['hookUrl'];
		}
		if (($url) && ($url !== "")) {
			if ($type) {
				if ($param) {
					$url .= "?value1=" . urlencode($param);
				}
				$url .= "&value2=" . $type;
			} else {
				if ($param) $url .= "?value1=" . urlencode($param);
			}
			write_log("Final hook URL: " . $url);
			$result = curlGet($url);
			write_log("Hook result: " . $result);
		} else {
			write_log("ERROR, no URL!", "ERROR");
		}
	}
}


//**
// Make sense of data
//  */

function mapApiRequest($request) {
	//First, figure out what intent we've fired:
	$intent = $request['metadata']['intentName'];
	$params = $request['parameters'] ?? [];
	$contexts = $request['contexts'] ?? [];
	$resolvedQuery = $request['resolvedQuery'];
	$yearVal = false;
	foreach ($contexts as $context) if ($context['name'] == 'actions_intent_option') {
		$resolvedQuery = $context['parameters']['OPTION'];
		$strings = explode(" ", $resolvedQuery);
		if (preg_match("/metadata/", $resolvedQuery) && isset($context['parameters']['text'])) {
			$resolved = $context['parameters']['text'];
			preg_match('#\((.*?)\)#', $resolved, $match);
			$yearVal = intval($match[1]);
			if ($yearVal > 1900 && $yearVal < 2050) $resolved = str_replace(" ($yearVal)", "", $resolved);
			$check = explode(" ", $resolved)[0];
			if ($check === 'play' || $check === 'fetch') $resolved = str_replace("$check ", "", $resolved);
			$resolvedQuery = $resolved;

		}

		$params['request'] = $resolvedQuery;

		if ($strings[0] === 'play') $intent = 'playMedia';
		if ($strings[0] === 'fetch') $intent = 'fetchMedia';
		if ($strings[0] === 'play' || $strings[0] === 'fetch') $resolvedQuery = $strings[0] . " " . $resolvedQuery;

	}
	$params['resolved'] = $resolvedQuery;
	$params['intent'] = $intent;
	$params['contexts'] = $contexts;
	$year = $params['year']['amount'] ?? false;
	if (!$year && $yearVal) $year = $yearVal;
	if ($year) {
		$req = $params['request'];
		// I'm going to take a risk here, and guess that if you're requesting music made before 1900,
		// you probably don't need to specify the year. We'll see.
		if (!($year >= 1900 && $year <= 2020)) {
			write_log("Value does not appear to be a year...");
			$yearWord = strOrdinalSwap($year);
			$rawWords = explode($req, $params['resolved']);
			$i = 0;
			$pre = $useWord = false;
			foreach ($rawWords as $group) {
				if ((preg_match("/$year/", $group)) || (preg_match("/$yearWord/", $group))) {
					if (preg_match("/$yearWord/", $group)) $useWord = true;
					$pre = ($i === 0);
				}
				$i++;
			}
			$add = $useWord ? $yearWord : $year;
			$req = ($pre ? "$add $req" : "$req $add");
			write_log("Request has been modified to '$req'", "INFO");
			$params['request'] = $req;
			unset($params['year']);
		}
	}
	write_log("Request is '$resolvedQuery'. Data: " . json_encode($request), "ALERT");
	// #TODO: Make sure this fires AFTER we output elsewhere...
//	if ($_SESSION['hookEnabled']) {
//		$custom = cleanCommandString($_SESSION['hookCustomPhrase']);
//		$resolved = cleanCommandString($resolvedQuery);
//		if (preg_match("/$custom/",$resolved)) {
//			write_log("Custom webhook fired.");
//			fireHook();
//		}
//	}
	/*
     *  Result format:
     *
     *  string $speech
     *  array $cards
     *  array $suggestions
     *  bool $waitForResponse
     *
     */
	#TODO Add a parser here to determine if we can prompt for more info, or if we just play something
	$result = false;
	$fallBackMedia = false;
	$fallBackAction = false;
	$params = checkDeviceChange($params);
	write_log("Intent is $intent", "INFO");
	switch ($intent) {
		case 'Media.multipleResults':
		case 'fetchInfo-MediaSelect':
			$result = buildQueryMulti($params);
			$media = $result['media'];
			if (count($media) == 1) {
				if ($_SESSION['intent'] == 'playMedia' || $_SESSION['intent'] == 'fetchInfo') {
					$params['resolved'] = $resolvedQuery = "Play " . $media[0]['title'] . ".";
					$playItem = fetchPlayItem($media[0]);
					$media = [$playItem];
					$result['media'] = $media;
					if ($playItem) $actionResult = sendMedia($playItem);
				}
				$result['actionResult'] = $actionResult ?? false;
				$params['control'] = $_SESSION['intent'];
			}
			break;
		case 'Fetch - yes':
			$media = $_SESSION['mediaArray'][0] ?? false;
			$type = $media['type'] ?? false;
			$id = $media['tadbId'] ?? $media['imdbId'] ?? $media['tvdbId'] ?? false;
			$items = scanFetchers($type, $id);
			$fetchers = $items['fetchers'];
			$existing = $items['existing'];
			$fetchResults = false;
			if (count($fetchers) && is_array($media)) {
				write_log("Olay then, we're going to download from " . join(", ", $fetchers), "INFO");
				$fetchResults = downloadMedia($media, $fetchers);
				write_log("Fetch Results: " . json_encode($fetchResults));
			} else {
				write_log("Error getting media from session data...", "ERROR");
			}
			$result['fetch'] = ['fetched' => $fetchResults, 'existing' => array_unique($existing)];
			$result['media'] = $media;
			$result['meta'] = $media;
			$params['request'] = $media['title'];
			$params['action'] = 'fetchMedia';
			break;
		case 'playMedia':
			$result = buildQueryMedia($params);
			$params = array_merge($params, $result['params']);
			$params['control'] = $params['control'] ?? 'play';
			break;
		case 'controlMedia':
			write_log("Control command!", "INFO");
			$result = buildQueryControl($params);
			break;
		case 'fetchInfo':
			write_log("Info command!", "INFO");
			$result = buildQueryInfo($params);
			break;
		case 'Default Welcome Intent':
			write_log("Let's say hello!", "INFO");
			$resolvedQuery = "Talk to Flex TV";
			break;
		case 'help':
		case 'welcome.help':
		case 'helpRequest':
			write_log("Help request!!", "INFO");
			break;
		default:
			write_log("Unknown intent $intent: " . json_encode($params));
			$result = buildQueryMulti($params);
	}
	write_log("Outgoing: " . json_encode([$params, $result]));
	$result = buildSpeech($params, $result);

	$data = [
		'context'    => $result['contextName'] ?? [],
		'sessionId'  => $_SESSION['sessionId'] ?? [],
		'intent'     => $intent,
		'mediaArray' => $result['mediaArray']
	];

	$clearSet = $result['wait'] ?? true;
	$clearSet = $clearSet ? false : true;
	$data['fallBackMedia'] = $fallBackMedia;
	$data['fallBackAction'] = $fallBackAction;
	writeSessionArray($data, $clearSet);
	$string = ($clearSet ? "Clearing" : "Setting");
	write_log("$string session context and media: " . json_encode($data));

	$result['initialCommand'] = $resolvedQuery;
	$result['timeStamp'] = timeStamp();

	if (!isset($_GET['say'])) {
		write_log("Returning speech.", "INFO");
		sendSpeech($result);
	}
	logCommand($result);
	bye();

}

function mapData($dataArray) {
	$media = $meta = $results = [];
	write_log("Mapping data: " . json_encode($dataArray), "ALERT");
	foreach ($dataArray as $key => $data) {
		if (is_array($data)) {
			$keys = explode(".", $key);
			$type = $keys[0];
			$sub = end($keys);
			switch ($type) {
				case 'meta':
					$meta = $data;
					break;
				case 'plex':
					$hubs = $data['MediaContainer']['Hub'] ?? false;
					if (is_array($hubs)) {
						$counts = 0;
						foreach ($hubs as $hub) {
							if ($hub['size'] >= 1) {
								$items = $hub['Directory'] ?? $hub['Track'] ?? $hub['Video'] ?? $hub['Season'] ?? $hub['Actor'] ?? $hub['Playlist'] ?? false;
								if (is_array($items)) {
									$counts += count($items);
									foreach ($items as $item) {
										$item['source'] = $sub;
										$return = mapDataPlex($item);
										if ($return) $media[] = $return;
									}
								}
							}
						}
					}
					break;
			}
		} else {
			write_log("Data for $key is not an array, skipping.", "INFO");
		}
	}
	$results = [
		'media' => $media,
		'meta'  => $meta
	];

	$mediaCount = count($media);
	$metaCount = count($meta);
	write_log("We have $mediaCount media ITEMS and $metaCount meta items: " . json_encode($results));
	return $results;
}

function mapDataPlex($data) {
	$result = (trim($data['title']) !== "") ? [
		'title'      => $data['title'],
		'year'       => $data['year'],
		'duration'   => $data['duration'],
		'key'        => $data['key'],
		'ratingKey'  => $data['ratingKey'] ?? '',
		'sectionKey' => $data['librarySectionKey'],
		'type'       => $data['type'],
		'source'     => $data['source'],
		'viewOffset' => $data['viewOffset'] ?? 0
	] : false;
	if ($result) {
		$thumb = $data['grandparentThumb'] ?? $data['parentThumb'] ?? $data['thumb'] ?? false;
		$art = $data['grandparentArt'] ?? $data['parentArt'] ?? $data['art'] ?? false;
		if ($art) $result['art'] = $art;
		if ($thumb) $result['thumb'] = $thumb;

		if ($data['type'] == 'track') {
			$result['artist'] = $data['grandparentTitle'];
			$result['album'] = $data['parentTitle'];
		}
		if ($data['type'] == 'show') {
			$ondecks = $data['OnDeck'];
			foreach ($ondecks as $ondeck) {
				$result['ondeck'][] = $ondeck['Video'][0];
			}
		}
		if ($data['type'] == 'episode') {
			$result['seriesTitle'] = $data['grandparentTitle'];
			$result['season'] = $data['parentIndex'];
			$result['episode'] = $data['index'];
			$result['summary'] = $data['summary'];
		}
		if (isset($data['tagline'])) $result['tagline'] = $data['tagline'];
		$result = array_filter($result);
	}
	return $result;
}


function mergeData($search, $media, $meta) {
	write_log("Incoming: " . json_encode([$search, $media, $meta]));
	$results = [];
	$artist = $search['artist'] ?? false;
	$album = $search['album'] ?? false;
	$intent = $search['intent'];
	$shuffle = $search['shuffle'] ?? false;
	$mediaCount = count($media);
	$mergeCount = 0;
	$newMedia = [];
	if ($media && $meta) {
		foreach ($media as $item) {
			foreach ($meta as $check) {
				$itemTitle = $item['title'] ?? "fooa";
				$checkTitle = $check['title'] ?? "foob";
				$itemType = $item['type'];
				$checkType = explode(".", $check['type'])[1] ?? $check['type'];
				if (compareTitles($itemTitle, $checkTitle, false, true) && $itemType == $checkType) {
					$merge = true;
					if (isset($item['artist']) && isset($check['artist'])) {
						if ($item['artist'] !== $check['artist']) {
							$merge = false;
						}
					}
					if (isset($item['year']) && isset($check['year'])) {
						if ($item['year'] !== $check['year']) {
							$merge = false;
						}
					}
					if ($merge) {
						$mergeCount++;
						if (isset($item['art']) && isset($check['art'])) unset($item['art']);
						if (isset($item['thumb']) && isset($check['thumb'])) unset($item['thumb']);
						$item = array_merge($check, $item);
						break(1);
					}
				}
			}
			array_push($newMedia, $item);
		}
		$media = $newMedia;
		write_log("Merged meta into $mergeCount items out of a total of $mediaCount items");
	}

	$vars = [
		'a_meta' => $meta,
		'media'  => $media

	];
	$castMedia = [];
	if ($artist && $album && count($meta)) {
		$albums = [];
		foreach ($meta as $item) {
			if ($item['type'] == 'album') {
				$year = $item['year'];
				$albums["$year"] = $item;
			}
		}
		ksort($albums);
		if (count($albums)) {
			$search = $album == -1 ? end($albums) : array_values($albums)[$album - 1];
			$search['type'] = 'album';
			$search['request'] = $search['title'];
		}
	}

	$metaExact = false;
	foreach ($vars as $section => $data) {
		if ($section === "a_meta") $section = 'meta';
		$exact = $fuzzy = false;
		foreach ($data as $item) {
			$typeMatch = $yearMatch = true;
			$searchTitle = $search['request'];
			$itemTitle = $item['title'];
			$searchTypes = $search['type'] ?? false;
			if ($searchTypes) {
				$typeMatch = false;
				if (is_array($searchTypes)) {
					foreach ($searchTypes as $searchType) {
						$types = explode(".", $item['type']);
						$itemTypes = explode(".", $searchType);
						$mt = $types[1] ?? $types[0] ?? $item['type'];
						$it = $itemTypes[1] ?? $itemTypes[0];
						if ($mt == $it) $typeMatch = true;
					}
				} else {
					$searchType = $searchTypes;
					$itemTypes = explode(".", $item['type']);
					$searchTypes = explode(".", $searchType);
					$st = $searchTypes[1] ?? $searchTypes[0] ?? $searchType;
					$it = $itemTypes[1] ?? $itemTypes[0] ?? $item['type'];
					if ($st == $it) $typeMatch = true;
					if ($st === 'music') {
						if ($it == 'artist' || $it == 'album' || $it == 'track') $typeMatch = true;
					}
				}
				if ($item['type'] === 'show' && $search['type'] === 'show.episode' && $shuffle) {
					$typeMatch = true;
				}
			}
			if (isset($search['year']) && isset($item['year'])) {
				$searchYear = trim($search['year']);
				$itemYear = trim($item['year']);
				$yearMatch = ($searchYear == $itemYear);
			}
			if (isset($search['artist'])) {
				$artist = $data['artist'] ?? 'asdf';
				$artistMatch = ($search['artist'] !== $artist);
			} else $artistMatch = true;
			$validMedia = ($section !== 'media' || isset($item['key']));
			if ($yearMatch && $typeMatch && $artistMatch && $validMedia) {
				if (isset($search['offset'])) $item['viewOffset'] = $search['offset'];
				if (compareTitles($itemTitle, $searchTitle, false, true)) {
					if (!is_array($exact)) $exact = [];
					array_push($exact, $item);
					$exCount = count($exact);
				} else if (!is_array($exact) && compareTitles($itemTitle, $searchTitle)) {
					if (!is_array($fuzzy)) $fuzzy = [];
					array_push($fuzzy, $item);
				}
			}
			if ($section === 'media') {
				$reason = $item['reason'] ?? 'foo';
				if ($reason === 'actor') {
					array_push($castMedia, $item);
				}
			}
		}
		if ($exact) {
			write_log("Using exact results for $section section");
			$metaExact = ($section === 'meta' && $exact);
		} else {
			if ($section === 'media' && $metaExact && $intent === 'fetchMedia') {
				write_log("Dumping results because of a fetch command and matching meta");
				$fuzzy = [];
			} else {
				write_log("Using fuzzy results for $section section.");
			}
		}
		$results["$section"] = ($exact ? $exact : ($fuzzy ? $fuzzy : []));
	}
	if (!count($results['media']) && count($castMedia)) {
		write_log("Hey, this is a search by cast, or at least we're going to pretend as much.");
		$results['media'] = [$castMedia[array_rand($castMedia)]];
	}
	$results['params'] = $search;
	return $results;
}

//**
//Build queries, speech, cards
// */

function buildCards($cards) {
	write_log("Incoming: " . json_encode($cards));
	$returns = [];
	foreach ($cards as $card) {
		$title = $card['title'];
		$year = $card['year'] ?? false;
		if (is_array($year)) $year = $year[0];
		$subTitle = false;
		switch ($card['type']) {
			case 'episode':
				$showName = $card['seriesTitle'] ?? $card['grandparentTitle'] ?? false;
				if ($showName) $title = "$showName - $title";
				$season = $card['season'] ?? $card['index'] ?? false;
				$episode = $card['episode'] ?? $card['parentIndex'] ?? false;
				$subTitle = ($season && $episode && $year) ? "S${season}E${episode} (${year})" : $card['summary'];
				break;
			case 'track':
				$title .= " (" . $card['album'] . ")";
				$subTitle = ($card['grandparentTitle'] ?? $card['artist']);
				break;
			case 'album':
				$title = ($card['parentTitle'] ?? $card['artist']) . " - $title";
				break;
			case 'movie':
				if ($year) $title .= " ($year)";
				break;
		}

		$subTitle = $subTitle ? $subTitle : $card['tagline'] ?? $card['description'] ?? '';
		$formattedText = $card['summary'] ?? $card['description'] ?? '';
		$image = $card['art'] ?? $card['thumb'] ?? '';
		if (preg_match("/library\/metadata/", $image)) {
			$server = findDevice("Id", $card['source'], 'Server');
			$image = transcodeImage($image, $server);
		}
		$returns[] = [
			'title'         => $title,
			'key'           => $card['key'] ?? $card['imdbId'] ?? $card['id'],
			'subTitle'      => $subTitle,
			'formattedText' => $formattedText,
			'image'         => ['url' => $image]
		];
	}
	write_log("Outgoing: " . json_encode($returns));
	return $returns;
}

function buildQueryControl($params) {
	write_log(" params: " . json_encode($params));
	//Sanitize our string and try to rule out synonyms for commands
	$command = $params['controls'];
	$value = $params['percentage'] ?? $params['duration'] ?? $params['language'] ?? false;
	//$synonyms = lang('commandSynonymsArray');
	$queryOut['initialCommand'] = $command;
	$queryOut['parsedCommand'] = "";
	$commands = [
		"volume.down"      => "volume.down",
		"volume.up"        => "volume.up",
		"volume.mute"      => "volume.mute",
		"volume.unmute"    => "volume.unmute",
		"volume"           => "volume",
		"resume"           => "play",
		"pause"            => "pause",
		"stop"             => "stop",
		"back"             => "previous",
		"next"             => "next",
		"seek"             => "seek",
		"subtitles.off"    => "subtitles",
		"subtitles.on"     => "subtitles",
		"subtitles.change" => "subtitles",
		"device.change"    => "device.change",
		'rescan'           => "rescan"
	];
	$cmd = $commands["$command"] ?? false;
	write_log("Command and value are $command and $value");
	if ($command === "rescan") {
		write_log("Triggering a device rescan.");
		return triggerRescan();
	}
	if ($command == "subtitles.on" || ($command == 'subtitles.change' && $value)) {
		$streamID = 0;
		$status = fetchPlayerStatus();
		$streams = $status['mediaResult']['streams'] ?? false;
		if ($streams && is_array($streams)) {
			foreach ($streams as $stream) {
				$lang = $stream['language'];
				$lang = localeName(substr($lang, 0, 2));
				$picked = $value ?? $_SESSION['appLanguage'];
				if ($lang === $picked) {
					write_log("Found a matching subtitle.");
					$streamID = $stream['id'];
				}
			}
			if (!$streamID) $streamID = $streams['0']['id'] ?? false;
		}
		if ($streamID) {
			$cmd = 'subtitles';
			$value = $streamID;
		}
	}
	if ($command == "device.change") {
		write_log("Change device requested.");
		$deviceType = $params['DeviceType'] ?? false;
		$device = $params['device'] ?? false;
		if ($device && $deviceType) {
			$device = findDevice("Name", $device, $deviceType);
			if ($device) {
				setSelectedDevice($deviceType, $device['Id']);
			} else {
				write_log("Unable to find specified device.", "ERROR");
			}
			return $device;
		} else write_log("Can't find device or type!!", "ERROR");
	} else {
		if (!$cmd) {
			$cmds = explode(" ", strtolower($command));
			$newString = array_intersect($commands, $cmds);
			$result = implode(" ", $newString);
			$cmd = trim($result) ? $result : false;
		}
		return $cmd ? sendCommand($cmd, $value) : $cmd;
	}
	return false;
}

function buildQueryFetch($params) {
	write_log(" params: " . json_encode($params));
	return ['speech' => __FUNCTION__];
}

function buildQueryInfo($params) {
	$result = [];
	write_log(" params: " . json_encode($params));
	$type = $params['infoRequests'] ?? false;
	switch ($type) {
		case 'recent':
			$mediaType = $params['type'] ?? false;
			write_log("Media type is $mediaType");
			$result['media'] = fetchHubList($type, $mediaType);
			$results['type'] = $mediaType;
			break;
		case 'ondeck':
			$mediaType = 'show';
			$result['media'] = fetchHubList($type, $mediaType);
			$results['type'] = $mediaType;
			break;
		case 'airings':
			$result['media'] = fetchAirings($params);
			break;
		case 'nowPlaying':
			$result['media'] = fetchNowPlaying();
			break;
		default:
			$result['media'] = [];
	}
	write_log("Returning: " . json_encode($result));
	return $result;
}

function buildQueryMedia($params) {
	write_log(" params: " . json_encode($params));
	$results = fetchMediaInfo($params);
	$params = $results['params'];
	$media = $results['media'];
	$meta = $results['meta'];
	$lastCheck = [];
	if (count($media) >= 2) {
		foreach ($media as $item) {
			$push = true;
			$i = 0;
			foreach ($lastCheck as $check) {
				$titleMatch = ($item['title'] === $check['title']);
				$yearMatch = ($item['year'] === $check['year']);
				$typeMatch = ($item['type'] === $check['type']);
				$itemId = $item['tmdbId'] ?? $item['tadbId'] ?? 'item';
				$checkId = $check['tmdbId'] ?? $check['tadbId'] ?? 'check';
				$idMatch = ($itemId === $checkId);
				if ($titleMatch && $yearMatch && ($idMatch || $typeMatch)) {
					$preferredId = $_SESSION['plexServerId'];
					$push = false;
					if ($item['source'] === $preferredId) {
						write_log("New item is preferred, replacing.", "INFO");
						$lastCheck[$i] = $item;
					} else {
						write_log("Skipping identical item " . $item['title'], "INFO");
					}
				}
				$i++;
			}
			if ($push) array_push($lastCheck, $item);
		}

		$media = $lastCheck;
		$results['media'] = $media;
		write_log("We now have " . count($media) . " items.");
	}

	$noPrompts = (isset($_GET['say']) && !isset($_GET['web']));
	$action = $params['control'] ?? $params['action'] ?? 'play';
	write_log("Params here: " . json_encode($params));
	write_log("Action is $action!!", "INFO");
	if ($action == 'fetchMedia') {
		if (count($media) === 0 && count($meta)) {
			if (count($meta) >= 2) {
				$results['fetch'] = "MULTI";
				writeSession("fallBackAction", 'fetchMedia');
				writeSession("fallBackMedia", $meta[0]);
			} else {
				write_log("We have one meta result, attempting to fetch.");
				$fetch = $meta[0];
				$type = $fetch['type'];
				$id = ($type == 'show.episode') ? $fetch['tvdbId'] ?? false : false;
				$matched = [];
				$scanResults = scanFetchers($type, $id);
				$fetchers = $scanResults['fetchers'];
				write_log("Lib return: " . json_encode($scanResults));
				$existing = $fetchResults = [];
				if (count($scanResults['items'])) {
					write_log("We have results from libraries and an item, let's check it out!!");
					$check = $fetch;
					$fetchers = $scanResults['fetchers'];
					foreach ($scanResults['items'] as $mediaItem) {
						if (strtolower($mediaItem['title']) == strtolower($check['title'])) {
							write_log("This exists in " . $mediaItem['source'] . ": " . json_encode($mediaItem));
							$index = array_search($mediaItem['source'], $fetchers);
							if ($index !== false) {
								unset($fetchers[$index]);
								array_push($existing, $mediaItem['source']);
							}
							array_push($matched, $mediaItem);
						}
					}
					write_log("Final list of fetchers: " . json_encode($fetchers));
				}
				if (count($fetchers)) {
					write_log("Olay then, we're going to download from " . join(", ", $fetchers), "INFO");
					$fetchResults = downloadMedia($fetch, $fetchers);
					write_log("Fetch Results: " . json_encode($fetchResults));
				}
				$results['fetch'] = ['fetched' => $fetchResults, 'existing' => array_unique($existing)];
			}

		}
	}

	if ($action == 'play' || $action == 'playMedia') {
		$shuffle = $params['shuffle'];
		$playItem = false;
		if (!count($media) && count($meta)) {
			write_log("Setting fallback action to fetch for item: " . json_encode($meta[0]));
			writeSession("fallBackAction", 'fetch');
			writeSession("fallBackMedia", $meta[0]);
		}
		write_log("We have " . count($media) . " item(s) to play from media array: " . json_encode($media));
		if (count($media) == 1 || $noPrompts) {
			$playItem = $media[0];
		}
		if (count($media) >= 2 && !$noPrompts) {
			write_log("Multiple results found, fetching fallback item.");
			$fallBackMedia = fetchPlayItem($media[0], $shuffle);
			if ($fallBackMedia) write_log("Fallback media saved.");
			writeSession("fallBackAction", 'play');
			writeSession("fallBackMedia", $fallBackMedia);
		}
		if ($playItem) {
			$playItem = fetchPlayItem($playItem, $shuffle);
			$media = [$playItem];
			$results['media'] = $media;
			$playResult = sendMedia($playItem, $shuffle);
		} else {
			write_log("NO PLAYBACK ITEM.", "ALERT");
			$playResult = false;
		}
		$results['playback'] = $playResult ?? false;
		write_log("PlayResult: " . json_encode($playResult));
	}
	if ($action == 'dvrMedia') {
		$data = sendCommandRecord($params['request']);
		write_log("Response from record command: " . json_encode($data));
		$results['dvr'] = $data;
	}

	$results['params'] = $params;
	write_log("Result: " . json_encode($results));
	return $results;
}

function buildQueryMulti($params) {
	write_log(" params: " . json_encode($params));
	$title = $params['request'] ?? $params['resolved'] ?? false;
	$resolved = strtolower($params['resolved']);
	if (preg_match("/broadcast/", $resolved)) {
		$msg = str_replace(["broadcast", "Broadcast"], "", $title);
		write_log("Cast audio command!");
		$result = [
			'status' => castAudio($msg),
			'speech' => "Okay, sending the message '$msg'"
		];
		return $result;
	}
	$year = $params['age']['amount'] ?? $params['number'] ?? false;
	$ordinal = $params['ordinal'] ?? false;
	$mediaArray = $_SESSION['mediaArray'] ?? [];
	write_log("Session Media array: " . json_encode($mediaArray));
	$result = [];
	if ($ordinal) {
		$ordinal = intval($ordinal);
		write_log("We have an ordinal: $ordinal");
		if ($ordinal <= count($mediaArray)) {
			write_log("We have the stuff.");
			$ordinal--;
			write_log("Ordinal is now $ordinal");
			$result = [$mediaArray[$ordinal]];
			write_log("Result: " . json_encode($result));
		}
	} else if ($year || $title) {
		foreach ($mediaArray as $media) {
			$resCheck = $resolved;
			$types = ['artist', 'album', 'episode', 'track', 'song', 'show'];
			foreach ($types as $check) {
				if (preg_match("/$check/", strtolower($resCheck))) {
					write_log("Type specification.");
					$resCheck = $check;
					break;
				}
			}
			$type = $params['mediaTypes'] ?? $params['type'][0] ?? $params['type'] ?? false;
			$mediaType = $media['type'];
			$match = $title ? (strtolower($title) == strtolower($media['title'])) : true;
			$match = $match ? $match : ($title == $media['key']);
			$match = $match ? $match : ($type ? preg_match("/$mediaType/", $type) : $type);
			if ($year && $match) {
				$match = $year == $media['year'];
			}
			if ($match) {
				write_log("Easy match.");
				array_push($result, $media);
			} else {
				write_log("No easy match, checking the hard way.");
				foreach ($media as $key => $value) {
					$value = strtolower($value);
					if (strpos($resCheck, $value) !== false) {
						write_log("We have a substring!");
						$resCheck = str_replace($value, "", $resCheck);
						write_log("Resolved value is now $resCheck");
					}
				}
				$resCheck = cleanCommandString($resCheck);
				if (trim($resCheck) === "") {
					array_push($result, $media);
				}
			}
		}
	}
	write_log("Found " . count($result) . " results out of " . count($mediaArray) . " items.");
	$response = [];
	$response['media'] = $result;
	return $response;
}

function buildSpeech($params, $results) {
	if (isset($results['speech'])) {
		$speech = $results['speech'];
	} else {
		$speech = "Tell dude to build me a speech string!";
	}
	$mediaArray = false;
	$playback = $meta = $media = $suggestions = $wait = false;
	$context = "end";
	write_log("Incoming: " . json_encode([$params, $results]));
	$wait = $params['wait'] ?? false;
	$media = $results['media'] ?? [];
	$meta = $results['meta'] ?? [];
	$intent = $params['intent'];
	if ($intent == 'Fetch - yes') {
		$intent = 'playMedia';
		$params['control'] = 'fetchMedia';
	}

	if ($intent == 'playMedia') {

		$playback = false;

		if (preg_match("/play/", $params['control'])) {
			$mediaArray = $media;
			$types = [];
			$artists = [];
			foreach ($media as $check) {
				$type = explode(".", $check['type'])[1] ?? $check['type'];
				$artist = $check['artist'];
				if (!in_array($type, $types)) $types[] = $type;
				if (!in_array($artist, $artists)) $artists[] = $artist;
			}
			switch (count($mediaArray)) {
				case 0:
					write_log("No results found.", "INFO");
					$mediaArray = $meta;
					$info = buildSpeechNoMedia($params);
					$speech = $info['speech'];
					$wait = $info['prompt'];
					if ($wait) $context = "playMedia-followup";
					break;
				case 1:
					$mediaArray = $media;
					write_log("just the right amount of results found.", "INFO");
					$speech = buildSpeechAffirmative($media[0]);
					$playback = $media[0];
					break;
				default:
					$speech = "Which one did you want, ";
					if (count($types) == 1) {
						$type = $types[0];
						if ($type === 'track') {
							$typeStr = (count($artists) == 1) ? "album" : "artist";
							$speech = "Which $typeStr was that from, ";
						}
						if ($type === 'episode') $speech = "Which show was that from, ";
					}
					$noType = isset($params['type']);
					$speech .= joinItems($media, "or", $noType);
					$mediaArray = $media;
					$wait = true;
					$context = "playMedia-followup";
			}
		}

		if ($params['control'] == 'dvrMedia') {
			$added = $existing = false;
			$dvr = $results['dvr'] ?? [];
			write_log("Okay, now we build speech for a fetch Command.");
			if (count($dvr)) {
				foreach ($dvr as $item) {
					$title = $item['title'];
					if ($item['addedAt'] ?? false) {
						if ($item['addedAt'] === "NOW") {
							$added = true;
							$speech = "Okay, I've added $title the recording schedule.";
							break;
						} else {
							$existing = true;
							if (count($media)) {
								foreach ($media as $mediaItem) {
									if ($mediaItem['title'] === $title) {
										write_log("We have a match for existing media, replace and prompt to play.");
										$speech = "It looks like $title is already set to record and in your library. Would you like to watch it now?";
										$wait = true;
										$mediaArray = [$mediaItem];
										$context = "recordMedia.multipleResults";
										break 2;
									}
								}
							} else {
								$title = ucfirst($title);
								$speech = "$title is already set to record, but nothing has been recorded yet. Check back later.";
								break;
							}
						}
					}
				}

				if (!$added && !$existing) {
					$speech = "Which one did you want? " . joinItems($dvr);
					$mediaArray = $dvr;
					writeSession("fallBackAction", 'record');
					writeSession("fallBackMedia", $mediaArray);
					$wait = true;
				}

			} else {
				write_log("No results?");
				$speech = buildSpeechNoResults($params);
			}
		}
	}

	if ($intent === 'fetchMedia') {
		#TODO: Add a param to download stuff even if someone else has it already
		if (count($media)) {
			foreach ($media as $item) {
				$server = $item['parent'] ?? $item['source'] ?? false;
				$server = $server ? findDevice('Id', $server, 'Server') : $server;
				if ($server['Owned'] === "1") {
					write_log("Media already exists on a server owned by the user.");
					$request = $media[0]['title'];
					$speech = "It looks like '$request' is already in your collection. Would you like me to play it?";
					$wait = true;
					$mediaArray = $media;
					writeSessionArray([
						'mediaItems' => $media,
						'metaItems'  => $meta
					]);
				}
			}
		} else {
			$data = $results['fetch'] ?? false;
			if ($data) {
				if (is_array($data)) {
					write_log("We have results from a download: " . json_encode($results['fetch']));
					$fetched = $results['fetch']['fetched'] ?? [];
					$existing = $results['fetch']['existing'] ?? [];
					if (count($fetched) || count($existing)) {
						$speech = buildSpeechFetch($meta[0], $fetched, $existing);
					}
				} else {
					write_log("Fetch wasn't run for some reason...");
					if ($data == "MULTI") {
						write_log("Multi speech...");
						$speech = "Which one did you want?  ";
						$noType = isset($params['type']);
						$speech .= joinItems($meta, "or", $noType);
					} else {
						$speech = "Unfortunately, there was an error communicating with your fetchers. Please check the configuration and try again.";
					}
				}
				$mediaArray = $meta;
			} else {
				$speech = buildSpeechNoResults($params);
			}
		}
	}

	if ($intent == 'controlMedia') {
		$cmd = $params['controls'];
		$params['result'] = $results;
		$speech = buildSpeechCommand($cmd, $params);
	}

	if ($intent == 'fetchInfo') {
		$media = $results['media'] ?? [];
		$mediaArray = $media;
		$speech = buildSpeechInfoQuery($params, $mediaArray);
		writeSession('mediaArray', $media);
	}

	if ($intent == 'Media.multipleResults') {
		$media = $results['media'];
		$wait = false;
		switch (count($media)) {
			case 0:
				$speech = "Unfortunately, I couldn't find anything by that name.";
				break;
			case 1:
				$speech = buildSpeechAffirmative($media[0]);
				$mediaArray = $media;
				break;
			default:
				$speech = "I'm still not sure which one you wanted.";
		}
	}

	if ($intent == 'Default Welcome Intent') {
		$speech = buildSpeechWelcome();
		$wait = true;
	}

	if ($intent == 'helpRequest') {
		$help = buildSpeechHelp();
		$speech = $help[0];
		if ($_SESSION['amazonRequest'] ?? false) {
			$sugs = [];
			foreach ($help[1] as $sug) array_push($sugs, "'$sug'");
			$strings = join(", ", $sugs);
			$speech .= $strings;
		} else {
			$suggestions = $help[1];
		}

		$wait = true;
	}

	$cards = buildCards($mediaArray);
	#TODO: Add the output context here
	return [
		'speech'      => $speech,
		'cards'       => $cards,
		'playback'    => $playback,
		'suggestions' => $suggestions,
		'meta'        => $meta,
		'media'       => $media,
		'wait'        => $wait,
		'contextName' => $context,
		'mediaArray'  => $mediaArray
	];
}

function buildSpeechAffirmative($media) {
	write_log("Incoming media: " . json_encode($media));
	$affirmatives = lang("speechPlaybackAffirmatives");
	$title = buildTitle($media);
	$eggs = lang("speechEggArray");
	foreach ($eggs as $eggTitle => $egg) {
		if (compareTitles($title, $eggTitle)) {
			write_log("Pushing $egg");
			array_push($affirmatives, $egg);
		}
	}
	$last = $_SESSION['affirmative'] ?? 'foo';
	do {
		$affirmative = $affirmatives[array_rand($affirmatives)];

	} while ($affirmative == $last);
	if ($_SESSION['shortAnswers'] ?? false) {
		$affirmative = lang('speechPlaybackAffirmativeShort');
		$title = $media['title'];
	}
	writeSession("affirmative", $affirmative);
	$player = findDevice(false, false, "Client");
	$clientCount = count($_SESSION['deviceList']['Client'] ?? []);
	$name = $player['Name'] ?? false;
	if ($name && $clientCount > 1) {
		$title .= " on $name";
	}
	$str = str_replace("<TITLE>", $title, $affirmative);

	return $str;
}

function buildSpeechCommand($cmd = false, $params = false) {
	write_log("Building speech for $cmd");
	if ($cmd == "device.change") {
		$result = $params['result'] ?? false;
		$deviceType = $params['DeviceType'] ?? 'device';
		$device = $result['name'] ?? $params['device'] ?? false;
		if ($device) $device = ucwords($device);
		#TODO: Localize this, you lazy bum...
		if ($result) {
			$msg = "Okay, I've set the $deviceType to $device.";
		} else {
			$msg = "I'm sorry, but I couldn't find a $deviceType " . ($device ? $device : 'with that name') . " to select.";
		}
	} else {
		$array = lang('speechControlArray');
		do {
			$msg = $array[array_rand($array)];
		} while ($msg == $_SESSION['cmdMsg'] ?? 'foo');
		if ($_SESSION['shortAnswers'] ?? false) $msg = lang('speechControlShort');
		writeSession('cmdMsg', $msg);
	}
	return $msg;
}

function buildSpeechFetch($media, $fetched, $existing) {
	$string = "";
	$title = $media['title'];
	if (count($fetched)) {
		$bad = $good = [];
		foreach ($fetched as $name => $status) {
			if ($status) array_push($good, $name); else array_push($bad, $name);
		}
		$affirmatives = lang("speechFetchAffirmatives");
		$last = $_SESSION['affirmative'] ?? 'foo';
		if (count($good)) {
			do {
				$affirmative = $affirmatives[array_rand($affirmatives)];

			} while ($affirmative == $last);
			if ($_SESSION['shortAnswers'] ?? false) $affirmative = "";
			$string .= $affirmative . "I've added $title to " . joinStrings($good);
		}
		if (count($bad)) {
			$string .= "I wasn't able to add it to " . joinStrings($bad);
		}
		return $string;
	}

	if (count($existing)) {
		$exists = lang("speechDownloadExistsArray");
		$last = $_SESSION['exist'] ?? 'foo';
		do {
			$exist = $exists[array_rand($exists)];

		} while ($exist == $last);
		writeSession("exist", $exist);
		$exist = str_replace("<TITLE>", $title, $exist);
		$string .= " $exist" . joinStrings($existing);
	}
	return $string;
}

function buildSpeechInfoQuery($params, $cards) {
	$type1 = $params['type'] ?? false;
	$type = $params['infoRequests'];
	$type = $type . ($type1 ? " $type1" . "s" : " items");
	$count = is_array($cards) ? count($cards) : 0;
	write_log("Building info query response for $count cards: " . json_encode($cards));
	switch ($count) {
		case 0:
			$speech = buildSpeechNoResults($params);
			break;
		default:
			$array = lang("speechReturnInfoArray");
			do {
				$info = $array[array_rand($array)];
			} while ($info == $_SESSION['infoMsg'] ?? 'foo');
			writeSession('infoMsg', $info);
			$info = str_replace("<TYPE>", $type, $info);
			$titles = [];
			foreach ($cards as $card) array_push($titles, $card['title']);
			$speech = $info . " " . joinItems($cards, "and", true);
			$speech .= " " . lang("speechReturnInfoTail");
	}
	return $speech;
}

/**
 * @param String | array $request
 * @return mixed
 */
function buildSpeechNoResults($request) {
	write_log("Request: " . json_encode($request));
	$title = is_string($request) ? $request : ($request['request'] ?? $request['type'] ?? 'that request');
	if (is_array($request) && isset($request['infoRequests'])) {
		$type = $request['infoRequests'];
		if ($type == 'recent') {
			if ($request['type'] == 'movie') {
				$title = "recent movies";
			} else {
				$title = "recent shows";
			}
		}
		if ($type == 'on deck') $title = "on deck items";
		if ($type == 'airings') $title = "upcoming shows";
	}
	write_log("No results for request '$request'");
	$array = lang('speechNoInfoResultsArray');
	do {
		$msg = $array[array_rand($array)];
	} while ($msg == $_SESSION['errorMsg'] ?? 'foo');
	writeSession('errorMsg', $msg);
	$msg = str_replace("<VAR>", $title, $msg);
	return $msg;
}

function buildSpeechNoMedia($request) {
	write_log("Request info: " . json_encode($request));
	$title = $request['request'] ?? "that request";
	$type = $request['type'] ?? false;
	$strings = lang("speechNoMediaArray");
	do {
		$string = $strings[array_rand($strings)];
	} while ($string == $_SESSION['noMedia'] ?? "foo");
	writeSession("noMedia", $string);
	$string = str_replace("<TITLE>", $title, $string);
	$prompt = false;
	$speech = $string;
	if ($type) {
		$fetchers = scanFetchers($type, false, true);
		if (count($fetchers['fetchers'])) {
			write_log("Fetchers are available for this type, we should prompt to dl.");
			$prompt = true;
			$strings = lang("speechNoMediaFetchPrompts");
			do {
				$promptString = $strings[array_rand($strings)];
			} while ($promptString == $_SESSION['noMediaPrompt'] ?? "foo");
			writeSession("noMediaPrompt", $promptString);
			$speech = "$string $promptString";
		}
	}

	return ['speech' => $speech, 'prompt' => $prompt];
}

function buildSpeechMultipleResults($media, $params) {

}

function buildSpeechWelcome() {
	$greetings = lang("speechGreetingArray");
	$help = lang("speechGreetingHelpPrompt");
	do {
		$greeting = $greetings[array_rand($greetings)];
	} while ($greeting == $_SESSION['greeting'] ?? "foo");
	do {
		$helpPrompt = $help[array_rand($help)];
	} while ($helpPrompt == $_SESSION['helpPrompt'] ?? "foo");
	writeSessionArray([
		"greeting"   => $greeting,
		"helpPrompt" => $helpPrompt
	]);
	return "$greeting $helpPrompt";
}

function buildSpeechHelp() {
	$helpArray = lang("errorHelpSuggestionsArray");
	$lastHelp = $_SESSION['help'] ?? "foo";
	do {
		$speech = $helpArray[array_rand($helpArray)];
	} while ($lastHelp == $speech);
	writeSession('help', $lastHelp);
	$helpSuggestions = lang("errorHelpCommandsArray");
	$appSuggestions = lang("suggestionsApps");
	$movie = ($_SESSION['couchEnabled'] ?? false) || ($_SESSION['radarrEnabled'] ?? false);
	$show = ($_SESSION['sickEnabled'] ?? false) || ($_SESSION['sonarrEnabled'] ?? false);
	$music = ($_SESSION['headphonesEnabled'] ?? false) || ($_SESSION['lidarrEnabled'] ?? false);
	$dvr = $_SESSION['plexDvrId'] ?? false;
	$movieSuggestions = $appSuggestions['movie'];
	$showSuggestions = $appSuggestions['show'];
	$musicSuggestions = $appSuggestions['music'];
	$dvrSuggestions = $appSuggestions['dvr'];
	if ($movie) array_push($helpSuggestions, $movieSuggestions[array_rand($movieSuggestions)]);
	if ($show) array_push($helpSuggestions, $showSuggestions[array_rand($showSuggestions)]);
	if ($music) array_push($helpSuggestions, $musicSuggestions[array_rand($musicSuggestions)]);
	if ($dvr) array_push($helpSuggestions, $dvrSuggestions[array_rand($dvrSuggestions)]);
	$helpStrings = $suggestions = [];
	foreach ($helpSuggestions as $string) {
		array_push($helpStrings, "'$string'");
		if (strlen($string) <= 25) array_push($suggestions, $string);
	}
	$suggestionString = joinStrings($helpStrings, "or");
	$speech .= " $suggestionString";

	return [
		$speech,
		$suggestions
	];
}

function buildTitle($item) {
	$year = $item['year'] ?? false;
	$type = explode(".", $item['type'])[1] ?? $item['type'];
	$title = $item['title'];
	switch ($type) {
		case 'movie':
			$string = $title;
			if ($year) $string .= " ($year)";
			break;
		case 'show':
			$string = $title;
			break;
		case 'episode':
			$episode = $item['episode'] ?? $item['index'] ?? false;
			$season = $item['season'] ?? $item['parentIndex'] ?? false;
			$seasonString = ($episode && $season) ? " season {$season} episode {$episode}, " : "";
			$show = $item['grandparentTitle'] ?? $item['seriesTitle'] ?? "";
			$string = "$show $seasonString $title";
			break;
		case 'track':
			$artist = $item['grandparentTitle'] ?? $item['artist'];
			$string = "$title by $artist";
			break;
		case 'album':
			$artist = $item['parentTitle'] ?? $item['artist'];
			$string = "the album $title by $artist";
			break;
		default:
			$string = $item['title'];
	}
	return $string;
}

function buildSWCache() {
	$jsFiles = getDirContents('./js/');
	$cssFiles = getDirContents('./css/');
	$imgFiles = getDirContents('./img/');

	$files = array_merge($jsFiles, $cssFiles, $imgFiles);
	$files = array_merge($files, $imgFiles);
	$out = [];
	foreach($files as $file) {
		$hash = md5_file($file);
		$file = str_replace(dirname(__FILE__),".",$file) . "?hash=$hash";
		$ignore = ["main.js", ".placeholder", "Thumbs.db"];
		$add = true;
		foreach ($ignore as $check) {
			if (preg_match("/$check/", $file)) $add = false;
		}
		if ($add) array_push($out, $file);
	}

//	header("Content-Type: text/plain");
//	echo join(", \n", $files);
	$out = array_unique($out);
	file_put_contents("./cacheItems.js", "var cacheData = " . json_encode($out));


}

function getDirContents($dir, &$results = array()){
	$files = scandir($dir);

	foreach($files as $key => $value){
		$path = realpath($dir.DIRECTORY_SEPARATOR.$value);
		if(!is_dir($path)) {
			$results[] = $path;
		} else if($value != "." && $value != "..") {
			getDirContents($path, $results);
		}
	}

	return $results;
}

function getRecommendations($type) {
	$response = [];

	$server = findDevice(false, false, 'Server');
	if (! $server) {
		write_log("No server!", "ERROR");
	}
	$uri = $server['Uri'];
	$key = $server['Token'];
	write_log("SERVER: ".json_encode($server));
	$sections = json_decode($server['Sections'], true);
	$sectionId = false;
	foreach($sections as $section) {
		write_log("Section: ".json_encode($section));
		if ($section['type'] == $type) {
			$sectionId = $section['id'];
		}
	}
	$user = $_SESSION['plexUserName'];
	$urls = [
		"user" => "$uri/stats/user?X-Plex-Username=$user&X-Plex-Type=$type&X-Plex-Token=$key",
		"popular" => "$uri/stats/library/popular?X-Plex-Token=$key&X-Plex-Type=$type&X-Plex-Container-Size=10000",
		//"genres" => "$uri/stats/tag/genre?X-Plex-Token=$key",
		"added" => "$uri/library/sections/$sectionId/recentlyAdded?X-Plex-Token=$key",
		"unwatched" => "$uri/library/sections/$sectionId/unwatched?X-Plex-Token=$key"
	];
	if ($type !== 'music') $urls['newest'] = "$uri/library/sections/$sectionId/newest?X-Plex-Token=$key";
	$results = (new multiCurl($urls))->process();

	$userItems = $results['user']['MediaContainer']['User'][0]['Views'][0][ucfirst($type)][0][ucfirst($type)] ?? [];
	$popularItems = $results['popular']['MediaContainer']['Hub'][0]['Video'] ?? [];
	write_log("Raw pop: ".json_encode($results['user']));

	unset($results['user']);
	unset($results['popular']);

	$genreCounts = [];
	$watchedItems = [];

	foreach($userItems as $item) {
		array_push($watchedItems, $item['ratingKey']);
		$genres = explode("|",$item['genre']);
		foreach($genres as $genre) {
			$count = ($genreCounts[$genre] ?? 0);
			$count++;
			$genreCounts[$genre] = $count;
		}
	}
	arsort($genreCounts);

	write_log("We have " . count($popularItems) . " items.");
	$popularKeys = [];
	foreach($popularItems as &$item) {
		unset($item['Users']);
		array_push($popularKeys, $item['ratingKey']);
	}

	$rawItems = [];
	$rawKeys = [];
	$keys = ['art', 'rating', 'year', 'thumb', 'title', 'contentRating', 'ratingKey', 'Genre'];

	foreach($results as $section => $result) {
		write_log("Looping $section");
		$list = $result['MediaContainer']['Video'] ?? [];
		foreach ($list as $item) {
			$media = [];
			$ratingKey = $item['ratingKey'];
			if (!in_array($ratingKey, $rawKeys)) {
				foreach ($keys as $key) $media[$key] = $item[$key];
				$rawItems[] = $media;
				array_push($rawKeys, $ratingKey);
			}
		}
	}

	$matched = array_values(array_intersect($rawKeys, $popularKeys));
	$matched = array_values(array_diff($matched, $watchedItems));
	foreach($matched as $find) {
		foreach($rawItems as $item) {
			if ($item['ratingKey'] == $find) {
				write_log("TITLE - ".$item['title']);
			}
		}
	}

	$genres = array_slice(array_keys($genreCounts),0, 5);

	$recommendedItems = [];
	foreach($rawItems as $item) {
		$genreSets = $item['Genre'];
		$keep = false;
		foreach($genreSets as $set) {
			if (in_array($set['tag'], $genres)) $keep = true;
		}
		if (in_array($item['ratingKey'], $watchedItems)) $keep = false;
		if ($keep) array_push($recommendedItems, $item);
	}

	usort($recommendedItems, function ($item1, $item2) {
		return $item2['rating'] <=> $item1['rating'];
	});


	write_log("Matched: ".json_encode($matched));
	write_log("Genres: ".json_encode($genres));
	write_log("Popular: ".json_encode($recommendedItems));

	return $recommendedItems;
}

function buildWidgets($widgets) {
	$widgetData = [];
	foreach ($widgets as $widget) {
		$widgetObject = false;
		$type = $widget['type'];
		try {
			$widgetObject = new widget($widget);
		} catch (widgetException $e) {
			write_log("Something went WRONG - '$e'.","ERROR");
		}
		if ($widgetObject) {
			$widgetArray = $widgetObject->serialize();
			array_push($widgetData, $widgetArray);
		}
	}
	writeSession('widgetArray', $widgetData);
	return $widgetData;
}


function updateWidgets() {
	$widgets = fetchWidgetArray();
	$widgetData = [];
	$widgetSettings = [];
	$count = count($widgets);
	if ($count) write_log("Updating $count widgets.","INFO",false,true);
	foreach ($widgets as $widget) {
		$widgetObject = false;
		try {
			$widgetObject = new widget($widget);
		} catch (widgetException $e) {
			write_log("Something went WRONG - $e.","ERROR");
		}
		if ($widgetObject) {
			$widgetInfo = $widgetObject->update();
			if ($widgetInfo !== $widget) write_log("Widget updated!");
			array_push($widgetSettings, $widgetInfo);
			unset($widgetInfo['lastUpdate']);
			array_push($widgetData, $widgetInfo);
		}
		updateUserPreference('jsonWidgetArray', $widgetSettings);
	}
	return $widgetData;
}


function castAudio($speech, $test = false) {
	$path = fetchTtsFile($speech);
	if ($path) {
		$url = false;
		$device = $_SESSION['broadcastDevice'] ?? "all";
		$path = urlencode($path);
		if ($device === 'all') {
			$host = findDevice(false, false, 'Server');
			$url = $host['Uri'] . "/chromecast/broadcast?X-Plex-Path=$path&X-Plex-Token=" . $host['Token'];
		} else {
			$client = findDevice('Id', $device, 'Client');
			$parent = $client['Parent'] ?? false;
			if ($parent) {
				$host = findDevice('Id', $client['Parent'], 'Server');
				$url = $host['Uri'] . "/chromecast/audio?X-Plex-Path=$path&X-Plex-Uri=$device&X-Plex-Token=" . $host['Token'];
			}
		}
		if ($url) {
			if (!$test) {
				$startTime = strtotime($_SESSION['quietStart'] ?? "20:00");
				$endTime = strtotime($_SESSION['quietStop'] ?? "8:00");
				$now = strtotime('now');
				if ($now < $startTime && $now > $endTime) {
					curlGet($url);
					$result = "SUCCESS.";
				} else {
					$result = "Shhh, It's quiet time.";
				}
			} else {
				curlGet($url);
				$result = "SUCCESS.";
			}
		} else {
			$result = "Error finding host.";
		}

		write_log("Result: " . $result);
		$queryOut = [
			'initialCommand' => "Broadcast the message '$speech'.",
			'speech'         => $result
		];
		logCommand(json_encode($queryOut));
	} else {
		write_log("Unable to retrieve audio clip!", "ERROR");
		$result = "ERROR FETCHING CLIP";
	}
	return $result;
}

function checkDeviceChange($params) {
	$request = $params['request'] ?? false;
	$device = $params['Devices'] ?? false;
	$player = false;
	if (!$device) {
		#TODO: These will need to be internationalized
		$loc = [
			" on the ",
			" in the ",
			" to the ",
			" in ",
			" on ",
			" to "
		];
		foreach ($loc as $delimiter) {
			$exploded = explode($delimiter, $request);
			$device = (count($exploded) >= 2) ? end($exploded) : false;
			$player = $device ? findDevice("Name", $device, "Client") : false;
			if ($player) {
				array_pop($exploded);
				$request = implode($delimiter, $exploded);
				write_log("New request string is '$request'");
				break;
			}

		}
	} else {
		$player = findDevice("Name", $device, "Client");
		$request = str_replace($device, "", $request);
	}
	if ($player) {
		write_log("Switching client...", "INFO");
		setSelectedDevice("Client", $player['Id']);
		$params['request'] = $request;
	}

	return $params;
}

function downloadCastLogs() {
	$hasPlugin = getPreference('userdata', ['hasPlugin'], false, ['apiToken' => $_SESSION['apiToken']]);
	$urls = [];
	$servers = $_SESSION['deviceList']['Server'];

	if ($hasPlugin) {
		foreach ($servers as $server) {
			$serverUri = $server['Uri'];
			$token = $server['Token'];
			$serverName = $server['Name'];
			$url = "$serverUri/chromecast/logs?X-Plex-Token=$token";
			$urls[$serverName] = $url;
		}
	}
	if (count($urls)) {
		$savePath = dirname(__FILE__) . "/rw";
		$mc = new multiCurl($urls, 45, $savePath);
		$data = $mc->process();
		write_log("Got some files? " . json_encode($data));
		foreach ($data as $name => $path) {
			write_log("Got the path too.");
			$fileName = "$name.zip";
			$newName = "$savePath/$fileName";
			if (filesize($path) >= 1024) {
				write_log("File is big enough.");
				rename($path, $newName);
				$mm_type = "application/x-compressed"; // modify accordingly to the file type of $path, but in most cases no need to do so
				$len = filesize($newName);
				header("Pragma: public");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Cache-Control: public");
				header("Content-Description: File Transfer");
				header("Content-Type: $mm_type");
				header("Content-Length: $len");
				header("Content-Disposition: attachment; filename='$fileName'");
				header("Content-Transfer-Encoding: binary\n");
				readfile($newName); // outputs the content of the file
				unlink($newName);
			} else {
				write_log("This isn't a logfile.");
				unlink($path);
			}
		}
	}
}

