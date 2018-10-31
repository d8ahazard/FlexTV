<?php

namespace digitalhigh;

require_once dirname(__FILE__) . "/widgetException.php";

class widgetGeneric {
	public $data;

	public function __construct($data) {
		$this->data = $data;
	}

	public function update() {
		return $this->serialize();
	}

	public function serialize() {
		return $this->data;
	}

}