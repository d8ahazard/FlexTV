<?php

namespace digitalhigh\widget\template;

require_once dirname(__FILE__) . "/../base/widgetBase.php";

// Rename this class
class widgetGeneric {
	public $data;
	public $type;

	public function __construct($data) {
		// Auto sets the type based on the class name.
		$last = lcfirst(str_replace("widget", "", array_pop(explode("\\", get_called_class()))));
		$this->type = $last;
		$this->data = $data;
	}

	public function update() {
		return $this->serialize();
	}

	public function serialize() {
		return $this->data;
	}

	public static function widgetHTML() {
		$last = lcfirst(str_replace("widget", "", array_pop(explode("\\", get_called_class()))));
		$attributes = [
			'type' => $last,
			'target' => "",
			'gs-x' => 1,
			'gs-y' => 0,
			'gs-width' =>3,
			'gs-height' => 3,
			'gs-min-width' => 1,
			'gs-min-height' => 1,
			'gs-max-width' => 12,
			'gs-max-height' => 12,
			'gs-auto-position' => true
		];
		$attributeStrings = [];
		foreach($attributes as $key => $value) $attributeStrings[] ="data-${key}='${value}'";
		$attributeString = join(" ", $attributeStrings);
		return '
		<div class="widgetCard card m-0 grid-stack-item '.$last.'" '.$attributeString.'>
			<div class="grid-stack-item-content">
				<!-- Optional header to show buttons, drag handle, and a title -->
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
				
				<!-- Card body goes here -->
				<div class="card-content">
				
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