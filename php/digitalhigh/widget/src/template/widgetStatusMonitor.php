<?php

namespace digitalhigh\widget\template;

use digitalhigh\widget\exception\widgetException;

class widgetStatusMonitor {
	// Unique ID for each widget
	// Data store for other values
	private $data;
	// Required values in order for other things to work
	const required = ['target', 'color', 'icon', 'label', 'url'];
	// Set these accordingly
	const maxWidth = 8;
	const maxHeight = 3;
	const minWidth = 3;
	const minHeight = 1;
	const refreshInterval = 5;
	const type = "statusMonitor";
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

	public function testConnection() {
			//check, if a valid url is provided
			if(!filter_var($this->data['url'], FILTER_VALIDATE_URL)) {
				$this->data['service-status'] = "offline";
			} else {
				$curlInit = curl_init($this->data['url']);
				curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 10);
				curl_setopt($curlInit, CURLOPT_HEADER, true);
				curl_setopt($curlInit, CURLOPT_NOBODY, true);
				curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curlInit, CURLOPT_SSL_VERIFYPEER, false);

				$response = curl_exec($curlInit);
				curl_close($curlInit);

				$result = ($response !== false) ? "online" : "offline";
				$this->data['service-status'] = $result ?? "offline";
			}
	}

	public function update($force=false) {
		$lastUpdate = $this->data['lastUpdate'];
		$int = self::refreshInterval;
		$total = $lastUpdate + $int;
		$now = time();
		if ($now > $total || $force) {
			$this->data['lastUpdate'] = time();
			$this->testConnection();
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
			'gs-auto-position' => "1"
		];
		$attributeStrings = [];
		foreach($attributes as $key => $value) $attributeStrings[] ="data-${key}='${value}'";
		$attributeString = join(" ", $attributeStrings);
		return '
				<div class="widgetCard card m-0 grid-stack-item '.self::type.'" '.$attributeString.'>
					<div class="grid-stack-item-content">
						<div class="service-icon-wrapper">
							<i class="muximux-sonarr service-icon"></i>
						</div>
						<span class="d-flex align-items-center">
							<i class="material-icons dragHandle editItem">drag_indicator</i></span>
						<span>
								
						<div class="service-text p-3">
							<div class="row">
								<div class="col">
									<h4 class="card-title text-white my-0 statTitle">No Services...</h4>
								</div>
								<div class="col d-flex align-items-center justify-content-end">
									<h4 class="my-0">
										<span class="online-indicator">Online</span>
										
										<svg class="svg-inline--fa fa-check-circle fa-w-16 fa-fw online-indicator" title="Service Online" data-fa-transform="grow-3" aria-labelledby="svg-inline--fa-title-2" data-prefix="fas" data-icon="check-circle" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="" style="transform-origin: 0.5em 0.5em;"><title id="svg-inline--fa-title-2">Sonarr Online</title><g transform="translate(256 256)"><g transform="translate(0, 0)  scale(1.1875, 1.1875)  rotate(0 0 0)"><path fill="currentColor" d="M504 256c0 136.967-111.033 248-248 248S8 392.967 8 256 119.033 8 256 8s248 111.033 248 248zM227.314 387.314l184-184c6.248-6.248 6.248-16.379 0-22.627l-22.627-22.627c-6.248-6.249-16.379-6.249-22.628 0L216 308.118l-70.059-70.059c-6.248-6.248-16.379-6.248-22.628 0l-22.627 22.627c-6.248 6.248-6.248 16.379 0 22.627l104 104c6.249 6.249 16.379 6.249 22.628.001z" transform="translate(-256 -256)"></path></g></g></svg>
										
										<span class="offline-indicator">Offline</span>
										<svg class="svg-inline--fa fa-exclamation-circle fa-w-16 fa-fw offline-indicator" title="Service Offline" data-fa-transform="grow-3" aria-labelledby="svg-inline--fa-title-3" data-prefix="fas" data-icon="exclamation-circle" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="" style="transform-origin: 0.5em 0.5em;"><title id="svg-inline--fa-title-3">Tautulli Offline</title><g transform="translate(256 256)"><g transform="translate(0, 0)  scale(1.1875, 1.1875)  rotate(0 0 0)"><path fill="currentColor" d="M504 256c0 136.997-111.043 248-248 248S8 392.997 8 256C8 119.083 119.043 8 256 8s248 111.083 248 248zm-248 50c-25.405 0-46 20.595-46 46s20.595 46 46 46 46-20.595 46-46-20.595-46-46-46zm-43.673-165.346l7.418 136c.347 6.364 5.609 11.346 11.982 11.346h48.546c6.373 0 11.635-4.982 11.982-11.346l7.418-136c.375-6.874-5.098-12.654-11.982-12.654h-63.383c-6.884 0-12.356 5.78-11.981 12.654z" transform="translate(-256 -256)"></path></g></g></svg>
										
										<button type="button" class="btn btn-settings editItem widgetMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
											<i class="material-icons">more_vert</i>
										</button>
										<div class="dropdown-menu dropdown-menu-right">
											<button class="dropdown-item widgetEdit" type="button">Edit</button>
											<button class="dropdown-item widgetRefresh" type="button">Refresh</button>
											<div class="dropdown-divider"></div>
											<button class="dropdown-item widgetDelete" type="button">Delete</button>
										</div>
									
									</h4>
								</div>
							</div>
						</div>
						<div class="card-settings">
		                    <!-- Card setting markup goes here -->
		                    <div class="form-group">
                                <label for="serverList">Target</label>
                                <select class="form-control custom-select serviceList statInput" data-for="target" title="Target">
                                </select>
                        	</div>
			            </div>
					</div>
				</div>';
	}

	public static function widgetCSS() {
		return '
			.service-icon-wrapper {
				position: absolute;
			    top: 0;
			    left: 0;
			    width: 100%;
			    height: 100%;
			    overflow: hidden;
			}
			
			.service-text {
			    padding: 20px !important;
		    }
		    
		    .dragHandle {
		        position: absolute;
                left: -10px;
                top: 21px;
		    }
		    
		    .widgetMenu {
			    position: absolute;
			    right: -10px;
			    top: 2px;
		    }
		
			.service-icon {
			    font-size: 100px;
			    position: absolute;
			    top: 14%;
			    left: 14%;
			    opacity: 0.1;
			}
			
			.online-indicator {
				display: none;
			}
		';
	}

	public static function widgetJS() {
		$init = "				
				if (target.data('target') === undefined || target.data('target' === 0)) {
					var drawerItems = $('#AppzDrawer').find('.drawer-item');
					if (drawerItems.length) {
						id = $('#AppzDrawer').find('.drawer-item').attr('id').replace('Btn','');
						console.log('No defined target, using' + id);
					} else {
						id = false;
					}
				} else {
					id = target.data('target');
					console.log('using target ID of ' + id);
				}
				console.log('Target id is  ' + id, target);
				
				var targetBtn = $('#' + id + 'Btn');
				var dataSet = targetBtn.data();
				console.log('Dataset: ', dataSet);
				if (dataSet !== undefined) {
					var icon = dataSet['icon'];
					var label = dataSet['label'];
					var url = dataSet['url'];
					var color = dataSet['color'];
					var color2 = shadeColor(color, -30);
					var colString = 'background: linear-gradient(60deg, '+color+', '+color2+');';
					target.attr('data-target', id);
					target.attr('data-icon', icon);
					target.attr('data-label', label);
					target.attr('data-color', color);
					target.attr('data-url', url);
					
					target.find('.service-icon').attr('class', 'service-icon ' + icon);
					target.find('.statTitle').text(label);
					target.find('.offline-indicator').show();
					target.find('.online-indicator').hide();
					target.find('.card-background').attr('style', colString);
		    }";

		$update = "";
		return ['init' => $init];
	}
}