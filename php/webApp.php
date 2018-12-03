<?php
require_once dirname(__FILE__) . '/util.php';
require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/digitalhigh/gitUpdate/GitUpdate.php';
require_once dirname(__FILE__) . '/digitalhigh/config/appConfig.php';
scriptDefaults();
checkFiles();

use digitalhigh\gitUpdate\gitUpdate;
use digitalhigh\config\appConfig;
use Filebase\Database;

$isWebapp = isWebApp();
$_SESSION['webApp'] = $isWebapp;
$GLOBALS['webApp'] = $isWebapp;

$publicAddress = serverAddress();
$_SESSION['appAddress'] = $publicAddress;
$_SESSION['publicAddress'] = $publicAddress;

function updateUserPreference($key, $value, $section = 'userdata') {
	$value = scrubBools($value, $key);
	setPreference($section, [$key => $value], ['apiToken' => $_SESSION['apiToken']]);
}

function updateUserPreferenceArray($data, $section = 'userdata') {
	$data = scrubBools($data);
	setPreference($section, $data, ['apiToken' => $_SESSION['apiToken']]);
}

function scrubBools($scrub, $key = false) {
	$booleans = [
		'couchEnabled',
		'headphonesEnabled',
		'ombiEnabled',
		'radarrEnabled',
		'sickEnabled',
		'sonarrEnabled',
		'lidarrEnabled',
		'watcherEnabled',
		'darkTheme',
		'hasPlugin',
		'alertPlugin',
		'masterUser',
		'notifyUpdate',
		'autoUpdate',
		'plexDvrReplaceLower',
		'plexDverNewAirings',
		'plexDvrComskipEnabled',
		'hookPausedEnabled',
		'hookPlayEnabled',
		'hookFetchEnabled',
		'hookCustomEnabled',
		'hookSplitEnabled',
		'hookStopEnabled'
	];

	if (is_array($scrub) && !$key) {
		$return = [];
		foreach ($scrub as $key => $value) {
			if (in_array($key, $booleans)) {
				if ($value === 'true') $value = true;
				if ($value === 'false') $value = false;
			}
			$return[$key] = $value;
		}
	} else {
		$return = "";
		if (in_array($key, $booleans)) {
			if ($scrub === 'true') $scrub = true;
			if ($scrub === 'false') $scrub = false;
		}
		$return = $scrub;
	}
	return $return;
}

function initConfig() {
	$configObject = false;
	$error = false;
	$dbConfig = dirname(__FILE__) . "/../rw/db.conf.php";
	$dbDir = dirname(__FILE__) . "/../rw/db";
	$type = file_exists($dbConfig) ? 'db' : 'file';
	$config = file_exists($dbConfig) ? $dbConfig : $dbDir;
	if ($type === 'db') checkDefaultsDb($config);
	try {
		$config = new appConfig($config, $type);
	} catch (\digitalhigh\config\ConfigException $e) {
		write_log("An exception occurred creating the configuration. '$e'", "ERROR", false, false, true);
		$error = true;
	}
	if (!$error) {
		$configObject = $config->ConfigObject;
	}

	return $configObject;
}

function setPreference($section, $data, $selector = false) {
	$config = initConfig();
	$config->set($section, $data, $selector);
	if ($section === 'userdata') writeSessionArray($data);
	if ($section === 'general') writeSessionArray(fetchGeneralData());
}

/**
 * @param $table
 * @param bool | array $what - An array of row names to select. Not setting returns all data
 * @param bool | mixed $default - The default value to return if none exists
 * @param bool | array $where - An array of key/value pairs to match in a WHERE statement
 * @param bool $single | Return the first row of data, or all rows (when selecting commands)
 * @return array|bool|mixed
 */
function getPreference($table, $what = false, $default = false, $where = false, $single = true) {
	$config = initConfig();

	$data = $config->get($table, $what, $where);

	if (empty($data) && !is_array($data)) {
		return $default;
	}

	if ($table === 'general') {
		$tmp = [];
		foreach($data as $row) $tmp[$row['name']] = $row['value'];
		$data = [$tmp];
		if ($single && is_array($where)) $what = [$where['name']];
	}

	if ($single && count($data)) {
		$data = $data[0];
		if ($what && count($what) === 1) {
			$data = $data[$what[0]] ?? $default;
		}
	}


	return $data;
}

function deletePreference($table, $selector) {
	$config = initConfig();
	write_log("Got a command to delete from $table using: " . json_encode($selector));
	$result = $config->delete($table, $selector);
	write_log("Result is: $result");
	return $result;
}

function checkUpdate() {
	if (isWebApp()) return false;
	$updates = [];
	$git = new GitUpdate();
	if ($git->hasGit) {
		$updates = $git->checkMissing();
		$refs = $updates['refs'];
		writeSession('neededUpdates', $refs);
		$revision = $git->revision;
		$updates['last'] = $git->fetchCommits([$revision]);
		$updates['revision'] = $revision;
	}

	return $updates;
}

function checkRevision($short = false) {
	$git = new GitUpdate();
	$revision = ($git->hasGit) ? $git->revision : false;
	write_log("REVISION: $revision","INFO", false, true);
	return ($short && $revision) ? substr($revision, 0, 7) : $revision;
}

function installUpdate() {
	write_log("Function firsssed!!");
	$git = new GitUpdate();
	$result = [];
	if ($git->hasGit) {
		write_log("We have gitUpdate!");
		$installed = $git->update();
		$updates = $_SESSION['neededUpdates'] ?? false;
		if ($installed && $updates) {
			write_log("Updates installed, saving last refs...");
			writeSession('neededUpdates', [], true);
			$revision = $git->revision;
			$result['last'] = $git->fetchCommits([$revision]);
			$result['revision'] = $revision;
			$result['commits'] = [];
		}
	}

	return $result;
}

function scriptDefaults() {
	$errorLogPath = file_build_path(dirname(__FILE__), '..', 'logs', 'Error.log.php');
	ini_set("log_errors", 1);
	ini_set("display_errors", 0);
	ini_set("display_startup_errors", 0);
	ini_set('max_execution_time', 300);
	ini_set("error_log", $errorLogPath);
	error_reporting(E_ERROR);
	date_default_timezone_set((date_default_timezone_get() ? date_default_timezone_get() : "America/Chicago"));
}

function checkDefaults() {
	$config = dirname(__FILE__) . "/../rw/db.conf.php";
	mapIcons(__DIR__ . '/../css/font/font-muximux.css', '.muximux-');
	$useDb = file_exists($config);
	$migrated = false;
	if ($useDb) {
		checkDefaultsDb($config);
		upgradeDbTable($config);
	} else {
		$jsonFile = dirname(__FILE__) . "/../rw/config.php";
		if (file_exists($jsonFile)) {
			migrateSettings($jsonFile);
			return ['migrated' => true];
		}
	}

	// Loading from General
	$defaults = getPreference('general', false, [], false, true);

	if (empty($defaults)) {
		write_log("Creating default values!", "ALERT");
		$currentAddress = currentAddress();
		$defaults = [
			'deviceId'      => randomToken(12),
			'forceSSL'      => false,
			'isWebApp'      => false,
			'noNewUsers'    => false,
			'deviceName'    => "Flex TV (Home)",
			'publicAddress' => $currentAddress,
			'revision'      => '000',
			'updates'       => "[]",
			'cleanLogs'     => true
		];

		foreach ($defaults as $key => $value) {
			$data = ['name' => $key, 'value' => $value];
			setPreference('general', $data, ["name" => $key]);
		}

		$valid = validateIp($currentAddress);
		if ($valid) {
			setStartUrl();
		}

	}
	return $defaults;
}

function migrateSettings($jsonFile) {
	write_log("Migrating settings.", "ALERT", false, false, true);
	$db = [
		'path' => __DIR__ . "/../rw/db"
	];
	$database = $jsonArray = false;
	$jsonData = file_get_contents($jsonFile);
	if ($jsonData) {
		$jsonData = str_replace("'; <?php die('Access denied'); ?>", "", $jsonData);
		$jsonArray = json_decode($jsonData, true);
	}
	try {
		$database = new Database($db);
	} catch (Exception $e) {
		write_log("Exception occurred loading database.", "INFO", false, false, true);
	}

	if ($jsonArray && $database) {
		write_log("Converting configs...", "ALERT", false, false, true);
		foreach ($jsonArray as $section => $sectionData) {
			$table = $database->table($section);
			write_log("Creating $section table.", "ALERT", false, false, true);
			foreach ($sectionData as $record) {
				switch ($section) {
					case 'userdata':
						$rec = $table->get($record['apiToken']);
						break;
					case 'general':
						$rec = $table->get($record['name']);
						break;
					default:
						$rec = $table->get(uniqid());
				}
				foreach ($record as $key => $value) {
					$rec->$key = $value;
				}
				$rec->save();
			}
			file_put_contents(__DIR__ . "/../rw/db/$section/index.html", "SUCK IT.");
		}
		write_log("Conversion complete!", "INFO", false, false, true);
		rename($jsonFile, "$jsonFile.bak");
	}
}

function checkDefaultsDb($config) {
	$config = parse_ini_file($config);
	$db = $config['dbname'];
	$head = '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Getting some things ready...</title>
        </head>
        <body>
    <div style="text-align: center">';

	$tail = '</div>
                </body>
                </html>';

	$mysqli = new mysqli('localhost', $config['username'], $config['password']);
	$noDb = false;
	if (!$mysqli->select_db($db)) {
		$noDb = true;
		echo $head;
		echo "<span>Creating database...</span><br>" . PHP_EOL;
		write_log("No database exists, creating.", "ALERT");
		if (!$mysqli->query("CREATE DATABASE $db")) {
			write_log("Error creating database!", "ERROR");
			echo "<span>Error creating database, please check credentials!!</span><br>";
			echo $tail;
			die();
		} else {
			echo "<span>Database created successfully!</span><br>" . PHP_EOL;
			write_log("Created db successfully.");
			$mysqli->select_db($db);
		}
	}
	$tables = ['general', 'userdata', 'commands'];
	$created = false;
	foreach ($tables as $table) {
		$rows = [];
		$result = $mysqli->query("SHOW TABLES LIKE '$table'");
		while ($row = $result->fetch_assoc()) {
			$rows[] = $row;
		}
		if (!count($rows)) {
			$created = true;
			if (!$noDb) echo $head;
			echo "<span>Table $table doesn't exist, creating.</span><br>" . PHP_EOL;
			write_log("Table $table doesn't exist, creating...", "ALERT");
			$query = "";
			switch ($table) {
				case 'general':
					$query = "CREATE TABLE `general` (
 `name` varchar(250) NOT NULL,
 `value` longtext NOT NULL,
 PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1";
					break;
				case 'commands':
					$query = "CREATE TABLE `commands` (
 `stamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `apiToken` mediumtext NOT NULL,
 `data` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1";
					break;
				case 'userdata':
					$query = "CREATE TABLE `userdata` (
 `apiToken` varchar(42) NOT NULL,
 `plexUserName` tinytext NOT NULL,
 `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 `plexEmail` tinytext NOT NULL,
 `plexAvatar` longtext NOT NULL,
 `plexToken` tinytext NOT NULL,
 `publicAddress` mediumtext NOT NULL,
 `lastScan` tinytext NOT NULL,
 `returnItems` int(2) NOT NULL DEFAULT '6',
 `rescanTime` int(2) NOT NULL DEFAULT '10',
 `couchEnabled` tinyint(1) NOT NULL DEFAULT '0',
 `couchUri` tinytext NOT NULL,
 `couchToken` tinytext NOT NULL,
 `couchProfile` tinytext NOT NULL,
 `couchList` longtext NOT NULL,
 `headphonesEnabled` tinyint(1) NOT NULL DEFAULT '0',
 `headphonesUri` tinytext NOT NULL,
 `headphonesToken` tinytext NOT NULL,
 `ombiEnabled` tinyint(1) NOT NULL DEFAULT '0',
 `ombiUri` tinytext NOT NULL,
 `ombiToken` tinytext NOT NULL,
 `radarrEnabled` tinyint(1) NOT NULL DEFAULT '0',
 `radarrUri` tinytext NOT NULL,
 `radarrToken` tinytext NOT NULL,
 `radarrProfile` tinytext NOT NULL,
 `radarrRoot` tinytext NOT NULL,
 `radarrList` longtext NOT NULL,
 `sickEnabled` tinyint(1) NOT NULL DEFAULT '0',
 `sickToken` tinytext NOT NULL,
 `sickProfile` tinytext NOT NULL,
 `sickUri` tinytext NOT NULL,
 `sickList` longtext NOT NULL,
 `sonarrEnabled` tinyint(1) NOT NULL DEFAULT '0',
 `sonarrUri` tinytext NOT NULL,
 `sonarrToken` tinytext NOT NULL,
 `sonarrProfile` tinytext NOT NULL,
 `sonarrRoot` tinytext NOT NULL,
 `sonarrList` longtext NOT NULL,
 `lidarrEnabled` tinyint(1) NOT NULL DEFAULT '0',
 `lidarrUri` tinytext NOT NULL,
 `lidarrToken` tinytext NOT NULL,
 `lidarrProfile` tinytext NOT NULL,
 `lidarrRoot` tinytext NOT NULL,
 `lidarrList` tinytext NOT NULL,
 `watcherEnabled` tinyint(1) NOT NULL DEFAULT '0',
 `watcherUri` tinytext NOT NULL,
 `watcherToken` tinytext NOT NULL,
 `watcherList` tinytext NOT NULL,
 `watcherProfile` tinyint(4) NOT NULL,
 `darkTheme` tinyint(1) NOT NULL DEFAULT '0',
 `shortAnswers` tinyint(1) NOT NULL DEFAULT '0',
 `appLanguage` char(2) NOT NULL DEFAULT 'en',
 `searchAccuracy` int(3) NOT NULL DEFAULT '70',
 `hasPlugin` tinyint(1) NOT NULL DEFAULT '0',
 `alertPlugin` tinyint(1) NOT NULL,
 `notifyUpdate` tinyint(1) NOT NULL DEFAULT '0',
 `masterUser` tinyint(1) NOT NULL DEFAULT '0',
 `autoUpdate` tinyint(1) NOT NULL DEFAULT '0',
 `jsonDeviceArray` JSON NOT NULL,
 `plexPassUser` tinyint(1) NOT NULL,
 `plexServerId` tinytext NOT NULL,
 `plexDvrId` tinytext NOT NULL,
 `plexDvrReplaceLower` tinytext NOT NULL,
 `plexDvrKey` tinytext NOT NULL,
 `plexDvrEndOffsetMinutes` int(3) NOT NULL DEFAULT '2',
 `plexDvrStartOffsetMinutes` int(3) NOT NULL DEFAULT '2',
 `plexDvrResolution` text NOT NULL,
 `plexDvrNewAirings` tinyint(1) NOT NULL DEFAULT '0',
 `plexDvrComskipEnabled` tinyint(1) NOT NULL DEFAULT '0',
 `plexClientId` text NOT NULL,
 `hookEnabled` tinyint(1) NOT NULL DEFAULT '0',
 `hookPausedEnabled` int(1) NOT NULL DEFAULT '0',
 `hookPlayEnabled` int(1) NOT NULL DEFAULT '0',
 `hookFetchEnabled` int(1) NOT NULL DEFAULT '0',
 `hookCustomEnabled` int(1) NOT NULL DEFAULT '0',
 `hookSplitEnabled` int(1) NOT NULL DEFAULT '0',
 `hookStopEnabled` int(1) NOT NULL DEFAULT '0',
 `hookUrl` longtext NOT NULL,
 `hookPlayUrl` longtext NOT NULL,
 `hookPausedUrl` longtext NOT NULL,
 `hookFetchUrl` longtext NOT NULL,
 `hookCustomUrl` longtext NOT NULL,
 `broadcastDevice` tinytext NOT NULL,
 `quietStart` tinytext NOT NULL DEFAULT '20:00',
 `quietStop` tinytext NOT NULL DEFAULT '8:00',
 PRIMARY KEY (`apiToken`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1";
					break;
			}
			if (!$mysqli->query($query)) {
				write_log("Error creating table $table!", "ERROR");
				echo "<span>Unable to create table $table!</span><br>";
				echo $tail;
				die();
			} else {
				write_log("Table $table created successfully!");
				echo "<span>Table $table created successfully!</span><br>" . PHP_EOL;
			}
		}
	}
	if ($created) {
		echo "<span>All tables created successfully, page will reload in 10 seconds.<span><br>";
		echo "<script type='text/javascript'>
            setTimeout(function() {window.location.reload(true)},10000);
        </script>";
		echo $tail;
		die();
	}
}

function upgradeDbTable($config) {
	$config = parse_ini_file($config);
	$db = $config['dbname'];
	$mysqli = new mysqli('localhost', $config['username'], $config['password']);
	if ($mysqli->select_db($db)) {
		$checkQuery = "DESCRIBE userdata;";
		$columns = [];
		$results = $mysqli->query($checkQuery);
		if ($results) {
			while ($row = $results->fetch_assoc()) {
				$columns[] = $row['Field'];
			}
		}

		$addStrings = [];
		$dbStrings = [
			'plexClientName',
			"quietStart",
			"quietStop"
		];
		foreach ($dbStrings as $string) {
			if (!in_array($string, $columns)) {
				write_log("String $string is missing.");
				array_push($addStrings, $string);
			}
		}


		$addLong = [];
		$dbLong = ['appArray', 'widgetArray'];
		foreach ($dbLong as $long) {
			if (!in_array($long, $columns)) {
				write_log("Long $long is missing.");
				array_push($addLong, $long);
			}
		}
		if (count($addStrings) || count($addLong)) {
			write_log("We've gotta add some stuff here.");
			$query = "ALTER TABLE userdata" . PHP_EOL;
			$items = [];
			foreach ($addStrings as $item) {
				$items[] = "ADD COLUMN $item text NOT NULL";
			}
			foreach ($addLong as $item) {
				$items[] = "ADD COLUMN $item longtext NOT NULL";
			}
			$itemString = join(", " . PHP_EOL, $items);
			$query .= $itemString . ";";
			write_log("Final query is '$query'");
			$mysqli->query($query);
		}

		// Convert lists to proper JSON items
		$query = "ALTER TABLE `userdata` CHANGE `jsonDeviceArray` `jsonDeviceArray` JSON NOT NULL, CHANGE `widgetArray` `widgetArray` JSON NOT NULL, CHANGE `appArray` `appArray` JSON NOT NULL;";
		$mysqli->query($query);
	}
}

function checkSetDeviceID() {
	$deviceId = getPreference('general', ['value'], 'foo', ['name' => 'deviceId'],true);
	return $deviceId;
}

function checkSSL() {
	$forceSSL = getPreference('general', ['value'], false, ['name' => 'forceSSL'], true);
	return $forceSSL;
}

function isWebApp() {
	$isWebApp = file_exists(dirname(__FILE__) . "/../rw/db.conf.php");
	return $isWebApp;
}

function currentAddress() {
	$url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	if (strpos($url, "?") !== false) $url = strtok($url, '?');
	write_log("URL: $url");
	$url = str_replace("index.php", "", $url);
	write_log("URL: $url");
	return $url;
}

function serverAddress() {
	//write_log("function fired: ".json_encode(getSessionData()),"ALERT");
	$loggedIn = isset($_SESSION['apiToken']);
	$default = 'http://localhost';
	if ($loggedIn) {
		$serverAddress = getPreference('userdata', ['publicAddress'], $default, ['apiToken' => $_SESSION['apiToken']]);
	} else {
		$serverAddress = getPreference('general', 'value', $default, ['name' => 'publicAddress']);
	}
	while (is_array($serverAddress)) $serverAddress = $serverAddress[0] ?? "";

	return $serverAddress;
}

function fetchCommands($limit = false) {
	$commands = getPreference('userdata', ['commands'], [], ['apiToken' => $_SESSION['apiToken']], true);
	if (is_string($commands)) $commands = json_decode($commands, true);
	write_log("Fetched commands: ".json_encode($commands));
	if ($limit) $commands = array_slice($commands, 0, $limit);
	return $commands;
}

function fetchDeviceCache() {
	$list = [];
	$keys = ['jsonDeviceArray', 'plexServerId', 'plexDvrId', 'plexClientId'];
	$cache = getPreference('userdata', $keys, false, ['apiToken' => $_SESSION['apiToken']]);
	if (is_array($cache) && count($cache)) {
		$list = is_string($cache['jsonDeviceArray']) ? json_decode($cache['jsonDeviceArray'], true) : $cache['jsonDeviceArray'];
		unset($cache['jsonDeviceArray']);
		writeSessionArray($cache);
	}
	return $list;
}

function fetchAppArray() {
	$data = getPreference('userdata', ['jsonAppArray'], [], ['apiToken' => $_SESSION['apiToken']], true);
	$data2 = getPreference('userdata', ['appArray'], [], ['apiToken' => $_SESSION['apiToken']], true);
	if (is_string($data)) $data = json_decode($data, true);
	if ($data2) {
		$oldData = false;
		try {
			$oldData = base64_decode(json_decode($data2, true));
		} catch (Exception $e) {

		}
		if (is_array($oldData) && empty($data)) {
			write_log("Converting and storing old format app array...");
			$data = $oldData;
			updateUserPreference('jsonAppArray',$data);
			updateUserPreference('appArray',false);
		}
	}
	return $data;
}

function fetchWidgetArray() {
	$data = getPreference('userdata', ['jsonWidgetArray'], [], ['apiToken' => $_SESSION['apiToken']], true);
	if (is_string($data)) $data = json_decode($data, true);

	return $data;
}

function fetchUser($userData) {
	if (isset($userData['apiToken'])) {
		$selector = ['apiToken' => $userData['apiToken']];
	} else {
		$selector = ['plexEmail' => $userData['plexEmail']];
	}
	$data = getPreference('userdata', false, false, $selector);
	return $data;
}

function fetchUserData($rescan = false) {
	$temp = getPreference('userdata', false, false, ['apiToken' => $_SESSION['apiToken']],true);
	$data = [];
	foreach ($temp as $key => $value) {
		if (isJSON($value)) $value = json_decode($value, true);
		$value = scrubBools($value, $key);
		$data[$key] = $value;
	}
	$jsonDeviceArray = $data['jsonDeviceArray'] ?? false;
	$aList = $data['appArray'] ?? false;
//	if ($aList) {
//		if (is_string($jsonDeviceArray)) $aList = json_decode($aList, true);
//	}
	$data['appArray'] = $aList;
	$devices = is_string($jsonDeviceArray) ? json_decode($jsonDeviceArray, true) : $jsonDeviceArray;
	if ($rescan || !$devices) $devices = scanDevices(true);
	if (isset($data['jsonDeviceArray'])) unset($data['jsonDeviceArray']);
	$data['deviceList'] = $devices;
	return $data;
}

function fetchGeneralData() {
	$data = getPreference('general', false, [], false, true);

	return $data;
}

function logCommand($resultObject) {

	if (isset($_GET['noLog'])) {
		write_log("UI command, not logging.");
		return;
	}

	$resultObject = (!is_array($resultObject)) ? json_decode($resultObject, true) : $resultObject;

	$initial = $resultObject['initialCommand'] ?? "";
	$speech = $resultObject['speech'] ?? "";

	$logItem = [
		'speech' => $speech,
		'initialCommand' => $initial,
		'cards' => $resultObject['cards'] ?? [],
		'stamp' => date("Y-m-d h:m:s")
	];

	write_log("Final response for request of '$initial' is '$speech': ".json_encode($logItem), "ALERT");
	if (isset($_GET['say'])) echo json_encode(['commands' => $logItem]);

	$apiToken = $_SESSION['apiToken'];
	if (trim($apiToken) && count($resultObject)) {
		$commands = fetchCommands();
		$commands = array_reverse($commands);
		array_push($commands, $logItem);
		$commands = array_slice($commands, 0, 10);
		updateUserPreference('commands',array_reverse($commands));
	} else {
		write_log("No token or data, skipping log.", "WARNING");
	}
}

function firstUser() {
	$data = getPreference('userdata', false, []);
	$isFirst = (is_array($data) && count($data)) ? false : true;
	if ($isFirst) write_log("HELLO, MASTER.", "ALERT");
	return $isFirst;
}

function newUser($user) {
	$userName = $user['plexUserName'];
	$apiToken = randomToken(21);
	$user['apiToken'] = $apiToken;
	$_SESSION['apiToken'] = $apiToken;
	$currentAddress = currentAddress();
	$defaults = [
		'returnItems'               => '6',
		'rescanTime'                => '6',
		'couchUri'                  => 'http://localhost',
		'sonarrUri'                 => 'http://localhost',
		'sickUri'                   => 'http://localhost',
		'radarrUri'                 => 'http://localhost',
		'plexDvrResolution'         => '0',
		'plexDvrNewAirings'         => 'true',
		'plexDvrStartOffsetMinutes' => '2',
		'plexDvrEndOffsetMinutes'   => '2',
		'appLanguage'               => getLocale(),
		'searchAccuracy'            => '70',
		'darkTheme'                 => true,
		'hasPlugin'                 => false,
		'notifyUpdate'              => false,
		'masterUser'                => firstUser(),
		'publicAddress'             => $currentAddress,
		'shortAnswers'              => false,
		'autoUpdate'                => false,
		'quietStart'                => "20:00",
		'quietStop'                 => "8:00"
	];
	$user = array_merge($user, $defaults);
	if (validateIp($currentAddress)) setStartUrl();
	write_log("Creating and saving $userName as a new user: " . json_encode($user), "ALERT");
	setPreference('userdata', $user, ['apiToken' => $apiToken]);
	writeSessionArray($user);
	$_SESSION['plexToken'] = $user['plexToken'];
	return $user;
}

function popCommand($id) {
	write_log("Popping it like it's hot.");
	$commands = fetchCommands();
	if (($key = array_search($commands, $id)) !== false) {
		unset($commands[$key]);
		updateUserPreference('commands',json_encode($commands));
	}
}

function validateIp($address) {
	$parts = parse_url($address);
	$user_ip = $parts['host'];
	$isIP = (bool)ip2long($user_ip);
	return ($isIP) ? (filter_var(
		$user_ip,
		FILTER_VALIDATE_IP,
		FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
	)) : true;

}

function verifyApiToken($apiToken) {
	$data = false;
	if (trim($apiToken)) {
		$keys = [
			'plexUserName', 'plexEmail', 'apiToken', 'plexAvatar', 'plexPassUser', 'plexToken', 'apiToken',
			'appLanguage', 'darkTheme'
		];
		$data = getPreference('userdata', $keys, false, ['apiToken' => $apiToken]);
	}
	if (!$data) {
		write_log("ERROR, api token $apiToken not recognized, called by " . getCaller(), "ERROR");
		dumpRequest();
		sendLogout();
	} else {
		if (!session_started()) {
			$ok = @session_start();
			if (!$ok) {
				write_log("REGENERATING SESSION ID.", "WARN", false, true, true);
				session_regenerate_id(true);
				session_start();
			}
		}
		writeSessionArray($data);
	}
	return $data;
}

function sendLogout() {
	write_log("Terminating session.","INFO",false,true,true);
	header("Content-Type: application/json");
	clearSession();
	$result['dologout'] = true;
	echo json_encode($result);
	die();
}

function checkGit() {
	if (isset($_SESSION['hasGit'])) {
		return $_SESSION['hasGit'];
	} else {
		exec("gitUpdate", $lines);
		$hasGit = (preg_match("/gitUpdate help/", implode(" ", $lines)));
		writeSession('hasGit', $hasGit);
	}
	return $hasGit;
}

function checkFiles() {
	if (isWebApp()) return [];
	$messages = [];
	$extensions = [
		'curl',
		'xml'
	];

	$logDir = file_build_path(dirname(__FILE__), "..", "logs");
	$rwDir = file_build_path(dirname(__FILE__), "..", "rw");
	$dbDir = file_build_path($rwDir, "db");
	$genDir = file_build_path($dbDir, "general");
	$userDir = file_build_path($dbDir, "userdata");
	$cmdDir = file_build_path($dbDir, "commands");
	$logPath = file_build_path($logDir, "Main.log.php");
	$errorLogPath = file_build_path($logDir, "Error.log.php");
	$updateLogPath = file_build_path($logDir, "Update.log.php");

	$dirs = [$rwDir, $dbDir, $logDir, $genDir, $userDir, $cmdDir];

	$files = [
		$logPath,
		$errorLogPath,
		$updateLogPath
	];

	$secureString = "'; <?php die('Access denied'); ?>";
	foreach ($dirs as $dir) {
		if (!file_exists($dir)) {
			if (!mkdir($dir, 0777, true)) {
				$message = "Unable to create directory at '$dir', please check permissions and try again.";
				$error = [
					'title'   => 'Permission error.',
					'message' => $message,
					'url'     => false
				];
				array_push($messages, $error);
			}
		}
		if (!file_exists("${dir}/index.html")) file_put_contents("${dir}/index.html", "ACCESS DENIED");
	}

	foreach ($files as $file) {
		if (!file_exists($file)) {
			$name = basename($file);
			write_log("Creating file $name", "INFO", false, false, true);
			touch($file);
			chmod($file, 0777);
			file_put_contents($file, $secureString);
		}
		if ((file_exists($file) && (!is_writable(dirname($file)) || !is_writable($file))) || !is_writable(dirname($file))) { // If file exists, check both file and directory writeable, else check that the directory is writeable.
			$message = 'Either the file ' . $file . ' and/or it\'s parent directory is not writable by the PHP process. Check the permissions & ownership and try again.';
			$url = '';
			if (PHP_SHLIB_SUFFIX === "so") { //Check for POSIX systems.
				$message .= "  Current permission mode of " . $file . " is " . decoct(fileperms($file) & 0777);
				$message .= "  Current owner of " . $file . " is " . posix_getpwuid(fileowner($file))['name'];
				$message .= "  Refer to the README on instructions how to change permissions on the aforementioned files.";
				$url = 'http://www.computernetworkingnotes.com/ubuntu-12-04-tips-and-tricks/how-to-fix-permission-of-htdocs-in-ubuntu.html';
			} else if (PHP_SHLIB_SUFFIX === "dll") {
				$message .= "  Detected Windows system, refer to guides on how to set appropriate permissions."; //Can't get fileowner in a trivial manner.
				$url = 'https://stackoverflow.com/questions/32017161/xampp-on-windows-8-1-cant-edit-files-in-htdocs';
			}
			write_log($message, "ERROR");

			$error = [
				'title'   => 'File error.',
				'message' => $message,
				'url'     => $url
			];
			array_push($messages, $error);
		}
	}

	foreach ($extensions as $extension) {
		if (!extension_loaded($extension)) {
			$message = "The " . $extension . " PHP extension, which is required for Flex TV to work correctly, is not loaded." . " Please enable it in php.ini, restart your webserver, and then reload this page to continue.";
			write_log($message, "ERROR");
			$url = "http://php.net/manual/en/book.$extension.php";
			$error = [
				'title'   => 'PHP Extension not loaded.',
				'message' => $message,
				'url'     => $url
			];
			array_push($messages, $error);
		}
	}

	return $messages;
}

function deviceName() {
	$app = isWebApp() ? 'Web' : 'Home';
	return "Flex TV ($app)";
}


function isJSON($string) {
	return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
}

function parseUpdateLog($log) {
	$html = '';
	foreach ($log as $commit) {
		$html .= '
                            <div class="panel panel-primary">
                                <div class="panel-heading cardHeader">
                                    <div class="panel-title">' . $commit['shortHead'] . ' - ' . $commit['date'] . '</div>
                                </div>
                                <div class="panel-body cardHeader">
                                    <b>' . $commit['subject'] . '</b><br>' . $commit['body'] . '
                                </div>
                            </div>';
	}
	return $html;
}

function readUpdate() {
	$log = false;
	$filename = file_build_path(dirname(__FILE__), "..", 'logs', "Phlex_update.log.php");
	if (file_exists($filename)) {
		$authString = "'; <?php die('Access denied'); ?>" . PHP_EOL;
		$file = file_get_contents($filename);
		$file = str_replace($authString, "", $file);
		$log = json_decode($file, true) ?? [];
	}
	return $log;
}


function verifyPlexToken($token) {
	$user = $userData = false;
	$url = "https://plex.tv/users/account?X-Plex-Token=$token";
	$data = curlGet($url, ['Accept: application/json']);
	if ($data) {
		write_log("Received userdata from Plex: " . json_encode($data), "INFO", false, true);
		$userData = [
			'plexUserName' => $data['title'] ?? $data['username'],
			'plexEmail'    => $data['email'],
			'plexAvatar'   => $data['thumb'],
			'plexPassUser' => ($data['roles']['role']['id'] == "plexpass"),
			'plexToken'    => $data['authToken']
		];
	}
	if ($userData) {
		write_log("Recieved valid user data.", "INFO");
		$user = fetchUser($userData);
		if (!$user) {
			write_log("User fetch failed, tring to create new.");
			$user = newUser($userData);
		}
	}

	if ($user) {
		write_log("We have the user, should be setting token here...");
		if (!session_started()) {
			$ok = @session_start();
			if (!$ok) {
				write_log("REGENERATING SESSION ID.", "WARN", false, true, true);
				session_regenerate_id(true);
				session_start();
			}
		}
		writeSessionArray($user);
		write_log("Session token: " . $_SESSION['apiToken']);
		updateUserPreferenceArray($userData);
	}
	return $user;
}

function webAddress() {
	return serverAddress();
}

