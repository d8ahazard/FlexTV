<?php

namespace digitalhigh\widget\template;

use digitalhigh\widget\exception\widgetException;

class widgetUrl {
	// Unique ID for each widget
	// Data store for other values
	public $data;
	// Required values in order for other things to work
	const required = [];
	// Set these accordingly
	const maxWidth = 5;
	const maxHeight = 5;
	const minWidth = 1;
	const minHeight = 1;
	const refreshInterval = 30;
	const type = "URL";

	/**
	 * widgetURL constructor.
	 * @param $data
	 * @throws widgetException
	 */
	function __construct($data) {
		$data['type'] = self::type;
		foreach(self::required as $key)	{
			if (!isset($data[$key])) throw new widgetException("Missing required key $key");
		}
		$this->data = $data;
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
			'gs-width' =>2,
			'gs-height' => 2,
			'type' => self::type,
			'gs-min-width' => self::minWidth,
			'gs-min-height' => self::minHeight,
			'gs-max-width' => self::maxWidth,
			'gs-max-height' => self::maxHeight,
			'gs-auto-position' => true
		];
		$randId = rand(0,10000);
		$attributeStrings = [];
		foreach($attributes as $key => $value) $attributeStrings[] ="data-${key}='${value}'";
		$attributeString = join(" ", $attributeStrings);
		return '
		<div class="widgetCard card m-0 grid-stack-item widget-no-header '.self::type.'" '.$attributeString.'>
			<div class="grid-stack-item-content">
				
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
					<div class="urlWidget">
						<a href="" target="_blank" class="urlURL">
							<img src="" width="40" alt="" class="urlImg">
							<h4 class="urlTitle">URL Widget</h4>
							<p class="urlSubtitle"></p>
						</a>	
					</div>			
	            </div>
	            
	            <div class="card-settings">
                    <!-- Card setting markup goes here -->
                    <div class="bmd-form-group-sm">
                        <label class="bmd-label-floating" for="img'.$randId.'">Image URL</label>
                        <input type="text" class="form-control linkInput imgInput" id="img'.$randId.'" data-for="img" title="Image">
                        </text>                        
                        </div>
                        <div class="bmd-form-group-sm">
                        <label class="bmd-label-floating" for="url'.$randId.'">URL</label>
                        <input type="text" class="form-control linkInput urlInput" id="url'.$randId.'" data-for="url" title="URL">
                        </text>
                        </div>
                        <div class="bmd-form-group-sm">
                        <label class="bmd-label-floating" for="title'.$randId.'">Title</label>
                        <input type="text" class="form-control linkInput titleInput" id="title'.$randId.'" data-for="title" title="Title">URL Widget
                        </text>
                        </div>
                        <div class="bmd-form-group-sm">
                        <label class="bmd-label-floating" for="sub'.$randId.'">Subtitle</label>
                        <input type="text" class="form-control linkInput subtitleInput" id="sub'.$randId.'" data-for="subtitle" title="Subtitle">
                        </text>
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
			
			.linkInput {
				width; 100%;
				height: 40px;
			}
			
			.urlWidget {
				width: 100%;
				text-align: center;
			}
			
			.urlWidget img {
				top: -15px;
                position: relative;
                width: 40px;
                height: 40px;
                corner-radius: 20px;
			}
					
			
			.urlWidget * {			
				color: var(--theme-accent) !important;
			}
			
			.urlWidget:hover * {
				color: var(--theme-accent-light) !important;
			}
			
			.widgetMenu {
				position: absolute;
			    top: 10px;
			    right: 0px;
			}
			
			.dragHandle {
				position: absolute;
	            top: 10px;
			}
			
			.card-settings {
				padding: 20px;
			    margin: 10px;
			    width: 104%;
			    left: -19px;
			    top: 35px;
			}
		';
	}


	public static function widgetJS() {
		return [];
	}

}