<?php

namespace digitalhigh\widget\template;

class widgetNowPlaying {
	public $data;

	// Set these accordingly
	const maxWidth = 8;
	const maxHeight = 8;
	const minWidth = 3;
	const minHeight = 2;
	const refreshInterval = 2;
	const type = "NowPlaying";
	public function __construct($data) {
		$data['type'] = self::type;
		$this->data = $data;
		$this->data['gs-max-width'] = self::maxWidth;
		$this->data['gs-min-width'] = self::minWidth;
		$this->data['gs-max-height'] = self::maxHeight;
		$this->data['gs-min-height'] = self::minHeight;
	}

	public function update($force=false) {
		write_log("Update function fired!!", "ALERT", false, true, true);
		$lastUpdate = $this->data['lastUpdate'] ?? 0;
		$int = self::refreshInterval;
		$total = $lastUpdate + $int;
		$now = time();
		if ($now > $total || $force) {
			$this->data['lastUpdate'] = time();
			write_log("Update triggered...", "ALERT", false, true, true);
			$sessions = $this->fetchNowPlaying();
			$this->data['sessions'] = $sessions;
		}
		return $this->serialize();
	}

	private function fetchNowPlaying() {
		$result = [];
		$uri = $this->data['uri'] ?? false;
		$token = $this->data['token'] ?? false;
		write_log("Fetching now playing...", "ALERT", false, true, true);
		if ($uri && $token) {
			write_log("URI and token are set, we should be good to go.","INFO", false, true, true);
			$url = "$uri/status/sessions?X-Plex-Token=$token";
			$data = curlGet($url);
			$server = [
				'Uri' => $uri,
				'Token' => $token
			];
			write_log("Got session data: ".json_encode($data), false, true, true);
			if (is_array($data)) {
				$sessionData = $data['MediaContainer'] ?? [];
				foreach($sessionData as $type => $sessions) {
					foreach ($sessions as $session) {
						write_log("Parse this: " . json_encode($session), "ALERT", false, true, true);
						$profile = $session['Media'][0]['videoProfile'] ?? $session['Media'][0]['audioProfile'];
						#TODO: Add the stream infos
						$item = [
							'id' => $session['sessionKey'],
							'type' => $session['type'],
							'art' => transcodeImage($session['art'], $server),
							'poster' => transcodeImage($session['thumb'] ?? $session['grandparentThumb'], $server),
							'title' => $session['title'],
							'grandparentTitle' => $session['grandparentTitle'] ?? false,
							'parentTitle' => $session['parentTitle'] ?? false,
							'parentIndex' => $session['parentIndex'] ?? false,
							'player' => $session['Player'][0]['product'],
							'state' => ucfirst($session['Player'][0]['state']),
							'bandwidth' => $session['Session'][0]['bandwidth'] . "kbps - (" . ucfirst($session['Session'][0]['location']) . ")",
							'quality' => ucfirst($profile),
							'direct' => $session['Media'][0]['Part'][0]['decision'],
							'offset' => $session['viewOffset'],
							'duration' => $session['Media'][0]['Part'][0]['duration'],
							'percent' => round(($session['viewOffset'] / $session['Media'][0]['Part'][0]['duration']) * 100),
							'year' => $session['year'],
							'index' => $session['index'] ?? 0,
							'user' => ucfirst($session['User'][0]['title']),
							'userThumb' => $session['User'][0]['thumb']
						];
						$result[] = $item;
					}
				}
			}
		} else {
			write_log("No URI/Token, can't fetch status.");
		}
		return $result;
	}

	public function serialize() {
		return $this->data;
	}

	public static function widgetHTML() {
		// As odd as it may seem, this is where we set our "default" values for the widget.
		// Auto-position will be turned off when the widget is created.
		$attributes = [
			'gs-x' => 1,
			'gs-y' => 0,
			'gs-width' => 3,
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
		return '
		<div class="widgetCard card grid-stack-item '.self::type.'" '.$attributeString.'>
			<div class="grid-stack-item-content">
		        <h4 class="card-header d-flex justify-content-between align-items-center text-white px-3">
					<span class="d-flex align-items-center">
					<i class="material-icons dragHandle editItem">drag_indicator</i></span>Now Playing 
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
				
				<!-- Now start putting actual content -->
				<div class="noItems">Nothing is currently being played.</div>
			    <div class="carousel slide card m-0 bg-dark transparent shadow" data-ride="carousel">            
		            
	                <div class="carousel-inner slideContent">
	                    
	                </div>
	                <ol class="carousel-indicators">                
	            	</ol>
	            </div>
	            
	            
	            
	            <div class="card-settings">
					<!-- Card setting markup goes here -->
					<div class="form-group">
						<label class="appLabel" for="serverList">Target</label>
						<select class="form-control custom-select serverList statInput" data-for="target" title="Target">
						</select>
					</div>
				</div>
            </div>
            <!-- This is hidden -->
            <div class="carousel-template">
                <div class="carousel-item carousel-template-item">			
                    <div class="card-body">
                        <div class="row mediaDiv text-white">
                        	<div class="media-image col-5">                        		
                        	</div>
                            <div class="media-text col-7">
	                            <dl class="row mb-0 textInner">
	                                <dt class="col-sm-5 text-sm-right">Status</dt>
	                                <dt class="col-sm-7 npStatusText text-truncate"></dt>
	                                <dt class="col-sm-5 text-sm-right">Quality</dt>
	                                <dt class="col-sm-7 npStatusQuality text-truncate"></dt>
	                                <dt class="col-sm-5 text-sm-right">Bandwidth</dt>
	                                <dt class="col-sm-7 npStatusBandwidth text-truncate"></dt>
	                                <dt class="col-sm-5 text-sm-right">Stream</dt>
	                                <dt class="col-sm-7 npStreamType text-truncate">
	                                <a data-toggle="collapse" href="#streamInfo1" role="button" aria-expanded="false" aria-controls="streamInfo1">+</a></dt>
	
	                                <dl class="collapse col-sm-5 offset-sm-4" id="streamInfo1">
	                                    <dt>Container</dt>
	                                    <dt class="npStreamContainer text-truncate"></dt>
	                                    <dt>Video</dt>
	                                    <dt class="npTranscodeVideo text-truncate"></dt>
	                                    <dt>Audio</dt>
	                                    <dt class="npTranscodeAudio text-truncate"></dt>
	                                    <dt>Subtitle</dt>
	                                    <dt class="npTranscodeSubtitle text-truncate"></dt>
	                                </dl>
	
	                                <dt class="col-sm-5 text-sm-right">Player</dt>
	                                <dt class="col-sm-7 npPlayerName text-truncate"></dt>
	                                <dt class="col-sm-5 text-sm-right">User</dt>
	                                <dt class="col-sm-7 npUserName text-truncate"></dt>
	                            </dl>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer d-flex justify-content-between align-items-end p-3">
	                    <div class="progress" style="height: 12px;">
	                        <div class="progress-bar" role="progressbar" style="width: 15%; text-align: right; padding-right: 5px;" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100">15%</div>
	                        <!--<div class="progress-bar bg-secondary" role="progressbar" style="width: 18%; text-align: right; padding-right: 5px;" aria-valuenow="18" aria-valuemin="0" aria-valuemax="100">33%</div>-->
	                    </div>

                        <small class="npMediaTitle"></small>
                        <small class="npMediaTime"></small>
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
		
		
		.carousel {
			height: calc(100% - 70px);
		}
		
		.carousel-inner, .carousel-item {
			height: 100%;
		}
		
		.carousel-template {
			display: none;
		}
		
		.textInner {
			position: relative;
            top: 50%;
            transform: translateY(-50%);
		}
		
		.text-truncate {
			
		}
		
		.card-body {
			background-image: url(\'https://image.tmdb.org/t/p/original/ilKE2RPD8tkynAOHefX9ZclG1yq.jpg\');
			background-position: center;
			background-repeat: no-repeat;
			background-size: cover;
			background-color: #333;
			background-blend-mode: overlay;
			height: calc(100% - 52px) !important;
			padding-top: 0;
            padding-right: 1.25rem;
            padding-bottom: 0;
            padding-left: 1.25rem;
		}
		
		.media-image {
			background-position: center;
			background-repeat: no-repeat;
			background-size: contain;
		}
		
		.mediaDiv, .media-image, .media-text {
			height: 100%;
		}
		
		.mediaDiv {
			margin: 0 !important;
		}
		
		.media-image img {
			width: 100%;
			margin-top: 25%;
		}
		
		.card-footer {
			width: 100%;			
		    background: #474747;
		    position: absolute;
		    bottom: 0;
		    height: 52px;
		}
		
		.noItems {
			text-align: center;
		    background: gray;
		    display: inline-block;
		    position: absolute;
		    left: 50%;
		    transform: translateX(-50%);
		    border-radius: 10px;
		    padding: 5px;
		}
		
		.npMediaTitle {
			position: absolute;
			bottom: 5px;	
		}
		
		.progress {
			height: 12px;
		    position: absolute;
		    bottom: 40px;
		    left: 0;
		    width: 100%;
		}
		
		
		
		';
	}

	public static function widgetJS() {
		return [];
	}

}