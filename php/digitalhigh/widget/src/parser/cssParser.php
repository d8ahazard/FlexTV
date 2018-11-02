<?php

namespace digitalhigh\parser;
/**
 * CSSPARSER
 * Copyright (C) 2009 Peter Kröner
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Modified, DH, 2018 to add prepend functionality, update phpdoc, fix minor syntactical issues.
 */





/**
 * CSS PARSER
 * General purpose CSS parser
 */
class cssParser {


	public $css;
	public $parsed;

	/**
	 * cssParser constructor.
	 * @param bool | string | array $input -
	 * Either a path to CSS, a string of CSS, or an array of pre-parsed CSS. Or false to just initialize for later.
	 */
	public function __construct($input = false) {
		$this->css = "";
		if ($input) {
			if (file_exists($input)) {
				$this->load_file($input);
			} else if (is_string($input)) {
				$this->load_string($input);
			} else if (is_array($input)) {
				$this->parsed = $input;
			}
		}
		if ($this->css !== "") {
			$this->parse();
		}
	}

	/**
	 * LOAD_STRING
	 * Loads a css string
	 * @param $string
	 * @param bool $overwrite
	 */
	public function load_string($string, $overwrite = false){
		if($overwrite){
			$this->css = $string;
		} else {
			$this->css .= $string;
		}
	}


	/**
	 * LOAD_FILE
	 * Loads a file
	 * @param $file
	 * @param bool $overwrite
	 */
	public function load_file($file, $overwrite = false){
		$this->load_string(file_get_contents($file), $overwrite);
	}


	/**
	 * LOAD_FILES
	 * Loads a number of files
	 * @param $files
	 */
	public function load_files($files){
		$files = explode(';', $files);
		foreach($files as $file){
			$this->load_file($file, false);
		}
	}


	/**
	 * PARSE
	 * Parses some CSS into an array
	 */
	public function parse(){
		$css = $this->css;
		// Remove CSS-Comments
		$css = preg_replace('/\/\*.*?\*\//ms', '', $css);
		// Remove HTML-Comments
		$css = preg_replace('/([^\'"]+?)(\<!--|--\>)([^\'"]+?)/ms', '$1$3', $css);
		// Extract @media-blocks into $blocks
		preg_match_all('/@.+?\}[^\}]*?\}/ms',$css, $blocks);
		// Append the rest to $blocks
		array_push($blocks[0],preg_replace('/@.+?\}[^\}]*?\}/ms','',$css));
		$ordered = array();
		for($i=0;$i<count($blocks[0]);$i++){
			// If @media-block, strip declaration and parenthesis
			if(substr($blocks[0][$i],0,6) === '@media')
			{
				$ordered_key = preg_replace('/^(@media[^\{]+)\{.*\}$/ms','$1',$blocks[0][$i]);
				$ordered_value = preg_replace('/^@media[^\{]+\{(.*)\}$/ms','$1',$blocks[0][$i]);
			}
			// Rule-blocks of the sort @import or @font-face
			elseif(substr($blocks[0][$i],0,1) === '@')
			{
				$ordered_key = $blocks[0][$i];
				$ordered_value = $blocks[0][$i];
			}
			else
			{
				$ordered_key = 'main';
				$ordered_value = $blocks[0][$i];
			}
			// Split by parenthesis, ignoring those inside content-quotes
			$ordered[$ordered_key] = preg_split('/([^\'"\{\}]*?[\'"].*?(?<!\\\)[\'"][^\'"\{\}]*?)[\{\}]|([^\'"\{\}]*?)[\{\}]/',trim($ordered_value," \r\n\t"),-1,PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
		}

		// Beginning to rebuild new slim CSS-Array
		foreach($ordered as $key => $val){
			$new = array();
			for($i = 0; $i<count($val); $i++){
				// Split selectors and rules and split properties and values
				$selector = trim($val[$i]," \r\n\t");

				if(!empty($selector)){
					if(!isset($new[$selector])) $new[$selector] = array();
					$rules = explode(';',$val[++$i]);
					foreach($rules as $rule){
						$rule = trim($rule," \r\n\t");
						if(!empty($rule)){
							$rule = array_reverse(explode(':', $rule));
							$property = trim(array_pop($rule)," \r\n\t");
							$value = implode(':', array_reverse($rule));

							if(!isset($new[$selector][$property]) || !preg_match('/!important/',$new[$selector][$property])) $new[$selector][$property] = $value;
							elseif(preg_match('/!important/',$new[$selector][$property]) && preg_match('/!important/',$value)) $new[$selector][$property] = $value;
						}
					}
				}
			}
			$ordered[$key] = $new;
		}
		$this->parsed = $ordered;
	}


	/**
	 * @param bool | string $prepend - Optional, the class name to prepend to the CSS
	 * @return string
	 */
	public function glue($prepend=false){
		$output = '';
		if($this->parsed){
			foreach($this->parsed as $media => $content){
				if(substr($media,0,6) === '@media'){
					$output .= $media . " {\n";
					$prefix = "\t";
				}
				else $prefix = "";
				$preString = "";
				if ($prepend) $preString = "$prepend";

				foreach($content as $selector => $rules){
					$output .= $prefix.$preString.$selector . " {\n";
					foreach($rules as $property => $value){
						$output .= $prefix."\t".$property.': '.$value;
						$output .= ";\n";
					}
					$output .= $prefix."}\n\n";
				}
				if(substr($media,0,6) === '@media'){
					$output .= "}\n\n";
				}
			}
		}
		return $output;
	}

	public function prepend($prepend) {
		return $this->glue($prepend);
	}

}