<?php

$jsFiles = getDirContents('./js/');
$cssFiles = getDirContents('./css/');

$files = array_merge($jsFiles, $cssFiles);

foreach($files as $file) {
	$file = str_replace("/volume1/Webroot/FlexTV",".",$file);
	echo "'$file'," . "<BR>";
}

function getDirContents($dir, &$results = array()){
	$files = scandir($dir);

	foreach($files as $key => $value){
		$path = realpath($dir.DIRECTORY_SEPARATOR.$value);
		if(!is_dir($path)) {
			$results[] = $path;
		} else if($value != "." && $value != "..") {
			getDirContents($path, $results);
		}
	}

	return $results;
}