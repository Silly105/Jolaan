<?php
ini_set ("expose_php", "off");        // versuche die php herkunft zu verschweigen
ini_set ("url_rewriter.tags", "a=href"); // verhindert das automatische einfuegen eines "<input SESSID" nach dem "<form"

header("Content-type: text/html");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

if(session_status() == PHP_SESSION_NONE){session_start();}

require_once('i/mysql.php');
require_once('i/western_design.php');

$scriptadresse = "https://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
$serveradresse = "https://".$_SERVER['SERVER_NAME'];

if($_SESSION['user_id']!='' AND $_SESSION['logedin']=true)
{
  $content = "Du kannst nun die Basisfunktionen der Webseite nutzen.<br />";

  //Hier den QI Teil entnommen

  $content .= "<br />Achte auf das Men&uuml; links an der Seite, dort findest Du Dein Profil, wo Du Deine Daten sowie das Passwort &auml;ndern oder Dich abmelden kannst.<br>
  <br>Weitere M&ouml;glichkeiten der Converwaltung stehen erst nach der Freischaltung durch Jonas zur Verf&uuml;gung. Bitte sag ihm Bescheid.<br><br>
  <br><br><br><br>

<!-- <table border='1' align='center' width='50%' cellspacing='0' cellpadding='3'><tr><td>&Uuml;ber den Men&uuml;punkt &quot;Charakter verwalten&quot; kannst Du Charaktere anlegen und Dich online einchecken.</td></tr></table> -->

  ";

  print_site(
  "Angemeldet",
  "$content"
  );
}
else
{
  print_site(
  'Zugriff verweigert',
  'Du musst eingeloggt sein, um auf diese Seite zuzugreifen.'
  );
}

?>