<?php

namespace digitalhigh\widget\base;

use digitalhigh\widget\exception\widgetException;

class widgetBase {

	public $gsId;
	public $gsWidth;
	public $gsHeight;
	public $gsX;
	public $gsY;
	public $maxWidth;
	public $maxHeight;
	public $minWidth;
	public $minHeight;
	public $noResize;
	public $noMove;
	public $autoPosition;
	public $locked;

	public $type;
	public $lastUpdate;
	public $refreshInterval;

	public $DEFAULT_REFRESH_INTERVAL = 30;

	/**
	 * widgetBase constructor.
	 * @param $data
	 * @throws widgetException
	 */

	function __construct($data) {

		// Change this per widget name, then add a loader in widget.php
		$this->type = 'serverStatus';

		// Load refresh interval and last update time
		$this->refreshInterval = $data['refreshInterval'] ?? $this->DEFAULT_REFRESH_INTERVAL;
		$this->lastUpdate = $data['lastUpdate'] ?? (time() - $this->refreshInterval);

		// List of properties to require, and props to filter from general data
		$required = ['gs-x', 'gs-y', 'gs-height', 'gs-width'];

		// Make sure we have something we can put back in the UI
		foreach($required as $key) {
			if (!isset($data[$key])) throw new widgetException("Required key $key is missing.");
		}

		// Store required UI props
		$this->gsId = $data['gs-id'] ?? $data['id'] ?? rand(1,1000);
		$this->gsX = $data['gs-x'];
		$this->gsY = $data['gs-y'];
		$this->gsHeight = $data['gs-height'];
		$this->gsWidth = $data['gs-width'];

		// Store optional UI props
		if (isset($data['gs-max-width'])) $this->maxWidth = $data['gs-max-width'];
		if (isset($data['gs-min-width'])) $this->maxWidth = $data['gs-min-width'];
		if (isset($data['gs-max-height'])) $this->maxWidth = $data['gs-max-height'];
		if (isset($data['gs-min-height'])) $this->maxWidth = $data['gs-min-height'];

		$this->noResize = $data['gs-no-resize'] ?? false;
		$this->noMove = $data['gs-no-move'] ?? false;
		$this->autoPosition = false;
		$this->locked = $data['gs-locked'] ?? false;

	}


	public function serialize() {
		$data = [
			'type' => 'serverStatus',
			'refreshInterval' => $this->refreshInterval,
			'lastUpdate' => $this->lastUpdate
		];

		$data['gs-id'] = $this->gsId;
		$data['gs-x'] = $this->gsX;
		$data['gs-y'] = $this->gsY;
		$data['gs-height'] = $this->gsHeight;
		$data['gs-width'] = $this->gsWidth;

		$data['gs-max-width'] = $this->maxWidth;
		$data['gs-min-width'] = $this->maxWidth;
		$data['gs-max-height'] = $this->maxWidth;
		$data['gs-min-height'] = $this->maxWidth;

		$data['gs-no-resize'] = $this->noResize;
		$data['gs-no-move'] = $this->noMove;
		$data['gs-auto-position'] = $this->autoPosition;
		$data['gs-locked'] = $this->locked;

		return $data;
	}

}