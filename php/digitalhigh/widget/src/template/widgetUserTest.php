<?php

namespace digitalhigh\widget\template;

require_once dirname(__FILE__) . "/../base/widgetBase.php";

class widgetUserTest {
	public $data;
	public $type;

	public function __construct($data) {
		$this->data = $data;
		$this->type = 'userTest';
	}

	public function update() {
		return $this->serialize();
	}

	public function serialize() {
		return array_merge($this->data,['type' => $this->type]);
	}

	public static function widgetHTML() {
		return '
		<div class="widgetCard grid-stack-item" data-type="userTest" data-target="" data-gs-x="4" data-gs-y="0" data-gs-width="3" data-gs-height="2">
		    <div class="spinCard grid-stack-item-content">
		        <div class="card m-0 card-rotate card-background">
		            <!-- This is the UI side. -->
		            <div class="front front-background">
                        <h4 class="card-header d-flex justify-content-between align-items-center text-white px-3">
							<span class="d-flex align-items-center">
							<i class="material-icons editItem">drag_indicator</i></span>User Activity 
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

						<ul id="userInformation" class="list-group list-group-flush">
					
							<!-- Check if Plex Server is online -->
							<li id="userDataSample" class="list-group-item d-flex justify-content-between align-items-center list-group-item-primary">User: ' . $_SESSION['plexUserName'] . ' <svg class="svg-inline--fa fa-check-circle fa-w-16 fa-fw" data-fa-transform="grow-4" aria-hidden="true" data-prefix="fas" data-icon="check-circle" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="" style="transform-origin: 0.5em 0.5em;"><g transform="translate(256 256)"><g transform="translate(0, 0)  scale(1.25, 1.25)  rotate(0 0 0)"><path fill="currentColor" d="M504 256c0 136.967-111.033 248-248 248S8 392.967 8 256 119.033 8 256 8s248 111.033 248 248zM227.314 387.314l184-184c6.248-6.248 6.248-16.379 0-22.627l-22.627-22.627c-6.248-6.249-16.379-6.249-22.628 0L216 308.118l-70.059-70.059c-6.248-6.248-16.379-6.248-22.628 0l-22.627 22.627c-6.248 6.248-6.248 16.379 0 22.627l104 104c6.249 6.249 16.379 6.249 22.628.001z" transform="translate(-256 -256)"></path></g></g></svg><!-- <i class="fas fa-fw fa-check-circle" data-fa-transform="grow-4"></i> --></li>
							
							<li id="currentActivitySample" class="list-group-item d-flex justify-content-between align-items-center bg-dark">Current activity:
							</li>
							<li id="currentViewsSample" class="list-group-item d-flex justify-content-between align-items-center bg-dark">Most viewed:
							</li>
						</ul>

		            </div>
					<!-- These are the settings -->
		            <div class="back card-rotate back-background">
		                <h4 class="widgetHandle card-header card-header-primary text-center px-2 statHeader">Settings</h4>
		                <div class="form-group bmd-form-group">
		                <label class="appLabel" for="serverList">Label</label>
	                        <input type="text" class="statInput" data-for="label"/>
	                        <label class="appLabel" for="serverList">Server</label>
                           <input type="select" class="form-control custom-select serverList statInput statTarget" data-for="target"/>
	                    </div>
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