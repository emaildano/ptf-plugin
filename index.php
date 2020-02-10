<?php

/*
 * Bootstrap
 */
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/wordpress/wp-blog-header.php';

header("HTTP/1.1 200 OK");

extract($_POST);

if(isset($class) && isset($process)){

	$obj = new $class();
	if(isset($var)){
		echo $obj->$process($var);




	}else{
		echo $obj->$process();
	}
}

?>
