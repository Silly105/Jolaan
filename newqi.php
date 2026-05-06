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

$senden = $_REQUEST['senden'];
$qiconname = $_REQUEST['qiconname'];
$qicountdown = $_REQUEST['qicountdown'];
$qistarttermin = $_REQUEST['qistarttermin'];
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

//if(is_array($_SESSION['level']) AND in_array("Administrator",$_SESSION['level']))
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

    $qi_eintrag = "conname, conuntertitel, conlocation, conlocationvorsatz, conlocationnachsatz, datumbeginn, datumende, freisc, freinsc, flyeradresse, anmeldungsadresse, forumsadresse, stand, countdown, starttermin";
    $qi_eintrag2 = "'$qiconname', '$qiconuntertitel', '$qiconlocation', '$qiconlocationvorsatz', '$qiconlocationnachsatz', '$qidatumbeginn', '$qidatumende', '$qifreisc', '$qifreinsc', '$qiflyeradresse', '$qianmeldungsadresse', '$qiforumsadresse', '$aktuellesdatum', '$countdown', '$qistarttermin'";

    connect2mysql();

    $mysql="INSERT conmanagement_aktuelletermine ($qi_eintrag) values($qi_eintrag2)";
    $mysql = mysqli_query($link,$mysql);

    $num2 = mysqli_affected_rows($link);

    disconnectmysql();

    if ($num2>0)
    {
      print_site(
      'Schnellinfoeintrag erstellt',
      'Der Schnellinfo-Eintrag wurde in die Datenbank eingetragen. Zur&uuml;ck zur <a href="./index.php">Startseite</a>'
      );
    }
    if ($num2<=0)
    {
      print_site(
      'Schnellinfoeintrag FEHLGESCHLAGEN!',
      'Die Daten konnten NICHT eingetragen werden. Wenn das Problem l&auml;nger besteht, sag Jonas Bescheid.'
      );
    }
  }
  else
  {
    print_site(
    'Veranstaltungs-Schnellinformation',
    "<p class='block1'>
      Hier k&ouml;nnen neue Schnellinfos &uuml;ber Veranstaltungen <b>f&uuml;r die Startseite</b> von con.westliches-siegel.de eingegeben werden. HTML-Tags sind m&ouml;glich, aber sollten nicht eingesetzt werden, da die Infos von der Seite formatiert werden.<br>
      Deutsche Umlaute werden in HTML Code umgewandelt.<br>
      Der Stand der Anmeldungen wird automatisch auf das heutige Datum gesetzt, wenn der Eintrag erstellt wird.
     </p>
     <form action='./newqi.php' method='post'>
     Conname: (z.B.: 'Stille Wasser I')<br> <input type='Text' name='qiconname' size='60' value=''><br>
     Conuntertitel: (z.B.: 'Eine dunkle Hoffnung')<br> <input type='Text' name='qiconuntertitel' size='60' value=''><br>
     Conlocationvorsatz: (z.B.: 'in der')<br> <input type='Text' name='qiconlocationvorsatz' size='60' value=''><br>
     Conlocation: (z.B.: 'Jugendherberge Inzm&uuml;hlen')<br> <input type='Text' name='qiconlocation' size='60' value=''><br>
     Conlocationnachsatz: (z.B.: 'bei Hamburg')<br> <input type='Text' name='qiconlocationnachsatz' size='60' value=''><br>
     Datumbeginn:<br> <input type='Text' name='qibeginntag' size='3' maxlength='2' value=''>.<input type='Text' name='qibeginnmonat' size='3' maxlength='2' value=''>.<input type='Text' name='qibeginnjahr' size='5' maxlength='4' value=''><br>
     Datumende:<br> <input type='Text' name='qiendetag' size='3' maxlength='2' value=''>.<input type='Text' name='qiendemonat' size='3' maxlength='2' value=''>.<input type='Text' name='qiendejahr' size='5' maxlength='4' value=''><br>
     Freie SC Pl&auml;tze:<br> <input type='Text' name='qifreisc' size='60' value=''><br>
     Freie NSC Pl&auml;tze:<br> <input type='Text' name='qifreinsc' size='60' value=''><br>
     Flyeradresse: (Komplette URL eintragen)<br> <input type='Text' name='qiflyeradresse' size='60' value=''><br>
     Anmeldungsadresse: (Komplette URL eintragen)<br> <input type='Text' name='qianmeldungsadresse' size='60' value=''><br>
     Forumsadresse: (Komplette URL eintragen)<br> <input type='Text' name='qiforumsadresse' size='60' value=''><br>
     Countdown?<br><input type='Checkbox' name='qicountdown' value='1'><br>
     CD-Starttermin:<br> <input type='Text' name='qistarttermin' size='60' value=''><br>
     Syntax exakt befolgen. &quot;December 27, 2006 14:00:00 GMT&quot;<br>
     (GMT ist deutsche Winterzeit minus einer Stunde)<br>
     <br>
     <input type='Submit' name='senden' value='Abschicken'>
     </form>"
    );

  }

}
else
{
  print_site(
  'Zugriff verweigert',
  "Man muss als Administrator angemeldet sein, um Schnellinfos erstellen zu k&ouml;nnen."
  );
}

?>