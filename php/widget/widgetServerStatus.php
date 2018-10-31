<?php

namespace digitalhigh;

require_once dirname(__FILE__) . "/widgetException.php";
require_once dirname(__FILE__) . "/../multiCurl.php";



class widgetServerStatus {

	public $widgetId;
	public $serverId;
	public $serverAddress;
	public $serverToken;
	public $serverName;
	public $sessionData;
	public $libraryData;

	private $type;
	private $lastUpdate;
	private $refreshInterval;

	/**
	 * widgetServerStatus constructor.
	 * @param $data
	 * @param int $refreshInterval
	 * @throws widgetException
	 */
	function __construct($data, $refreshInterval = 30) {
		$serverId = $data['target'] ?? false;
		$serverToken = $data['token'] ?? false;
		$serverName = $data['label'] ?? false;
		$serverAddress = $data['url'] ?? false;
		$widgetId = $data['id'] ?? false;
		if (!$widgetId || !$serverId || !$serverToken || !$serverName || !$serverAddress) {
			$param = "";
			if (!$widgetId) $param .= " widgetId";
			if (!$serverId) $param .= " serverId";
			if (!$serverToken) $param .= " serverToken";
			if (!$serverName) $param .= " serverName";
			if (!$serverId) $param .= " serverId";
			if (!$serverAddress) $param .= " serverAddress";
			throw new widgetException("Missing parameters - '$param' in: ".json_encode($data));
		}
		$this->sessionData = $data['sessionData'] ?? [];
		$this->libraryData = $data['libraryData'] ?? [];

		$this->widgetId = $widgetId;
		$this->serverId = $serverId;
		$this->serverAddress = $serverAddress;
		$this->serverToken = $serverToken;
		$this->serverName = $serverName;

		$this->type = 'serverStatus';
		$this->refreshInterval = $refreshInterval;
		$this->lastUpdate = $data['lastUpdate'] ?? (time() - $refreshInterval);
	}

	public function update() {
		$lastUpdate = $this->lastUpdate;
		$int = $this->refreshInterval;
		$total = $lastUpdate + $int;
		$now = time();
		write_log("Last update is '$lastUpdate', interval is $int, total is $total, now is $now","INFO", false, true);
		if ($now > $total) {
			write_log("Updating widget!","INFO", false, true);
			$this->lastUpdate = time();
			$address = $this->serverAddress;
			$token = $this->serverToken;
			$url1 = "${address}/status/sessions?X-Plex-Token=$token";
			$url2 = "${address}/stats/library?X-Plex-Token=$token";
			$header = ["Aceept: application/json"];
			$queries = ["sessions" => [$url1, $header], "library" => [$url2, $header]];
			write_log("INITMC: " . json_encode($queries), "INFO", false, true, true);
			$mc = new multiCurl($queries);

			$results = $mc->process();
			write_log("Results: " . json_encode($results), "ALERT", false, true, true);
			$libraryData = $results['library']['MediaContainer']['Section'] ?? [];
			$sections = [];
			foreach ($libraryData as $section) {
				write_log("SECTION: " . json_encode($section));
				$sectionItem = [];
				foreach ($section as $key => $value) {
					if (!is_array($value)) $sectionItem[$key] = $value;
				}
				array_push($sections, $sectionItem);
			}
			$this->libraryData = $sections;
			$this->sessionData = $results['session']['MediaContainer'] ?? [];
		}
		return $this->serialize();
	}

	public function serialize() {
		return [
			'id' => $this->widgetId,
			'target' => $this->serverId,
			'url' => $this->serverAddress,
			'token' => $this->serverToken,
			'label' => $this->serverName,
			'sectionData' => $this->libraryData,
			'sessionData' => $this->sessionData,
			'type' => $this->type,
			'lastUpdate' => $this->lastUpdate
		];
	}
}