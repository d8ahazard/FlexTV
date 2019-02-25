<?php

namespace digitalhigh\widget\template;

require_once dirname(__FILE__) . "/../base/widgetBase.php";

class widgetNowPlaying {
	public $data;
	public $type;

	public function __construct($data) {
		$this->type = 'NowPlaying';
		$this->data = $data;
	}

	public function update() {
		return $this->serialize();
	}

	public function serialize() {
		return $this->data;
	}

	public static function widgetHTML() {
		$last = lcfirst(str_replace("widget", "", array_pop(explode("/", get_called_class()))));
		$attributes = [
			'type' => $last,
			'target' => "",
			'gs-x' => 1,
			'gs-y' => 0,
			'gs-width' =>4,
			'gs-height' => 5,
			'gs-min-width' => 2,
			'gs-min-height' => 2,
			'gs-max-width' => 8,
			'gs-max-height' => 5,
			'gs-auto-position' => true
		];
		$attributeStrings = [];
		foreach($attributes as $key => $value) $attributeStrings[] ="data-${key}='${value}'";
		$attributeString = join(" ", $attributeStrings);
		return '
		<div class="widgetCard grid-stack-item" '.$attributeString.'>
			<div class="spinCard grid-stack-item-content">
				<div class="card m-0 card-rotate card-background">
					<div class="front front-background">
						<!-- You can start putting content here -->
						
						<!-- Delete this if the header menu is not needed -->
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
						    <div id="currentActivity" class="carousel slide card m-0 bg-dark transparent shadow" data-ride="carousel">
			                    <ol class="carousel-indicators">
			                        <li data-target="#currentActivity" data-slide-to="0" class="active"></li>
			                        <li data-target="#currentActivity" data-slide-to="1" class=""></li>
			                    </ol>
					    		
			                    <div class="carousel-inner">
			                        <div class="carousel-item active">
			
			                            <div class="card-body" id="alertBody" style="background-image: url(\'https://image.tmdb.org/t/p/original/ilKE2RPD8tkynAOHefX9ZclG1yq.jpg\'); background-position: center; background-repeat: no-repeat; background-size: cover; background-color: #333; background-blend-mode: overlay;">
			                                <div class="media text-white">
			                                    <img class="mr-3" src="https://image.tmdb.org/t/p/original/7htwyZzjIUFIIkGQ6HhMgv2kVmM.jpg" alt="Poster" width="100">
			                                    <dl class="row mb-0">
			                                        <dt class="col-sm-4 text-sm-right">Status</dt>
			                                        <dd class="col-sm-8">Playing</dd>
			                                        <dt class="col-sm-4 text-sm-right">Quality</dt>
			                                        <dd class="col-sm-8">SD (1.9 Mbps)</dd>
			                                        <dt class="col-sm-4 text-sm-right">Bandwidth</dt>
			                                        <dd class="col-sm-8">1.7 Mbps</dd>
			                                        <dt class="col-sm-4 text-sm-right">Stream</dt>
			                                        <dd class="col-sm-8">Transcode (throttled) <a data-toggle="collapse" href="#streamInfo1" role="button" aria-expanded="false" aria-controls="streamInfo1">+</a></dd>
			
			                                        <dl class="collapse col-sm-8 offset-sm-4" id="streamInfo1">
			                                            <dt>Container</dt>
			                                            <dd>Transcode (MP4)</dd>
			                                            <dt>Video</dt>
			                                            <dd>Transcode (720p (H.264) 🡒 SD (H.264))</dd>
			                                            <dt>Audio</dt>
			                                            <dd>Transcode (AC3 5.1 🡒 AAC 2.0)</dd>
			                                            <dt>Subtitle</dt>
			                                            <dd>None</dd>
			                                        </dl>
			
			                                        <dt class="col-sm-4 text-sm-right">Player</dt>
			                                        <dd class="col-sm-8">Plex Web (Chrome)</dd>
			                                        <dt class="col-sm-4 text-sm-right">User</dt>
			                                        <dd class="col-sm-8">tylerforesthauser</dd>
			                                    </dl>
			                                </div>
			                            </div>
			
			                            <div class="progress" style="height: 12px;">
			                                <div class="progress-bar" role="progressbar" style="width: 15%; text-align: right; padding-right: 5px;" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100">15%</div>
			                                <div class="progress-bar bg-secondary" role="progressbar" style="width: 18%; text-align: right; padding-right: 5px;" aria-valuenow="18" aria-valuemin="0" aria-valuemax="100">33%</div>
			                            </div>
			
			                            <div class="card-footer d-flex justify-content-between align-items-end p-3">
			                                <small>
			                                    American Horror Story<br>
			                                    S08 • E07 - Traitor
			                                </small>
			                                <small>
			                                    9:32 / 1:58:11
			                                </small>
			                            </div>
			
			                        </div>
			                        <div class="carousel-item">
			
			                            <div class="card-body" id="alertBody" style="background-image: url(\'https://image.tmdb.org/t/p/original/uqTCaYBoSLT9MAdyQ9tU6QyCZ3A.jpg\'); background-position: center; background-repeat: no-repeat; background-size: cover; background-color: #333; background-blend-mode: overlay;">
			                                <div class="media text-white">
			                                    <img class="mr-3" src="https://image.tmdb.org/t/p/original/hnGbyiKLmK6gciYgD2HvXi9Pi29.jpg" alt="Poster" width="100">
			                                    <dl class="row mb-0">
			                                        <dt class="col-sm-4 text-sm-right">Status</dt>
			                                        <dd class="col-sm-8">Playing</dd>
			                                        <dt class="col-sm-4 text-sm-right">Quality</dt>
			                                        <dd class="col-sm-8">720p (3.9 Mbps)</dd>
			                                        <dt class="col-sm-4 text-sm-right">Bandwidth</dt>
			                                        <dd class="col-sm-8">3.4 Mbps</dd>
			                                        <dt class="col-sm-4 text-sm-right">Stream</dt>
			                                        <dd class="col-sm-8">Direct Play <a data-toggle="collapse" href="#streamInfo2" role="button" aria-expanded="false" aria-controls="streamInfo2">+</a></dd>
			
			                                        <dl class="collapse col-sm-8 offset-sm-4" id="streamInfo2">
			                                            <dt>Container</dt>
			                                            <dd>Direct Play (MKV)</dd>
			                                            <dt>Video</dt>
			                                            <dd>Direct Play (720p (H.264))</dd>
			                                            <dt>Audio</dt>
			                                            <dd>Direct Play (AC3 5.1)</dd>
			                                            <dt>Subtitle</dt>
			                                            <dd>None</dd>
			                                        </dl>
			
			                                        <dt class="col-sm-4 text-sm-right">Player</dt>
			                                        <dd class="col-sm-8">Bedroom (Roku)</dd>
			                                        <dt class="col-sm-4 text-sm-right">User</dt>
			                                        <dd class="col-sm-8">tylerforesthauser</dd>
			                                    </dl>
			                                </div>
			                            </div>
			
			                            <div class="progress" style="height: 12px;">
			                                <div class="progress-bar" role="progressbar" style="width: 85%; text-align: right; padding-right: 5px;" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100">85%</div>
			                            </div>
			
			                            <div class="card-footer d-flex justify-content-between align-items-end p-3">
			                                <small>
			                                    It\'s Always Sunny in Philadelphia<br>
			                                    S13 • E09 - The Gang Wins the Big Game
			                                </small>
			                                <small>
			                                    16:50 / 19:49
			                                </small>
			                            </div>
			
			                        </div>
			                    </div>
			                </div>
			            
						
					</div>
					
				</div>
			</div>
		</div>
		';
	}

	/**
	 * CSS here will be prepended with the class selector of the widget, so it's safe to re-use
	 * selectors between widgets.
	 * @return string
	 */
	public static function widgetCSS() {
		return '';
	}

	public static function widgetJS() {
		return [];
	}

}