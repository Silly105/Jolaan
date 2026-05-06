<?php
function safe_min($value){
  strtr($value, [
    "\x00" => '\x00',
    "\n" => '\n',
    "\r" => '\r',
    '\\' => '\\\\',
    "'" => "\'",
    '"' => '\"',
    "\x1a" => '\x1a'
  ]);

  return $value;
}

ini_set ("expose_php", "off");        // versuche die php herkunft zu verschweigen
ini_set ("url_rewriter.tags", "a=href"); // verhindert das automatische einfuegen eines "<input SESSID" nach dem "<form"

header("Content-type: text/html");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

session_cache_expire ( 720 );

if(session_status() == PHP_SESSION_NONE)
{
  session_start();
}

$password = safe_min($_POST['p']);
$usermail = safe_min($_POST['m']);

// Mysql verbindung herstellen
require_once('i/mysql.php');
connect2mysql();

// Benutzername und oder Passwort sind "wahrscheinlich" falsch
$_SESSION['FEHLER']['LOGIN']="ZUGANGSDATEN";
if(!isset($usermail) OR trim($usermail)=='') $fehler=true;
if($fehler==true) 
{  
  die(header('Location: hpanmeldung.php'));
}

$_SESSION['mail'] = trim((string) $usermail);
$savesessionmail =  safe_min($_SESSION['mail']);
// SICHER! die ID zur emailAdresse besorgen
$sql = "SELECT id FROM conmanagement_user WHERE mail=\"".$savesessionmail."\" LIMIT 1;";

$sql=mysqli_query($link,$sql);
if(mysqli_affected_rows($link)!=1) $fehler=true;

// das Passwort ist vermutlich falsch
if($fehler==false) $_SESSION['FEHLER']['LOGIN']="PASSWORD";

if(!isset($password) OR trim($password)=='') $fehler=true;
if($fehler==true)
{  
  die(header('Location: hpanmeldung.php'));
}

unset($p);
$p = trim((string) $password);

// restdaten zum benutzer holen... password pruefen
// id - typ - vname - nname - sex - strasse - plz - ort - mail - geb_dat
//$sql = sprintf("SELECT id, typ, vname, nname, bemerkung FROM conmanagement_user WHERE mail=%s AND password LIKE PASSWORD(%s) LIMIT 1;",

$sql = "SELECT id, typ, vname, nname, bemerkung, PASSWORD(\"$password\") as pw FROM conmanagement_user WHERE mail=\"".$savesessionmail."\" AND password LIKE PASSWORD(\"$password\") LIMIT 1;";
$sql = mysqli_query($link,$sql);
if(mysqli_num_rows($sql)!=1) // wenn abfrage fehlgeschlagen
{
  die(header('Location: hpanmeldung.php'));
}

$abfrage = mysqli_fetch_row($sql);

$_SESSION['nutzeremailadresse'] = $_SESSION['mail'];
$_SESSION['pw'] = $abfrage[5];
$_SESSION['user_id'] = $abfrage[0];
$_SESSION['level'] = explode(",",(string) $abfrage[1]);
$_SESSION['vname'] = $abfrage[2];
$_SESSION['nname'] = $abfrage[3];
$_SESSION['bemerkung'] = $abfrage[4];
mysqli_free_result($sql);
$_SESSION['logedin']=true;

// die Zugangsdaten sind bestimmt richtig
$_SESSION['FEHLER']['LOGIN']="";

die(header('Location: logedin.php'));
?>