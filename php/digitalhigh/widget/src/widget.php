<?php

namespace digitalhigh\widget;

$paths = ['template', 'exception', 'curl', 'parser'];
foreach ($paths as $path) {
	$items = array_slice(scandir(dirname(__FILE__) . "/$path"), 2);
	foreach ($items as $item) {
		require_once dirname(__FILE__) . "/${path}/${item}";
	}
}

require_once dirname(__FILE__) . "/parser/cssParser.php";
use digitalhigh\parser\cssParser;

class widget {
	public $type;
	private $widgetObject;

	/**
	 * widget constructor.
	 * @param bool $data
	 * @throws exception\widgetException
	 */

	function __construct($data=false) {
		$files = array_slice(scandir(dirname(__FILE__) . "/template"), 2);
		$classes = array_map(function($file){
			return str_replace('.php', '', $file);
		}, $files);
		$type = ucfirst($data['type'] ?? 'generic');

		$typeCheck = "widget$type";
		if (in_array($typeCheck, $classes)) {
			$class = "digitalhigh\\widget\\template\\$typeCheck";
			$widgetObject = new $class($data);
		} else {
			$widgetObject = new template\widgetGeneric($data);
		}
		$this->widgetObject = $widgetObject;
		return $this->widgetObject;
	}

	public function serialize() {
		return $this->widgetObject->serialize();
	}

	public function update() {
		return $this->widgetObject->update();
	}

	public static function getMarkup($type) {
		$files = array_slice(scandir(dirname(__FILE__) . "/template"), 2);
		$classes = array_map(function ($file) {
			return str_replace('.php', '', $file);
		}, $files);
		$templates = [];
		foreach ($classes as $className) {
				$class = "digitalhigh\\widget\\template\\$className";
			if ($type === 'CSS') {
				$markup = $class::widgetCSS();
				$last = str_replace("widget", "", array_pop(explode("\\", $class)));
				$templates[] = (new cssParser($markup))->glue(".$last ");

			} else if ($type === 'JS') {
				$templates[$className] = $class::widgetJS();

			} else {
				$templates[] = $class::widgetHTML();
			}
		}


		if ($type === 'JS') {
			$result = self::buildJs($templates);

		} else if ($type === 'CSS') {
			$result = self::buildCss($templates);
		} else {
			$result = join(PHP_EOL, $templates);
		}

		return $result;
	}

	/**
	 * Generate Widget JQuery Plugin
	 * @param $templates
	 * @return string
	 */
	private static function buildJs($templates) {
		$initChecks = "";
		$updateChecks = "";
		// Loop through each set of markup from classes and create a function to init/update that widget
		foreach($templates as $key => $functions) {
			$propKey = lcfirst(str_replace("widget", "", $key));
			$initFunction = $functions['init'] ?? "console.log('No init function defined for $propKey');";
			$updateFunction = $functions['update'] ?? "console.log('No update function defined for $propKey');";
			$initChecks .= "
			case '$propKey':
				$initFunction
				break;
					";

			$updateChecks .= "
			case '$propKey':
				$updateFunction
				break;
					";
		}

		// Build our jQuery plugin to init and control the plugins from the UI.
		$result = "
!function($) {
    $.flexWidget = function(action, id) {
	var target = $('#widget' + id);
	var type = target.data('type');
	if (action === 'init') {
		console.log('Initializing target from id ' + id, target);
		init(type, target);	
	} else if (action === 'update') {
		console.log('Updating target from id ' + id, target);
		update(type, target);
	} else {
		initTemplates();
		initGrid();
		console.log('Initializing the grid.');
	}
	
	function initGrid() {
		console.log(\"Max rows?\");
	    var options = {
	        alwaysShowResizeHandle: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
	        cellHeight: 70,
	        acceptWidgets: true,
	        animate: true,
	        float: true,
	        height: 10
	    };
	
	    var wl = $('#widgetList');
	
	    wl.gridstack(options);
	
	    $('#widgetDeleteList').gridstack(options);
	    widgetList = wl.data('gridstack');
	
        wl.on('dragstop', function(event, ui) {
            console.log('Drag stop');
	         //saveWidgetContainers();
		});
	
	    wl.on('change', function() {
	        console.log('Change');
	        saveWidgetContainers();
	    });
	    
	}
	
	function initTemplates() {
	    console.log(\"INITIALIZING TEMPLATES.\");
	    var wt = $('#widgetTemplates').clone().prop('id', 'widgetAddList');
	    wt.css('display', 'block');
	    var wd = $('#widgetDrawer');
	    wd.html(\"\");
	    wt.appendTo(wd);
	
	    var addOptions = {
	        cellHeight: 70,
	        acceptWidgets: false
	    };
	
	    var wal = $('#widgetAddList');
	
	    wal.gridstack(addOptions);
	
	    wal.on('removed', function() {
	        initTemplates();
	    });
	
	}
	
	function init(type, target) {
	console.log(\"ItemEL: \", target);
    var type = target.data('type');
    var targetId = target.data('target');
    console.log(\"Type is \" + type, \"target is \" + targetId);
		switch(type) {
			$initChecks
			default: 
				return false;
		}
	}
	
	function update(type, target) {
		switch(type) {
			$updateChecks
			default:
				return false;
		}
	}
	}
}( jQuery );";

		return $result;
	}


	/**
	 * Generate Widget CSS
	 * Adds CSS code for general widget functionality
	 * @param $templates
	 * @return string
	 */
	private static function buildCss($templates) {
		$results = "			
			.card-background {
			    overflow: hidden;
			    width: 100%;
			    height: 100%;
			}
			
			.editItem {
			    display: none;
			}
			
			.grid-stack-item-content {
				overflow: visible !important;
			}
			
			.dropdown-menu {
				overflow: hidden;
				margin: 0;
			}
			
			.widgetMenu {
			    color: white;
			}
			
			.dropdown-item {
				width: 100%;
				margin: 0;
				background: var(--theme-primary);
				color: var(--theme-primary-inverse);
			}
			
			.btn-settings {
			    padding: 0;
			}
			
			.card-settings {
				display: none;
				position: relative;
			}
			
			.form-group {
				z-index: 8;
			}
		";

		$noPrepend = "
			.widgetCard { transition: all .2s ease-in-out; }
			
			.widgetCard.editCard {
				z-index: 10;
				height: 100% !important;
				box-shadow: 0 10px 20px rgba(0,0,0,0.19), 0 6px 6px rgba(0,0,0,0.23);
			}
		";

		// Format un-prepended CSS
		$noPrepend = (new CssParser($noPrepend))->glue();

		// Append child selector to above elements
		$baseResults = (new cssParser($results))->glue(".widgetCard ");

		// Append modifying selector to sub-widgets
		$widgetResults = (new cssParser(join(PHP_EOL, $templates)))->glue(".widgetCard");


		return $noPrepend . PHP_EOL . $baseResults . PHP_EOL . $widgetResults;
	}
}