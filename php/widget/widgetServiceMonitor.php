<?php
/**
 * Created by PhpStorm.
 * User: digitalhigh
 * Date: 10/30/2018
 * Time: 9:18 AM
 */

namespace digitalhigh;

require_once dirname(__FILE__) . "/widgetException.php";

class widgetServiceMonitor {

	public $target;
	public $color;
	public $icon;
	public $label;
	public $url;
	public $online;
	public $type;

	/**
	 * widgetServiceMonitor constructor.
	 * @param $data
	 * @throws widgetException
	 */
	function __construct($data) {

		$target = $data['target'] ?? false;
		$color = $data['color'] ?? false;
		$icon = $data['icon'] ?? false;
		$label = $data['label'] ?? false;
		$url = $data['url'] ?? false;

		if (!$target || !$color || !$icon || !$label || !$url) {
			$param = "";
			if (!$target) $param .= " target";
			if (!$color) $param .= " color";
			if (!$icon) $param .= " icon";
			if (!$label) $param .= " label";
			if (!$url) $param .= " url";
			throw new widgetException("Missing parameters - '$param' in: ".json_encode($data));
		}

		$this->target = $target;
		$this->color = $color;
		$this->icon = $icon;
		$this->label = $label;
		$this->url = $url;
		$this->online = false;
		$this->type = "statusMonitor";
	}

	public function testConnection() {
			//check, if a valid url is provided
			if(!filter_var($this->url, FILTER_VALIDATE_URL))
			{
				$this->online = false;
			}

			//initialize curl
			$curlInit = curl_init($this->url);
			curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT,10);
			curl_setopt($curlInit,CURLOPT_HEADER,true);
			curl_setopt($curlInit,CURLOPT_NOBODY,true);
			curl_setopt($curlInit,CURLOPT_RETURNTRANSFER,true);
			curl_setopt($curlInit, CURLOPT_SSL_VERIFYPEER, false);

			//get answer
			$response = curl_exec($curlInit);

			curl_close($curlInit);

			if ($response) {
				$this->online = true;
			} else {
				$this->online = false;
			}
			return $this->online;
	}

	public function update() {
		$this->testConnection();
		return $this->serialize();
	}

	public function serialize() {
		return [
			'target' => $this->target,
			'color' => $this->color,
			'icon' => $this->icon,
			'label' => $this->label,
			'url' => $this->url,
			'online' => $this->online,
			'type' => $this->type
		];
	}
}