<?php
/**
 * Created by PhpStorm.
 * User: digitalhigh
 * Date: 10/30/2018
 * Time: 9:18 AM
 */

namespace digitalhigh;

require_once dirname(__FILE__) . "/widgetException.php";

class widgetStatusMonitor {

	public $widgetId;
	public $target;
	public $color;
	public $icon;
	public $label;
	public $url;
	public $online;

	private $type;
	private $lastUpdate;
	private $refreshInterval;

	/**
	 * widgetStatusMonitor constructor.
	 * @param $data
	 * @param int $refreshInterval
	 * @throws widgetException
	 */
	function __construct($data, $refreshInterval = 10) {

		$widgetId = $data['id'] ?? false;
		$target = $data['target'] ?? false;
		$color = $data['color'] ?? false;
		$icon = $data['icon'] ?? false;
		$label = $data['label'] ?? false;
		$url = $data['url'] ?? false;
		$online = $data['online'] ?? false;

		if (!$widgetId || !$target || !$color || !$icon || !$label || !$url) {
			$param = "";
			if (!$widgetId) $param .= " id";
			if (!$target) $param .= " target";
			if (!$color) $param .= " color";
			if (!$icon) $param .= " icon";
			if (!$label) $param .= " label";
			if (!$url) $param .= " url";
			throw new widgetException("Missing parameters - '$param' in: ".json_encode($data));
		}

		$this->widgetId = $widgetId;
		$this->target = $target;
		$this->color = $color;
		$this->icon = $icon;
		$this->label = $label;
		$this->url = $url;
		$this->online = $online;

		$this->type = "statusMonitor";
		$this->refreshInterval = $refreshInterval;
		$this->lastUpdate = $data['lastUpdate'] ?? (time() - $refreshInterval);
	}

	public function testConnection() {
			//check, if a valid url is provided
			if(!filter_var($this->url, FILTER_VALIDATE_URL)) {
				$this->online = false;
			} else {
				write_log("Testing connection for service...","INFO", false, true);
				$curlInit = curl_init($this->url);
				curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 10);
				curl_setopt($curlInit, CURLOPT_HEADER, true);
				curl_setopt($curlInit, CURLOPT_NOBODY, true);
				curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curlInit, CURLOPT_SSL_VERIFYPEER, false);

				$response = curl_exec($curlInit);
				curl_close($curlInit);

				$result = ($response !== false);
				if ($result) write_log("ONLINE - $result"); else write_log("OFFLINE");
				$this->online = ($response !== false);
			}
			return $this->online;
	}

	public function update() {
		$lastUpdate = $this->lastUpdate;
		$int = $this->refreshInterval;
		$total = $lastUpdate + $int;
		$now = time();
		$diff = $now - $total;
		write_log("Last update is '$lastUpdate', interval is $int, total is $total, now is $now, diff is $diff","WARN", false, true);
		if ($now > $total) {
			write_log("Updating status monitor widget.","WARN", false, true);
			$this->lastUpdate = time();
			$this->testConnection();
		}
		return $this->serialize();
	}

	public function serialize() {
		return [
			'id' => $this->widgetId,
			'target' => $this->target,
			'color' => $this->color,
			'icon' => $this->icon,
			'label' => $this->label,
			'url' => $this->url,
			'online' => $this->online,
			'type' => $this->type,
			'lastUpdate' => $this->lastUpdate
		];
	}
}