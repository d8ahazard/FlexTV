<?php

namespace digitalhigh\widget\template;

use digitalhigh\widget\curl\curlGet;
use digitalhigh\widget\exception\widgetException;

class widgetTopPlayed {
	// Unique ID for each widget
	// Data store for other values
	private $data;
	// Required values in order for other things to work
	const required = [];
	// Set these accordingly
	const maxWidth = 3;
	const maxHeight = 7;
	const minWidth = 1;
	const minHeight = 2;
	const refreshInterval = 30;
	const type = "TopPlayed";
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
			$this->data['stats'] = $this->fetchStats();
		}
		return $this->serialize();
	}

	public function fetchStats() {
		$uri = $this->data['uri'] ?? false;
		$token = $this->data['token'] ?? false;
		$mediaType = $this->data['mediatype'] ?? false;
		$interval = $this->data['interval'] ?? 0;
		$limit = $this->data['limit'] ?? 6;
		$items = [];
		if ($uri && $token && $mediaType) {
			$server = [
				'Uri' => $uri,
				'Token' => $token
			];
			write_log("Fetching stats for $uri");
			$url = "$uri/stats/library/popular?X-Plex-Token=$token&X-Plex-Type=$mediaType&X-Plex-Accept=json";
			if ($interval) $url .= "&X-Plex-Interval=$interval";
			$data = curlGet($url);
			$mt = ucfirst($mediaType);
			$data = $data['MediaContainer']['Hub'][0] ?? $data;
			$data = $data['Video'] ?? $data['Directory'] ?? $data;
			write_log("Raw data from $url: ".json_encode($data),"INFO", false,true, true);
			foreach($data as $item) {
				write_log("Media Item: ".json_encode($item), "INFO", false, true, true);
				$media = [
					'title' => $item['title'],
					'poster' => transcodeImage($item['poster'], $server),
					'art' => transcodeImage($item['art'], $server),
					'playCount' => $item['playCount'],
					'userCount' => $item['userCount'],
					'key' => $item['ratingKey']
				];
				$items[] = $media;
			}
			usort($items, function ($item1, $item2) {
				if ($item2['playCount'] === $item1['playCount']) {
					return $item2['userCount'] <=> $item1['userCount'];
				} else {
					return $item2['playCount'] <=> $item1['playCount'];
				}

			});

			$items = array_slice($items, 0, $limit);
			$data = $items;
		} else {
			write_log("We're missing a param.");
			$data = [];
		}
		write_log("Data: ".json_encode($data));
		return $data;
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
			'gs-width' =>1,
			'gs-height' => 3,
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
		<div class="widgetCard card m-0 grid-stack-item widget-no-header '.self::type.'" '.$attributeString.'>
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
					<!-- Header/card -->
					<div class="catHeader"></div>
					<!-- Popular items go here -->
					<div class="catItems">
					
					</div>
				</div>
				
				<div class="card-settings">
                    <!-- Card setting markup goes here -->
                    <div class="form-group">
						<label class="appLabel" for="serverList">Target</label>
						<select class="form-control custom-select serverList statInput" data-for="target" title="Target">
						</select>
					</div>
                    <div class="form-group">
                        <label class="appLabel" for="mediaType">Type</label>
                        <select class="form-control custom-select typeInput" data-for="mediatype" title="Type">
                        	<option value="movie">Movies</option>
                        	<option value="episode">TV</option>
                        	<option value="track">Artists</option>
                        </select>
                    </div>                    
                    <div class="form-group">
                        <label class="appLabel" for="intInput">Interval</label>
                        <select class="form-control custom-select intInput" data-for="interval" title="Interval">
                        	<option value="0">All</option>
                        	<option value="7">7</option>
                        	<option value="90">30</option>
                        	<option value="60">60</option>
                        	<option value="90">90</option>                        	
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
			
			.catHeader {
				height: 100px;
				width: 100%;
				background-position: center;
				background-repeat: no-repeat;
				background-size: cover;
				background-color: #333;
				background-blend-mode: overlay;
				text-align: center;
			}
			
			.catItems {
				height: calc(100% - 100px);
				position: absolute;
				top: 100px;
				width: 100%;
				overflow-y: scroll;
			}
			
			.itemPoster {
				background-size: contain; 
				background-repeat: no-repeat;
				height: 80px;
				position: absolute;
				left: 0;
				z-index: 2;
			}
			
			.mediaRow, .posterWrap {
				height: 80px;
			}
			
			.mediaDescription {
				text-align: right;
				position: relative;
				width: 100%;
				height: 100%;
			}
			
			.no-header {
				z-index: 5;
			}
			
			.headerText {
				position: absolute;
				top: 15px;
				left: 0;
				width: 100%;
			}
			
			.mediaInner {
				position: absolute;
                right: 12px;
			    top: 50%;
			    transform: translatey(-50%);
			}
			
			.topPlayedColumnRow {
				width: 100%;
				height: 80px;
			}
			
			a, a:visited {
				color: var(--theme-accent);				
			}
			
			a:hover {
				color: var(--theme-accent-light);
			}
			
			.grid-stack-item-content {
				left: 0 !important;
				right: 0 !important;				
			}
			
			.widgetMenu {
				right: 20px;
			}
			
			.dragHandle {
				left: 20px;
			}
			
		';
	}


	public static function widgetJS() {
		return [];
	}

}