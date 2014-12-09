<?php

//If data is in the posted array
if($_POST){
	//Pull data out of $_POST
	//Good for readability, not necessary
	$Append = $_POST['append'];
	$Points = $_POST['points'];

	//Init my debug array
	$debug = array();

	//If Append is true, make file open method append
	//otherwise, erase everything.
	if($Append == 'true'){
		$type="a";
		$debug['open_type'] = 'a';
	}else{
		$type="w";
		$debug['open_type'] = 'w';
	}

	//Used for debugging
	//Create a file called log.txt and chmod it to 777
	$debug['points'] = $_POST['points'];
	$debug['time'] = time();
	file_put_contents("log.txt",print_r($debug,true));

	//Open file as specifed (append, overwrite)
	//Make sure bus_stops.txt is 777
	$fp = fopen("bus_stops.txt",$type);

	//Write data to file.
	fwrite($fp,$Points."\n");

	//Return something that lets front end know if things worked. 
	//I just returned true, no matter what for now.
	header('Content-Type: application/json');
	echo '{"success":true}';
}