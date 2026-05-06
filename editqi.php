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
require_once('validate.php');

$qiid = $_REQUEST['qiid'];
$senden = $_REQUEST['senden'];
$qiconname = $_REQUEST['qiconname'];
$qiconuntertitel = $_REQUEST['qiconuntertitel'];
$qiconlocation = $_REQUEST['qiconlocation'];
$qiconlocationvorsatz = $_REQUEST['qiconlocationvorsatz'];
$qiconlocationnachsatz = $_REQUEST['qiconlocationnachsatz'];

$qibeginnmonat = $_REQUEST['qibeginnmonat'];
if($qibeginnmonat < '10')
{
  $qibeginnmonat = "0".$qibeginnmonat;
}
$qibeginntag = $_REQUEST['qibeginntag'];
if($qibeginntag < '10')
{
  $qibeginntag = "0".$qibeginntag;
}
$qidatumbeginn = $_REQUEST['qibeginnjahr']."-".$qibeginnmonat."-".$qibeginntag;

$qiendemonat = $_REQUEST['qiendemonat'];
if($qiendemonat < '10')
{
  $qiendemonat = "0".$qiendemonat;
}
$qiendetag = $_REQUEST['qiendetag'];
if($qiendetag < '10')
{
  $qiendetag = "0".$qiendetag;
}
$qidatumende = $_REQUEST['qiendejahr']."-".$qiendemonat."-".$qiendetag;

$qifreisc = $_REQUEST['qifreisc'];
$qifreinsc = $_REQUEST['qifreinsc'];
$qiflyeradresse = $_REQUEST['qiflyeradresse'];
$qianmeldungsadresse = $_REQUEST['qianmeldungsadresse'];
$qiforumsadresse = $_REQUEST['qiforumsadresse'];
$qicountdown = $_REQUEST['qicountdown'];
$qistarttermin = $_REQUEST['qistarttermin'];

if($qiid != '')
{

  connect2mysql();

  $mysql="SELECT `id`, `conname`, `conuntertitel`, `conlocation`, `conlocationvorsatz`, `conlocationnachsatz`, `datumbeginn`, DAYOFMONTH( datumbeginn ) AS beginntag , MONTH( datumbeginn ) AS beginnmonat , YEAR( datumbeginn ) AS beginnjahr, DAYOFMONTH( datumende ) AS endetag , MONTH( datumende ) AS endemonat , YEAR( datumende ) AS endejahr, `datumende`, `freisc`, `freinsc`, `flyeradresse`, `anmeldungsadresse`, `forumsadresse`, `countdown`, `starttermin` FROM `conmanagement_aktuelletermine` WHERE id=$qiid;";
  $mysql = mysqli_query($link,$mysql);

  $num4 =  mysqli_affected_rows($link);

  if($num4 > 0)
  {
    $oldqiid = just_my_result($mysql, 0, "id");
    $oldqiconname = just_my_result($mysql, 0, "conname");
    $oldqiconuntertitel = just_my_result($mysql, 0, "conuntertitel");
    $oldqiconlocation = just_my_result($mysql, 0, "conlocation");
    $oldqiconlocationvorsatz = just_my_result($mysql, 0, "conlocationvorsatz");
    $oldqiconlocationnachsatz = just_my_result($mysql, 0, "conlocationnachsatz");
    $oldqibeginntag = just_my_result($mysql, 0, "beginntag");
    $oldqibeginnmonat = just_my_result($mysql, 0, "beginnmonat");
    $oldqibeginnjahr = just_my_result($mysql, 0, "beginnjahr");
    $oldqiendetag = just_my_result($mysql, 0, "endetag");
    $oldqiendemonat = just_my_result($mysql, 0, "endemonat");
    $oldqiendejahr = just_my_result($mysql, 0, "endejahr");
    $oldqifreisc = just_my_result($mysql, 0, "freisc");
    $oldqifreinsc = just_my_result($mysql, 0, "freinsc");
    $oldqiflyeradresse = just_my_result($mysql, 0, "flyeradresse");
    $oldqianmeldungsadresse = just_my_result($mysql, 0, "anmeldungsadresse");
    $oldqiforumsadresse = just_my_result($mysql, 0, "forumsadresse");
    $oldqicountdown = just_my_result($mysql, 0, "countdown");
    $oldqistarttermin = just_my_result($mysql, 0, "starttermin");
  }
}

if(validate("Administrator"))
{

if($senden == 'Abschicken')
{
  $qiconname = str_replace("ä", "&auml;", $qiconname);
  $qiconname = str_replace("ö", "&ouml;", $qiconname);
  $qiconname = str_replace("ü", "&uuml;", $qiconname);
  $qiconname = str_replace("Ä", "&Auml;", $qiconname);
  $qiconname = str_replace("Ö", "&Ouml;", $qiconname);
  $qiconname = str_replace("Ü", "&Uuml;", $qiconname);
  $qiconname = str_replace("ß", "&szlig;", $qiconname);
  $bkslashquot = '\"';
  $qiconname = str_replace($bkslashquot, "&quot;", $qiconname);
  $quotzeichen = '"';
  $qiconname = str_replace($quotzeichen, "&quot;", $qiconname);
  $qiconname = str_replace("€", "&euro;", $qiconname);

  $qiconuntertitel = str_replace("ä", "&auml;", $qiconuntertitel);
  $qiconuntertitel = str_replace("ö", "&ouml;", $qiconuntertitel);
  $qiconuntertitel = str_replace("ü", "&uuml;", $qiconuntertitel);
  $qiconuntertitel = str_replace("Ä", "&Auml;", $qiconuntertitel);
  $qiconuntertitel = str_replace("Ö", "&Ouml;", $qiconuntertitel);
  $qiconuntertitel = str_replace("Ü", "&Uuml;", $qiconuntertitel);
  $qiconuntertitel = str_replace("ß", "&szlig;", $qiconuntertitel);
  $bkslashquot = '\"';
  $qiconuntertitel = str_replace($bkslashquot, "&quot;", $qiconuntertitel);
  $quotzeichen = '"';
  $qiconuntertitel = str_replace($quotzeichen, "&quot;", $qiconuntertitel);
  $qiconuntertitel = str_replace("€", "&euro;", $qiconuntertitel);

  $qiconlocation = str_replace("ä", "&auml;", $qiconlocation);
  $qiconlocation = str_replace("ö", "&ouml;", $qiconlocation);
  $qiconlocation = str_replace("ü", "&uuml;", $qiconlocation);
  $qiconlocation = str_replace("Ä", "&Auml;", $qiconlocation);
  $qiconlocation = str_replace("Ö", "&Ouml;", $qiconlocation);
  $qiconlocation = str_replace("Ü", "&Uuml;", $qiconlocation);
  $qiconlocation = str_replace("ß", "&szlig;", $qiconlocation);
  $bkslashquot = '\"';
  $qiconlocation = str_replace($bkslashquot, "&quot;", $qiconlocation);
  $quotzeichen = '"';
  $qiconlocation = str_replace($quotzeichen, "&quot;", $qiconlocation);
  $qiconlocation = str_replace("€", "&euro;", $qiconlocation);

  $qiconlocationvorsatz = str_replace("ä", "&auml;", $qiconlocationvorsatz);
  $qiconlocationvorsatz = str_replace("ö", "&ouml;", $qiconlocationvorsatz);
  $qiconlocationvorsatz = str_replace("ü", "&uuml;", $qiconlocationvorsatz);
  $qiconlocationvorsatz = str_replace("Ä", "&Auml;", $qiconlocationvorsatz);
  $qiconlocationvorsatz = str_replace("Ö", "&Ouml;", $qiconlocationvorsatz);
  $qiconlocationvorsatz = str_replace("Ü", "&Uuml;", $qiconlocationvorsatz);
  $qiconlocationvorsatz = str_replace("ß", "&szlig;", $qiconlocationvorsatz);
  $bkslashquot = '\"';
  $qiconlocationvorsatz = str_replace($bkslashquot, "&quot;", $qiconlocationvorsatz);
  $quotzeichen = '"';
  $qiconlocationvorsatz = str_replace($quotzeichen, "&quot;", $qiconlocationvorsatz);
  $qiconlocationvorsatz = str_replace("€", "&euro;", $qiconlocationvorsatz);

  $qiconlocationnachsatz = str_replace("ä", "&auml;", $qiconlocationnachsatz);
  $qiconlocationnachsatz = str_replace("ö", "&ouml;", $qiconlocationnachsatz);
  $qiconlocationnachsatz = str_replace("ü", "&uuml;", $qiconlocationnachsatz);
  $qiconlocationnachsatz = str_replace("Ä", "&Auml;", $qiconlocationnachsatz);
  $qiconlocationnachsatz = str_replace("Ö", "&Ouml;", $qiconlocationnachsatz);
  $qiconlocationnachsatz = str_replace("Ü", "&Uuml;", $qiconlocationnachsatz);
  $qiconlocationnachsatz = str_replace("ß", "&szlig;", $qiconlocationnachsatz);
  $bkslashquot = '\"';
  $qiconlocationnachsatz = str_replace($bkslashquot, "&quot;", $qiconlocationnachsatz);
  $quotzeichen = '"';
  $qiconlocationnachsatz = str_replace($quotzeichen, "&quot;", $qiconlocationnachsatz);
  $qiconlocationnachsatz = str_replace("€", "&euro;", $qiconlocationnachsatz);

  $aktuellesdatum = date("Y-m-d");

  $qi_aendern = "CONNAME='$qiconname',";
  $qi_aendern .= "CONUNTERTITEL='$qiconuntertitel',";
  $qi_aendern .= "CONLOCATION='$qiconlocation',";
  $qi_aendern .= "CONLOCATIONVORSATZ='$qiconlocationvorsatz',";
  $qi_aendern .= "CONLOCATIONNACHSATZ='$qiconlocationnachsatz',";
  $qi_aendern .= "DATUMBEGINN='$qidatumbeginn',";
  $qi_aendern .= "DATUMENDE='$qidatumende',";
  $qi_aendern .= "FREISC='$qifreisc',";
  $qi_aendern .= "FREINSC='$qifreinsc',";
  $qi_aendern .= "FLYERADRESSE='$qiflyeradresse',";
  $qi_aendern .= "ANMELDUNGSADRESSE='$qianmeldungsadresse',";
  $qi_aendern .= "FORUMSADRESSE='$qiforumsadresse',";
  $qi_aendern .= "STAND='$aktuellesdatum',";
  $qi_aendern .= "COUNTDOWN='$qicountdown',";
  $qi_aendern .= "STARTTERMIN='$qistarttermin'";

  $mysql = "UPDATE conmanagement_aktuelletermine SET $qi_aendern WHERE id='$qiid';";
  $mysql = mysqli_query($link,$mysql);

  $num2 = mysqli_affected_rows($link);

  if ($num2>0)
  {
    print_site(
    'Schnellinfo erfolgreich ge&auml;ndert',
    'Die Schnellinfo wurde in der Datenbank ge&auml;ndert. Du kannst sie von jetzt an auf der <a href="./index.php">Startseite</a> lesen.'
    );
  }
  if ($num2<=0)
  {
    print_site(
    'Schnellinfo&auml;nderung FEHLGESCHLAGEN!',
    'Es gab einen <b>Fehler</b> beim &Auml;ndern der Schnellinfo. Wenn das Problem &ouml;fter auftritt, sag Jonas bescheid.'
    );
  }
}
elseif($senden == 'Loeschen (Vorsicht)')
{
  $mysql = "DELETE FROM conmanagement_aktuelletermine WHERE id='$qiid' LIMIT 1;";
  $mysql = mysqli_query($link,$mysql);

  $num2 = mysqli_affected_rows($link);

  if ($num2>0)
  {
    print_site(
    'Schnellinfoeintrag erfolgreich gel&ouml;scht',
    'Zur&uuml;ck zur <a href="./index.php">Startseite</a>'
    );
  }
  if ($num2<=0)
  {
    print_site(
    'Schnellinfoeintrag konnte NICHT gel&ouml;scht werden',
    'Es gab einen Fehler beim l&ouml;schen. Wenn das Problem l&auml;nger besteht, wende Dich bitte an Jonas.'
    );
  }
}
else
{
  $checked = '';
  if($oldqicountdown == '1'){
    $checked = 'checked';
  }

  print_site(
  'Schnellinfoeintrag &auml;ndern',
  "<p class='block1'>
    Die Termin-Schnellinfos k&ouml;nnen hier ge&auml;ndert werden. HTML-Tags sind m&ouml;glich, aber sollten nicht eingesetzt werden, da die Infos von der Seite formatiert werden.<br>
    Der Stand der Anmeldungen wird automatisch auf das heutige Datum gesetzt, wenn der Eintrag ge&auml;ndert wird.
   </p>
   <form action='./editqi.php' method='post'>
   Conname:<br> <input type='Text' name='qiconname' size='60' value='$oldqiconname'><br>
   Conuntertitel:<br> <input type='Text' name='qiconuntertitel' size='60' value='$oldqiconuntertitel'><br>
   Conlocationvorsatz:<br> <input type='Text' name='qiconlocationvorsatz' size='60' value='$oldqiconlocationvorsatz'><br>
   Conlocation:<br> <input type='Text' name='qiconlocation' size='60' value='$oldqiconlocation'><br>
   Conlocationnachsatz:<br> <input type='Text' name='qiconlocationnachsatz' size='60' value='$oldqiconlocationnachsatz'><br>
   Datumbeginn:<br> <input type='Text' name='qibeginntag' size='3' maxlength='2' value='$oldqibeginntag'>.<input type='Text' name='qibeginnmonat' size='3' maxlength='2' value='$oldqibeginnmonat'>.<input type='Text' name='qibeginnjahr' size='5' maxlength='4' value='$oldqibeginnjahr'><br>
   Datumende:<br> <input type='Text' name='qiendetag' size='3' maxlength='2' value='$oldqiendetag'>.<input type='Text' name='qiendemonat' size='3' maxlength='2' value='$oldqiendemonat'>.<input type='Text' name='qiendejahr' size='5' maxlength='4' value='$oldqiendejahr'><br>
   Freie SC Pl&auml;tze:<br> <input type='Text' name='qifreisc' size='60' value='$oldqifreisc'><br>
   Freie NSC Pl&auml;tze:<br> <input type='Text' name='qifreinsc' size='60' value='$oldqifreinsc'><br>
   Flyeradresse:<br> <input type='Text' name='qiflyeradresse' size='60' value='$oldqiflyeradresse'><br>
   Anmeldungsadresse:<br> <input type='Text' name='qianmeldungsadresse' size='60' value='$oldqianmeldungsadresse'><br>
   Forumsadresse:<br> <input type='Text' name='qiforumsadresse' size='60' value='$oldqiforumsadresse'><br>
   Countdown?<br><input type='Checkbox' name='qicountdown' value='1' $checked><br>
   CD-Starttermin:<br> <input type='Text' name='qistarttermin' size='60' value='$oldqistarttermin'><br>
   Syntax exakt befolgen. &quot;December 27, 2006 14:00:00 GMT&quot;<br>
   (GMT ist deutsche Winterzeit minus einer Stunde)<br>
   <br>
   <input type='hidden' name='qiid' value='$oldqiid'>
   <br>
   <input type='Submit' name='senden' value='Abschicken'>&nbsp;&nbsp;<input type='Submit' name='senden' value='Loeschen (Vorsicht)'>
   </form>"
  );


}

}
else
{
  print_site(
  'Zugriff verweigert',
  'Man muss als Administrator angemeldet sein, um Schnellinfos bearbeiten zu k&ouml;nnen.'
  );
}

disconnectmysql();

?>