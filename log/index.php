<?php
require_once dirname(__FILE__) . '/../php/vendor/autoload.php';
require_once dirname(__FILE__) . "/../php/webApp.php";
require_once dirname(__FILE__) . '/../php/util.php';
require_once dirname(__FILE__) . '/MultiTail.php';
use digitalhigh\MultiTail;

error_reporting(0);
if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
	if(!ob_start("ob_gzhandler")) ob_start();
} else ob_start();
if (!session_started()) {
	$ok = @session_start();
	if (!$ok) {
		write_log("REGENERATING SESSION ID.", "WARN", false, true, true);
		session_regenerate_id(true);
		session_start();
	}
}
if (isset($_GET['test'])) {
    header("Content-Type: text/plain");
	echo ini_get('open_basedir').PHP_EOL;
	echo php_ini_loaded_file().PHP_EOL;
}

$logPath = realpath(ini_get("error_log"));
$output['PHP Error'] = $logPath;

$config = parse_ini_file("./config.ini", true);

$files = $config['files'] ?? false;
$directories = $config['directories']['path'] ?? false;


if ($files) {
    foreach($files as $key => $fileCheck) {
	    if (isset($_GET['test'])) echo "$key for $fileCheck" . PHP_EOL;

	    if (is_array($fileCheck)) {
            foreach($fileCheck as $file) {
                $valid = validLog($file);
                if ($valid && !in_array($valid, $output)) {
                    $output[$key] = $valid;
                    break;
                }
            }
        } else if (is_string($fileCheck)) {
	        $valid = validLog($fileCheck);
	        if ($valid && !in_array($valid, $output)) $output[$key] = $valid;
        }
    }
}

if ($directories) {
    if (isset($_GET['test'])) echo json_encode($directories) . PHP_EOL;
    foreach($directories as $name => $dir) {
        $files = glob("$dir/*log*");
        foreach($files as $file) {
            if (!preg_match("/old/", $file)) {
	            $valid = validLog($file);
	            if ($valid && !in_array($valid, $output)) $output[$name."_".basename($valid)] = $valid;
            }
        }
    }
}

$out = [];
foreach ($output as $name => $path) {
	$out[$name] = [
		"path" => $path,
		"line" => 0
	];
}

if (isset($_GET['test'])) echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
$logs = $out;

$noHeader = $_GET['noHeader'] ?? false;

/**
 * We're getting an AJAX call
 */
if(isset($_GET['fetch']))  {
//	if (!isset($_GET['apiToken']) || isWebApp()) {
//		die("Unauthorize access detected.");
//	} else {
//		$apiToken = $_GET['apiToken'];
//		if (!verifyApiToken($apiToken)) {
//			write_log("Invalid API Token used for logfile access.");
//			die("Invalid API Token");
//		}
//	}
    $refresh = isset($_GET['refresh']);

    if ($refresh && isset($_SESSION['logs'])) $logs = $_SESSION['logs'];
	$tail = new MultiTail($logs, $noHeader);
	header("Content-Type: application/json");
	echo json_encode($tail->fetch(), JSON_PRETTY_PRINT);
	$_SESSION['logs'] = $tail->logs;
	die();
}

function validLog($file) {
    $result = false;
	try {
		$path = realpath($file);
		if (is_readable($path)) {
			$result = $path;
		}
	} catch (Exception $e) {
	}
	if (isset($_GET['test'])) echo ($result ? "$file is valid" : "$file is not valid") . PHP_EOL;
	return $result;
}

if (isset($_GET['test'])) die;

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Flex TV Log Viewer</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css" integrity="sha384-B4dIYHKNBt8Bc12p+WXckhzcICo0wtJAoU8YZTY5qE0Id1GSseTk6S+L3BlXeVIU" crossorigin="anonymous">
	<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/themes/smoothness/jquery-ui.css"/>
	<link rel="stylesheet" href="./css/log.css" />

	<script type="text/javascript">

	</script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <a class="navbar-brand" href="#">Logs</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Files
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <div class="form-group">
                        <label for="docSelect" class="dd-label btn btn-info selectAll">Select All</label>
                        <select multiple class="form-control tableFilter" id="docSelect">

                        </select>
                    </div>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Level
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <div class="form-group">
                        <label for="levelSelect" class="dd-label btn btn-info selectAll">Select All</label>
                        <select multiple class="form-control tableFilter" id="levelSelect">
                            <option value="DEBUG" selected>Debug</option>
                            <option value="INFO" selected>Info</option>
                            <option value="WARN" selected>Warn</option>
                            <option value="ERROR" selected>Error</option>
                            <option value="CRITICAL" selected>Critical</option>
                            <option value="FATAL" selected>Fatal</option>
                            <option value="ALERT" selected>Alert</option>
                            <option value="ORANGE" selected>Orange</option>
                            <option value="PINK" selected>Pink</option>
                        </select>
                    </div>
                </div>
            </li>
        </ul>
        <div class="form-inline my-2 my-lg-0">
            <input class="form-control mr-sm-2" type="text" placeholder="Filter" aria-label="Filter" id="textFilter">
            <div class="btn-group">
                <button class="btn btn-outline-info my-2 my-sm-0" id="filterLog"><i class="fas fa-filter"></i></button>
                <button class="btn btn-outline-warn my-2 my-sm-0" id="clearFilter"><i class="fas fa-times"></i></button>
            </div>
        </div>
    </div>
</nav>
<div class="contents table-responsive-md">
    <table class="table table-striped table-dark table-bordered table-hover table-sm" id="log">
        <thead>
            <tr id="headerRow">
                <th scope="col">#</th>
                <th scope="col">Doc</th>
                <th scope="col">Level</th>
                <th scope="col">Stamp</th>
                <th scope="col">User</th>
                <th scope="col" class="funcCol">Function</th>
                <th scope="col">Body</th>
            </tr>
        </thead>
        <tbody id="results" class="results">
        </tbody>
    </table>
</div>
<div class="load-div"><div></div><div></div><div></div></div>

<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<script type="text/javascript">
    var logData = JSON.parse('<?php echo json_encode($_SESSION['logs']); ?>');
</script>
<script src="./js/log.js"></script>
</body>
</html>
