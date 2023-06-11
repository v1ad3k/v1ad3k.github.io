<?php 
error_reporting(E_ERROR | E_PARSE);

$plugin = explode(',',$_GET['mod'] ? $_GET['mod'] : 'o,s');
$conf   = $_GET['conf'];
$type   = $_GET['type'];
$folder = explode("?",substr($_SERVER['REQUEST_URI'],1));

$path = substr($folder[0], 0, 2);


if($folder[0]) $plugin = [$folder[0]];

if(file_exists('raw/'.$path)){
	$plugin = [json_decode(file_get_contents('raw/' . $path . '/' . $folder[0] .'.conf.json'),true)['mod']];
	$conf   = json_encode(json_decode(file_get_contents('raw/' . $path . '/' . $folder[0] .'.json'),true));
}

$file = file_get_contents('main.js');

if((isset($_GET['mod']) || isset($_GET['conf'])|| isset($_GET['type']) || $folder[0]) && count($plugin) > 0){
	header('Content-Type: application/javascript');

	$lines = explode("\n",$file);
	$need  = [];

	foreach ($plugin as $key => $value) {
		if(file_exists('plugins/' . $value . '.js')) $need[] = $value . '.js';
	}

	if(count($need) == 0) $need = ['o.js','s.js'];

	foreach ($lines as $key => $value) {
		if(strpos($value, 'var plugins =') !== false){
			$lines[$key] = "            var plugins = ".json_encode($need).";";
		}
		else if(strpos($value, 'var conf =') !== false){
			if(isset($_GET['conf'])) $lines[$key] = "          var conf = '".$_GET['conf']."';";
			else if($conf) $lines[$key] = "          var conf = '".$conf."';";
		}else if(strpos($value, 'var type =') !== false){
			if(isset($type))
				$lines[$key] = "          var type = '".$type."';";
		}
	}

	$file = implode("\n",$lines);

	//if(isset($_GET['conf'])) $file = str_replace("'web', '')","'web', '".$conf."')",$file);
	
	echo $file;
}
else{
	echo file_get_contents('index.html');
}

?>