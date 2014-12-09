<?php
error_reporting (0);
$bus_id = '461';
$url = "http://gpsi.co/k/transitdispatch/{$bus_id}/4110";
$url = "http://gpsi.co/k/transitdispatch/4110";

//print_r(get_headers($url));

$cacheTime = time() - filemtime("current.kml");

if($cacheTime>=0){
    $kmz = file_get_contents($url);
    $kml = decompress_first_file_from_zip($kmz);
    file_put_contents("current.kml",$kml);
}else{
    $kml = file_get_contents("current.kml");
}

$p = xml_parser_create();
xml_parse_into_struct($p, $kml, $vals, $index);
xml_parser_free($p);

$data[] = print_r($vals,true);
$data[] = print_r($index,true);
for($i=0;$i<sizeof($index['DESCRIPTION']);$i++){
	$str = ExtractData($vals[$index['DESCRIPTION'][$i]]['value']);
	$location = $vals[$index['COORDINATES'][$i]]['value'];
	$str['location'] = fix_location($location);
	$str['Recorded'] = fix_date($str['Recorded']);
	$str['Current'] = fix_date($str['Current']);
	$data['Info'][] = print_r($str,true);
}

$data = print_r($data,true);
file_put_contents("log.txt",$data);

//header('Content-Type: application/json; charset=utf-8');
//echo $json;


//Functions:
//========================================================================================

// Why use a badass regular expression when you could take about 5 or 6 different
// steps to do the same thing??
function ExtractData($str){
	$str = strip_tags($str,'<td>');														//remove unwanted tags
	$str = preg_replace('/(?:<|&lt;)\/?([a-zA-Z]+) *[^<\/]*?(?:>|&gt;)/', '~', $str);	//replace tags with tildes
	$str = trim($str,'~'); 																//trim tildes from front and rear
	$str = trim($str,'~'); 																//trim tildes from front and rear (again)
	$str = str_replace(' ~ ',' ',$str);													//leave only tildes seperating key:value pairs
	$str = str_replace(': ','=',$str);
	$tmpArray = explode('~',$str);														//now we have an array of key:value
	foreach($tmpArray as $item){
		list($key,$val) = explode('=',$item);
		$dataArray[$key] = $val;
	}
	return $dataArray;
}

function fix_location($loc){
	list($lon,$lat,$null) = explode(',',$loc);
	return array('lat'=>$lat,'lon'=>$lon);
}

function fix_date($date){
	//Jul 16, 2014 19:39:41

	list($month,$day,$year,$time) = explode(' ',$date);
	$Date['string'] = $date;
	$Date['day'] = trim($day,',')*1;
	$Date['month'] = StrMonthToInt($month);
	$Date['year'] = $year;
	list($Date['hrs'],$Date['min'],$Date['sec']) = explode(':',$time);
	$Date['unix'] = mktime($Date['hrs'],$Date['min'],$Date['sec'],$Date['month'],$Date['day'],$Date['year']);
	return $Date;
}

function StrMonthToInt($month){
	$m = array(
		'jan'=>1,
		'feb'=>2,
		'mar'=>3,
		'apr'=>4,
		'may'=>5,
		'jun'=>6,
		'jul'=>7,
		'aug'=>8,
		'sep'=>9,
		'oct'=>10,
		'nov'=>11,
		'dec'=>12);
	return $m[strtolower($month)];
}

function decompress_first_file_from_zip($ZIPContentStr){ 
//Input: ZIP archive - content of entire ZIP archive as a string 
//Output: decompressed content of the first file packed in the ZIP archive 
    //let's parse the ZIP archive 
    //(see 'http://en.wikipedia.org/wiki/ZIP_%28file_format%29' for details) 
    //parse 'local file header' for the first file entry in the ZIP archive 
    if(strlen($ZIPContentStr)<102){ 
        //any ZIP file smaller than 102 bytes is invalid 
        printf("error: input data too short<br />\n"); 
        return ''; 
    } 
    $CompressedSize=binstrtonum(substr($ZIPContentStr,18,4)); 
    $UncompressedSize=binstrtonum(substr($ZIPContentStr,22,4)); 
    $FileNameLen=binstrtonum(substr($ZIPContentStr,26,2)); 
    $ExtraFieldLen=binstrtonum(substr($ZIPContentStr,28,2)); 
    $Offs=30+$FileNameLen+$ExtraFieldLen; 
    $ZIPData=substr($ZIPContentStr,$Offs,$CompressedSize); 
    $Data=gzinflate($ZIPData); 
    if(strlen($Data)!=$UncompressedSize){ 
        printf("error: uncompressed data have wrong size<br />\n"); 
        return ''; 
    } 
    else return $Data; 
} 

function binstrtonum($Str){ 
//Returns a number represented in a raw binary data passed as string. 
//This is useful for example when reading integers from a file, 
// when we have the content of the file in a string only. 
//Examples: 
// chr(0xFF) will result as 255 
// chr(0xFF).chr(0xFF).chr(0x00).chr(0x00) will result as 65535 
// chr(0xFF).chr(0xFF).chr(0xFF).chr(0x00) will result as 16777215 
    $Num=0; 
    for($TC1=strlen($Str)-1;$TC1>=0;$TC1--){ //go from most significant byte 
        $Num<<=8; //shift to left by one byte (8 bits) 
        $Num|=ord($Str[$TC1]); //add new byte 
    } 
    return $Num; 
}
