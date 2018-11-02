<?php

namespace digitalhigh\widget\template;

use digitalhigh\widget\base\widgetBase;
use digitalhigh\widget\exception\widgetException;

class widgetStatusMonitor extends widgetBase {

	public $widgetId;
	public $target;
	public $color;
	public $icon;
	public $label;
	public $url;
	public $online;

	/**
	 * widgetStatusMonitor constructor.
	 * @param $data
	 * @throws widgetException
	 */
	function __construct($data) {
		parent::__construct($data);
		$this->type = 'statusMonitor';
		$required = ['target', 'color', 'icon', 'label', 'url'];
		foreach($required as $key)	{
			if (!isset($data[$key])) throw new widgetException("Missing required key $key");
		}

		$this->target = $data['target'];
		$this->color = $data['color'];
		$this->icon = $data['icon'];
		$this->label = $data['label'];
		$this->url = $data['url'];
		$this->online = $data['online'] ?? false;

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
		write_log("Update called for statusmonitor...","ALERT",false,true);
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
		$parentKeys = parent::serialize();
		$data = array_merge($parentKeys, [
			'target' => $this->target,
			'color' => $this->color,
			'icon' => $this->icon,
			'label' => $this->label,
			'url' => $this->url,
			'online' => $this->online
		]);

		write_log("Serializing: ".json_encode($data));
		return $data;
	}

	public static function widgetHTML() {
		$last = lcfirst(str_replace("widget", "", array_pop(explode("\\", get_called_class()))));
		$attributes = [
			'type' => $last,
			'target' => "",
			'gs-x' => 7,
			'gs-y' => 0,
			'gs-width' =>3,
			'gs-height' => 1,
			'gs-min-width' => 2,
			'gs-min-height' => 1,
			'gs-max-width' => 8,
			'gs-max-height' => 4,
			'gs-auto-position' => true
		];
		$attributeStrings = [];
		foreach($attributes as $key => $value) $attributeStrings[] ="data-${key}='${value}'";
		$attributeString = join(" ", $attributeStrings);
		return '
				<div class="widgetCard card m-0 grid-stack-item '.$last.'" '.$attributeString.'>
					<div class="grid-stack-item-content">
						<div class="service-icon-wrapper">
							<i class="muximux-sonarr service-icon"></i>
						</div>
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
                                <label class="appLabel" for="serverList">Target</label>
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
					console.log('Motherfucker...');
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