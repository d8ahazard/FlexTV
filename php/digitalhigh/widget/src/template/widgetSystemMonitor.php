<?php

namespace digitalhigh\widget\template;

use digitalhigh\widget\exception\widgetException;
use digitalhigh\widget\curl\curlGet;

class widgetSystemMonitor {
	// Unique ID for each widget
	// Data store for other values
	private $data;
	// Required values in order for other things to work
	const required = [];
	// Set these accordingly
	const maxWidth = 6;
	const maxHeight = 6;
	const minWidth = 2;
	const minHeight = 3;
	const refreshInterval = 5;
	const type = "SystemMonitor";
	/**
	 * widgetSystemMonitor constructor.
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
		$this->data['limit'] = $data['limit'] ?? 20;
		write_log("WE HAVE VALUES.", "INFO", false, true);
	}


	public function update($force=false) {
		$lastUpdate = $this->data['lastUpdate'];
		$int = self::refreshInterval;
		$total = $lastUpdate + $int;
		$now = time();
		if ($now > $total || $force) {
			$this->data['lastUpdate'] = time();
			// Do stuff here to update
			$stats = $this->fetchSystemStats();
			$this->data['stats'] = $stats;
		}
		return $this->serialize();
	}

	private function fetchSystemStats() {
		write_log("WE ARE FETCHING STATS.", "PINK", false, true);
		$uri = $this->data['uri'];
		$token = $this->data['token'];
		$url = "$uri/stats/system?X-Plex-Accept=json&X-Plex-Token=$token";
		$data = curlGet($url, false, 10);
		write_log("RAW RESULT from $url: ".json_encode($data), "ORANGE", false, true);
		return $data['MediaContainer'] ?? false;
	}


	public function serialize() {
		return $this->data;
	}

	public static function widgetHTML() {
		// As odd as it may seem, this is where we set our "default" values for the widget.
		// Auto-position will be turned off when the widget is created.
		$attributes = [
			'gs-x' => 1,
			'gs-y' => 1,
			'gs-width' =>4,
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
		return '
		<div class="widgetCard card grid-stack-item '.self::type.'" '.$attributeString.'>
			<h4 class="card-header d-flex justify-content-between align-items-center text-white px-3">
					<span class="d-flex align-items-center">
						<i class="material-icons dragHandle editItem">drag_indicator</i></span>Server Overview
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
				
			<div class="card-body card-content slideContent">
				<div class="serverOverviewBars" style="width: 100%;height: 100%;"></div>
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
		$init = /** @lang JavaScript */
			'
		var devOutput = "";
                        console.log("We have a device list.", devices)  ;
                        var deviceList = devices;
                        if (deviceList.hasOwnProperty(\'Server\')) {
                            var serverList = deviceList[\'Server\'];
                            var i = 0;
                            $.each(serverList, function (key, device) {
                                var selected = "";
                                    if (i === 0) {
                                        console.log("Selecting");
                                        selected = " selected";
                                    }
                                var id = device["Id"];
                                var name = device["Name"];
                                if (device[\'HasPlugin\']) {
                                    devOutput += "<option data-type=\'Server\' value=\'" + id + "\'" + selected + ">" + name + "</option>";
                                    if (selected !== "") {
                                        console.log("Setting attributes...");
                                        widget.attr(\'data-target\', id);
                                        widget.attr(\'data-uri\', device[\'Uri\']);
                                        widget.attr(\'data-token\', device[\'Token\']);
                                    }
                                }
                                i++;
                            });
                        }

                    var list = widget.find(\'.serverList\');
                    console.log("Setting serverList to " + devOutput, list);
                    list.html(devOutput);
                    var bars = widget.find(\'.serverOverviewBars\');
                    var chartData = buildChart(\'systemMonitor\', widgetData[\'stats\']);
                    console.log("Chart data from widgetData", widgetData, chartData);
                    var seriesData = chartData[0];
                    var drillDownData = chartData[1];

                    var chartOpts = {
                        chart: {
                            type: \'bar\'
                        },
                        title: {
                            text: null
                        },
                        legend: {
                            enabled: false
                        },
                        tooltip: {
                            outside: true
                        },
                        xAxis: {
                            type: \'category\',
                            title: {
                                text: null
                            }
                        },
                        yAxis: {
                            min: 0,
                            max: 100,
                            title: {
                                text: null
                            },
                            labels: {
                                formatter: function () {
                                    return Math.abs(this.value) + \'%\';
                                }
                            }
                        },
                        plotOptions: {
                            series: {
                                borderWidth: 0
                            },
                            bar: {
                                dataLabels: {
                                    enabled: true,
                                    format: \'{point.percent}%\'
                                }
                            }
                        },
                        series: seriesData,
                        drilldown: drillDownData
                    };
                    console.log("Chart options: ", chartOpts);
                    var serverOverviewBars = Highcharts.chart(bars[0], chartOpts);

                    $(document).on(\'gsresizestop\', widget, function() {
                        console.log("REFLOW TRIGGERED.");
                       serverOverviewBars.reflow();
                    });
		';

		$update = /** @lang JavaScript */
		'
		
		';
		return [];
	}

}