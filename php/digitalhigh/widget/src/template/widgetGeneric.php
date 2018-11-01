<?php

namespace digitalhigh\widget\template;

require_once dirname(__FILE__) . "/../base/widgetBase.php";

class widgetGeneric {
	public $data;
	public $type;

	public function __construct($data) {
		$this->type = 'generic';
		$this->data = $data;
	}

	public function update() {
		return $this->serialize();
	}

	public function serialize() {
		return $this->data;
	}

	public static function widgetHTML() {
		return '
		<div class="widgetCard grid-stack-item" data-type="'.get_called_class().'" data-target="" data-gs-x="1" data-gs-y="0" data-gs-width="3" data-gs-height="3">
			<div class="spinCard grid-stack-item-content">
				<div class="card m-0 card-rotate card-background">
					<div class="front front-background">
						<!-- Card front markup goes in here -->
					</div>
					<div class="back card-rotate back-background">
		                <div class="widgetHandle btn">
							<h4 class="card-header text-center px-2">Settings</h4>
						 </div>
	                    <!-- Card back markup goes here -->
		            </div>
				</div>
			</div>
		</div>
		';
	}

	public static function widgetCSS() {
		return '';
	}

	public static function widgetJS() {
		return [];
	}

}