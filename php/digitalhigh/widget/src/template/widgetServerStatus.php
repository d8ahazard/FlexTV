<?php

namespace digitalhigh\widget\template;

require_once dirname(__FILE__) . "/../../../../multiCurl.php";

use digitalhigh\widget\base\widgetBase;
use digitalhigh\widget\exception\widgetException;
use digitalhigh\multiCurl;

class widgetServerStatus extends widgetBase {

	public $serverId;
	public $serverAddress;
	public $serverToken;
	public $serverName;

	public $sessionData;
	public $libraryData;

	public $DEFAULT_REFRESH_INTERVAL = 30;

	public function __construct($data) {
		parent::__construct($data);
		// A list of params required for update to work
		$required = ['target', 'token', 'label', 'url'];
		foreach($required as $key) if (!isset($data[$key])) throw new widgetException("Required key $key is missing.");

		$this->serverId = $data['target'];
		$this->serverAddress = $data['url'];
		$this->serverToken = $data['token'];
		$this->serverName = $data['label'];

		$this->sessionData = $data['sessionData'] ?? [];
		$this->libraryData = $data['libraryData'] ?? [];

		$autoType = lcfirst(str_replace(['widget', '.php'], "", __FILE__));
		write_log("Auto type: $autoType");
		$this->type = $autoType;
		$this->type = "serverStatus";
	}

	/**
	 * @param bool $force
	 * @return array
	 */
	public function update($force=false) {
		$lastUpdate = $this->lastUpdate;
		$int = $this->refreshInterval;
		$total = $lastUpdate + $int;
		$now = time();
		if ($now > $total || $force) {
			write_log("Updating widget!","INFO", false, true);
			$this->lastUpdate = time();
			$address = $this->serverAddress;
			$token = $this->serverToken;
			$url1 = "${address}/status/sessions?X-Plex-Token=$token";
			$url2 = "${address}/stats/library?X-Plex-Token=$token";
			$header = ["Aceept: application/json"];
			$queries = ["sessions" => [$url1, $header], "library" => [$url2, $header]];
			write_log("INITMC: " . json_encode($queries), "INFO", false, true, true);
			$mc = new multiCurl($queries);

			$results = $mc->process();
			write_log("Results: " . json_encode($results), "ALERT", false, true, true);
			$libraryData = $results['library']['MediaContainer']['Section'] ?? [];
			$sections = [];
			foreach ($libraryData as $section) {
				write_log("SECTION: " . json_encode($section));
				$sectionItem = [];
				foreach ($section as $key => $value) {
					if (!is_array($value)) $sectionItem[$key] = $value;
				}
				array_push($sections, $sectionItem);
			}
			$this->libraryData = $sections;
			$this->sessionData = $results['session']['MediaContainer'] ?? [];
		}
		return $this->serialize();
	}

	/**
	 * @return array
	 */
	public function serialize() {
		$keyData = parent::serialize();

		return (array_merge($keyData, [
			'url' => $this->serverAddress,
			'token' => $this->serverToken,
			'label' => $this->serverName,
			'sectionData' => $this->libraryData,
			'sessionData' => $this->sessionData,
		]));
	}

	public static function widgetHTML() {
		return '
		<div class="widgetCard grid-stack-item" data-type="serverStatus" data-target="" data-gs-x="1" data-gs-y="0" data-gs-width="3" data-gs-height="3">
			<div class="spinCard grid-stack-item-content">
				<div class="card m-0 card-rotate card-background">
					<div class="front front-background">
						<h4 class="card-header d-flex justify-content-between align-items-center text-white px-3">
							<span class="d-flex align-items-center">
							<i class="material-icons editItem">drag_indicator</i></span>Status 
							<span>
								<button type="button" class="btn btn-settings editItem" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
									<i class="material-icons">more_vert</i>
								</button>
								<div class="dropdown-menu dropdown-menu-right">
									<button class="dropdown-item" type="button">Edit</button>
									<button class="dropdown-item" type="button">Refresh</button>
									<div class="dropdown-divider"></div>
									<button class="dropdown-item" type="button">Delete</button>
								</div>
							</span>
						</h4>
						
						<ul id="serverInformation" class="list-group list-group-flush">
						
						<!-- Check if Plex Server is online -->
						<li id="serverStatus" class="list-group-item d-flex justify-content-between align-items-center list-group-item-success">Server Status: Online <svg class="svg-inline--fa fa-check-circle fa-w-16 fa-fw" data-fa-transform="grow-4" aria-hidden="true" data-prefix="fas" data-icon="check-circle" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="" style="transform-origin: 0.5em 0.5em;"><g transform="translate(256 256)"><g transform="translate(0, 0)  scale(1.25, 1.25)  rotate(0 0 0)"><path fill="currentColor" d="M504 256c0 136.967-111.033 248-248 248S8 392.967 8 256 119.033 8 256 8s248 111.033 248 248zM227.314 387.314l184-184c6.248-6.248 6.248-16.379 0-22.627l-22.627-22.627c-6.248-6.249-16.379-6.249-22.628 0L216 308.118l-70.059-70.059c-6.248-6.248-16.379-6.248-22.628 0l-22.627 22.627c-6.248 6.248-6.248 16.379 0 22.627l104 104c6.249 6.249 16.379 6.249 22.628.001z" transform="translate(-256 -256)"></path></g></g></svg><!-- <i class="fas fa-fw fa-check-circle" data-fa-transform="grow-4"></i> --></li>
						
						<!-- Check Current Activity -->
						<li id="currentActivity" class="list-group-item d-flex justify-content-between align-items-center bg-dark">
							<span class="d-flex align-items-center">
							Current Activity <button type="button" id="getCurrentActivity" title="Refresh Current Activity" onclick="getCurrentActivityViaPlex()" class="btn btn-sm btn-link text-muted py-0"><svg class="svg-inline--fa fa-sync-alt fa-w-16 fa-fw" aria-hidden="true" data-prefix="fas" data-icon="sync-alt" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg=""><path fill="currentColor" d="M370.72 133.28C339.458 104.008 298.888 87.962 255.848 88c-77.458.068-144.328 53.178-162.791 126.85-1.344 5.363-6.122 9.15-11.651 9.15H24.103c-7.498 0-13.194-6.807-11.807-14.176C33.933 94.924 134.813 8 256 8c66.448 0 126.791 26.136 171.315 68.685L463.03 40.97C478.149 25.851 504 36.559 504 57.941V192c0 13.255-10.745 24-24 24H345.941c-21.382 0-32.09-25.851-16.971-40.971l41.75-41.749zM32 296h134.059c21.382 0 32.09 25.851 16.971 40.971l-41.75 41.75c31.262 29.273 71.835 45.319 114.876 45.28 77.418-.07 144.315-53.144 162.787-126.849 1.344-5.363 6.122-9.15 11.651-9.15h57.304c7.498 0 13.194 6.807 11.807 14.176C478.067 417.076 377.187 504 256 504c-66.448 0-126.791-26.136-171.315-68.685L48.97 471.03C33.851 486.149 8 475.441 8 454.059V320c0-13.255 10.745-24 24-24z"></path></svg><!-- <i class="fas fa-fw fa-sync-alt fa-spin"></i> --></button>
							</span>
							<span id="currentActivityStreamCount">1 Stream <span id="currentActivityBandwidth" title="" data-toggle="tooltip" data-original-title="4 Mbps / 25 Mbps"><svg class="svg-inline--fa fa-info-circle fa-w-16 fa-fw" aria-hidden="true" data-prefix="fas" data-icon="info-circle" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg=""><path fill="currentColor" d="M256 8C119.043 8 8 119.083 8 256c0 136.997 111.043 248 248 248s248-111.003 248-248C504 119.083 392.957 8 256 8zm0 110c23.196 0 42 18.804 42 42s-18.804 42-42 42-42-18.804-42-42 18.804-42 42-42zm56 254c0 6.627-5.373 12-12 12h-88c-6.627 0-12-5.373-12-12v-24c0-6.627 5.373-12 12-12h12v-64h-12c-6.627 0-12-5.373-12-12v-24c0-6.627 5.373-12 12-12h64c6.627 0 12 5.373 12 12v100h12c6.627 0 12 5.373 12 12v24z"></path></svg><!-- <i class="fas fa-fw fa-info-circle"></i> --></span></span>
						</li>
						
						<li class="list-group-item d-flex justify-content-between bg-dark"><span>Movies</span><span class="text-right">1,542</span></li><li class="list-group-item d-flex justify-content-between bg-dark"><span>TV Shows</span><span class="text-right">552</span></li><li class="list-group-item d-flex justify-content-between bg-dark"><span>TV Episodes</span><span class="text-right">27,685</span></li><li class="list-group-item d-flex justify-content-between bg-dark"><span>Monthly Active Users</span><span id="montlyActiveUsers" class="text-right">21<span class="text-muted"> / 26</span></span></li></ul>
					</div>
					<div class="back card-rotate back-background">
		                <div class="widgetHandle btn">
							<h4 class="card-header text-center px-2">Settings</h4>
						 </div>
	                    <div class="form-group bmd-form-group">
                            <label class="appLabel" for="serverList">Server</label>
                            <select class="form-control custom-select serverList statInput statTarget" data-for="target" title="The server to monitor">
                            </select>
                        </div>
		            </div>
				</div><!-- card -->
			</div>
		</div>
		';
	}

	public static function widgetCSS() {
		return '';
	}

	public static function widgetJS() {
		$init = "
				console.log(\"Trying to add server status widget...\");
				if (window.hasOwnProperty('plexServerId')) {
					console.log(\"We have a server ID.\");
					target.attr('data-target', window['plexServerId']);
				} else {
					console.log(\"We need that ID.\");
				}
				";
		return ['init' => $init];
	}
}