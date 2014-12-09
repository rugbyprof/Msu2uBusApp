<?php
//Gets us connected to our mysql database on the LOCAL server.
//$db = new PDO('mysql:host=localhost;dbname=mobile_web;charset=utf8', 'mobile', 'mobile');
try {
	$db = new PDO('mysql:host=localhost;dbname=mobile_web;charset=utf8', 'mobile', 'mobile');
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$conn = true;
} catch(PDOException $e) {
	$Result = array("Success"=>0,"Message"=>"Database connection failed!");
	$conn = false;
}

$json = json_decode(file_get_contents('http://api.randomuser.me/?results=20'));

echo"<pre>";

foreach($json->results as $person){
	$person = $person->user;
	
	$gender = $person->gender;
	$fname = ucfirst($person->name->first);
	$lname = ucfirst($person->name->last);
	$email = $person->email;
	$thumb = $person->picture;
	$sex = strtoupper(substr($person->gender,0,1));
	$phone = $person->cell;
	$password = md5('password');
	$busy = 0;
	$logged_in = 0;
	$lat = 0.0;
	$lon = 0.0;
	
	AddUser($db,$max,$fname,$lname,$thumb,$lat,$lon,$sex,$phone,$email,$busy,$logged_in,$password);
}

/*
* Adds a user to the database.
* @Params: 
* 	$db - database connection resource
*	$_POST - POST array is a SUPER GLOBAL.
* @Returns:
*	bool - success or fail 
*/
function AddUser($db,$max,$fname,$lname,$thumb,$lat,$lon,$sex,$phone,$email,$busy,$logged_in,$password){
	$sth = $db->prepare("SELECT MAX(Id) as Max FROM Users");
	$sth->execute();
	$result = $sth->fetchAll();
	
	$max = $result[0]['Max'] + 1;

	$sql = "INSERT INTO `mobile_web`.`Users` (`Id`, `First`, `Last`, `Thumbnail`, `Lat`, `Lon`, `Sex`, `Phone`, `Email`, `Busy`, `LoggedIn`,`Password`) 
			VALUES ('{$max}', '{$fname}', '{$lname}', '{$thumb}','{$lat}', '{$lon}','{$sex}', '{$phone}', '{$email}', '{$busy}', '{$logged_in}','{$password}');";

	$sth = $db->prepare($sql);
	return $sth->execute();		//return true if query ran
}