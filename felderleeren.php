<?php

require_once('i/western_design.php');
require_once('i/mysql.php');
require_once('validate.php');

if(session_status() == PHP_SESSION_NONE){session_start();}
$sessionid = session_id();

$scriptadresse = "http://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];

$id = $_REQUEST['id'];
$action = $_REQUEST['action'];

$conname = $_REQUEST['conname'];
$connamekurz = $_REQUEST['connamekurz'];
$conuntertitel = $_REQUEST['conuntertitel'];
$condatum = $_REQUEST['condatum'];
$conlocationvorsatz = $_REQUEST['conlocationvorsatz'];
$conlocation = $_REQUEST['conlocation'];
$adminemail = $_REQUEST['adminemail'];
$flyeradresse = $_REQUEST['flyeradresse'];
$agbadresse = $_REQUEST['agbadresse'];
$sperre = $_REQUEST['sperre'];
$minscbetrag = $_REQUEST['minscbetrag'];
$minnscbetrag = $_REQUEST['minnscbetrag'];
$entsperrungspasswort = $_REQUEST['entsperrungspasswort'];
$deleteid = $_REQUEST['deleteid'];
$sicherheitsfrage = $_REQUEST['sicherheitsfrage'];

$sf1 = $_REQUEST['sf1'];
$sf2 = $_REQUEST['sf2'];
$sf3 = $_REQUEST['sf3'];
$sf4 = $_REQUEST['sf4'];
$sf5 = $_REQUEST['sf5'];

$bahnhof = $_REQUEST['bahnhof'];
$zugtermin = $_REQUEST['zugtermin'];

connect2mysql();

if(validate("Administrator"))
{       
	$anmeldungen_aendern = "SCHWANGER='',";    
    $anmeldungen_aendern .= "ALLERGIEN='',";
    $anmeldungen_aendern .= "KRANKHEITEN='',";
    $anmeldungen_aendern .= "PHOBIEN=''";	

	  $mysql = $mysqltxt = "UPDATE conmanagement_anmeldungen SET $anmeldungen_aendern";
      $mysql = mysqli_query($link,$mysql);
      $num = mysqli_affected_rows($link);		
	  
      if($num >= 1)
      {
		  print_site(
          'Veranstaltung erfolgreich gel&ouml;scht',
          'Es wurden &quot;'.$num.'&quot; Eintr&auml;ge in der Anmeldeliste bearbeitet.'
          );
	  }		
	  else
	  {
		  print_site(
          'Fehler beim Bearbeiten',
          'Es wurden &quot;'.$num.'&quot; Eintr&auml;ge in der Anmeldeliste bearbeitet.'
          );
	  }	
}
else
{
  print_site(
    'Zugriff verweigert',
    'Man muss als Administrator angemeldet sein, um auf diese Seite zugreifen zu k&ouml;nnen.'
  );
}

disconnectmysql();

?>