<?php

namespace digitalhigh\widget\template;

use digitalhigh\widget\exception\widgetException;

class widgetGeneric {
	// Unique ID for each widget
	// Data store for other values
	private $data;
	// Required values in order for other things to work
	const required = [];
	// Set these accordingly
	const maxWidth = 3;
	const maxHeight = 3;
	const minWidth = 1;
	const minHeight = 1;
	const refreshInterval = 30;
	const type = "Generic";
	/**
	 * widgetStatusMonitor constructor.
	 * @param $data
	 * @throws widgetException
	 */
	function __construct($data) {
		$data['type'] = self::type;

		foreach(self::required as $key)	{
			if (!isset($data[$key])) throw new widgetException("Missing required key $key");
		}
		$this->data = $data;
		$this->data['service-status'] = $data['service-status'] ?? "offline";
		$this->data['gs-max-width'] = self::maxWidth;
		$this->data['gs-min-width'] = self::minWidth;
		$this->data['gs-max-height'] = self::maxHeight;
		$this->data['gs-min-height'] = self::minHeight;
	}


	public function update($force=false) {
		$lastUpdate = $this->data['lastUpdate'];
		$int = self::refreshInterval;
		$total = $lastUpdate + $int;
		$now = time();
		if ($now > $total || $force) {
			$this->data['lastUpdate'] = time();
			// Do stuff here to update
		}
		return $this->serialize();
	}

	public function serialize() {
		return $this->data;
	}

	public static function widgetHTML() {
		// As odd as it may seem, this is where we set our "default" values for the widget.
		// Auto-position will be turned off when the widget is created.
		$attributes = [
			'gs-x' => 7,
			'gs-y' => 0,
			'gs-width' =>3,
			'gs-height' => 1,
			'type' => self::type,
			'gs-min-width' => self::minWidth,
			'gs-min-height' => self::minHeight,
			'gs-max-width' => self::maxWidth,
			'gs-max-height' => self::maxHeight,
			'gs-auto-position' => true
		];
		$attributeStrings = [];
		foreach($attributes as $key => $value) $attributeStrings[] ="data-${key}='${value}'";
		$attributeString = join(" ", $attributeStrings);

		// Add widget-header to display a header/title
		// Use widget-no-header to only show drag handle and menu dots

		return '
		<div class="widgetCard card m-0 grid-stack-item '.self::type.'" '.$attributeString.'>
			<div class="grid-stack-item-content">
				<!-- Optional header to show buttons, drag handle, and a title, add .widget-no-header to the widgetCard class to disable -->
				<h4 class="card-header d-flex justify-content-between align-items-center text-white px-3">
					<span class="d-flex align-items-center">
						<i class="material-icons dragHandle editItem">drag_indicator</i></span>Server Status
					<span>
						<button type="button" class="btn btn-settings editItem widgetMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<i class="material-icons">more_vert</i>
						</button>
						<div class="dropdown-menu dropdown-menu-right">
							<button class="dropdown-item widgetEdit" type="button">Edit</button>
							<button class="dropdown-item widgetRefresh" type="button">Refresh</button>
							<div class="dropdown-divider"></div>
							<button class="dropdown-item widgetDelete" type="button">Delete</button>
						</div>
					</span>
				</h4>
				
				<!-- Only visible if .widget-no-header is present in the class list of the widget card -->
				<div class="no-header">
					<span class="d-flex align-items-center">
						<i class="material-icons dragHandle editItem">drag_indicator</i>
					</span>
				
		            <button type="button" class="btn btn-settings editItem widgetMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						<i class="material-icons">more_vert</i>
					</button>
					<div class="dropdown-menu dropdown-menu-right">
						<button class="dropdown-item widgetEdit" type="button">Edit</button>
						<button class="dropdown-item widgetRefresh" type="button">Refresh</button>
						<div class="dropdown-divider"></div>
						<button class="dropdown-item widgetDelete" type="button">Delete</button>
					</div>
				</div>
				
				<!-- Card body goes here -->
				<div class="card-content slideContent">
				
				</div>
				
				<div class="card-settings">
                    <!-- Card setting markup goes here -->
                    <div class="form-group">
                        <label class="appLabel" for="serverList">Target</label>
                        <select class="form-control custom-select serviceList statInput" data-for="target" title="Target">
                        </select>
                    </div>
	            </div>
			</div>
		</div>
		';
	}

	/**
	 * CSS Defined here will be prepended with the className of the widget, whis is
	 * determined by the class name. So, it's safe to re-use selectors within the cards, and not define
	 * additional classes. I'm lazy, so be sure classes have a newline before and between them...
	 * You can manipulate card-settings as needed to get it to move around.
	 * @return string
	 */
	public static function widgetCSS() {
		return '
		
			.someSelector {
				background: black;
				text-align: center;
			}
			
			.anotherSelector {
				width: 50%;
			}
			
		';
	}


	public static function widgetJS() {
		return [];
	}

}