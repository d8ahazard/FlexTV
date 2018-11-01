<?php

namespace digitalhigh\widget;

$paths = ['template', 'exception'];
foreach ($paths as $path) {
	$items = array_slice(scandir(dirname(__FILE__) . "/$path"), 2);
	foreach ($items as $item) {
		require_once $libPath = dirname(__FILE__) . "/${path}/${item}";
	}
}

class widget {
	public $type;
	private $widgetObject;
	/**
	 * widget constructor.
	 * @param $type
	 * @param $data
	 * @throws widgetException
	 */
	function __construct($type, $data=false) {
		$files = array_slice(scandir(dirname(__FILE__) . "/template"), 2);
		$classes = array_map(function($file){
			return str_replace('.php', '', $file);
		}, $files);
		$type = ucfirst($data['type'] ?? 'generic');

		$typeCheck = "widget$type";

		if (isset($classes, $typeCheck)) {
			$class = "digitalhigh\\widget\\template\\$typeCheck";
			$widgetObject = new $class($data);
		} else {
			$widgetObject = new digitalhigh\widget\template\widgetGeneric($data);
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
		$classes = array_map(function($file){
			return str_replace('.php', '', $file);
		}, $files);
		$templates = [];
		foreach ($classes as $className) {
			$class = "digitalhigh\\widget\\template\\$className";
			if ($type === 'CSS') {
				$templates[] = $class::widgetCSS();
				$result = join(PHP_EOL,$templates);

			} else if ($type === 'JS') {
				$templates[$className] = $class::widgetJS();
				$i = 0;
				$js = "";
				$initChecks = "";
				$updateChecks = "";
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
			} else {
				$templates[] = $class::widgetHTML();
				$result = join(PHP_EOL,$templates);
			}
		}
		return $result;
	}

}