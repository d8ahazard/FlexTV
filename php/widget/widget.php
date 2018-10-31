<?php
/**
 * Created by PhpStorm.
 * User: digitalhigh
 * Date: 10/30/2018
 * Time: 9:16 AM
 */

namespace digitalhigh;

require_once dirname(__FILE__) . "/widgetGeneric.php";
require_once dirname(__FILE__) . "/widgetStatusMonitor.php";
require_once dirname(__FILE__) . "/widgetServerStatus.php";
require_once dirname(__FILE__) . "/widgetException.php";


class widget {
	public $type;
	private $widgetObject;

	/**
	 * widget constructor.
	 * @param $type
	 * @param $data
	 * @throws widgetException
	 */
	function __construct($type, $data) {
		switch ($type) {
			case 'statusMonitor':
				$widgetObject =  new widgetStatusMonitor($data);
				break;
			case 'serverStatus':
				$widgetObject = new widgetServerStatus($data);
				break;
			default:
				$widgetObject = new widgetGeneric($data);
		}
		$this->widgetObject = $widgetObject;
		return $this->widgetObject;
	}

	public function serialize() {
		return $this->widgetObject->serialize();
	}

	public function update() {
		return $this->widgetObject->update();
	}

}