<?php
set_time_limit (0); //don't timeout on big queries
//show errors 
ini_set('display_errors',1);
error_reporting(E_ALL);
ini_set('memory_limit', '-1');

//connect to the database
$db_connection = pg_connect("host=localhost dbname=wooden_ships user=scottsfarley password=xP73m3YAb1"); // geography grad server
//user scottsfarley has all permissions on this database
if (!$db_connection){
	die("Could not connect to database: ".pg_errormessage($db_connection));
}else{
}
//all requests are GET requests -- > don't try to use POST 
// 
// header=False, logbookLanguage = "", voyageFrom = "", voyageTo = "", shipName = "", shipType = "", company = "", nationality = "",
            // name1= "", year = "", month = "", day = "", lat = "", lng = "", obsGen = False, cargo = False, biology=False,
            // warsAndFights=False, otherObs=False, illustrations=False, shipsAndRig=False, lifeOnBoard=False,
            // anchored = False, windDirection = "", windForce="", weather="", shapeClouds= "", dirClouds="", clearness="",
            // precipitationDescriptor="", gusts=False, rain=False, fog=False, snow=False, thunder=False, hail=False, seaice=False,
            // minYear="", maxYear="", minMonth="", maxMonth="", minDay="", maxDay="", latN="", latS = "", lngE="", lngW="", weatherCodeNotNull=False,
            // fieldlist="", callback="", windSpeedMax = "", windDirMax = "",windSpeedMin="", windDirMin = "", join=False, *args, **kwargs):


$sql = "SET statement_timeout TO 100000000;";
$result = pg_query($sql);         
//start building the sql
$sql = "SELECT DISTINCT companies.companyName, nations.nationality, companies.companyID FROM companies INNER JOIN nations on nations.
nationid=companies.nationid ";
$sql .= " INNER JOIN voyages on voyages.companyid=companies.companyid WHERE ";

if(isset($_GET{'nationality'})){
	if($_GET['nationality'] != ""){
		$sql .= " nationality = '" . $_GET['nationality'] . "' AND ";
	}
}
$sql .= " companyname <> '' AND ";

// //temporal search --> better than specifying year and month and day
if(isset($_GET['maxDate'])){
	if($_GET['maxDate'] != ""){
		$sql .= " startDate <= CAST('" . $_GET['maxDate'] . "' AS DATE) AND ";
	}
}
if(isset($_GET['minDate'])){
	if($_GET['minDate'] != ""){
		$sql .= " startDate >= CAST('" . $_GET['minDate'] . "' AS DATE) AND ";
	}
}


$colnames = array("Company", "Nationality", "companyID");

// 
// //the SQL is fully build with all the parameters now
// //clear the ending
$whereCheck = substr($sql, -7);
if ($whereCheck == " WHERE "){
	$sql = substr($sql, 0, -7);
}
$andCheck = substr($sql, -5);
if($andCheck == " AND "){
	$sql = substr($sql, 0, -5);
}

$sql .= ";";


// //do the sql
$result = pg_query($sql);
// 

//make the result
if($result){
	$success = true;
}else{
	$success = false;
}


$out = array(
	'timestamp'=> date('l jS \of F Y h:i:s A'),
	'success'=>$success
);

$out['query'] = $sql;
$allData = array(); 

// 
while ($data = pg_fetch_row($result)) {
	$itemData = array();
	$i = 0;
	while ($i < count($colnames)){
		$thisCol = $colnames[$i];
		$thisVal = $data[$i];
		$itemData[$thisCol] = $thisVal;
		try {
			$itemData[$thisCol] = $thisVal;
		} catch (Exception $e) {
		    echo $e;
		}
		 $i = $i + 1;
	}
	array_push($allData, $itemData);
}
$out['data'] = $allData;
$out = json_encode($out);
if(isset($_GET['callback'])){
	if($_GET['callback'] != ""){
		$out = $_GET['callback'] . "(" . $out . ")";
	}
}
echo $out;
?>