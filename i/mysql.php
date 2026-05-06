<?php
//require_once("mysql-wrapper.php");
//ini_set('display_errors', '1');
//error_reporting(E_ALL);


require_once("/var/www/vhosts/westliches-siegel.de/mysqli_consystem.php");

function disconnectmysql(){
 if($GLOBALS["link"]) {
   mysqli_close($GLOBALS["link"]);
 }
}

function just_my_result($result, $row, $field = 0)
	{
		$result->data_seek($row);
		$row = $result->fetch_array();
		if (!isset($row[$field])) {
			return false;
		}

		return $row[$field];
	}
?>