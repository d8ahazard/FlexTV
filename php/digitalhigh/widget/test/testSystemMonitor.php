<?php

require_once dirname(__FILE__) . "/../src/monitor/linuxMonitor.php";

use digitalhigh\widget\monitor;

$data = [];
if (isset($_GET['cpu'])) {
	$data['cpu'] = (new monitor\linuxMonitor())::getCpu();
}

if (isset($_GET['memory'])) {
	$data['memory'] = (new monitor\linuxMonitor())::getMemory();
}

if (isset($_GET['disk'])) {
	$data['disk'] = (new monitor\linuxMonitor())::getDisk();
}

if (isset($_GET['temp'])) {
	$data['temp'] = (new monitor\linuxMonitor())::getTemp();
}

header("Content-type: application/json");
echo json_encode($data, JSON_PRETTY_PRINT);