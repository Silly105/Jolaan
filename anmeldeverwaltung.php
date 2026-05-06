<?php

function safe($value){
  $value = str_replace("&", "&amp;", $value);

  $bkslashquot = '\"';
  $value = str_replace($bkslashquot, "&quot;", $value);
  $quotzeichen = '"';
  $value = str_replace($quotzeichen, "&quot;", $value);
  $bkslashquot = "\'";
  $value = str_replace($bkslashquot, "&apos;", $value);
  $quotzeichen = "'";
  $value = str_replace($quotzeichen, "&apos;", $value);

  $value = str_replace("€", "&euro;", $value);

  $value = str_replace("<", "&lt;", $value);
  $value = str_replace(">", "&gt;", $value);

  $value = str_replace("ä", "&auml;", $value);
  $value = str_replace("ö", "&ouml;", $value);
  $value = str_replace("ü", "&uuml;", $value);
  $value = str_replace("Ä", "&Auml;", $value);
  $value = str_replace("Ö", "&Ouml;", $value);
  $value = str_replace("Ü", "&Uuml;", $value);
  $value = str_replace("ß", "&szlig;", $value);

  $value = str_replace('À', '&Agrave;', $value);
  $value = str_replace('à', '&agrave;', $value);
  $value = str_replace('Á', '&Aacute;', $value);
  $value = str_replace('á', '&aacute;', $value);
  $value = str_replace('Â', '&Acirc;', $value);
  $value = str_replace('â', '&acirc;', $value);

  $value = str_replace('Æ', '&AElig;', $value);
  $value = str_replace('æ', '&aelig;', $value);

  $value = str_replace('È', '&Egrave;', $value);
  $value = str_replace('è', '&egrave;', $value);
  $value = str_replace('É', '&Eacute;', $value);
  $value = str_replace('é', '&eacute;', $value);
  $value = str_replace('Ê', '&Ecirc;', $value);
  $value = str_replace('ê', '&ecirc;', $value);

  $value = str_replace('Ì', '&Igrave;', $value);
  $value = str_replace('ì', '&igrave;', $value);
  $value = str_replace('Í', '&Iacute;', $value);
  $value = str_replace('í', '&iacute;', $value);
  $value = str_replace('Î', '&Icirc;', $value);
  $value = str_replace('î', '&icirc;', $value);

  $value = str_replace('Ñ', '&Ntilde;', $value);
  $value = str_replace('ñ', '&ntilde;', $value);
  $value = str_replace('Ò', '&Ograve;', $value);
  $value = str_replace('ò', '&ograve;', $value);
  $value = str_replace('Ó', '&Oacute;', $value);
  $value = str_replace('ó', '&oacute;', $value);
  $value = str_replace('Ô', '&Ocirc;', $value);
  $value = str_replace('ô', '&ocirc;', $value);

  $value = str_replace('Ø', '&Oslash;', $value);
  $value = str_replace('ø', '&oslash;', $value);

  $value = str_replace('Ù', '&Ugrave;', $value);
  $value = str_replace('ù', '&ugrave;', $value);
  $value = str_replace('Ú', '&Uacute;', $value);
  $value = str_replace('ú', '&uacute;', $value);
  $value = str_replace('Û', '&Ucirc;', $value);
  $value = str_replace('û', '&ucirc;', $value);

  //--------------

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

function print_druckansicht($title,$content){

  ini_set ("expose_php", "off");   // versuche die php herkunft zu verschweigen

  header("Content-type: text/html; ; charset=UTF-8");
  header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
  header("Cache-Control: no-cache, must-revalidate");
  header("Pragma: no-cache");

  if(session_status() == PHP_SESSION_NONE){session_start();}

  echo "<html><head><link href='./css/druck.css' rel='stylesheet' type='text/css'><title>Anmeldeverwaltung - Druckansicht</title></head><body>";
  echo "<h3>".$title."</h3>".$content;
  echo "</body></html>";
}

require_once('i/western_design.php');
require_once('i/mysql.php');
require_once('validate.php');
require_once('./mailsenden.php');

if(session_status() == PHP_SESSION_NONE){session_start();}
$sessionid = session_id();

$scriptadresse = "https://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];

$action = $_REQUEST['action'];
$view = $_REQUEST['view'];
$id = $_REQUEST['id'];

connect2mysql();

if(validate("Administrator"))
{
  if($action == "showlist")
  {
    $sort = $_REQUEST['sort'];

    $mysql = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql = mysqli_query($link,$mysql);
    $conname = just_my_result($mysql, 0, "conname");

    //############################################

    //Abfrage SC

    if($sort == "nd")
    {
      $mysqlsortquery = "ORDER BY `nachname` DESC";
      $namesortlink = "";
      $siegelsortlink = "&sort=sa";
      $namesortlogo = "<img src='./western_images/rauf.gif'>";
    }
    elseif($sort == "sa")
    {
      $mysqlsortquery = "ORDER BY `siegel` ASC";
      $siegelsortlink = "&sort=sd";
      $namesortlink = "&sort=nd";
      $siegelsortlogo = "<img src='./western_images/runter.gif'>";
    }
    elseif($sort == "sd")
    {
      $mysqlsortquery = "ORDER BY `siegel` DESC";
      $siegelsortlink = "&sort=sa";
      $namesortlink = "&sort=nd";
      $siegelsortlogo = "<img src='./western_images/rauf.gif'>";
    }
    else
    {
      $mysqlsortquery = "ORDER BY `nachname` ASC";
      $namesortlink = "&sort=nd";
      $siegelsortlink = "&sort=sa";
      $namesortlogo = "<img src='./western_images/runter.gif'>";
    }

    $mysql = "SELECT id, vorname, nachname, anmeldung_bestaetigt, geldeingang_bestaetigt, scnsc, eingangsbetrag, siegel FROM conmanagement_anmeldungen WHERE conid='$id' AND scnsc='SC' AND freigeschaltet!='nein' $mysqlsortquery";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    if($num <= 0) { $teilnehmerliste = "<tr valign='middle'><td colspan='5'><div align='center'> - Bisher keine Teilnehmer in dieser Kategorie. - </div></td></tr>"; }

    for($i=0; $i < $num; $i++)
    {
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $anmeldung = just_my_result($mysql, $i, "anmeldung_bestaetigt");
      $geldeingang = just_my_result($mysql, $i, "geldeingang_bestaetigt");
      $scnsc = just_my_result($mysql, $i, "scnsc");
      $siegel = just_my_result($mysql, $i, "siegel");

      $eingangsbetrag = just_my_result($mysql, $i, "eingangsbetrag");
      $eingangsbetrag = str_replace(".", ",", $eingangsbetrag);
      $eingangsbetrag = str_replace(",00", "", $eingangsbetrag);

      if($anmeldung == "1")
      {
        $anmeldung = "<img src='./western_images/checked.gif'>";
      }
      else
      {
        $anmeldung = "<img src='./western_images/unchecked.gif'>";
      }

      if($geldeingang == "1")
      {
        $geldeingang = "<img src='./western_images/checked.gif'>";
      }
      else
      {
        $geldeingang = "<img src='./western_images/unchecked.gif'>";
      }

      $teilnehmerliste .= "<tr valign='middle'><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname, $vorname&nbsp;</a></td><td>$siegel&nbsp;</td><td>$anmeldung</td><td valign='middle'>$geldeingang</td><td width='10'><a href='$scriptadresse?action=eingangsbetrag&id=$personid'>$eingangsbetrag&nbsp;&euro;</a></td></tr>";
    }

    $scanzahl = $num;
    $teilnehmeranzahl = $num;

    //############################################

    //Abfrage NSC
    $mysql = "SELECT id, vorname, nachname, anmeldung_bestaetigt, geldeingang_bestaetigt, scnsc, eingangsbetrag FROM conmanagement_anmeldungen WHERE conid='$id' AND scnsc='NSC'  AND freigeschaltet!='nein' ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    if($num <= 0) { $teilnehmerliste2 = "<tr valign='middle'><td colspan='4'><div align='center'> - Bisher keine Teilnehmer in dieser Kategorie. - </div></td></tr>"; }

    for($i=0; $i < $num; $i++)
    {
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $anmeldung = just_my_result($mysql, $i, "anmeldung_bestaetigt");
      $geldeingang = just_my_result($mysql, $i, "geldeingang_bestaetigt");
      $scnsc = just_my_result($mysql, $i, "scnsc");
      $eingangsbetrag = just_my_result($mysql, $i, "eingangsbetrag");
      $eingangsbetrag = str_replace(".", ",", $eingangsbetrag);
      $eingangsbetrag = str_replace(",00", "", $eingangsbetrag);

      if($anmeldung == "1")
      {
        $anmeldung = "<img src='./western_images/checked.gif'>";
      }
      else
      {
        $anmeldung = "<img src='./western_images/unchecked.gif'>";
      }

      if($geldeingang == "1")
      {
        $geldeingang = "<img src='./western_images/checked.gif'>";
      }
      else
      {
        $geldeingang = "<img src='./western_images/unchecked.gif'>";
      }

      $teilnehmerliste2 .= "<tr valign='middle'><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname, $vorname&nbsp;</a></td><td>$anmeldung</td><td valign='middle'>$geldeingang</td><td width='10'><a href='$scriptadresse?action=eingangsbetrag&id=$personid'>$eingangsbetrag&nbsp;&euro;</a></td></tr>";
    }

    $nscanzahl = $num;
    $teilnehmeranzahl += $num;

    //#####################################################

    //Abfrage Tagesgast
    $mysql = "SELECT id, vorname, nachname, anmeldung_bestaetigt, geldeingang_bestaetigt, scnsc, eingangsbetrag FROM conmanagement_anmeldungen WHERE conid='$id' AND scnsc='Tagesgast' AND freigeschaltet!='nein' ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    if($num <= 0) { $teilnehmerliste3 = "<tr valign='middle'><td colspan='4'><div align='center'> - Bisher keine Teilnehmer in dieser Kategorie. - </div></td></tr>"; }

    for($i=0; $i < $num; $i++)
    {
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $anmeldung = just_my_result($mysql, $i, "anmeldung_bestaetigt");
      $geldeingang = just_my_result($mysql, $i, "geldeingang_bestaetigt");
      $scnsc = just_my_result($mysql, $i, "scnsc");
      $eingangsbetrag = just_my_result($mysql, $i, "eingangsbetrag");
      $eingangsbetrag = str_replace(".", ",", $eingangsbetrag);
      $eingangsbetrag = str_replace(",00", "", $eingangsbetrag);

      if($anmeldung == "1")
      {
        $anmeldung = "<img src='./western_images/checked.gif'>";
      }
      else
      {
        $anmeldung = "<img src='./western_images/unchecked.gif'>";
      }

      if($geldeingang == "1")
      {
        $geldeingang = "<img src='./western_images/checked.gif'>";
      }
      else
      {
        $geldeingang = "<img src='./western_images/unchecked.gif'>";
      }

      $teilnehmerliste3 .= "<tr valign='middle'><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname, $vorname&nbsp;</a></td><td>$anmeldung</td><td valign='middle'>$geldeingang</td><td width='10'><a href='$scriptadresse?action=eingangsbetrag&id=$personid'>$eingangsbetrag&nbsp;&euro;</a></td></tr>";
    }

    $tagesgastanzahl = $num;
    $teilnehmeranzahl += $num;

    //##########################################################

    //Abfrage Team
    $mysql = "SELECT id, vorname, nachname, anmeldung_bestaetigt, geldeingang_bestaetigt, scnsc, eingangsbetrag FROM conmanagement_anmeldungen WHERE conid='$id' AND scnsc='Team' AND freigeschaltet!='nein' ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    if($num <= 0) { $teilnehmerliste4 = "<tr valign='middle'><td colspan='4'><div align='center'> - Bisher keine Teilnehmer in dieser Kategorie. - </div></td></tr>"; }

    for($i=0; $i < $num; $i++)
    {
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $anmeldung = just_my_result($mysql, $i, "anmeldung_bestaetigt");
      $geldeingang = just_my_result($mysql, $i, "geldeingang_bestaetigt");
      $scnsc = just_my_result($mysql, $i, "scnsc");
      $eingangsbetrag = just_my_result($mysql, $i, "eingangsbetrag");
      $eingangsbetrag = str_replace(".", ",", $eingangsbetrag);
      $eingangsbetrag = str_replace(",00", "", $eingangsbetrag);

      if($anmeldung == "1")
      {
        $anmeldung = "<img src='./western_images/checked.gif'>";
      }
      else
      {
        $anmeldung = "<img src='./western_images/unchecked.gif'>";
      }

      if($geldeingang == "1")
      {
        $geldeingang = "<img src='./western_images/checked.gif'>";
      }
      else
      {
        $geldeingang = "<img src='./western_images/unchecked.gif'>";
      }

      $teilnehmerliste4 .= "<tr valign='middle'><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname, $vorname&nbsp;</a></td><td>$anmeldung</td><td valign='middle'>$geldeingang</td><td width='10'><a href='$scriptadresse?action=eingangsbetrag&id=$personid'>$eingangsbetrag&nbsp;&euro;</a></td></tr>";
    }

    $teamanzahl = $num;
    $teilnehmeranzahl += $num;

    //##########################################################

    //Abfrage Nicht-Freigeschaltet
    $mysql = "SELECT id, vorname, nachname, anmeldung_bestaetigt, geldeingang_bestaetigt, scnsc, eingangsbetrag FROM conmanagement_anmeldungen WHERE conid='$id' AND freigeschaltet='nein' ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    if($num <= 0) { $teilnehmerliste6 = "<tr valign='middle'><td colspan='4'><div align='center'> - Keine Teilnehmer in dieser Kategorie. - </div></td></tr>"; }

    for($i=0; $i < $num; $i++)
    {
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $anmeldung = just_my_result($mysql, $i, "anmeldung_bestaetigt");
      $geldeingang = just_my_result($mysql, $i, "geldeingang_bestaetigt");
      $scnsc = just_my_result($mysql, $i, "scnsc");
      $eingangsbetrag = just_my_result($mysql, $i, "eingangsbetrag");
      $eingangsbetrag = str_replace(".", ",", $eingangsbetrag);
      $eingangsbetrag = str_replace(",00", "", $eingangsbetrag);

      if($anmeldung == "1")
      {
        $anmeldung = "<img src='./western_images/checked.gif'>";
      }
      else
      {
        $anmeldung = "<img src='./western_images/unchecked.gif'>";
      }

      if($geldeingang == "1")
      {
        $geldeingang = "<img src='./western_images/checked.gif'>";
      }
      else
      {
        $geldeingang = "<img src='./western_images/unchecked.gif'>";
      }

      $teilnehmerliste6 .= "<tr valign='middle'><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname, $vorname&nbsp;</a></td><td>$anmeldung</td><td valign='middle'>$geldeingang</td><td width='10'><a href='$scriptadresse?action=eingangsbetrag&id=$personid'>$eingangsbetrag&nbsp;&euro;</a></td></tr>";
    }

    $nichtfreianzahl = $num;
    $teilnehmeranzahl += $num;

    //##########################################################

    //Abfrage Abgesagt
    $mysql = "SELECT id, vorname, nachname, anmeldung_bestaetigt, geldeingang_bestaetigt, scnsc, eingangsbetrag FROM conmanagement_anmeldungen WHERE conid='$id' AND scnsc='Abgesagt' AND freigeschaltet!='nein' ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    if($num <= 0) { $teilnehmerliste5 = "<tr valign='middle'><td colspan='4'><div align='center'> - Bisher keine Teilnehmer in dieser Kategorie. - </div></td></tr>"; }

    for($i=0; $i < $num; $i++)
    {
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $anmeldung = just_my_result($mysql, $i, "anmeldung_bestaetigt");
      $geldeingang = just_my_result($mysql, $i, "geldeingang_bestaetigt");
      $scnsc = just_my_result($mysql, $i, "scnsc");
      $eingangsbetrag = just_my_result($mysql, $i, "eingangsbetrag");
      $eingangsbetrag = str_replace(".", ",", $eingangsbetrag);
      $eingangsbetrag = str_replace(",00", "", $eingangsbetrag);

      if($anmeldung == "1")
      {
        $anmeldung = "<img src='./western_images/checked.gif'>";
      }
      else
      {
        $anmeldung = "<img src='./western_images/unchecked.gif'>";
      }

      if($geldeingang == "1")
      {
        $geldeingang = "<img src='./western_images/checked.gif'>";
      }
      else
      {
        $geldeingang = "<img src='./western_images/unchecked.gif'>";
      }

      $teilnehmerliste5 .= "<tr valign='middle'><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname, $vorname&nbsp;</a></td><td>$anmeldung</td><td valign='middle'>$geldeingang</td><td width='10'><a href='$scriptadresse?action=eingangsbetrag&id=$personid'>$eingangsbetrag&nbsp;&euro;</a></td></tr>";
    }

    $abgesagtanzahl = $num;
    $teilnehmeranzahl += $num;

    if($teilnehmeranzahl > 0)
    {
      $content = "Anmeldungs&uuml;bersicht:<table border='0'>
      <tr><td width='50'>$scanzahl</td><td>Spieler (<a href='$scriptadresse?action=showcomplete&id=$id&welche=sc'>Druckansicht)</a></    td></tr>
      <tr><td>$nscanzahl</td><td>NSC (<a href='$scriptadresse?action=showcomplete&id=$id&welche=nsc'>Druckansicht</a>)</td></tr>
      <tr><td>$tagesgastanzahl</td><td>Tagesg&auml;ste (<a href='$scriptadresse?action=showcomplete&id=$id&welche=tagesgast'>Druckansicht</a>)</td></tr>
      <tr><td>$teamanzahl</td><td>Teamanzahl (<a href='$scriptadresse?action=showcomplete&id=$id&welche=team'>Druckansicht</a>)</td></tr>
      <tr><td>$abgesagtanzahl</td><td>Absagen (<a href='$scriptadresse?action=showcomplete&id=$id&welche=abgesagt'>Druckansicht</a>)</td></tr>
      <tr><td>$nichtfreianzahl</td><td>Nicht freigeschaltete Teilnehmer (Email nicht best&auml;tigt, Jonas bescheidsagen!)</td></tr>
      </table><br>
      <a href='$scriptadresse?action=showlists&id=$id'>Automatische Listen und Berechnungen anzeigen</a><br>

      <!--  <a href=\"./orgaanmeldung.php?conid=$id\">Orga-Anmeldungen eintragen</a><br> -->


      <br>
      <b>SC</b><br>
      <table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td><b><a href='$scriptadresse?action=showlist&id=$id$namesortlink'>Name&nbsp;$namesortlogo</a></b></td><td width='5%'><b><a href='$scriptadresse?action=showlist&id=$id$siegelsortlink'>Siegel&nbsp;$siegelsortlogo</a></b></td><td width='5%'><b>Anmeld.&nbsp;best?&nbsp;</b></td><td width='5%'><b>Geldeingang?&nbsp;</b></td><td><b>Betrag</b></td></tr>
      $teilnehmerliste
      </table>
      <br>
      <b>NSC</b><br>
      <table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td><b>Name</b></td><td width='5%'><b>Anmeld.&nbsp;best?&nbsp;</b></td><td width='5%'><b>Geldeingang?&nbsp;</b></td><td><b>Betrag</b></td></tr>
      $teilnehmerliste2
      </table>
      <br>
      <b>Tagesg&auml;ste</b><br>
      <table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td><b>Name</b></td><td width='5%'><b>Anmeld.&nbsp;best?&nbsp;</b></td><td width='5%'><b>Geldeingang?&nbsp;</b></td><td><b>Betrag</b></td></tr>
      $teilnehmerliste3
      </table>
      <br>
      <b>Team</b><br>
      <table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td><b>Name</b></td><td width='5%'><b>Anmeld.&nbsp;best?&nbsp;</b></td><td width='5%'><b>Geldeingang?&nbsp;</b></td><td><b>Betrag</b></td></tr>
      $teilnehmerliste4
      </table>
      <br>
      <b>Abgesagt</b><br>
      <table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td><b>Name</b></td><td width='5%'><b>Anmeld.&nbsp;best?&nbsp;</b></td><td width='5%'><b>Geldeingang?&nbsp;</b></td><td><b>Betrag</b></td></tr>
      $teilnehmerliste5
      </table>
      <br>
      <b>Nicht Freigeschaltet</b><br>
      <table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td><b>Name</b></td><td width='5%'><b>Anmeld.&nbsp;best?&nbsp;</b></td><td width='5%'><b>Geldeingang?&nbsp;</b></td><td><b>Betrag</b></td></tr>
      $teilnehmerliste6
      </table>";
    }
    else
    {
      $content = "Es sind bisher keine Teilnehmer zum &quot;$conname&quot; angemeldet.";
    }

    print_site(
      "Teilnehmerliste $conname",
      "$content"
    );
  }

//###########################################################################################################################
//########################################## uebersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showlists")
  {
    $mysql = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql = mysqli_query($link,$mysql);
    $conname = just_my_result($mysql, 0, "conname");

    print_site(
      "Listen und Berechnungen",
      "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a>
      <br><br><a href='$scriptadresse?action=showmedics&id=$id'>Medizinisch ausgebildete Teilnehmer</a><br>
      <a href='$scriptadresse?action=showcar&id=$id'>Liste der Kennzeichen</a><br>
      <a href='$scriptadresse?action=showapk&id=$id'>Krankheiten, Allergien, Phobien</a><br>
      <a href='$scriptadresse?action=showvegi&id=$id'>Teilnehmer mit besonderen Essgewohnheiten</a><br>
      <a href='$scriptadresse?action=showaufbau&id=$id'>Aufbauhelfer</a><br>
      <a href='$scriptadresse?action=showabbau&id=$id'>Abbauhelfer</a><br>
      <a href='$scriptadresse?action=showminderj&id=$id'>Minderj&auml;hrige Teilnehmer</a><br>
      <a href='$scriptadresse?action=showgeldfehlt&id=$id'>Teilnehmer die unvollst&auml;ndig bezahlt haben</a><br>
      <a href='$scriptadresse?action=showzimmerwerte&id=$id'>Liste von Teilnehmern mit &quot;Zimmerdaten&quot;</a><br>
      <a href='$scriptadresse?action=showfde&id=$id'>Liste von Teilnehmern mit Freundschaft der Elemente</a><br>
      <a href='$scriptadresse?action=showstimmung&id=$id'>Meinungsbild zum Constil anzeigen</a><br>
      <a href='$scriptadresse?action=showgruppe&id=$id'>Spieler nach Gruppen geordnet</a><br>
	  <a href='$scriptadresse?action=showart&id=$id'>Spieler mit Angaben zu Besonderheiten, Artefakte, Tribes</a><br>
	  
      <a href='$scriptadresse?action=showdieb&id=$id'>Diebesliste</a><br>
      <a href='$scriptadresse?action=showgesleg&id=$id'>Spieler mit Geschichten & Legenden</a><br>
      <a href='$scriptadresse?action=showmag&id=$id'>Spieler mit magischen Fertigkeiten</a><br>
      <a href='$scriptadresse?action=showalch&id=$id'>Spieler mit alchemistischen Fertigkeiten</a><br>
      <a href='$scriptadresse?action=showspaehre&id=$id'>Spieler mit Sp&auml;hrenkunde</a><br>
      <a href='$scriptadresse?action=showritual&id=$id'>Spieler, die Rituale machen wollen</a><br>
      <a href='$scriptadresse?action=showconwerbung&id=$id'>Teilnehmer, die Conwerbung zulassen</a><br>
      <a href='$scriptadresse?action=showsonderfelder&id=$id'>Angaben in den Sonderfeldern</a> (<b>CORONA!</b>)<br>
      
	  
	  <br><a href='$scriptadresse?action=showemaillist&id=$id'>Link f&uuml;r eine Email an alle</a><br>

      <br><a href='$scriptadresse?action=showsum&id=$id'>Summe der Conbeitr&auml;ge</a><br>"
    );
  }

//###########################################################################################################################
//########################################## uebersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showvegi")
  {
    $mysql = "SELECT id, vorname, nachname, spielergruppe, scnsc, vegetarier FROM conmanagement_anmeldungen WHERE conid='$id' AND vegetarier!='' AND scnsc!='Abgesagt' ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");

    $vegiliste = "";

    for($i=0; $i < $num; $i++)
    {
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $spielergruppe = just_my_result($mysql, $i, "spielergruppe");
      $scnsc = just_my_result($mysql, $i, "scnsc");
      $vegetarier = just_my_result($mysql, $i, "vegetarier");

      $vegiliste .= "<tr valign='middle'><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname&nbsp;</a></td><td>$vorname&nbsp;</td><td>$scnsc</td><td>$vegetarier&nbsp;</td></tr>";
    }

    if($num > 0)
    {
      $content = "Es sind $num Teilnehmer mit besonderen Essgewohnheiten angemeldet.<br><br><table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td><b>Name</b></td><td><b>Vorname</b></td><td width='5%'><b>SC&nbsp;/&nbsp;NSC&nbsp;</b></td><td><b>Essgewohnheit</b></td></tr>
      $vegiliste
      </table>";
    }
    else
    {
      $content = "Es sind bisher keine Teilnehmer mit besonderen Essgewohnheiten angemeldet.";
    }

    if($view == "druckansicht")
    {
      print_druckansicht(
        "Liste der Teilnehmer mit besonderen Essgewohnheiten",
        "<a href='$scriptadresse?action=showvegi&id=$id' style='font-weight: bold; color: #0000FF;'>Normale Ansicht</a><br><br>
        $content"
      );
    }
    else
    {
      print_site(
        "Liste der Teilnehmer mit besonderen Essgewohnheiten",
        "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br><a href='$scriptadresse?action=showvegi&id=$id&view=druckansicht'>Druckansicht</a><br><br>
        $content"
      );
    }
  }

//###########################################################################################################################
//########################################## uebersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showzimmerwerte")
  {
    $mysql = "SELECT id, vorname, nachname, zimmer, spielergruppe, charname, scnsc FROM conmanagement_anmeldungen WHERE conid='$id' AND zimmer!='' AND scnsc!='Abgesagt' ORDER BY `scnsc`,`nachname`  ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");

    $endnscliste = "";

    for($i=0; $i < $num; $i++)
    {
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $zimmer = just_my_result($mysql, $i, "zimmer");
          $spielergruppe = just_my_result($mysql, $i, "spielergruppe");
      $charname = just_my_result($mysql, $i, "charname");
      $scnsc = just_my_result($mysql, $i, "scnsc");

      $endnscliste .= "<tr valign='middle'><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname&nbsp;</a></td><td>$vorname&nbsp;</td><td>$scnsc&nbsp;</td><td>$zimmer&nbsp;</td><td>$spielergruppe&nbsp;</td></tr>";
    }

    if($num > 0)
    {
      $content = "Es sind $num Teilnehmer in dieser Liste angemeldet.<br><br><table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td><b>Name</b></td><td><b>Vorname</b></td><td>SC/NSC</td><td width='5%'><b>Zimmer&nbsp;</b></td><td width='5%'><b>Gruppe&nbsp;</b></td></tr>
      $endnscliste
      </table>";
    }
    else
    {
      $content = "Es sind bisher keine Teilnehmer in dieser Liste angemeldet.";
    }

    if($view == "druckansicht")
    {
      print_druckansicht(
        "Liste der Zimmerdaten",
        "<a href='$scriptadresse?action=showzimmerwerte&id=$id' style='font-weight: bold; color: #0000FF;'>Normale Ansicht</a><br><br>
        $content"
      );
    }
    else
    {
      print_site(
        "Liste der Zimmerdaten",
        "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br><a href='$scriptadresse?action=showzimmerwerte&id=$id&view=druckansicht'>Druckansicht</a><br><br>
         $content"
      );
    }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showgeldfehlt")
  {
    $mysql = "SELECT id, vorname, nachname, telefon, handy, charname, email, eingangsbetrag FROM conmanagement_anmeldungen WHERE conid='$id' AND geldeingang_bestaetigt='0' AND scnsc!='Abgesagt' ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");

    $geldfehltliste = "";

    for($i=0; $i < $num; $i++)
    {
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $telefon = just_my_result($mysql, $i, "telefon");
      $email = just_my_result($mysql, $i, "email");
      $handy = just_my_result($mysql, $i, "handy");
      $charname = just_my_result($mysql, $i, "charname");
      $eingangsbetrag = just_my_result($mysql, $i, "eingangsbetrag");
      $eingangsbetrag = str_replace(".", ",", $eingangsbetrag);
      $eingangsbetrag = str_replace(",00", "", $eingangsbetrag);

      $geldfehltliste .= "<tr valign='middle'><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname, $vorname</a>&nbsp;</td><td>$charname&nbsp;</td><td>$telefon&nbsp;</td><td>$handy&nbsp;</td><td>$email</td><td>$eingangsbetrag&nbsp;</td></tr>";
    }

    if($num > 0)
    {
      $content = "Es sind $num Teilnehmer angemeldet die nicht vollst&auml;ndig bezahlt haben.<br><br><table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td width='5%'><b>Name</b></td><td><b>Charakter-<br>name</b></td><td width='5%'><b>Telefon&nbsp;</b></td><td width='5%'><b>Handy&nbsp;</b></td><td width='5%'><b>Email&nbsp;</b></td><td width='5%'><b>Eingangs-<br>betrag&nbsp;</b></td></tr>
      $geldfehltliste
      </table>";
    }
    else
    {
      $content = "Es wurden keine Anmeldungen gefunden, deren Geldeingang nicht best&auml;tigt wurde.";
    }

    if($view == "druckansicht")
    {
      print_druckansicht(
        "Liste der Teilnehmer die nicht vollst&auml;ndig bezahlt haben",
        "<a href='$scriptadresse?action=showgeldfehlt&id=$id' style='font-weight: bold; color: #0000FF;'>Normale Ansicht</a><br><br>
        $content"
      );
    }
    else
    {
      print_site(
        "Liste der Teilnehmer die nicht vollst&auml;ndig bezahlt haben",
        "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br><a href='$scriptadresse?action=showgeldfehlt&id=$id&view=druckansicht'>Druckansicht</a><br><br>
        Diese Liste ist zu gro&szlig; um normal angezeigt zu werden, bitte w&auml;hle die Druckansicht."
      );
    }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showapk")
  {
    $mysql = "SELECT id, vorname, nachname, allergien, krankheiten, phobien, scnsc, zimmer, spielergruppe FROM conmanagement_anmeldungen WHERE conid='$id' AND scnsc!='Abgesagt' AND (allergien!='' OR phobien!='' OR krankheiten!='') ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");

    $apkliste = "";

    for($i=0; $i < $num; $i++)
    {
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $allergien = just_my_result($mysql, $i, "allergien");
      $phobien = just_my_result($mysql, $i, "phobien");
      $krankheiten = just_my_result($mysql, $i, "krankheiten");
      $zimmer = just_my_result($mysql, $i, "zimmer");
          $spielergruppe = just_my_result($mysql, $i, "spielergruppe");
      $scnsc = just_my_result($mysql, $i, "scnsc");

      $apkliste .= "<tr valign='middle'><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname&nbsp;</a></td><td>$vorname&nbsp;</td><td>$scnsc&nbsp;</td><td>$krankheiten&nbsp;</td><td>$allergien&nbsp;<td>$phobien&nbsp;</td><td>$zimmer&nbsp;</td><td>$spielergruppe&nbsp;</td></tr>";
    }

    if($num > 0)
    {
      $content = "<table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td><b>Name</b></td><td><b>Vorname</b></td><td width='5%'><b>SC&nbsp;/&nbsp; NSC&nbsp;</b></td><td width='5%'><b>Krankheiten&nbsp;</b></td><td><b>Allergien:</b>&nbsp;</td><td><b>Phobien:&nbsp;</b></td><td width='5%'><b>Zimmer Nr.&nbsp;</b></td><td width='5%'><b>Gruppe&nbsp;</b></td></tr>
      $apkliste
      </table>";
    }
    else
    {
      $content = "Es sind bisher keine Teilnehmer mit Krankheiten, Allergien oder Phobien angemeldet.";
    }

    if($view == "druckansicht")
    {
      print_druckansicht(
        "Krankheiten, Allergien, Phobien",
        "<a href='$scriptadresse?action=showapk&id=$id' style='font-weight: bold; color: #0000FF;'>Normale Ansicht</a><br><br>
        $content"
      );
    }
    else
    {
      print_site(
        "Krankheiten, Allergien, Phobien",
        "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br><a href='$scriptadresse?action=showapk&id=$id&view=druckansicht'>Druckansicht</a><br><br>
        Hier vorerst nur Druckansicht, die normale passt nicht ins Layout."
        //"$content"
      );
    }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showmedics")
  {
    $mysql = "SELECT id, vorname, nachname, med_ausbildung, scnsc, zimmer, spielergruppe, geschlecht FROM conmanagement_anmeldungen WHERE conid='$id' AND scnsc!='Abgesagt' AND med_ausbildung!='keine' ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");

    $saniliste = "";

    for($i=0; $i < $num; $i++)
    {
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $med_ausbildung = just_my_result($mysql, $i, "med_ausbildung");
      $zimmer = just_my_result($mysql, $i, "zimmer");
          $spielergruppe = just_my_result($mysql, $i, "spielergruppe");
      $scnsc = just_my_result($mysql, $i, "scnsc");
      $geschlecht = just_my_result($mysql, $i, "geschlecht");
      if($geschlecht == "maennlich")
      {
        $geschlecht = "m&auml;nnlich";
      }


      $saniliste .= "<tr valign='middle'><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname&nbsp;</a></td><td>$vorname&nbsp;</td><td>$scnsc</td><td>$med_ausbildung&nbsp;</td><td>$geschlecht&nbsp;</td><td>$zimmer&nbsp;</td><td>$spielergruppe&nbsp;</td></tr>";
    }

    if($num > 0)
    {
      $content = "<table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td><b>Name</b></td><td><b>Vorname</b></td><td width='5%'><b>SC&nbsp;/&nbsp;<br>NSC&nbsp;</b></td><td width='5%'><b>Med. Ausbildung&nbsp;</b></td><td><b>Geschlecht:&nbsp;</b></td><td width='5%'><b>Zimmer Nr.&nbsp;</b></td><td width='5%'><b>Gruppe&nbsp;</b></td></tr>
      $saniliste
      </table>";
    }
    else
    {
      $content = "Es sind bisher keine Teilnehmer mit medizinischen Kentnissen angemeldet.";
    }

    if($view == "druckansicht")
    {
      print_druckansicht(
        "Liste der medizinisch ausgebildeten Teilnehmer",
        "<a href='$scriptadresse?action=showmedics&id=$id' style='font-weight: bold; color: #0000FF;'>Normale Ansicht</a><br><br>
        $content"
      );
    }
    else
    {
      print_site(
        "Liste der medizinisch ausgebildeten Teilnehmer",
        "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br><a href='$scriptadresse?action=showmedics&id=$id&view=druckansicht'>Druckansicht</a><br><br>
        $content"
      );
    }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showsum")
  {
    $mysql = "SELECT eingangsbetrag FROM conmanagement_anmeldungen WHERE conid='$id' AND eingangsbetrag!=''";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");

    $consumme = "";

    for($i=0; $i < $num; $i++)
    {
      $eingangsbetrag = just_my_result($mysql, $i, "eingangsbetrag");

      $consumme += $eingangsbetrag;
    }

    $consumme = str_replace(".", ",", $consumme);


    print_site(
      "Summe der Conbeitr&auml;ge",
      "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br>
      Die Summe der Beit&auml;ge f&uuml;r das $conname ist: <b>$consumme &euro;</b><br>Es haben <b>$num</b> Teilnehmer bezahlt."
    );
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showcar")
  {
    $mysql = "SELECT id, vorname, nachname, spielergruppe, zimmer, scnsc, kennzeichen FROM conmanagement_anmeldungen WHERE conid='$id' AND kennzeichen!='' AND scnsc!='Abgesagt' ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");

    $autoliste = "";

    for($i=0; $i < $num; $i++)
    {
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $kennzeichen = just_my_result($mysql, $i, "kennzeichen");
      $spielergruppe = just_my_result($mysql, $i, "spielergruppe");
          $zimmer = just_my_result($mysql, $i, "zimmer");
      $scnsc = just_my_result($mysql, $i, "scnsc");

      $autoliste .= "<tr valign='middle'><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname&nbsp;</a></td><td>$vorname&nbsp;</td><td>$scnsc</td><td>$kennzeichen&nbsp;</td><td>$zimmer&nbsp;</td><td>$spielergruppe&nbsp;</td></tr>";
    }

    if($num > 0)
    {
      $content = "<table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td><b>Name</b></td><td><b>Vorname</b></td><td width='5%'><b>SC&nbsp;/&nbsp;NSC&nbsp;</b></td><td width='5%'><b>Kennzeichen&nbsp;</b></td><td width='5%'><b>Zimmer Nr.&nbsp;</b></td><td width='5%'><b>Gruppe&nbsp;</b></td></tr>
      $autoliste
      </table>";
    }
    else
    {
      $content = "Bisher hat niemand bei Anmeldung sein Kennzeichen angegeben.";
    }

    if($view == "druckansicht")
    {
      print_druckansicht(
        "Liste der KFZ-Kennzeichen",
        "<a href='$scriptadresse?action=showcar&id=$id' style='font-weight: bold; color: #0000FF;'>Normale Ansicht</a><br><br>
        $content"
      );
    }
    else
    {
      print_site(
        "Liste der KFZ-Kennzeichen",
        "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br><a href='$scriptadresse?action=showcar&id=$id&view=druckansicht'>Druckansicht</a><br><br>
        $content"
      );
    }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showfde")
  {
    $mysql = "SELECT id, vorname, nachname, spielergruppe, zimmer, scnsc, freundschaft FROM conmanagement_anmeldungen WHERE conid='$id' AND freundschaft!='' AND scnsc!='Abgesagt' ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");

    $fdeliste = "";

    for($i=0; $i < $num; $i++)
    {
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $freundschaft = just_my_result($mysql, $i, "freundschaft");
      $spielergruppe = just_my_result($mysql, $i, "spielergruppe");
          $zimmer = just_my_result($mysql, $i, "zimmer");
      $scnsc = just_my_result($mysql, $i, "scnsc");

      $fdeliste .= "<tr valign='middle'><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname&nbsp;</a></td><td>$vorname&nbsp;</td><td>$scnsc</td><td>$freundschaft&nbsp;</td><td>$zimmer&nbsp;</td><td>$spielergruppe&nbsp;</td></tr>";
    }

    if($num > 0)
    {
      $content = "<table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td><b>Name</b></td><td><b>Vorname</b></td><td width='5%'><b>SC&nbsp;/&nbsp;NSC&nbsp;</b></td><td width='5%'><b>Freundschaft&nbsp;</b></td><td width='5%'><b>Zimmer Nr.&nbsp;</b></td><td width='5%'><b>Gruppe&nbsp;</b></td></tr>
      $fdeliste
      </table>";
    }
    else
    {
      $content = "Bisher hat niemand bei Anmeldung eine Freundschaft der Elemente angegeben.";
    }

    if($view == "druckansicht")
    {
      print_druckansicht(
        "Liste der Teilnehmer mit Freundschaft der Elemente",
        "<a href='$scriptadresse?action=showfde&id=$id' style='font-weight: bold; color: #0000FF;'>Normale Ansicht</a><br><br>
        $content"
      );
    }
    else
    {
      print_site(
        "Liste der Teilnehmer mit Freundschaft der Elemente",
        "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br><a href='$scriptadresse?action=showfde&id=$id&view=druckansicht'>Druckansicht</a><br><br>
        $content"
      );
    }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showgruppe")
  {
    $mysql = "SELECT id, vorname, nachname, spielergruppe, zimmer, scnsc FROM conmanagement_anmeldungen WHERE conid='$id' AND spielergruppe!='' AND scnsc!='Abgesagt' ORDER BY `spielergruppe` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");

    $fdeliste = "";

    for($i=0; $i < $num; $i++)
    {
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $spielergruppe = just_my_result($mysql, $i, "spielergruppe");
      $zimmer = just_my_result($mysql, $i, "zimmer");
      $scnsc = just_my_result($mysql, $i, "scnsc");

      $gruppenliste .= "<tr valign='middle'><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname&nbsp;</a></td><td>$vorname&nbsp;</td><td>$scnsc</td><td>$zimmer&nbsp;</td><td>$spielergruppe&nbsp;</td></tr>";
    }

    if($num > 0)
    {
      $content = "<table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td width='5%'><b>Name</b></td><td width='5%'><b>Vorname</b></td><td width='5%'><b>SC&nbsp;/&nbsp;NSC&nbsp;</b></td><td><b>Zimmer Nr.&nbsp;</b></td><td><b>Gruppe&nbsp;</b></td></tr>
      $gruppenliste
      </table>";
    }
    else
    {
      $content = "Bisher hat niemand bei Anmeldung seine Spielergruppe angegeben.";
    }

    if($view == "druckansicht")
    {
      print_druckansicht(
        "Liste der Teilnehmer sortiert nach Spielergruppe",
        "<a href='$scriptadresse?action=showgruppe&id=$id' style='font-weight: bold; color: #0000FF;'>Normale Ansicht</a><br><br>
        $content"
      );
    }
    else
    {
      print_site(
        "Liste der Teilnehmer sortiert nach Spielergruppe",
        "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br><a href='$scriptadresse?action=showgruppe&id=$id&view=druckansicht'>Druckansicht</a><br><br>
        $content"
      );
    }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showart")
  {
    $mysql = "SELECT id, vorname, nachname, zimmer, artefakte FROM conmanagement_anmeldungen WHERE conid='$id' AND artefakte!='' AND scnsc!='Abgesagt' ORDER BY `artefakte` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");

    $artefaktliste = "";

    for($i=0; $i < $num; $i++)
    {
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $artefakte = just_my_result($mysql, $i, "artefakte");
      $zimmer = just_my_result($mysql, $i, "zimmer");      

      $artefaktliste .= "<tr valign='middle'><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname&nbsp;</a></td><td>$vorname&nbsp;</td><td>$zimmer&nbsp;</td><td>$artefakte&nbsp;</td></tr>";
    }

    if($num > 0)
    {
      $content = "<table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td width='5%'><b>Name</b></td><td width='5%'><b>Vorname</b></td><td><b>Zimmer Nr.&nbsp;</b></td><td><b>Angaben zu Besonderheiten, Artefakten oder Tribes&nbsp;</b></td></tr>
      $artefaktliste
      </table>";
    }
    else
    {
      $content = "Bisher hat niemand bei Anmeldung Angaben zu Besonderheiten, Artefakte oder Tribes gemacht.";
    }

    if($view == "druckansicht")
    {
      print_druckansicht(
        "Liste der Teilnehmer mit Angaben zu Besonderheiten, Artefakte, Tribes",
        "<a href='$scriptadresse?action=showart&id=$id' style='font-weight: bold; color: #0000FF;'>Normale Ansicht</a><br><br>
        $content"
      );
    }
    else
    {
      print_site(
        "Liste der Teilnehmer mit Angaben zu Besonderheiten, Artefakte, Tribes",
        "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br><a href='$scriptadresse?action=showart&id=$id&view=druckansicht'>Druckansicht</a><br><br>
        $content"
      );
    }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showdieb")
  {
    $mysql = "SELECT id, vorname, nachname, spielergruppe, zimmer FROM conmanagement_anmeldungen WHERE conid='$id' AND dieben='1' AND scnsc!='Abgesagt' ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");

    $fdeliste = "";

    for($i=0; $i < $num; $i++)
    {
      $nr = $i+1;
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $zimmer = just_my_result($mysql, $i, "zimmer");
      $spielergruppe = just_my_result($mysql, $i, "spielergruppe");

      $gruppenliste .= "<tr valign='middle'><td>$nr&nbsp;</td><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname&nbsp;</a></td><td>$vorname&nbsp;</td><td>$zimmer&nbsp;</td><td>$spielergruppe&nbsp;</td></tr>";
    }

    if($num > 0)
    {
      $content = "<table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td width='1%'><b>#</b></td><td width='5%'><b>Name</b></td><td width='5%'><b>Vorname</b></td><td><b>Zimmer Nr.&nbsp;</b></td><td><b>Gruppe&nbsp;</b></td></tr>
      $gruppenliste
      </table>";
    }
    else
    {
      $content = "Bisher hat sich niemand als Dieb angemeldet.";
    }

    if($view == "druckansicht")
    {
      print_druckansicht(
        "Diebesliste",
        "<a href='$scriptadresse?action=showdieb&id=$id' style='font-weight: bold; color: #0000FF;'>Normale Ansicht</a><br><br>
        $content"
      );
    }
    else
    {
      print_site(
        "Diebesliste",
        "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br><a href='$scriptadresse?action=showdieb&id=$id&view=druckansicht'>Druckansicht</a><br><br>
        $content"
      );
    }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showgesleg")
  {
    $mysql = "SELECT id, vorname, nachname, spielergruppe, zimmer, legenden FROM conmanagement_anmeldungen WHERE conid='$id' AND legenden!='' AND scnsc!='Abgesagt' ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");

    $liste = "";

    for($i=0; $i < $num; $i++)
    {
      $nr = $i+1;
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $zimmer = just_my_result($mysql, $i, "zimmer");
      $spielergruppe = just_my_result($mysql, $i, "spielergruppe");
      $legenden = just_my_result($mysql, $i, "legenden");

      $liste .= "<tr valign='middle'><td>$nr&nbsp;</td><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname&nbsp;</a></td><td>$vorname&nbsp;</td><td>$zimmer&nbsp;</td><td>$spielergruppe&nbsp;</td><td>$legenden&nbsp;</td></tr>";
    }

    if($num > 0)
    {
      $content = "<table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td width='1%'><b>#</b></td><td width='5%'><b>Name</b></td><td width='5%'><b>Vorname</b></td><td><b>Zimmer Nr.&nbsp;</b></td><td><b>Gruppe&nbsp;</b></td><td><b>Geschichten &amp; Legenden&nbsp;</b></td></tr>
      $liste
      </table>";
    }
    else
    {
      $content = "Bisher hat kein Spieler Geschichten &amp; Legenden angegeben.";
    }

    if($view == "druckansicht")
    {
      print_druckansicht(
        "Liste der Teilnehmer mit Geschichten &amp; Legenden",
        "<a href='$scriptadresse?action=showgesleg&id=$id' style='font-weight: bold; color: #0000FF;'>Normale Ansicht</a><br><br>
        $content"
      );
    }
    else
    {
      print_site(
        "Liste der Teilnehmer mit Geschichten &amp; Legenden",
        "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br><a href='$scriptadresse?action=showgesleg&id=$id&view=druckansicht'>Druckansicht</a><br><br>
        $content"
      );
    }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showmag")
  {
    $mysql = "SELECT id, vorname, nachname, spielergruppe, zimmer, magiekundig FROM conmanagement_anmeldungen WHERE conid='$id' AND scnsc!='Abgesagt' AND magiekundig='1' ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");

    $liste = "";

    for($i=0; $i < $num; $i++)
    {
      $nr = $i+1;
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $zimmer = just_my_result($mysql, $i, "zimmer");
      $spielergruppe = just_my_result($mysql, $i, "spielergruppe");
      $magie = just_my_result($mysql, $i, "magiekundig");

      $liste .= "<tr valign='middle'><td>$nr&nbsp;</td><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname&nbsp;</a></td><td>$vorname&nbsp;</td><td>$zimmer&nbsp;</td><td>$spielergruppe&nbsp;</td></tr>";
    }

    if($num > 0)
    {
      $content = "<table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td width='1%'><b>#</b></td><td width='5%'><b>Name</b></td><td width='5%'><b>Vorname</b></td><td><b>Zimmer Nr.&nbsp;</b></td><td><b>Gruppe&nbsp;</b></td></tr>
      $liste
      </table>";
    }
    else
    {
      $content = "Bisher hat kein Spieler magische Fertigkeiten angegeben.";
    }

    if($view == "druckansicht")
    {
      print_druckansicht(
        "Liste der Teilnehmer mit Magie",
        "<a href='$scriptadresse?action=showmag&id=$id' style='font-weight: bold; color: #0000FF;'>Normale Ansicht</a><br><br>
        $content"
      );
    }
    else
    {
      print_site(
        "Liste der Teilnehmer mit Magie",
        "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br><a href='$scriptadresse?action=showmag&id=$id&view=druckansicht'>Druckansicht</a><br><br>
        $content"
      );
    }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showalch")
  {
    $mysql = "SELECT id, vorname, nachname, spielergruppe, zimmer, alchemist FROM conmanagement_anmeldungen WHERE conid='$id' AND scnsc!='Abgesagt' AND alchemist='1' ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");

    $liste = "";

    for($i=0; $i < $num; $i++)
    {
      $nr = $i+1;
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $zimmer = just_my_result($mysql, $i, "zimmer");
      $spielergruppe = just_my_result($mysql, $i, "spielergruppe");
      $alchemie = just_my_result($mysql, $i, "alchemist");

      $liste .= "<tr valign='middle'><td>$nr&nbsp;</td><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname&nbsp;</a></td><td>$vorname&nbsp;</td><td>$zimmer&nbsp;</td><td>$spielergruppe&nbsp;</td></tr>";
    }

    if($num > 0)
    {
      $content = "<table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td width='1%'><b>#</b></td><td width='5%'><b>Name</b></td><td width='5%'><b>Vorname</b></td><td><b>Zimmer Nr.&nbsp;</b></td><td><b>Gruppe&nbsp;</b></td></tr>
      $liste
      </table>";
    }
    else
    {
      $content = "Bisher hat kein Spieler alchemistische Fertigkeiten angegeben.";
    }

    if($view == "druckansicht")
    {
      print_druckansicht(
        "Liste der Teilnehmer mit Alchemie",
        "<a href='$scriptadresse?action=showalch&id=$id' style='font-weight: bold; color: #0000FF;'>Normale Ansicht</a><br><br>
        $content"
      );
    }
    else
    {
      print_site(
        "Liste der Teilnehmer mit Alchemie",
        "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br><a href='$scriptadresse?action=showalch&id=$id&view=druckansicht'>Druckansicht</a><br><br>
        $content"
      );
    }
  }
  

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showsonderfelder")
  {
    $mysql = "SELECT id, vorname, nachname, sfa1, sfa2, sfa3, sfa4, sfa5 FROM conmanagement_anmeldungen WHERE conid='$id' AND scnsc!='Abgesagt' ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");

    $liste = "";

    for($i=0; $i < $num; $i++)
    {
      $nr = $i+1;
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      
      $sfa1 = just_my_result($mysql, $i, "sfa1");
      $sfa2 = just_my_result($mysql, $i, "sfa2");
      $sfa3 = just_my_result($mysql, $i, "sfa3");
      $sfa4 = just_my_result($mysql, $i, "sfa4");
      $sfa5 = just_my_result($mysql, $i, "sfa5");

      $liste .= "<tr valign='middle'><td>$nr&nbsp;</td><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname&nbsp;</a></td><td>$vorname&nbsp;</td><td>$sfa1&nbsp;</td><td>$sfa2&nbsp;</td><td>$sfa3&nbsp;</td><td>$sfa4&nbsp;</td><td>$sfa5&nbsp;</td></tr>";
    }

    if($num > 0)
    {
      $content = "<table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td width='1%'><b>#</b></td><td width='5%'><b>Name</b></td><td width='5%'><b>Vorname</b></td><td><b>Sonderfeld 1&nbsp;</b></td><td><b>Sonderfeld 2&nbsp;</b></td><td><b>Sonderfeld 3&nbsp;</b></td><td><b>Sonderfeld 4&nbsp;</b></td><td><b>Sonderfeld 5&nbsp;</b></td></tr>
      $liste
      </table>";
    }
    else
    {
      $content = "Bisher hat kein Spieler Sonderfelder angegeben.";
    }

    if($view == "druckansicht")
    {
      print_druckansicht(
        "Liste der Sonderangaben",
        "<a href='$scriptadresse?action=showsonderfelder&id=$id' style='font-weight: bold; color: #0000FF;'>Normale Ansicht</a><br><br>
        $content"
      );
    }
    else
    {
      print_site(
        "Liste der Sonderangaben",
        "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br><a href='$scriptadresse?action=showsonderfelder&id=$id&view=druckansicht'>Druckansicht</a><br><br>
        $content"
      );
    }
  }  

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showspaehre")
  {
    $mysql = "SELECT id, vorname, nachname, spielergruppe, zimmer, skundig FROM conmanagement_anmeldungen WHERE conid='$id' AND scnsc!='Abgesagt' AND skundig!='' ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");

    $liste = "";

    for($i=0; $i < $num; $i++)
    {
      $nr = $i+1;
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $zimmer = just_my_result($mysql, $i, "zimmer");
      $spielergruppe = just_my_result($mysql, $i, "spielergruppe");
      $skunde = just_my_result($mysql, $i, "skundig");

      $liste .= "<tr valign='middle'><td>$nr&nbsp;</td><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname&nbsp;</a></td><td>$vorname&nbsp;</td><td>$zimmer&nbsp;</td><td>$spielergruppe&nbsp;</td><td>$skunde&nbsp;</td></tr>";
    }

    if($num > 0)
    {
      $content = "<table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td width='1%'><b>#</b></td><td width='5%'><b>Name</b></td><td width='5%'><b>Vorname</b></td><td><b>Zimmer Nr.&nbsp;</b></td><td><b>Gruppe&nbsp;</b></td><td><b>Sp&auml;hrenkunde&nbsp;</b></td></tr>
      $liste
      </table>";
    }
    else
    {
      $content = "Bisher hat kein Spieler Sp&auml;hrenkunde angegeben.";
    }

    if($view == "druckansicht")
    {
      print_druckansicht(
        "Liste der Spieler mit Sp&auml;hrenkunde",
        "<a href='$scriptadresse?action=showspaehre&id=$id' style='font-weight: bold; color: #0000FF;'>Normale Ansicht</a><br><br>
        $content"
      );
    }
    else
    {
      print_site(
        "Liste der Spieler mit Sp&auml;hrenkunde",
        "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br><a href='$scriptadresse?action=showspaehre&id=$id&view=druckansicht'>Druckansicht</a><br><br>
        $content"
      );
    }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showritual")
  {
    $mysql = "SELECT id, vorname, nachname, spielergruppe, zimmer, rituale FROM conmanagement_anmeldungen WHERE conid='$id' AND scnsc!='Abgesagt' AND rituale='1' ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");

    $liste = "";

    for($i=0; $i < $num; $i++)
    {
      $nr = $i+1;
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $zimmer = just_my_result($mysql, $i, "zimmer");
      $spielergruppe = just_my_result($mysql, $i, "spielergruppe");

      $liste .= "<tr valign='middle'><td>$nr&nbsp;</td><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname&nbsp;</a></td><td>$vorname&nbsp;</td><td>$zimmer&nbsp;</td><td>$spielergruppe&nbsp;</td></tr>";
    }

    if($num > 0)
    {
      $content = "<table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td width='1%'><b>#</b></td><td width='5%'><b>Name</b></td><td width='5%'><b>Vorname</b></td><td><b>Zimmer Nr.&nbsp;</b></td><td><b>Gruppe&nbsp;</b></td></tr>
      $liste
      </table>";
    }
    else
    {
      $content = "Bisher hat sich kein Spieler, der Rituale machen will, angemeldet.";
    }

    if($view == "druckansicht")
    {
      print_druckansicht(
        "Liste der Spieler, die Rituale machen wollen",
        "<a href='$scriptadresse?action=showritual&id=$id' style='font-weight: bold; color: #0000FF;'>Normale Ansicht</a><br><br>
        $content"
      );
    }
    else
    {
      print_site(
        "Liste der Spieler, die Rituale machen wollen",
        "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br><a href='$scriptadresse?action=showritual&id=$id&view=druckansicht'>Druckansicht</a><br><br>
        $content"
      );
    }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showconwerbung")
  {
    $mysql = "SELECT id, vorname, nachname, email FROM conmanagement_anmeldungen WHERE conid='$id' AND speichern='1' ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");

    $liste = "";

    for($i=0; $i < $num; $i++)
    {
      $nr = $i+1;
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $email = just_my_result($mysql, $i, "email");

      $liste .= "<tr valign='middle'><td>$nr&nbsp;</td><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname&nbsp;</a></td><td>$vorname&nbsp;</td><td>$email&nbsp;</td></tr>";
    }

    if($num > 0)
    {
      $content = "<table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td width='1%'><b>#</b></td><td width='5%'><b>Name</b></td><td width='5%'><b>Vorname</b></td><td><b>Email&nbsp;</b></td></tr>
      $liste
      </table>";
    }
    else
    {
      $content = "Bisher hat sich keine Teilnehmer die Conwerbung zulassen m&ouml;chten angemeldet.";
    }

    if($view == "druckansicht")
    {
      print_druckansicht(
        "Liste der Teilnehmer, die Conwerbung zulassen",
        "<a href='$scriptadresse?action=showconwerbung&id=$id' style='font-weight: bold; color: #0000FF;'>Normale Ansicht</a><br><br>
        $content"
      );
    }
    else
    {
      print_site(
        "Liste der Teilnehmer, die Conwerbung zulassen",
        "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br><a href='$scriptadresse?action=showconwerbung&id=$id&view=druckansicht'>Druckansicht</a><br><br>
        $content"
      );
    }
  }
  
//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showemaillist")
  {
    $mysql = "SELECT email FROM conmanagement_anmeldungen WHERE conid='$id' AND scnsc!='Abgesagt'";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");

    $liste = "";
	$link = "";

    for($i=0; $i < $num; $i++)
    {      
      $email = just_my_result($mysql, $i, "email");

      $liste .= "<tr valign='middle'><td>$email&nbsp;</td></tr>";
	  $link_semi .= "$email;";
	  $link_komma .= "$email,";
    }

    if($num > 0)
    {
      $content = "Hier gibt es den Link f&uuml;r eine Email an alle Teilnehmer. Je nach verwendetem Email-Programm funktioniert nur einer der beiden Links. Im Notfall kann aus der unten stehenden Liste mit Excel etwas eigenes erstellt werden.<br><br>
	  <b>ACHTUNG:</b> Aus Datenschutzgr&uuml;nden d&uuml;rfen Emails keinesfalls alle Teilnehmer als direkte Empf&auml;nger haben. Immer BCC benutzen, damit nicht jeder alle Adressen bekommt.<br><br>
	  <a href=\"mailto:con@westliches-siegel.de?bcc=$link_semi\">Direktlink mit Semikolon als Trennzeichen</a><br>
	  <a href=\"mailto:con@westliches-siegel.de?bcc=$link_komma\">Direktlink mit Komma als Trennzeichen</a><br><br>
	  <table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td><b>Email&nbsp;</b></td></tr>
      $liste
      </table>";
    }
    else
    {
      $content = "Bisher keine Anmeldungen zu dieser Veranstaltung.";
    }

    if($view == "druckansicht")
    {
      print_druckansicht(
        "Liste aller Email-Adressen",
        "<a href='$scriptadresse?action=showemaillist&id=$id' style='font-weight: bold; color: #0000FF;'>Normale Ansicht</a><br><br>
        $content"
      );
    }
    else
    {
      print_site(
        "Liste aller Email-Adressen",
        "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br><a href='$scriptadresse?action=showemaillist&id=$id&view=druckansicht'>Druckansicht</a><br><br>
        $content"
      );
    }
  }  

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showstimmung")
  {
    $mysql = "SELECT stimmung FROM conmanagement_anmeldungen WHERE conid='$id' AND stimmung!='' AND scnsc!='Abgesagt'";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");

    $stimmungscountk = $stimmungscountp = $stimmungscountz = 0;

    for($i=0; $i < $num; $i++)
    {
      $stimmung = just_my_result($mysql, $i, "stimmung");

      if($stimmung == "kaempfen")
      {
        $stimmungscountk++;
      }
      elseif($stimmung == "plotten")
      {
        $stimmungscountp++;
      }
      else // $stimmung == "zaubern"
      {
        $stimmungscountz++;
      }
    }

    if($num > 0)
    {
      $prozentk = round(100*$stimmungscountk/$num);
      $prozentp = round(100*$stimmungscountp/$num);
      $prozentz = round(100*$stimmungscountz/$num);

      $content = "Hier ist das Stimmungsbild der $num Teilnehmer, die Ihre Stimmung angegeben haben:<br><br>".
      $prozentk."% m&ouml;chten am liebsten K&auml;mpfen<br>".
      $prozentp."% m&ouml;chten am liebsten Plotten<br>".
      $prozentz."% m&ouml;chten am liebsten Zaubern<br>";
    }
    else
    {
      $content = "Bisher hat niemand bei Anmeldung seine Stimmung angegeben.";
    }

      print_site(
      "Stimmungsbild",
      "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br>
      $content"
    );
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showaufbau")
  {
    $mysql = "SELECT id, vorname, nachname, zimmer, spielergruppe, bauhelfer FROM conmanagement_anmeldungen WHERE conid='$id' AND scnsc!='Abgesagt' AND (bauhelfer='Aufbau' OR bauhelfer='Beides') ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");

    $aufbauliste = "";

    for($i=0; $i < $num; $i++)
    {
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $bauhelfer = just_my_result($mysql, $i, "bauhelfer");
      $zimmer = just_my_result($mysql, $i, "zimmer");
          $spielergruppe = just_my_result($mysql, $i, "spielergruppe");

      $aufbauliste .= "<tr valign='middle'><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname&nbsp;</a></td><td>$vorname&nbsp;</td><td>$bauhelfer</td><td>$zimmer&nbsp;</td><td>$spielergruppe&nbsp;</td></tr>";
    }

    if($num > 0)
    {
      $content = "Es sind $num Aufbauhelfer angemeldet.<br><br><table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td><b>Name</b></td><td><b>Vorname</b></td><td width='5%'><b>Auf- / Abbauhelfer</b></td><td width='5%'><b>Zimmer Nr.&nbsp;</b></td><td width='5%'><b>Gruppe&nbsp;</b></td></tr>
      $aufbauliste
      </table>";
    }
    else
    {
      $content = "Es sind bisher keine Teilnehmer als Aufbauhelfer angemeldet.";
    }

    if($view == "druckansicht")
    {
      print_druckansicht(
        "Liste der Aufbauhelfer",
        "<a href='$scriptadresse?action=showaufbau&id=$id' style='font-weight: bold; color: #0000FF;'>Normale Ansicht</a><br><br>
        $content"
      );
    }
    else
    {
      print_site(
        "Liste der Aufbauhelfer",
        "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br><a href='$scriptadresse?action=showaufbau&id=$id&view=druckansicht'>Druckansicht</a><br><br>
        $content"
      );
    }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showabbau")
  {
    $mysql = "SELECT id, vorname, nachname, zimmer, spielergruppe, bauhelfer FROM conmanagement_anmeldungen WHERE conid='$id' AND scnsc!='Abgesagt' AND (bauhelfer='Abbau' OR bauhelfer='Beides') ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");

    $abbauliste = "";

    for($i=0; $i < $num; $i++)
    {
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $bauhelfer = just_my_result($mysql, $i, "bauhelfer");
      $zimmer = just_my_result($mysql, $i, "zimmer");
          $spielergruppe = just_my_result($mysql, $i, "spielergruppe");

      $abbauliste .= "<tr valign='middle'><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname&nbsp;</a></td><td>$vorname&nbsp;</td><td>$bauhelfer</td><td>$zimmer&nbsp;</td><td>$spielergruppe&nbsp;</td></tr>";
    }

    if($num > 0)
    {
      $content = "Es sind $num Abbauhelfer angemeldet.<br><br><table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td><b>Name</b></td><td><b>Vorname</b></td><td width='5%'><b>Auf- / Abbauhelfer</b></td><td width='5%'><b>Zimmer&nbsp;</b></td><td width='5%'><b>Gruppe&nbsp;</b></td></tr>
      $abbauliste
      </table>";
    }
    else
    {
      $content = "Es sind bisher keine Teilnehmer als Abbauhelfer angemeldet.";
    }

    if($view == "druckansicht")
    {
      print_druckansicht(
        "Liste der Abbauhelfer",
        "<a href='$scriptadresse?action=showabbau&id=$id' style='font-weight: bold; color: #0000FF;'>Normale Ansicht</a><br><br>
        $content"
      );
    }
    else
    {
      print_site(
        "Liste der Abbauhelfer",
        "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br><a href='$scriptadresse?action=showabbau&id=$id&view=druckansicht'>Druckansicht</a><br><br>
        $content"
      );
    }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showminderj")
  {
    $mysql = "SELECT id, vorname, nachname, zimmer, spielergruppe, geburtstag, geburtsmonat, geburtsjahr FROM conmanagement_anmeldungen WHERE conid='$id' AND scnsc!='Abgesagt' ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $mysql2 = "SELECT conname, condatum FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");
    $condatum = just_my_result($mysql2, 0, "condatum");    //09.10.2015 - 11.10.2015
    $minderjliste = "";

    $anzahlminderj = 0;

    $datumarray = str_split($condatum);
    $conterminjahr = $datumarray[6].$datumarray[7].$datumarray[8].$datumarray[9];
    $conterminmonat = $datumarray[3].$datumarray[4];
    $contermintag = $datumarray[0].$datumarray[1];

    $erstercontag = "$contermintag.$conterminmonat.$conterminjahr";

    $contermintimestamp = mktime ( 0, 0, 0, $conterminmonat, $contermintag, ($conterminjahr-18) );
    //Contermintimestamp minus 18 Jahre.
    $contermintimestampreal = mktime ( 0, 0, 0, $conterminmonat, $contermintag, $conterminjahr );

    for($i=0; $i < $num; $i++)
    {
      $personid = just_my_result($mysql, $i, "id");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $spielergruppe = just_my_result($mysql, $i, "spielergruppe");
      $zimmer = just_my_result($mysql, $i, "zimmer");
      $gebtag = just_my_result($mysql, $i, "geburtstag");
      $gebmonat = just_my_result($mysql, $i, "geburtsmonat");
      $gebjahr = just_my_result($mysql, $i, "geburtsjahr");
      $gebtimestamp = mktime ( 0, 0, 0, $gebmonat, $gebtag, $gebjahr );
      $alter = floor(($contermintimestampreal - $gebtimestamp) / 31536000);

      if($gebtimestamp > $contermintimestamp)
      {
        $minderjliste .= "<tr valign='middle'><td><a href='$scriptadresse?action=showperson&id=$personid'>$nachname&nbsp;</a></td><td>$vorname</td><td>$gebtag.$gebmonat.$gebjahr</td><td>$alter&nbsp;</td><td>&nbsp;</td><td>$zimmer&nbsp;</td><td>$spielergruppe&nbsp;</td></tr>";
        $anzahlminderj++;
      }
    }

    $num = $anzahlminderj;

    if($num > 0)
    {
      $content = "Es sind $num Minderj&auml;hrige angemeldet.<br><br><table border='1' width='100%' cellspacing='0' border='1'>
      <tr><td><b>Name</b></td><td><b>Vorname</b></td><td width='5%'><b>Geburtstag&nbsp;</b></td><td width='15%'><b>Alter am $erstercontag&nbsp;</b></td><td><b>Pate</b></td><td><b>Zimmer</b></td><td><b>Gruppe</b></td></tr>
      $minderjliste
      </table>";
    }
    else
    {
      $content = "Es sind bisher keine minderj&auml;hrigen Teilnehmer angemeldet.";
    }

    if($view == "druckansicht")
    {
      print_druckansicht(
        "Liste minderj&auml;hriger Teilnehmer am Tag des Conbegins",
        "<a href='$scriptadresse?action=showminderj&id=$id' style='font-weight: bold; color: #0000FF;'>Normale Ansicht</a><br><br>
        $content"
      );
    }
    else
    {
      print_site(
        "Liste minderj&auml;hriger Teilnehmer am Tag des Conbegins",
        "<a href='$scriptadresse?action=showlist&id=$id'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br><a href='$scriptadresse?action=showminderj&id=$id&view=druckansicht'>Druckansicht</a><br><br>
        $content"
      );
    }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "mailneubest")
  {
    $mysql = "SELECT vorname, nachname, email, conid, code, anmeldesprache FROM conmanagement_anmeldungen WHERE id='$id'";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    if ($num>0)
    {
      $conid = just_my_result($mysql, 0, "conid");
      $vorname = just_my_result($mysql, 0, "vorname");
      $nachname = just_my_result($mysql, 0, "nachname");
      $email = just_my_result($mysql, 0, "email");
      $freipw = just_my_result($mysql, 0, "code");
      $ressource = just_my_result($mysql, 0, "anmeldesprache");

      $mysql2 = $mysql2txt = "SELECT conname, connamekurz FROM conmanagement_conliste WHERE id='$conid' LIMIT 1";
      $mysql2 = mysqli_query($link,$mysql2);
      $conname = just_my_result($mysql2, 0, "conname");
      $connamekurz = just_my_result($mysql2, 0, "connamekurz");

      $zieladresse = "https://".$_SERVER['SERVER_NAME']."/conanmeldung.php";

      require_once('./i/sprache.php');

      $varspace['to']=$email;
      $varspace['subject']=$connamekurz.$s_mailbest_mailsubject;
      $varspace['htmlmessage']="<HTML>
      <HEAD>
      <title>$s_mailbest_mailtext1 $vorname $nachname,<br><br>
      $s_mailbest_mailtext2<br><br>

      <a href='$zieladresse?action=mailbest&id=$id&conid=$conid&code=$freipw'><b><font size='-1'>$zieladresse?action=mailbest&id=$id&conid=$conid&code=$freipw</font></b></a><br>
      <br>$s_mailbest_mailtext3<br><br>
      </BODY></HTML>";

      $varspace['htmlmessage'] = str_replace("ä", "&auml;", $varspace['htmlmessage']);
      $varspace['htmlmessage'] = str_replace("ö", "&ouml;", $varspace['htmlmessage']);
      $varspace['htmlmessage'] = str_replace("ü", "&uuml;", $varspace['htmlmessage']);
      $varspace['htmlmessage'] = str_replace("Ä", "&Auml;", $varspace['htmlmessage']);
      $varspace['htmlmessage'] = str_replace("Ö", "&Ouml;", $varspace['htmlmessage']);
      $varspace['htmlmessage'] = str_replace("Ü", "&Uuml;", $varspace['htmlmessage']);
      $varspace['htmlmessage'] = str_replace("ß", "&szlig;", $varspace['htmlmessage']);
      $bkslashquot = '\"';
      $varspace['htmlmessage'] = str_replace($bkslashquot, "&quot;", $varspace['htmlmessage']);
      $quotzeichen = '"';
      $varspace['htmlmessage'] = str_replace($quotzeichen, "&quot;", $varspace['htmlmessage']);
      $varspace['htmlmessage'] = str_replace("€", "&euro;", $varspace['htmlmessage']);

      mail_senden($varspace);
    }

    print_site("Mail erneut versendet","Der Email-Best&auml;tigungslink wurde erneut versendet.<br><br><a href='$scriptadresse?action=showlist&id=$conid'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a>");
  }

//###########################################################################################################################
//########################################### Uebersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showcomplete")
  {
    $welche = $_REQUEST['welche'];

    $mysql2 = "SELECT conname, minscbetrag, minnscbetrag FROM conmanagement_conliste WHERE id='$id' LIMIT 1";
    $mysql2 = mysqli_query($link,$mysql2);
    $conname = just_my_result($mysql2, 0, "conname");
    $minscbetrag = just_my_result($mysql2, 0, "minscbetrag");
    $minnscbetrag = just_my_result($mysql2, 0, "minnscbetrag");
    $conname = just_my_result($mysql2, 0, "conname");

    if($welche == 'sc')
    {
      $auswahl = "scnsc='SC'";
      $listentitel = "Spieler";
      $zahlbetrag = $minscbetrag;
    }
    elseif($welche == 'nsc')
    {
      $auswahl = "scnsc='NSC'";
      $listentitel = "NSC";
      $zahlbetrag = $minnscbetrag;
    }
    elseif($welche == 'tagesgast')
    {
      $auswahl = "scnsc='Tagesgast'";
      $listentitel = "Tagesg&auml;ste";
      $zahlbetrag = $minscbetrag;
    }
    elseif($welche == 'team')
    {
      $auswahl = "scnsc='Team'";
      $listentitel = "Team";
      $zahlbetrag = $minnscbetrag;
    }
    elseif($welche == 'abgesagt')
    {
      $auswahl = "scnsc='Abgesagt'";
      $listentitel = "Abgesagen";
      $zahlbetrag = $minnscbetrag;
      //hier noch nachhaken
    }

    $mysql = "SELECT * FROM conmanagement_anmeldungen WHERE conid='$id' AND $auswahl ORDER BY `nachname` ASC";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $completeliste = "";

    for($i=0; $i < $num; $i++)
    {
      $personid = just_my_result($mysql, $i, "id");
      $anmeldedatum = just_my_result($mysql, $i, "anmeldedatum");
      $anmeldung_bestaetigt  = just_my_result($mysql, $i, "anmeldung_bestaetigt");
      $geldeingang_bestaetigt  = just_my_result($mysql, $i, "geldeingang_bestaetigt");
      $eingangsbetrag = just_my_result($mysql, $i, "eingangsbetrag");
      $vorname = just_my_result($mysql, $i, "vorname");
      $nachname = just_my_result($mysql, $i, "nachname");
      $strasse = just_my_result($mysql, $i, "strasse");
      $plz = just_my_result($mysql, $i, "plz");
      $ort = just_my_result($mysql, $i, "ort");
      $geburtstag = just_my_result($mysql, $i, "geburtstag");
      $geburtsmonat = just_my_result($mysql, $i, "geburtsmonat");
      $geburtsjahr = just_my_result($mysql, $i, "geburtsjahr");
      $geschlecht = just_my_result($mysql, $i, "geschlecht");
      $schwanger = just_my_result($mysql, $i, "schwanger");
      $vegetarier = just_my_result($mysql, $i, "vegetarier");
      $med_ausbildung = just_my_result($mysql, $i, "med_ausbildung");
      $telefon = just_my_result($mysql, $i, "telefon");
      $handy = just_my_result($mysql, $i, "handy");
      $email = just_my_result($mysql, $i, "email");
      $scnsc = just_my_result($mysql, $i, "scnsc");
      $allergien = just_my_result($mysql, $i, "allergien");
      $krankheiten = just_my_result($mysql, $i, "krankheiten");
      $phobien = just_my_result($mysql, $i, "phobien");
      $kennzeichen = just_my_result($mysql, $i, "kennzeichen");
      $stimmung = just_my_result($mysql, $i, "stimmung");
      $bemerkungen = just_my_result($mysql, $i, "bemerkungen");
      $wiezumcon = just_my_result($mysql, $i, "wiezumcon");
      $charname = just_my_result($mysql, $i, "charname");
      $klasse = just_my_result($mysql, $i, "klasse");
      $contage = just_my_result($mysql, $i, "contage");
      $spielergruppe = just_my_result($mysql, $i, "spielergruppe");
      $siegel = just_my_result($mysql, $i, "siegel");
          $legenden = just_my_result($mysql, $i, "legenden");
      $dieben = just_my_result($mysql, $i, "dieben");
      $magiekundig = just_my_result($mysql, $i, "magiekundig");
          $alchemist = just_my_result($mysql, $i, "alchemist");
      $freundschaft = just_my_result($mysql, $i, "freundschaft");
      $skundig = just_my_result($mysql, $i, "skundig");
      $rituale = just_my_result($mysql, $i, "rituale");
      $artefakte = just_my_result($mysql, $i, "artefakte");
      $speichern = just_my_result($mysql, $i, "speichern");

      $zimmer = just_my_result($mysql, $i, "zimmer");
      $slcharnotes = just_my_result($mysql, $i, "slcharnotes");
      $slpersnotes = just_my_result($mysql, $i, "slpersnotes");

      $anmeldebestdatum = just_my_result($mysql, $i, "anmeldebestdatum");
      $geldbestdatum = just_my_result($mysql, $i, "geldbestdatum");
      $bauhelfer = just_my_result($mysql, $i, "bauhelfer");

      if($anmeldung_bestaetigt == "1")
      {
        $anmeldung_bestaetigtx = "X";
      }
      else
      {
        $anmeldung_bestaetigtx = "";
      }

      if($geldeingang_bestaetigt == "1")
      {
        $geldeingang_bestaetigtx = "X";
      }
      else
      {
        $geldeingang_bestaetigtx = "";
      }

      if($speichern == "1")
      {
        $speichernx = "X";
      }
      else
      {
        $speichernx = "";
      }

      if($dieben == "1")
      {
        $diebenx = "X";
      }
      else
      {
        $diebenx = "";
      }

          if($magiekundig == "1")
      {
        $magiekundigx = "X";
      }
      else
      {
        $magiekundigx = "";
      }

          if($alchemist == "1")
      {
        $alchemistx = "X";
      }
      else
      {
        $alchemistx = "";
      }

          if($rituale == "1")
      {
        $ritualex = "X";
      }
      else
      {
        $ritualex = "";
      }

      if($schwanger == "1")
      {
        $schwangerx = "X";
      }
      else
      {
        $schwangerx = "";
      }

      if($geschlecht == "maennlich")
      {
        $geschlecht = "m&auml;nnlich";
      }

      if($eingangsbetrag < $zahlbetrag)
      {
        $red = "color = '#FF0000'";
      }
      else
      {
        $red = "";
      }

      $eingangsbetrag = str_replace(".", ",", $eingangsbetrag);
      $eingangsbetrag = str_replace(",00", "", $eingangsbetrag);

        $completeliste .= "
        <tr>
        <td>&nbsp;</td>
        <td>$nachname&nbsp;</td>
        <td>$vorname&nbsp;</td>
        <td><font $red><div align='center'>$eingangsbetrag&nbsp;</div></font></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>$zimmer&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>$strasse&nbsp;</td>
        <td>$plz&nbsp;</td>
        <td>$ort&nbsp;</td>
        <td>$geburtstag.$geburtsmonat.$geburtsjahr&nbsp;</td>
        <td>$geschlecht&nbsp;</td>
        <td><div align='center'>$schwangerx&nbsp;</div></td>
        <td>$vegetarier&nbsp;</td>
        <td>$telefon&nbsp;</td>
        <td>$handy&nbsp;</td>
        <td><a href='mailto:$email'>$email</a>&nbsp;</td>
        <td>$med_ausbildung&nbsp;</td>
        <td><div align='center'>$anmeldung_bestaetigtx&nbsp;</div></td>
        <td><div align='center'>$geldeingang_bestaetigtx&nbsp;</div></td>
        <td>&nbsp;</td>
        <td>$allergien&nbsp;</td>
        <td>$krankheiten&nbsp;</td>
        <td>$phobien&nbsp;</td>
        <td>$kennzeichen&nbsp;</td>
        <td>$stimmung&nbsp;</td>
        <td>$anmeldedatum&nbsp;</td>
        <td>$bemerkungen&nbsp;</td>
        <td>$slcharnotes&nbsp;</td>
        <td>$slpersnotes&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td><div align='center'>$speichernx&nbsp;</div></td>

        <td>$charname&nbsp;</td>
        <td>$klasse&nbsp;</td>
        <td>$contage&nbsp;</td>
        <td>$spielergruppe&nbsp;</td>
        <td>$siegel&nbsp;</td>
                <td>$legenden&nbsp;</td>
        <td><div align='center'>$diebenx&nbsp;</div></td>
                <td><div align='center'>$magiekundigx&nbsp;</div></td>
                <td><div align='center'>$alchemistx&nbsp;</div></td>
        <td>$freundschaft&nbsp;</td>
        <td>$skundig&nbsp;</td>
        <td><div align='center'>$ritualex&nbsp;</div></td>
        <td>$artefakte&nbsp;</td>
        <td>$wiezumcon&nbsp;</td>

        <td><div align='center'>$anmeldebestdatum&nbsp;</div></td>
        <td><div align='center'>$geldbestdatum&nbsp;</div></td>
        <td><div align='center'>$bauhelfer&nbsp;</div></td>

        </tr>";
    }

    if($num > 0)
    {
        $content = "<table border='1' width='100%' cellspacing='0' border='1'>
        <tr bgcolor='#C0C0C0'>
        <td><b>Nr.&nbsp;</b></td>
        <td><b>Nachname&nbsp;</b></td>
        <td><b>Vorname&nbsp;</b></td>
        <td><b>&Uuml;berwiesen (im &euro;)&nbsp;</b></td>
        <td><b>zzgl.&nbsp;</b></td>
        <td><b>Deals&nbsp;</b></td>
        <td><b>ZKZ&nbsp;</b></td>
        <td><b>Zimmer&nbsp;</b></td>
        <td><b>Fotos&nbsp;</b></td>
        <td><b>CD bestellt&nbsp;</b></td>
        <td><b>CD bezahlt&nbsp;</b></td>
        <td><b>Vermi&szlig;te Gegenst&auml;nde&nbsp;</b></td>
        <td><b>Strasse&nbsp;</b></td>
        <td><b>PLZ&nbsp;</b></td>
        <td><b>Ort&nbsp;</b></td>
        <td><b>Geburtsdatum&nbsp;</b></td>
        <td><b>Geschlecht&nbsp;</b></td>
        <td><b>Schwanger&nbsp;</b></td>
        <td><b>Besonderen Essgewohnheiten&nbsp;</b></td>
        <td><b>Telefon&nbsp;</b></td>
        <td><b>Handy&nbsp;</b></td>
        <td><b>Email&nbsp;</b></td>
        <td><b>Med. Ausbildung&nbsp;</b></td>
        <td><b>Anmeldung best&auml;tigt&nbsp;</b></td>
        <td><b>Geldeingang best&auml;tigt&nbsp;</b></td>
        <td><b>Infopaket&nbsp;</b></td>
        <td><b>Allergien&nbsp;</b></td>
        <td><b>Krankheiten&nbsp;</b></td>
        <td><b>Phobien&nbsp;</b></td>
        <td><b>Kennzeichen&nbsp;</b></td>
        <td><b>Stimmung&nbsp;</b></td>
        <td><b>Anmeldedatum&nbsp;</b></td>
        <td><b>Bemerkungen des Spielers&nbsp;</b></td>
        <td><b>SL-Charakterbemerkungen&nbsp;</b></td>
        <td><b>SL-Personenbemerkungen&nbsp;</b></td>
        <td><b>Charakterbogen&nbsp;</b></td>
        <td><b>Charakterstory&nbsp;</b></td>
        <td><b>Bild abgegeben&nbsp;</b></td>
        <td><b>Daten Speichern?&nbsp;</b></td>
        <td><b>Charaktername&nbsp;</b></td>
        <td><b>Klasse&nbsp;</b></td>
        <td><b>Contage&nbsp;</b></td>
        <td><b>Spielergruppe&nbsp;</b></td>
        <td><b>Siegel&nbsp;</b></td>
                <td><b>Geschichten &amp; Legenden&nbsp;</b></td>
        <td><b>Dieben&nbsp;</b></td>
                <td><b>Magiekundig&nbsp;</b></td>
                <td><b>Alchemiekundig&nbsp;</b></td>
        <td><b>Freundschaft der Elemente&nbsp;</b></td>
        <td><b>Sph&auml;renkundig&nbsp;</b></td>
        <td><b>Rituale geplant&nbsp;</b></td>
        <td><b>Beonderheiten / Artefakte&nbsp;</b></td>
        <td><b>Wie&nbsp;zum Con gekommen?&nbsp;</b></td>

        <td><b>Anmeldebest&auml;tigungsdatum</b>&nbsp;</td>
        <td><b>Versanddatum der Geldeingansbest&auml;tigung</b>&nbsp;</td>
        <td><b>Auf- / Abbauhelfer</b>&nbsp;</td>

        </tr>

        $completeliste
        </table>";
    }
    else
    {
      $content = "Es sind bisher keine Teilnehmer in dieser Kategorie angemeldet.";
    }

    print_druckansicht(
      "$listentitel-Liste mit allen Daten vom $conname",
      "<a href='$scriptadresse?action=showlist&id=$id' style='font-weight: bold; color: #0000FF;'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br>
      $content"
    );
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################
  elseif($action == "bestmail")
  {
    $mysql = "SELECT id, conid, vorname, nachname, email, scnsc, anmeldung_bestaetigt, anmeldesprache FROM conmanagement_anmeldungen WHERE id='$id'";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    if($num <= 0)
    {
      print_site(
        "Fehler",
        "Es wurde kein Datensatz mit dieser ID gefunden."
      );
    }
    else
    {
      $personid = just_my_result($mysql, 0, "id");
      $conid = just_my_result($mysql, 0, "conid");
      $vorname = just_my_result($mysql, 0, "vorname");
      $nachname = just_my_result($mysql, 0, "nachname");
      $email = just_my_result($mysql, 0, "email");
      $scnsc = just_my_result($mysql, 0, "scnsc");
      $anmeldung_bestaetigt = just_my_result($mysql, 0, "anmeldung_bestaetigt");
      $anmeldesprache = just_my_result($mysql, 0, "anmeldesprache");

      if($anmeldung_bestaetigt == "1")
      { //Wenn der Eingang der Anmeldung bereits einmal bestaetigt wurde.
        print_site(
        'Empfangs-Best&auml;tigungsmail wurde bereits verschickt',
        "Dem Spieler $vorname $nachname wurde bereits eine Empfangsbest&auml;tigungsmail geschickt, daher wird jetzt <b>keine</b> Email verschickt und nichts an der Datenbank ge&auml;ndert."
        );
      }
      else
      {
        $mysql2 = "SELECT conname, conuntertitel, condatum, flyeradresse FROM conmanagement_conliste WHERE id='$conid' LIMIT 1";
        $mysql2 = mysqli_query($link,$mysql2);
        $conname = just_my_result($mysql2, 0, "conname");
        $conuntertitel = just_my_result($mysql2, 0, "conuntertitel");
        $condatum = just_my_result($mysql2, 0, "condatum");
        $flyeradresse = just_my_result($mysql2, 0, "flyeradresse");


        //$text = rawurlencode ($text);

        if($anmeldesprache == "de")
        {
        $text_bestmail_nsc = "Hallo $vorname $nachname!<br>
            <br>
            Vielen Dank f&uuml;r Deine $scnsc-Anmeldung zum<br><br>$conname - $conuntertitel,<br>
            $condatum,<br><br>deren Erhalt wir hiermit best&auml;tigen.<br>
            <br>
            Bitte &uuml;berweise den Conbeitrag auf das angegebene Konto, erst dann bist Du verbindlich angemeldet.<br>
            <br>
            Empf&auml;nger: Florian M&uuml;llerheim<br>
            IBAN: DE98200411550454665100<br>
            BIC: COBADEHD055 (Comdirekt Bank)<br>
            <br>
            Informationen zur aktuellen Preisstaffel findest du hier:<br>
            <a href='$flyeradresse'>$flyeradresse</a><br>
            <br>
            Eine weitere Mail erh&auml;ltst Du nach Zahlungseingang.<br>
            <br>
            <!-- Registriere Dich bitte im Forum (falls noch nicht geschehen). Dort kannst Du Dich voll mit einbringen. Das Passwort f&uuml;r das NSC Board ist &quot;Endgame&quot; (ohne Anf&uuml;hrungszeichen). Wenn du gerne an der Vorbereitung mit mischen willst, melde Dich gerne bei der NSC Koordination.<br>
            <br> -->
            Sp&auml;testens 2 Wochen vor dem Con verschicken wir auch noch ein Info-Paket, welches Regelinformationen, Organisatorisches, die Anfahrtsbeschreibung und einiges mehr enth&auml;lt.<br>
            <br>
            Wir sehen uns auf dem $conname !!!<br>
            <br>
            Gr&uuml;&szlig;e,<br>
            Deine Westorga";

        $text_bestmail_sc = "Hallo $vorname $nachname!<br>
            <br>
            Vielen Dank f&uuml;r Deine $scnsc-Anmeldung zum<br><br>$conname - $conuntertitel,<br>
            $condatum,<br><br>deren Erhalt wir hiermit best&auml;tigen.<br>
            Bitte &uuml;berweise den Conbeitrag auf das angegebene Konto, erst dann bist Du verbindlich angemeldet.<br>
            <br>
            Empf&auml;nger: Florian M&uuml;llerheim<br>
            IBAN: DE98200411550454665100<br>
            BIC: COBADEHD055 (Comdirekt Bank)<br>
            <br>
            Informationen zur aktuellen Preisstaffel findest du hier:<br>
            <a href='$flyeradresse'>$flyeradresse</a><br>
            <br>
            Eine weitere Mail erh&auml;ltst Du nach Zahlungseingang.<br>
            Sp&auml;testens 2 Wochen vor dem Con verschicken wir auch noch ein Info-Paket,<br>
            welches Regelinformationen, Organisatorisches, die Anfahrtsbeschreibung und<br>
            einiges mehr enth&auml;lt.<br>
            <br>
            Wir sehen uns auf dem $conname !!!<br>
            <br>
            Gr&uuml;&szlig;e,<br>
            Deine Westorga";

          $subject = "$conname - Deine Anmeldung ist bei uns eingegangen";
          $subject = rawurlencode ($subject);
        }
        else
        { //if $anmeldesprache = en
          $text_bestmail_nsc = "Hello $vorname $nachname!<br><br>
Thank you for your registration as NPC for:<br><br>$conname - $conuntertitel,<br>
$condatum,<br><br>
We hereby acknowledge the receipt of your registration.<br>
<br>
Please transfer the event contribution fee to the specified account. Your registration is binding only after transmitting that fee.<br>
<br>
Recipient: Florian M&uuml;llerheim<br>
IBAN: DE98200411550454665100<br>
BIC: COBADEHD055 (Comdirekt Bank)<br>
<br>
Information about the prices can be found here:<br>
<a href='$flyeradresse'>$flyeradresse</a><br>
<br>
You will receive another e-mail after the receipt of your payment.<br>
<br>
<!-- Registriere Dich bitte im Forum (falls noch nicht geschehen). Dort kannst Du Dich voll mit einbringen. Wenn du gerne an der Vorbereitung mit mischen willst, melde Dich gerne bei der NSC Koordination.<br> -->
No later than 2 weeks before the event we will also send you an information package, containing information about the rules, organizational information, travel directions and maybe something more.<br>
<br>
We will see you at the event: &quot;$conname&quot; !!!<br>
<br>
Regards,<br>
Your Western Orga";

          $text_bestmail_sc = "Hello $vorname $nachname!<br><br>
Thank you for your registration as a player character for:<br><br>$conname - $conuntertitel,<br>
$condatum,<br><br>
We hereby acknowledge the receipt of your registration.<br>
<br>
Please transfer the event contribution fee to the specified account. Your registration is binding only after transmitting that fee.<br>
<br>
Recipient: Florian M&uuml;llerheim<br>
IBAN: DE98200411550454665100<br>
BIC: COBADEHD055 (Comdirekt Bank)<br>
<br>
Information about the prices can be found here:<br>
<a href='$flyeradresse'>$flyeradresse</a><br>
<br>
You will receive another e-mail after the receipt of your payment.<br>
<br>
No later than 2 weeks before the event we will also send you an information package, containing information about the rules, organizational information, travel directions and maybe something more.<br>
<br>
We will see you at the event: &quot;$conname&quot; !!!<br>
<br>
Regards,<br>
Your Western Orga";

          $subject = "$conname - Your registration has been validated";
          $subject = rawurlencode ($subject);
        }

        if($scnsc == "NSC")
        {
          print_site(
            "Anmeldungseingang best&auml;tigen",
            "Klicke auf diesen Link, um die Email an den Teilnehmer &uuml;ber Dein Email-Programm zu schreiben:<br><br><a href=\"mailto:$vorname%20$nachname%20%3C$email%3E?subject=$subject\">$email</a><br><br>
            <div align='center'>Diesen Text kannst Du als Nachricht kopieren:</div><br>
            <table border='1' cellpadding='3' cellspacing='0' align='center' width='90%'><tr><td>
            $text_bestmail_nsc
            </td></tr></table>
            <br><br>
            F&uuml;r den Eintrag in der Datenbank:<br><form action='$scriptadresse' method='post'>
             <input type='hidden' name='action' value='bestmaileintrag'>
             <input type='hidden' name='id' value='$personid'>
             <input type='hidden' name='conid' value='$conid'>
             <input type='hidden' name='eintrag' value='1'>
             <input type='Submit' name='anmelden' value='Ja, die Email wurde abgeschickt'></form>

             <form action='$scriptadresse' method='post'>
             <input type='hidden' name='action' value='showperson'>
             <input type='hidden' name='id' value='$personid'>
             <input type='Submit' name='anmelden' value='Nein, die Email wurde NICHT abgeschickt'></form>
          ");
        }
        else
        {
          print_site(
            "Anmeldungseingang best&auml;tigen",
            "Klicke auf diesen Link, um die Email an den Teilnehmer &uuml;ber Dein Email-Programm zu schreiben:<br><br><a href=\"mailto:$vorname%20$nachname%20%3C$email%3E?subject=$subject\">$email</a><br><br>
            <div align='center'>Diesen Text kannst Du als Nachricht kopieren:</div><br>
            <table border='1' cellpadding='3' cellspacing='0' align='center' width='90%'><tr><td>
            $text_bestmail_sc
            </td></tr></table>
            <br><br>
            F&uuml;r den Eintrag in der Datenbank:<br><form action='$scriptadresse' method='post'>
             <input type='hidden' name='action' value='bestmaileintrag'>
             <input type='hidden' name='id' value='$personid'>
             <input type='hidden' name='conid' value='$conid'>
             <input type='hidden' name='eintrag' value='1'>
             <input type='Submit' name='anmelden' value='Ja, die Email wurde abgeschickt'></form>

             <form action='$scriptadresse' method='post'>
             <input type='hidden' name='action' value='showperson'>
             <input type='hidden' name='id' value='$personid'>
             <input type='Submit' name='anmelden' value='Nein, die Email wurde NICHT abgeschickt'></form>
          ");
        }
      }
    }
  }
//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "bestmaileintrag")
  {
      $id = $_REQUEST['id'];
      $eintrag = $_REQUEST['eintrag'];
      $conid = $_REQUEST['conid'];

      $jetzt = date("d.m.y H:i:s T");

      $aendern = "anmeldung_bestaetigt='1',";
      $aendern .= "anmeldebestdatum='$jetzt'";

      if($eintrag == "1")
      {
        $mysql = "UPDATE conmanagement_anmeldungen SET $aendern WHERE id='$id';";
        $mysql = mysqli_query($link,$mysql);
      }

      $num2 = mysqli_affected_rows($link);

      if ($num2>0)
      {
        //DBeintrag erfolgreich
        print_site(
        'Datenbankeintrag erfolgreich',
        "Die Daten wurden vollst&auml;ndig in die Datenbank eingetragen.<br><br><a href='./anmeldeverwaltung.php?action=showlist&id=$conid'>Zur&uuml;ck zur Teilnehmerliste</a>"
        );
      }
      if ($num2<=0)
      {
        print_site(
        'Datenbankeintrag FEHLGESCHLAGEN',
        "Die Daten konnten nicht in die Datenbank eingetragen werden. Sollte das Problem wiederholt auftreten, wende Dich an Jonas."
        );
      }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "zahlungsreminder")
  {
    $mysql = "SELECT id, conid, vorname, nachname, email, scnsc, geldeingang_bestaetigt, anmeldesprache FROM conmanagement_anmeldungen WHERE id='$id'";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    if($num <= 0)
    {
      print_site(
        "Fehler",
        "Es wurde kein Datensatz mit dieser ID gefunden."
      );
    }
    else
    {
      $personid = just_my_result($mysql, 0, "id");
      $conid = just_my_result($mysql, 0, "conid");
      $vorname = just_my_result($mysql, 0, "vorname");
      $nachname = just_my_result($mysql, 0, "nachname");
      $email = just_my_result($mysql, 0, "email");
      $scnsc = just_my_result($mysql, 0, "scnsc");
      $geldeingang_bestaetigt = just_my_result($mysql, 0, "geldeingang_bestaetigt");
      $anmeldesprache = just_my_result($mysql, 0, "anmeldesprache");

      if($geldeingang_bestaetigt == "1")
      { //Wenn der Eingang des Geldes bereits einmal best&auml;tigt wurde.
        print_site(
        'Geldeingang wurde bereits best&auml;tigt',
        "Dem Spieler $vorname $nachname wurde bereits der Geldeingang best&auml;tigt, daher wurde nichts getan oder ge&auml;ndert."
        );
      }
      else
      {
        $mysql2 = "SELECT conname, conuntertitel, condatum, flyeradresse FROM conmanagement_conliste WHERE id='$conid' LIMIT 1";
        $mysql2 = mysqli_query($link,$mysql2);
        $conname = just_my_result($mysql2, 0, "conname");
        $conuntertitel = just_my_result($mysql2, 0, "conuntertitel");
        $condatum = just_my_result($mysql2, 0, "condatum");
        $flyeradresse = just_my_result($mysql2, 0, "flyeradresse");

        //$text = rawurlencode ($text);

        if($anmeldesprache == "de")
        {
          $subject = "$conname - Zahlungserinnerung zum Staffelende";
          $subject = rawurlencode ($subject);

          $text_zahlungsreminder = "Hallo $vorname $nachname!<br>
<br>
Du hast dich f&uuml;r unsere Veranstaltung angemeldet, wir konnten aber noch keinen Zahlungseingang verbuchen.<br>
<br>
Bitte &uuml;berweise deinen Teilnehmerbeitrag f&uuml;r die Con.<br>
$conname - $conuntertitel<br>
$condatum<br>
Falls du der Meinung bist, da&szlig; deine Zahlung bereits eingegangen sein sollte, schreibe uns bitte eine Mail mit dem Datum deiner &Uuml;berweisung.<br>
<br>
Die aktuellen Staffelpreise und weitere Infos unter gibts im Flyer: https://con.westliches-siegel.de
<br>
Kontodaten:<br>
Empf&auml;nger: Florian M&uuml;llerheim <br>
IBAN: DE98200411550454665100 <br>
BIC: COBADEHD055 (Comdirekt Bank)<br>
<br>
Liebe Gr&uuml;&szlig;e<br>
Deine Westorga";          
        }
        else
        { // if $anmeldesprache == "en"
          $subject = "$conname - End of payment period reminder";
          $subject = rawurlencode ($subject);

          $text_zahlungsreminder = "Hello $vorname $nachname!<br>
<br>
You signed up for our event, but we haven't received any payments yet.<br>
<br>
Please transfer your participation fee for the event.<br>
$conname - $conuntertitel<br>
$condatum<br>
If you think that your payment should already have been received, please send us an email with the date of your transfer.<br>
<br>
The event fee of the actual payment period and additional information are listed in our event flyer: https://con.westliches-siegel.de<br>
<br>
Bank account information:<br>
Recipient: Florian M&uuml;llerheim <br>
IBAN: DE98200411550454665100 <br>
BIC: COBADEHD055 (Comdirekt Bank)<br>
<br>
Regards,<br>
Your Western Orga";         
        }

        print_site(
          "Geldeingang best&auml;tigen",
          "Klicke auf diesen Link, um die Email an den Teilnehmer &uuml;ber Dein Email-Programm zu schreiben:<br><br><a href=\"mailto:$vorname%20$nachname%20%3C$email%3E?subject=$subject\">$email</a><br><br>
          <div align='center'>Diesen Text kannst Du als Nachricht kopieren:</div><br>
          <table border='1' cellpadding='3' cellspacing='0' align='center' width='90%'><tr><td>
          $text_zahlungsreminder
          </td></tr></table>
		  <br><br>"
        );
      }
    }
  }
//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

elseif($action == "geldbest")
  {
    $mysql = "SELECT id, conid, vorname, nachname, email, scnsc, geldeingang_bestaetigt, anmeldesprache FROM conmanagement_anmeldungen WHERE id='$id'";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    if($num <= 0)
    {
      print_site(
        "Fehler",
        "Es wurde kein Datensatz mit dieser ID gefunden."
      );
    }
    else
    {
      $personid = just_my_result($mysql, 0, "id");
      $conid = just_my_result($mysql, 0, "conid");
      $vorname = just_my_result($mysql, 0, "vorname");
      $nachname = just_my_result($mysql, 0, "nachname");
      $email = just_my_result($mysql, 0, "email");
      $scnsc = just_my_result($mysql, 0, "scnsc");
      $geldeingang_bestaetigt = just_my_result($mysql, 0, "geldeingang_bestaetigt");
      $anmeldesprache = just_my_result($mysql, 0, "anmeldesprache");

      if($geldeingang_bestaetigt == "1")
      { //Wenn der Eingang des Geldes bereits einmal best&auml;tigt wurde.
        print_site(
        'Geldeingangs-Best&auml;tigungsmail wurde bereits verschickt',
        "Dem Spieler $vorname $nachname wurde bereits der Geldeingang best&auml;tigt, daher wird jetzt <b>keine</b> Email verschickt und nichts an der Datenbank ge&auml;ndert."
        );
      }
      else
      {
        $mysql2 = "SELECT conname, conuntertitel, condatum, flyeradresse FROM conmanagement_conliste WHERE id='$conid' LIMIT 1";
        $mysql2 = mysqli_query($link,$mysql2);
        $conname = just_my_result($mysql2, 0, "conname");
        $conuntertitel = just_my_result($mysql2, 0, "conuntertitel");
        $condatum = just_my_result($mysql2, 0, "condatum");
        $flyeradresse = just_my_result($mysql2, 0, "flyeradresse");

        //$text = rawurlencode ($text);

        if($anmeldesprache == "de")
        {
          $subject = "$conname - Conbeitrag eingegangen";
          $subject = rawurlencode ($subject);

          $text_geldbest_nsc = "Hallo $vorname $nachname!<br>
            <br>
            Vielen Dank f&uuml;r Deine NSC Anmeldung zum<br>$conname - $conuntertitel,<br>$condatum.<br>
            <br>
            Der Conbeitrag ist bei uns eingegangen.<br>
            <!-- <br>
            Registriere dich bitte im Forum (falls noch nicht geschehen). Dort kannst Du Dich voll mit einbringen. Das Passwort f&uuml;r das NSC Board ist &quot;Endgame&quot; (ohne Anf&uuml;hrungszeichen). Wenn du gerne an der Vorbereitung mit mischen willst, melde Dich gerne bei der NSC Koordination.
            <br> -->
            <br>
            Sp&auml;testens 2 Wochen vor dem Con verschicken wir auch noch ein Info-Paket, welches Regelinformationen, Organisatorisches, die Anfahrtsbeschreibung und
            einiges mehr enth&auml;lt.<br>
            <br>
            Wir sehen uns auf dem $conname !!!<br>
            <br>
            Gr&uuml;&szlig;e,<br>
            Deine Westorga";
          $text_geldbest_sc = "Hallo $vorname $nachname!<br>
            <br>
            Vielen Dank f&uuml;r Deine Anmeldung zum<br>$conname - $conuntertitel,<br>$condatum.<br>
            <br>
            Der Conbeitrag ist bei uns eingegangen.<br>
            <!-- <br>Schicke uns bitte (soweit noch nicht geschehen) Charakterbogen, Charaktergeschichte und ein Charakterbild zu.<br><br> -->
            Sp&auml;testens 2 Wochen vor dem Con verschicken wir auch noch ein Info-Paket, welches Regelinformationen, Organisatorisches, die Anfahrtsbeschreibung und
            einiges mehr enth&auml;lt.<br>
            <br>
            Wir sehen uns auf dem $conname !!!<br>
            <br>
            Gr&uuml;&szlig;e,<br>
            Deine Westorga";
        }
        else
        { // if $anmeldesprache == "en"
          $subject = "$conname - Event contribution received";
          $subject = rawurlencode ($subject);

          $text_geldbest_nsc = "Hello $vorname $nachname!<br>
<br>
Thank you for your registration for<br>$conname - $conuntertitel,<br>$condatum.<br>
<br>
We have received your event contribution.<br>
<br>
Please send us your character-sheet(s), Character history and a picture of your character, if you haven't already done so.<br>
<br>
No later than 2 weeks before the event we will also send you an information package,<br>
containing information about the rules, organizational information, travel directions and maybe something more.<br>
<br>
We will see you at the event: &quot;$conname&quot; !!!<br>
<br>
Regards,<br>
Your Western Orga";
          $text_geldbest_sc = "Hello $vorname $nachname!<br>
<br>
Thank you for your registration for<br>$conname - $conuntertitel,<br>$condatum.<br>
<br>
We have received your event contribution.<br>
<!-- <br>
Please send us your character-sheet(s), Character history and a picture of your character, if you haven't already done so.<br>
 -->
<br>
No later than 2 weeks before the event we will also send you an information package,<br>
containing information about the rules, organizational information, travel directions and maybe something more.<br>
<br>
We will see you at the event: &quot;$conname&quot; !!!<br>
<br>
Regards,<br>
Your Western Orga";

        }


        if($scnsc == "NSC")
        {
          print_site(
            "Geldeingang best&auml;tigen",
            "Klicke auf diesen Link, um die Email an den Teilnehmer &uuml;ber Dein Email-Programm zu schreiben:<br><br><a href=\"mailto:$vorname%20$nachname%20%3C$email%3E?subject=$subject\">$email</a><br><br>
            <div align='center'>Diesen Text kannst Du als Nachricht kopieren:</div><br>
            <table border='1' cellpadding='3' cellspacing='0' align='center' width='90%'><tr><td>
            $text_geldbest_nsc
            </td></tr></table>
            <br><br>
            F&uuml;r den Eintrag in der Datenbank:<br><form action='$scriptadresse' method='post'>
             <input type='hidden' name='action' value='geldbesteintrag'>
             <input type='hidden' name='id' value='$personid'>
             <input type='hidden' name='eintrag' value='1'>
             <input type='hidden' name='conid' value='$conid'>
             <input type='Submit' name='anmelden' value='Ja, die Email wurde abgeschickt'></form>
             <form action='$scriptadresse' method='post'>
             <input type='hidden' name='action' value='showperson'>
             <input type='hidden' name='id' value='$personid'>
             <input type='Submit' name='anmelden' value='Nein, die Email wurde NICHT abgeschickt'></form>"
          );
        }
        else
        {
          print_site(
            "Geldeingang best&auml;tigen",
            "Klicke auf diesen Link, um die Email an den Teilnehmer &uuml;ber Dein Email-Programm zu schreiben:<br><br><a href=\"mailto:$vorname%20$nachname%20%3C$email%3E?subject=$subject\">$email</a><br><br>
            <div align='center'>Diesen Text kannst Du als Nachricht kopieren:</div><br>
            <table border='1' cellpadding='3' cellspacing='0' align='center' width='90%'><tr><td>
            $text_geldbest_sc
            </td></tr></table>
            <br><br>
            F&uuml;r den Eintrag in der Datenbank:<br><form action='$scriptadresse' method='post'>
             <input type='hidden' name='action' value='geldbesteintrag'>
             <input type='hidden' name='id' value='$personid'>
             <input type='hidden' name='eintrag' value='1'>
             <input type='hidden' name='conid' value='$conid'>
             <input type='Submit' name='anmelden' value='Ja, die Email wurde abgeschickt'></form>

             <form action='$scriptadresse' method='post'>
             <input type='hidden' name='action' value='showperson'>
             <input type='hidden' name='id' value='$personid'>
             <input type='Submit' name='anmelden' value='Nein, die Email wurde NICHT abgeschickt'></form>"
          );
        }
      }
    }
  }
//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################


  elseif($action == "geldbesteintrag")
  {
      $id = $_REQUEST['id'];
      $eintrag = $_REQUEST['eintrag'];
      $conid = $_REQUEST['conid'];

      $jetzt = date("d.m.y H:i:s T");

      $aendern = "geldeingang_bestaetigt='1',";
      $aendern .= "geldbestdatum='$jetzt'";


      if($eintrag == "1")
      {
        $mysql = "UPDATE conmanagement_anmeldungen SET $aendern WHERE id='$id';";
        $mysql = mysqli_query($link,$mysql);
      }

      $num2 = mysqli_affected_rows($link);

      if ($num2>0)
      {
        //DBeintrag erfolgreich
        print_site(
        'Datenbankeintrag erfolgreich',
        "Die Daten wurden vollst&auml;ndig in die Datenbank eingetragen.<br><br><a href='./anmeldeverwaltung.php?action=showlist&id=$conid'>Zur&uuml;ck zur Teilnehmerliste</a>"
        );
      }
      if ($num2<=0)
      {
        print_site(
        'Datenbankeintrag FEHLGESCHLAGEN',
        "Die Daten konnten nicht in die Datenbank eingetragen werden. Sollte das Problem wiederholt auftreten, wende Dich an Jonas."
        );
      }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "showperson")
  {
    $mysql = "SELECT * FROM conmanagement_anmeldungen WHERE id='$id'";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    if($num <= 0)
    {
      print_site(
        "Fehler",
        "Es wurde kein Datensatz mit dieser ID gefunden."
      );
    }
    else
    {
      $personid = just_my_result($mysql, 0, "id");
      $conid = just_my_result($mysql, 0, "conid");
      $anmeldung = just_my_result($mysql, 0, "anmeldung_bestaetigt");
      $anmeldedatum = just_my_result($mysql, 0, "anmeldedatum");
      $anmeldebestdatum = just_my_result($mysql, 0, "anmeldebestdatum");
      $geldbestdatum = just_my_result($mysql, 0, "geldbestdatum");
      $geldeingang = just_my_result($mysql, 0, "geldeingang_bestaetigt");
      $eingangsbetrag = just_my_result($mysql, 0, "eingangsbetrag");
      $eingangsbetrag = str_replace(".", ",", $eingangsbetrag);
      $eingangsbetrag = str_replace(",00", "", $eingangsbetrag);

      $bauhelfer = just_my_result($mysql, 0, "bauhelfer");

      $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$conid' LIMIT 1";
      $mysql2 = mysqli_query($link,$mysql2);
      $conname = just_my_result($mysql2, 0, "conname");

      $vorname = just_my_result($mysql, 0, "vorname");
      $nachname = just_my_result($mysql, 0, "nachname");
      $strasse = just_my_result($mysql, 0, "strasse");
      $plz = just_my_result($mysql, 0, "plz");
      $ort = just_my_result($mysql, 0, "ort");
      $geburtstag = just_my_result($mysql, 0, "geburtstag");
      $geburtsmonat = just_my_result($mysql, 0, "geburtsmonat");
      $geburtsjahr = just_my_result($mysql, 0, "geburtsjahr");
      $geschlecht = just_my_result($mysql, 0, "geschlecht");
      $schwanger = just_my_result($mysql, 0, "schwanger");
      $vegetarier = just_my_result($mysql, 0, "vegetarier");
      $med_ausbildung = just_my_result($mysql, 0, "med_ausbildung");
      $telefon = just_my_result($mysql, 0, "telefon");
      $handy = just_my_result($mysql, 0, "handy");
      $email = just_my_result($mysql, 0, "email");
      $scnsc = just_my_result($mysql, 0, "scnsc");
      $allergien = just_my_result($mysql, 0, "allergien");
      $krankheiten = just_my_result($mysql, 0, "krankheiten");
      $phobien = just_my_result($mysql, 0, "phobien");
      $kennzeichen = just_my_result($mysql, 0, "kennzeichen");
      $stimmung = just_my_result($mysql, 0, "stimmung");
      $bemerkungen = just_my_result($mysql, 0, "bemerkungen");
      $wiezumcon = just_my_result($mysql, 0, "wiezumcon");
      $charname = just_my_result($mysql, 0, "charname");
      $klasse = just_my_result($mysql, 0, "klasse");
      $contage = just_my_result($mysql, 0, "contage");
      $spielergruppe = just_my_result($mysql, 0, "spielergruppe");
      $siegel = just_my_result($mysql, 0, "siegel");
      $legenden = just_my_result($mysql, 0, "legenden");
      $dieben = just_my_result($mysql, 0, "dieben");
      $magiekundig = just_my_result($mysql, 0, "magiekundig");
      $alchemist = just_my_result($mysql, 0, "alchemist");
      $freundschaft = just_my_result($mysql, 0, "freundschaft");
      $skundig = just_my_result($mysql, 0, "skundig");
      $rituale = just_my_result($mysql, 0, "rituale");
      $artefakte = just_my_result($mysql, 0, "artefakte");
      $speichern = just_my_result($mysql, 0, "speichern");
      $zimmer = just_my_result($mysql, 0, "zimmer");
      $slcharnotes = just_my_result($mysql, 0, "slcharnotes");
      $slpersnotes = just_my_result($mysql, 0, "slpersnotes");
      $freigeschaltet = just_my_result($mysql, 0, "freigeschaltet");
	    $anmeldesprache = just_my_result($mysql, 0, "anmeldesprache");
      
      $sfa1 = just_my_result($mysql, 0, "sfa1");
      $sfa2 = just_my_result($mysql, 0, "sfa2");
      $sfa3 = just_my_result($mysql, 0, "sfa3");
      $sfa4 = just_my_result($mysql, 0, "sfa4");
      $sfa5 = just_my_result($mysql, 0, "sfa5");
      
      if($freigeschaltet == "nein")
      {
        $emailbesttext = "<br><a href='$scriptadresse?action=mailneubest&id=$personid'>Best&auml;tigungs-Link erneut senden</a><br>";
      }

      if($anmeldung == "1")
      {
        $anmeldungimg = "<img src='./western_images/checked.gif'>";
      }
      else
      {
        $anmeldungimg = "<img src='./western_images/unchecked.gif'>";
        $anmeldbesttext = "<a href='$scriptadresse?action=bestmail&id=$personid&conid=$conid'>Empfang der Anmeldung best&auml;tigen</a><br>";
      }

      if($geldeingang == "1")
      {
        $geldeingangimg = "<img src='./western_images/checked.gif'>";
      }
      else
      {
        $geldeingangimg = "<img src='./western_images/unchecked.gif'>";
        $geldbesttext = "<a href='$scriptadresse?action=zahlungsreminder&id=$personid'>Zahlungserinnerung senden</a><br><br><a href='$scriptadresse?action=geldbest&id=$personid'>Geldeingang best&auml;tigen</a><br><a href='$scriptadresse?action=eingangsbetrag&id=$personid'>Geldeingangsbetrag festlegen</a><br>";
      }

      $eingangsbetrag .= " &euro;";

      if($speichern == "1")
      {
        $speichernimg = "<img src='./western_images/checked.gif'>";
      }
      else
      {
        $speichernimg = "<img src='./western_images/unchecked.gif'>";
      }

          if($dieben == "1")
      {
        $diebenimg = "<img src='./western_images/checked.gif'>";
      }
      else
      {
        $diebenimg = "<img src='./western_images/unchecked.gif'>";
      }

          if($magiekundig == "1")
      {
        $magiekundigimg = "<img src='./western_images/checked.gif'>";
      }
      else
      {
        $magiekundigimg = "<img src='./western_images/unchecked.gif'>";
      }

          if($alchemist == "1")
      {
        $alchemistimg = "<img src='./western_images/checked.gif'>";
      }
      else
      {
        $alchemistimg = "<img src='./western_images/unchecked.gif'>";
      }

          if($rituale == "1")
      {
        $ritualeimg = "<img src='./western_images/checked.gif'>";
      }
      else
      {
        $ritualeimg = "<img src='./western_images/unchecked.gif'>";
      }

      if($schwanger == "1")
      {
        $schwangerimg = "<img src='./western_images/checked.gif'>";
      }
      else
      {
        $schwangerimg = "<img src='./western_images/unchecked.gif'>";
      }

      if($geschlecht == "maennlich")
      {
        $geschlecht = "m&auml;nnlich";
      }
	  
	  if($anmeldesprache == "en")
      {
        $anmeldesprache = "Englisch";
      }
	  else
	  {
        $anmeldesprache = "Deutsch";
      }

        $daten = "";

        $daten .= "<tr><td>SL Personenkommentar:</td><td>$slpersnotes</td></tr>";
        $daten .= "<tr><td>SL Charakterkommentar:</td><td>$slcharnotes</td></tr>";
        $daten .= "<tr><td>&nbsp;</td><td>&nbsp;</td></tr>";
        $daten .= "<tr><td>Anmeldungseingang:</td><td>$anmeldedatum</td></tr>";
        $daten .= "<tr><td>Anmeldungsbest&auml;tigung Ausgang:</td><td>$anmeldebestdatum</td></tr>";
        $daten .= "<tr><td>Geldbest&auml;tigung Ausgang:</td><td>$geldbestdatum</td></tr>";
        $daten .= "<tr><td>Anmeldungseingang best&auml;tigt:</td><td>$anmeldungimg</td></tr>";
        $daten .= "<tr><td>Geldeingang best&auml;tigt:</td><td>$geldeingangimg</td></tr>";
        $daten .= "<tr><td>Geldeingangsbetrag:</td><td><a href='$scriptadresse?action=eingangsbetrag&id=$personid'>$eingangsbetrag</a></td></tr>";
        $daten .= "<tr><td>Auf- / Abbauhelfer:</td><td>$bauhelfer</td></tr>";
        $daten .= "<tr><td>Daten speichern:</td><td>$speichernimg</td></tr>";
        $daten .= "<tr><td>Zimmernummer:</td><td>$zimmer</td></tr>";
        $daten .= "<tr><td>Vorname:</td><td>$vorname</td></tr>";
        $daten .= "<tr><td>Nachname:</td><td>$nachname</td></tr>";
        $daten .= "<tr><td>Strasse + HN:</td><td>$strasse</td></tr>";
        $daten .= "<tr><td>PLZ + Ort:</td><td>$plz $ort</td></tr>";
        $daten .= "<tr><td>Geburtstag:</td><td>$geburtstag.$geburtsmonat.$geburtsjahr</td></tr>";
        $daten .= "<tr><td>Geschlecht:</td><td>$geschlecht</td></tr>";
        $daten .= "<tr><td>Schwanger:</td><td>$schwangerimg</td></tr>";
        $daten .= "<tr><td>Besondere Essgewohnheiten:</td><td>$vegetarier</td></tr>";
        $daten .= "<tr><td>Medizinische Ausbildung:</td><td>$med_ausbildung</td></tr>";
        $daten .= "<tr><td>Telefon:</td><td>$telefon</td></tr>";
        $daten .= "<tr><td>Handy:</td><td>$handy</td></tr>";
        $daten .= "<tr><td>Email:</td><td><a href='mailto:$email'>$email</a></td></tr>";
        $daten .= "<tr><td>SC / NSC:</td><td>$scnsc</td></tr>";
        $daten .= "<tr><td>Allergien:</td><td>$allergien</td></tr>";
        $daten .= "<tr><td>Krankheiten:</td><td>$krankheiten</td></tr>";
        $daten .= "<tr><td>Phobien:</td><td>$phobien</td></tr>";
        $daten .= "<tr><td>Kennzeichen:</td><td>$kennzeichen</td></tr>";
        $daten .= "<tr><td>Stimmung:</td><td>$stimmung</td></tr>";
        $daten .= "<tr><td>Bemerkungen:</td><td>$bemerkungen</td></tr>";
        $daten .= "<tr><td>Wie zum Con gekommen:</td><td>$wiezumcon</td></tr>";
		    $daten .= "<tr><td>Anmeldesprache:</td><td>$anmeldesprache</td></tr>";

        if(($scnsc == "SC") || ($scnsc == "Tagesgast"))
        {
          $daten .= "<tr><td>Charaktername:</td><td>$charname</td></tr>";
          $daten .= "<tr><td>Klasse:</td><td>$klasse</td></tr>";
          $daten .= "<tr><td>Contage:</td><td>$contage</td></tr>";
          $daten .= "<tr><td>Spielergruppe:</td><td>$spielergruppe</td></tr>";
          $daten .= "<tr><td>Siegelzugeh&ouml;rigkeit:</td><td>$siegel</td></tr>";
                  $daten .= "<tr><td>Geschichten &amp; Legenden:</td><td>$legenden</td></tr>";
          $daten .= "<tr><td>Dieben:</td><td>$diebenimg</td></tr>";
                  $daten .= "<tr><td>Magiekundig:</td><td>$magiekundigimg</td></tr>";
                  $daten .= "<tr><td>Alchemiekundig:</td><td>$alchemistimg</td></tr>";
          $daten .= "<tr><td>Freundschaft der Elemente:</td><td>$freundschaft</td></tr>";
          $daten .= "<tr><td>Sph&auml;renkundig:</td><td>$skundig</td></tr>";
          $daten .= "<tr><td>Rituale geplant:</td><td>$ritualeimg</td></tr>";
          $daten .= "<tr><td>Besonderheiten / Artefakte:</td><td>$artefakte</td></tr>";
        }
        else
        {
          $daten .= "<tr><td colspan='2'>- Charakterdaten wurden ausgeblendet, weil NSC oder Team -</td></tr>";
        }
        
        $daten .= "<tr><td>Sonderangabe 1:</td><td>$sfa1</td></tr>";
        $daten .= "<tr><td>Sonderangabe 2:</td><td>$sfa2</td></tr>";
        $daten .= "<tr><td>Sonderangabe 3:</td><td>$sfa3</td></tr>";
        $daten .= "<tr><td>Sonderangabe 4:</td><td>$sfa4</td></tr>";
        $daten .= "<tr><td>Sonderangabe 5:</td><td>$sfa5</td></tr>";
        
      if($view == "druckansicht")
      {
        $daten = str_replace("<img src='./western_images/checked.gif'>", "X", $daten);
        $daten = str_replace("<img src='./western_images/unchecked.gif'>", "", $daten);

        print_druckansicht(
          "Daten von $vorname $nachname",
          "<a href='$scriptadresse?action=showperson&id=$id' style='font-weight: bold; color: #0000FF;'>Normale Ansicht</a><br><br><table border='0' width='100%' cellpadding='2' border='1'>
          $daten
          </table>"
        );
      }
      else
      {
        print_site(
          "Daten von $vorname $nachname f&uuml;r das $conname",
          "<a href='$scriptadresse?action=showlist&id=$conid'>Zur&uuml;ck zur $conname-&Uuml;bersicht</a><br><br><a href='$scriptadresse?action=showperson&id=$id&view=druckansicht'>Druckansicht</a><br><br>
$anmeldbesttext$geldbesttext$emailbesttext<br>
<a href='$scriptadresse?action=editperson&id=$id'>Die Daten dieser Anmeldung &auml;ndern</a><br>
<a href='$scriptadresse?action=delete&id=$id'>Diese Anmeldung aus der Datenbank l&ouml;schen</a><br><br>
<table border='0' width='100%' cellpadding='3' border='1'>
          $daten
          </table>"
        );
      }
    }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "editperson")
  {
    $mysql = "SELECT * FROM conmanagement_anmeldungen WHERE id='$id'";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    if($num <= 0)
    {
      print_site(
        "Fehler",
        "Es wurde kein Datensatz mit dieser ID gefunden."
      );
    }
    else
    {
      $personid = just_my_result($mysql, 0, "id");
      $conid = just_my_result($mysql, 0, "conid");
      $anmeldung = just_my_result($mysql, 0, "anmeldung_bestaetigt");
      $anmeldedatum = just_my_result($mysql, 0, "anmeldedatum");
      $geldeingang = just_my_result($mysql, 0, "geldeingang_bestaetigt");
      $bauhelfer = just_my_result($mysql, 0, "bauhelfer");

      $mysql2 = "SELECT conname FROM conmanagement_conliste WHERE id='$conid' LIMIT 1";
      $mysql2 = mysqli_query($link,$mysql2);
      $conname = just_my_result($mysql2, 0, "conname");

      $vorname = just_my_result($mysql, 0, "vorname");
      $nachname = just_my_result($mysql, 0, "nachname");
      $strasse = just_my_result($mysql, 0, "strasse");
      $plz = just_my_result($mysql, 0, "plz");
      $ort = just_my_result($mysql, 0, "ort");
      $geburtstag = just_my_result($mysql, 0, "geburtstag");
      $geburtsmonat = just_my_result($mysql, 0, "geburtsmonat");
      $geburtsjahr = just_my_result($mysql, 0, "geburtsjahr");
      $geschlecht = just_my_result($mysql, 0, "geschlecht");
      $schwanger = just_my_result($mysql, 0, "schwanger");
      $vegetarier = just_my_result($mysql, 0, "vegetarier");
      $med_ausbildung = just_my_result($mysql, 0, "med_ausbildung");
      $telefon = just_my_result($mysql, 0, "telefon");
      $handy = just_my_result($mysql, 0, "handy");
      $email = just_my_result($mysql, 0, "email");
      $scnsc = just_my_result($mysql, 0, "scnsc");
      $allergien = just_my_result($mysql, 0, "allergien");
      $krankheiten = just_my_result($mysql, 0, "krankheiten");
      $phobien = just_my_result($mysql, 0, "phobien");
      $kennzeichen = just_my_result($mysql, 0, "kennzeichen");
      $stimmung = just_my_result($mysql, 0, "stimmung");
      $bemerkungen = just_my_result($mysql, 0, "bemerkungen");
      $wiezumcon = just_my_result($mysql, 0, "wiezumcon");
      $charname = just_my_result($mysql, 0, "charname");
      $klasse = just_my_result($mysql, 0, "klasse");
      $contage = just_my_result($mysql, 0, "contage");
      $spielergruppe = just_my_result($mysql, 0, "spielergruppe");
      $siegel = just_my_result($mysql, 0, "siegel");
      $legenden = just_my_result($mysql, 0, "legenden");
      $dieben = just_my_result($mysql, 0, "dieben");
      $magiekundig = just_my_result($mysql, 0, "magiekundig");
      $alchemist = just_my_result($mysql, 0, "alchemist");
      $freundschaft = just_my_result($mysql, 0, "freundschaft");
      $skundig = just_my_result($mysql, 0, "skundig");
      $rituale = just_my_result($mysql, 0, "rituale");
      $artefakte = just_my_result($mysql, 0, "artefakte");
      $speichern = just_my_result($mysql, 0, "speichern");
      $zimmer = just_my_result($mysql, 0, "zimmer");
      $slcharnotes = just_my_result($mysql, 0, "slcharnotes");
      $slpersnotes = just_my_result($mysql, 0, "slpersnotes");
      $freigeschaltet = just_my_result($mysql, 0, "freigeschaltet");
	    $anmeldesprache = just_my_result($mysql, 0, "anmeldesprache");
    
      $sfa1 = just_my_result($mysql, 0, "sfa1");
      $sfa2 = just_my_result($mysql, 0, "sfa2");
      $sfa3 = just_my_result($mysql, 0, "sfa3");
      $sfa4 = just_my_result($mysql, 0, "sfa4");
      $sfa5 = just_my_result($mysql, 0, "sfa5");    

//############################################################################################

      if($speichern == "1")
      {
        $speichernsel = " checked='checked'";
      }
      else
      {
        $speichernsel = "";
      }

          if($dieben == "1")
      {
        $diebensel = " checked='checked'";
      }
      else
      {
        $diebensel = "";
      }

          if($magiekundig == "1")
      {
        $magiekundigsel = " checked='checked'";
      }
      else
      {
        $magiekundigsel = "";
      }

          if($alchemist == "1")
      {
        $alchemistsel = " checked='checked'";
      }
      else
      {
        $alchemistsel = "";
      }

          if($rituale == "1")
      {
        $ritualesel = " checked='checked'";
      }
      else
      {
        $ritualesel = "";
      }

      if($schwanger == "1")
      {
        $schwangersel = " checked='checked'";
      }
      else
      {
        $schwangersel = "";
      }

      if($freigeschaltet == "ja")
      {
        $freigeschaltetsel = " checked='checked'";
      }
      else
      {
        $freigeschaltetsel = "";
      }

      if($geschlecht == "maennlich")
      {
        $mgeschlechtsel = " selected='selected'";
      }
      elseif($geschlecht == "weiblich")
      {
        $fgeschlechtsel = " selected='selected'";
      }
	  elseif($geschlecht == "divers")
      {
        $dgeschlechtsel = " selected='selected'";
      }
      else
      {
        $kgeschlechtsel = " selected='selected'";
      }


      if($stimmung == "plotten")
      {
        $stimmungpsel = " selected='selected'";
      }
      elseif($stimmung == "kaempfen")
      {
        $stimmungksel = " selected='selected'";
      }
      elseif($stimmung == "zaubern")
      {
        $stimmungzsel = " selected='selected'";
      }
      else
      {
        $stimmungnixsel = " selected='selected'";
      }

      if($med_ausbildung == "Arzt")
      {
        $medausbildungsel1 = " selected='selected'";
      }
      elseif($med_ausbildung == "Rettungsassistent")
      {
        $medausbildungsel2 = " selected='selected'";
      }
      elseif($med_ausbildung == "Mediziner")
      {
        $medausbildungsel3 = " selected='selected'";
      }
      elseif($med_ausbildung == "Sanitaeter")
      {
        $medausbildungsel4 = " selected='selected'";
      }
      elseif($med_ausbildung == "Ersthelfer")
      {
        $medausbildungsel5 = " selected='selected'";
      }
      else
      {
        $medausbildungsel6 = " selected='selected'";
      }

      if($scnsc == "SC")
      {
        $sscnscsel = " selected='selected'";
      }
      elseif($scnsc == "NSC")
      {
        $nscnscsel = " selected='selected'";
      }
      elseif($scnsc == "Tagesgast")
      {
        $tascnscsel = " selected='selected'";
      }
      elseif($scnsc == "Team")
      {
        $tescnscsel = " selected='selected'";
      }
      elseif($scnsc == "Abgesagt")
      {
        $ascnscsel = " selected='selected'";
      }

      if($bauhelfer == "Aufbau")
      {
        $aufbauhelfersel = " selected='selected'";
      }
      elseif($bauhelfer == "Abbau")
      {
        $abbauhelfersel = " selected='selected'";
      }
      elseif($bauhelfer == "Beides")
      {
        $beidesbauhelfersel = " selected='selected'";
      }
      else
      {
        $neinbauhelfersel = " selected='selected'";
      }

      if($speichern == "0")
      {
        $ndatenschutzsel = " selected='selected'";
      }
      else
      {
        $jdatenschutzsel = " selected='selected'";
      }
	  
	  if($anmeldesprache == "en")
      {
        $enanmeldesprachesel = " selected='selected'";
      }
      else
      {
        $deanmeldesprachesel = " selected='selected'";
      }
	  

      $geburtstagsel[$geburtstag] = " selected='selected'";
      $geburtsmonatsel[$geburtsmonat] = " selected='selected'";
      $geburtsjahrsel[$geburtsjahr] = " selected='selected'";

      print_site(
        "&Auml;ndern der Anmeldungsdaten von $vorname $nachname",
        "
        <form action='$scriptadresse' method='post'>
        <table align='center' border='0' width='100%' cellpadding='2' cellspacing='0'>
       <tr>
        <td id='eins' colspan='2'><div align='center'><font size='+1'><hr noshade size='2'><b>Teilnehmerdaten</b><br><hr noshade size='2'></font></div></td>
       </tr>
       <tr>
        <td id='eins' width='30%'>Vorname</td>
        <td id='eins'>
          <input type='text' name='vorname' value='$vorname' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='eins'>Nachname</td>
        <td id='eins'>
          <input type='text' name='nachname' value='$nachname' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='eins'>Stra&szlig;e und Hausnummer</td>
        <td id='eins'>
          <input type='text' name='strasse' value='$strasse' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='eins'>PLZ</td>
        <td id='eins'>
          <input type='text' name='plz' value='$plz' size='10' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='eins'>Ort</td>
        <td id='eins'>
          <input type='text' name='ort' value='$ort' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='eins'>Geburtsdatum</td>
        <td id='eins'>
          <select name='geburtstag'>
            <option value='1'$geburtstagsel[1]>1</option>
            <option value='2'$geburtstagsel[2]>2</option>
            <option value='3'$geburtstagsel[3]>3</option>
            <option value='4'$geburtstagsel[4]>4</option>
            <option value='5'$geburtstagsel[5]>5</option>
            <option value='6'$geburtstagsel[6]>6</option>
            <option value='7'$geburtstagsel[7]>7</option>
            <option value='8'$geburtstagsel[8]>8</option>
            <option value='9'$geburtstagsel[9]>9</option>
            <option value='10'$geburtstagsel[10]>10</option>
            <option value='11'$geburtstagsel[11]>11</option>
            <option value='12'$geburtstagsel[12]>12</option>
            <option value='13'$geburtstagsel[13]>13</option>
            <option value='14'$geburtstagsel[14]>14</option>
            <option value='15'$geburtstagsel[15]>15</option>
            <option value='16'$geburtstagsel[16]>16</option>
            <option value='17'$geburtstagsel[17]>17</option>
            <option value='18'$geburtstagsel[18]>18</option>
            <option value='19'$geburtstagsel[19]>19</option>
            <option value='20'$geburtstagsel[20]>20</option>
            <option value='21'$geburtstagsel[21]>21</option>
            <option value='22'$geburtstagsel[22]>22</option>
            <option value='23'$geburtstagsel[23]>23</option>
            <option value='24'$geburtstagsel[24]>24</option>
            <option value='25'$geburtstagsel[25]>25</option>
            <option value='26'$geburtstagsel[26]>26</option>
            <option value='27'$geburtstagsel[27]>27</option>
            <option value='28'$geburtstagsel[28]>28</option>
            <option value='29'$geburtstagsel[29]>29</option>
            <option value='20'$geburtstagsel[30]>30</option>
            <option value='31'$geburtstagsel[31]>31</option>
          </select>.
          <select name='geburtsmonat'>
            <option value='1'$geburtsmonatsel[1]>1</option>
            <option value='2'$geburtsmonatsel[2]>2</option>
            <option value='3'$geburtsmonatsel[3]>3</option>
            <option value='4'$geburtsmonatsel[4]>4</option>
            <option value='5'$geburtsmonatsel[5]>5</option>
            <option value='6'$geburtsmonatsel[6]>6</option>
            <option value='7'$geburtsmonatsel[7]>7</option>
            <option value='8'$geburtsmonatsel[8]>8</option>
            <option value='9'$geburtsmonatsel[9]>9</option>
            <option value='10'$geburtsmonatsel[10]>10</option>
            <option value='11'$geburtsmonatsel[11]>11</option>
            <option value='12'$geburtsmonatsel[12]>12</option>
          </select>.
          <select name='geburtsjahr'>
            <option value='1920'$geburtsjahrsel[1920]>1920</option>
            <option value='1921'$geburtsjahrsel[1921]>1921</option>
            <option value='1922'$geburtsjahrsel[1922]>1922</option>
            <option value='1923'$geburtsjahrsel[1923]>1923</option>
            <option value='1924'$geburtsjahrsel[1924]>1924</option>
            <option value='1925'$geburtsjahrsel[1925]>1925</option>
            <option value='1926'$geburtsjahrsel[1926]>1926</option>
            <option value='1927'$geburtsjahrsel[1927]>1927</option>
            <option value='1928'$geburtsjahrsel[1928]>1928</option>
            <option value='1929'$geburtsjahrsel[1929]>1929</option>
            <option value='1930'$geburtsjahrsel[1930]>1930</option>
            <option value='1931'$geburtsjahrsel[1931]>1931</option>
            <option value='1932'$geburtsjahrsel[1932]>1932</option>
            <option value='1933'$geburtsjahrsel[1933]>1933</option>
            <option value='1934'$geburtsjahrsel[1934]>1934</option>
            <option value='1935'$geburtsjahrsel[1935]>1935</option>
            <option value='1936'$geburtsjahrsel[1936]>1936</option>
            <option value='1937'$geburtsjahrsel[1937]>1937</option>
            <option value='1938'$geburtsjahrsel[1938]>1938</option>
            <option value='1939'$geburtsjahrsel[1939]>1939</option>
            <option value='1940'$geburtsjahrsel[1940]>1940</option>
            <option value='1941'$geburtsjahrsel[1941]>1941</option>
            <option value='1942'$geburtsjahrsel[1942]>1942</option>
            <option value='1943'$geburtsjahrsel[1943]>1943</option>
            <option value='1944'$geburtsjahrsel[1944]>1944</option>
            <option value='1945'$geburtsjahrsel[1945]>1945</option>
            <option value='1946'$geburtsjahrsel[1946]>1946</option>
            <option value='1947'$geburtsjahrsel[1947]>1947</option>
            <option value='1948'$geburtsjahrsel[1948]>1948</option>
            <option value='1949'$geburtsjahrsel[1949]>1949</option>
            <option value='1950'$geburtsjahrsel[1950]>1950</option>
            <option value='1951'$geburtsjahrsel[1951]>1951</option>
            <option value='1952'$geburtsjahrsel[1952]>1952</option>
            <option value='1953'$geburtsjahrsel[1953]>1953</option>
            <option value='1954'$geburtsjahrsel[1954]>1954</option>
            <option value='1955'$geburtsjahrsel[1955]>1955</option>
            <option value='1956'$geburtsjahrsel[1956]>1956</option>
            <option value='1957'$geburtsjahrsel[1957]>1957</option>
            <option value='1958'$geburtsjahrsel[1958]>1958</option>
            <option value='1959'$geburtsjahrsel[1959]>1959</option>
            <option value='1960'$geburtsjahrsel[1960]>1960</option>
            <option value='1961'$geburtsjahrsel[1961]>1961</option>
            <option value='1962'$geburtsjahrsel[1962]>1962</option>
            <option value='1963'$geburtsjahrsel[1963]>1963</option>
            <option value='1964'$geburtsjahrsel[1964]>1964</option>
            <option value='1965'$geburtsjahrsel[1965]>1965</option>
            <option value='1966'$geburtsjahrsel[1966]>1966</option>
            <option value='1967'$geburtsjahrsel[1967]>1967</option>
            <option value='1968'$geburtsjahrsel[1968]>1968</option>
            <option value='1969'$geburtsjahrsel[1969]>1969</option>
            <option value='1970'$geburtsjahrsel[1970]>1970</option>
            <option value='1971'$geburtsjahrsel[1971]>1971</option>
            <option value='1972'$geburtsjahrsel[1972]>1972</option>
            <option value='1973'$geburtsjahrsel[1973]>1973</option>
            <option value='1974'$geburtsjahrsel[1974]>1974</option>
            <option value='1975'$geburtsjahrsel[1975]>1975</option>
            <option value='1976'$geburtsjahrsel[1976]>1976</option>
            <option value='1977'$geburtsjahrsel[1977]>1977</option>
            <option value='1978'$geburtsjahrsel[1978]>1978</option>
            <option value='1979'$geburtsjahrsel[1979]>1979</option>
            <option value='1980'$geburtsjahrsel[1980]>1980</option>
            <option value='1981'$geburtsjahrsel[1981]>1981</option>
            <option value='1982'$geburtsjahrsel[1982]>1982</option>
            <option value='1983'$geburtsjahrsel[1983]>1983</option>
            <option value='1984'$geburtsjahrsel[1984]>1984</option>
            <option value='1985'$geburtsjahrsel[1985]>1985</option>
            <option value='1986'$geburtsjahrsel[1986]>1986</option>
            <option value='1987'$geburtsjahrsel[1987]>1987</option>
            <option value='1988'$geburtsjahrsel[1988]>1988</option>
            <option value='1989'$geburtsjahrsel[1989]>1989</option>
            <option value='1990'$geburtsjahrsel[1990]>1990</option>
            <option value='1991'$geburtsjahrsel[1991]>1991</option>
            <option value='1992'$geburtsjahrsel[1992]>1992</option>
            <option value='1993'$geburtsjahrsel[1993]>1993</option>
            <option value='1994'$geburtsjahrsel[1994]>1994</option>
            <option value='1995'$geburtsjahrsel[1995]>1995</option>
            <option value='1996'$geburtsjahrsel[1996]>1996</option>
            <option value='1997'$geburtsjahrsel[1997]>1997</option>
            <option value='1998'$geburtsjahrsel[1998]>1998</option>
            <option value='1999'$geburtsjahrsel[1999]>1999</option>
            <option value='2000'$geburtsjahrsel[2000]>2000</option>
            <option value='2001'$geburtsjahrsel[2001]>2001</option>
            <option value='2002'$geburtsjahrsel[2002]>2002</option>
            <option value='2003'$geburtsjahrsel[2003]>2003</option>
            <option value='2004'$geburtsjahrsel[2004]>2004</option>
            <option value='2005'$geburtsjahrsel[2005]>2005</option>
            <option value='2006'$geburtsjahrsel[2006]>2006</option>
            <option value='2007'$geburtsjahrsel[2007]>2007</option>
            <option value='2008'$geburtsjahrsel[2008]>2008</option>
            <option value='2009'$geburtsjahrsel[2009]>2009</option>
            <option value='2010'$geburtsjahrsel[2010]>2010</option>
            <option value='2011'$geburtsjahrsel[2011]>2011</option>
            <option value='2012'$geburtsjahrsel[2012]>2012</option>
            <option value='2013'$geburtsjahrsel[2013]>2013</option>
            <option value='2014'$geburtsjahrsel[2014]>2014</option>
            <option value='2015'$geburtsjahrsel[2015]>2015</option>
			<option value='2016'$geburtsjahrsel[2016]>2016</option>
			<option value='2017'$geburtsjahrsel[2017]>2017</option>
			<option value='2018'$geburtsjahrsel[2018]>2018</option>
			<option value='2019'$geburtsjahrsel[2019]>2019</option>
			<option value='2020'$geburtsjahrsel[2020]>2020</option>
			<option value='2021'$geburtsjahrsel[2021]>2021</option>
			<option value='2022'$geburtsjahrsel[2022]>2022</option>
			<option value='2023'$geburtsjahrsel[2023]>2023</option>			
          </select>
        </td>
       </tr>
       <tr>
        <td id='eins'>Geschlecht</td>

        <td id='eins'>
         <select name='geschlecht'>
          <option value='KeineAuswahl'$kgeschlechtsel>Keine Angabe</option>
          <option value='weiblich'$fgeschlechtsel>Weiblich</option>
          <option value='maennlich'$mgeschlechtsel>M&auml;nnlich</option>
		  <option value='divers'$dgeschlechtsel>Divers</option>
         </select>
        </td>
       </tr>
       <tr>
        <td id='eins'>Schwanger</td>
        <td id='eins'>
         <input type='checkbox' name='schwanger' value='1' tabindex='' $schwangersel>
        </td>
       </tr>
       <tr>
        <td id='eins'>Besondere Essgewohnheiten</td>
        <td id='eins'>
         <input type='text' name='vegetarier' value='$vegetarier' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='eins'>Medizinische Ausbildung</td>

        <td id='eins'>
         <select name='medausbildung'>
          <option value='Arzt'$medausbildungsel1>Arzt</option>
          <option value='Rettungsassistent'$medausbildungsel2>Rettungsassistent</option>
          <option value='Mediziner'$medausbildungsel3>Mediziner / AIP</option>
          <option value='Sanitaeter'$medausbildungsel4>Sanit&auml;ter</option>
          <option value='Ersthelfer'$medausbildungsel5>Ersthelfer</option>
          <option value='keine'$medausbildungsel6>keine</option>
         </select>
        </td>
       </tr>
       <tr>
        <td id='eins'>Telefon</td>
        <td id='eins'>
          <input type='text' name='telefon' value='$telefon' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='eins'>Handy</td>
        <td id='eins'>
          <input type='text' name='handy' value='$handy' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='eins'>Email</td>
        <td id='eins'>
          <input type='text' name='email' value='$email' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='eins'>Freigeschaltet</td>
        <td id='eins'>
         <input type='checkbox' name='freigeschaltet' value='ja' tabindex='' $freigeschaltetsel>
        </td>
       </tr>
       <tr>
        <td id='eins'>SC/NSC</td>

        <td id='eins'>
         <select name='scnsc'>
          <option value='KeineAuswahl'>Bitte w&auml;hlen</option>
          <option value='SC'$sscnscsel>Spieler</option>
          <option value='NSC'$nscnscsel>NSC</option>
          <option value='Tagesgast'$tascnscsel>Tagesgast</option>
          <option value='Team'$tescnscsel>Team</option>
          <option value='Abgesagt'$ascnscsel>Abgesagt</option>

         </select>
        </td>
       </tr>
       <tr>
        <td id='eins'>Allergien</td>
        <td id='eins'>
          <input type='text' name='allergien' value='$allergien' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='eins'>Krankheiten</td>
        <td id='eins'>
          <input type='text' name='krankheiten' value='$krankheiten' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='eins'>Phobien</td>
        <td id='eins'>
          <input type='text' name='phobien' value='$phobien' size='40' class='textfield'>
        </td>
       </tr>
	   <tr>
        <td id='eins'>Anmeldesprache</td>
        <td id='eins'>
          <select name='anmeldesprache'>
          <option value='en'$enanmeldesprachesel>Englisch</option>
          <option value='de'$deanmeldesprachesel>Deutsch</option>
         </select>
        </td>
       </tr>	   
	   
      </table>
      <br>
      <!-- ####################################### ubersichtlichkeitstrenner ####################################### -->
      <br>
       <table align='center' border='0' width='100%' cellpadding='0' cellspacing='0'>
       <tr>
        <td id='eins' colspan='2'>
        <div align='center'><font size='+1'><hr noshade size='2'><b>SL-Notizen</b></font><hr noshade size='2'></div></td>
       </tr>
       <tr>
        <td id='eins' width='30%'>SL Personen- kommentar (250 Zeichen max.)</td>
        <td id='eins'>
          <textarea name='slpersnotes' rows='3' cols='30' wrap='virtual' dir='ltr'>$slpersnotes</textarea>
        </td>
       </tr>
       <tr>
        <td id='eins' width='30%'>SL Charakter- kommentar (250 Zeichen max.)</td>
        <td id='eins'>
          <textarea name='slcharnotes' rows='3' cols='30' wrap='virtual' dir='ltr'>$slcharnotes</textarea>
        </td>
       </tr>
       <tr>
        <td id='eins' width='30%'>Der Teilnehmer schl&auml;ft in diesem Zimmer:</td>
        <td id='eins'>
          <input type='text' name='zimmer' value='$zimmer' size='40' class='textfield'>
        </td>
       </tr>
              <tr>
        <td id='eins'>Bauhelfer</td>
        <td id='eins'>
         <select name='bauhelfer'>
          <option value='Nein'$neinbauhelfersel>Nein</option>
          <option value='Aufbau'$aufbauhelfersel>Aufbau</option>
          <option value='Abbau'$abbauhelfersel>Abbau</option>
          <option value='Beides'$beidesbauhelfersel>Beides</option>
         </select>
        </td>
       </tr>
      </table>
      <br>
      <!-- ####################################### ubersichtlichkeitstrenner ####################################### -->
      <br>
       <table align='center' border='0' width='100%' cellpadding='0' cellspacing='0'>
       <tr>
        <td id='eins' colspan='2'>
        <div align='center'><font size='+1'><hr noshade size='2'><b>Diverses</b></font><hr noshade size='2'></div></td>
       </tr>
       <tr>
        <td id='fehler' width='30%'>Bemerkungen, Erg&auml;nzungen, Unterbringungsw&uuml;nsche, Sonstiges</td>
        <td id='fehler'>
         <textarea name='bemerkungen' rows='3' cols='30' wrap='virtual' dir='ltr'>$bemerkungen</textarea>
        </td>
       </tr>

       <tr>
        <td id='fehler' width='30%'>Wie ist der Teilnehmer auf unsere Cons gekommmen?</td>
        <td id='fehler'>
         <textarea name='wiedraufgekommen' rows='3' cols='30' wrap='virtual' dir='ltr'>$wiezumcon</textarea>
        </td>
       </tr>
       <tr>
         <td id='eins'>Bei Autoanreise: Kennzeichnen</td>
         <td id='eins'>
           <input type='text' name='kennzeichen' value='$kennzeichen' size='40' class='textfield'>
         </td>
       </tr>
       <tr>
        <td id='eins'>Was ist am wichtigsten?</td>
        <td id='eins'>
         <select name='stimmung'>
          <option value=''$stimmungnixsel>-</option>
          <option value='kaempfen'$stimmungksel>K&auml;mpfen</option>
          <option value='plotten'$stimmungpsel>Plotten</option>
          <option value='zaubern'$stimmungzsel>Zaubern</option>
         </select>
        </td>
       </tr>
      </table>
      <br>
      <!-- ####################################### ubersichtlichkeitstrenner ####################################### -->
      <br>
      <table align='center' border='0' width='100%' cellpadding='0' cellspacing='0'>
       <tr>
        <td id='eins' colspan='2'>
        <div align='center'><font size='+1'><hr noshade size='2'><b>Kurzfragebogen zum Charakter</b></font><hr noshade size='2'></div></td>
       </tr>
       <tr>
        <td id='eins' width='30%'>Charakter Name</td>
        <td id='eins'>
          <input type='text' name='charaktername' value='$charname' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='zwei'>Klasse</td>
        <td id='zwei'>
         <input type='text' name='klasse' value='$klasse' size='40' class='textfield'>
        </td>
       </tr>

       <tr>
        <td id='eins'>Contage</td>
        <td id='eins'>
         <input type='text' name='contage' value='$contage' size='10' class='textfield'>
        </td>
       </tr>

       <tr>
        <td id='zwei'>Spielergruppe</td>
        <td id='zwei'>
         <input type='text' name='spielergruppe' value='$spielergruppe' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='zwei'>Siegelzugeh&ouml;rigkeit</td>
        <td id='zwei'>
         <input type='text' name='siegel' value='$siegel' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='eins'>Geschichten &amp; Legenden</td>
        <td id='eins'>
         <input type='text' name='legenden' value='$legenden' size='10' class='textfield'>
        </td>
       </tr>

       <tr>
        <td id='zwei'>Dieben / Warendiebstahl</td>
        <td id='zwei'>
          <input type='checkbox' name='dieben' value='1' tabindex=''$diebensel>
        </td>
       </tr>
             <tr>
        <td id='zwei'>Magiekundig</td>
        <td id='zwei'>
          <input type='checkbox' name='magiekundig' value='1' tabindex=''$magiekundigsel>
        </td>
       </tr>
           <tr>
        <td id='zwei'>Alchemiekundig</td>
        <td id='zwei'>
          <input type='checkbox' name='alchemist' value='1' tabindex=''$alchemistsel>
        </td>
       </tr>
       <tr>
        <td id='zwei'>Freundschaft der Elemente</td>
        <td id='zwei'>
          <input type='text' name='freundschaft' value='$freundschaft' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='zwei'>Sph&auml;renkundig</td>
        <td id='zwei'>
          <input type='text' name='skundig' value='$skundig' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='zwei'>Rituale geplant?</td>
        <td id='zwei'>
          <input type='checkbox' name='rituale' value='1' tabindex=''$ritualesel>
        </td>
       </tr>
       <tr>
        <td id='zwei'>Besonderheiten, Artefakte, Tribes?</td>
        <td id='zwei'>
         <textarea name='artefakte' rows='4' cols='30' wrap='virtual' dir='ltr' id='field_24_3'>$artefakte</textarea>
        </td>
       </tr>
      </table>
      <br><br>
      <!-- ####################################### ubersichtlichkeitstrenner ####################################### -->
      <br>
       <table align='center' border='0' width='100%' cellpadding='0' cellspacing='0'>
       <tr>
        <td id='eins' colspan='2'>
        <div align='center'><font size='+1'><hr noshade size='2'><b>Sonderangaben zur Veranstaltung</b></font><hr noshade size='2'></div></td>
       </tr>
       <tr>
        <td id='fehler' width='30%'>Sonderfeld 1</td>
         <td id='eins'>
           <input type='text' name='sfa1' value='$sfa1' size='40' class='textfield'>
         </td>
       </tr>
       <tr>
        <td id='fehler'>Sonderfeld 2</td>
         <td id='eins'>
           <input type='text' name='sfa2' value='$sfa2' size='40' class='textfield'>
         </td>
       </tr>
       <tr>
        <td id='fehler'>Sonderfeld 3</td>
         <td id='eins'>
           <input type='text' name='sfa3' value='$sfa3' size='40' class='textfield'>
         </td>
       </tr>
       <tr>
        <td id='fehler'>Sonderfeld 4</td>
         <td id='eins'>
           <input type='text' name='sfa4' value='$sfa4' size='40' class='textfield'>
         </td>
       </tr>
       <tr>
        <td id='fehler'>Sonderfeld 5</td>
         <td id='eins'>
           <input type='text' name='sfa5' value='$sfa5' size='40' class='textfield'>
         </td>
       </tr>       
      </table>
      <br>
      <!-- ####################################### ubersichtlichkeitstrenner ####################################### -->
      <br>
      <table align='center' border='0' width='100%' cellpadding='0' cellspacing='0'>
       <tr>
        <td id='eins' colspan='2'>
        <div align='center'><font size='+1'><hr noshade size='2'><b>Rechtliches und Abschlie&szlig;endes</b></font><br><hr noshade size='2'></div></td>
       </tr>
       <tr>
        <td id='eins' width='30%'><br>Teilnehmer ist mit einer Speicherung seiner Daten einverstanden:</td>
        <td id='eins'> 
         <select name='datenschutz'>
          <option value='0'$ndatenschutzsel>Nein</option>
          <option value='1'$jdatenschutzsel>Ja</option>
         </select>
         </td>
       </tr>
       <tr>
        <td id='fehler' colspan='2'><div align='center'>
         <input type='hidden' name='action' value='edit'>
         <input type='hidden' name='id' value='$personid'>
         <br><br><input type='Submit' name='anmelden' value='Anmeldungsdaten &auml;ndern'></form><br><br>
         <b><font color='#FF0000'>ACHTUNG:</font> Hiermit werden ohne weitere Nachfrage die Anmeldungsdaten endg&uuml;ltig ge&auml;ndert, ohne das der Teilnehmer informiert wird.</b>
         </div>
         </td>
       </tr>
      </table>

        "
      );
    }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "edit")
  {
    $id = safe($_REQUEST['id']);
    $conid = safe($_REQUEST['conid']);
    $vorname = safe($_REQUEST['vorname']);
    $bestmailpw = safe($_REQUEST['bestmailpw']);
    $nachname = safe($_REQUEST['nachname']);
    $strasse = safe($_REQUEST['strasse']);
    $plz = safe($_REQUEST['plz']);
    $ort = safe($_REQUEST['ort']);
    $geburtstag = safe($_REQUEST['geburtstag']);
    $geburtsmonat = safe($_REQUEST['geburtsmonat']);
    $geburtsjahr = safe($_REQUEST['geburtsjahr']);
    $geschlecht = safe($_REQUEST['geschlecht']);
    $vegetarier = safe($_REQUEST['vegetarier']);
    $schwanger = safe($_REQUEST['schwanger']);
    $medausbildung = safe($_REQUEST['medausbildung']);
    $telefon = safe($_REQUEST['telefon']);
    $handy = safe($_REQUEST['handy']);
    $email = safe($_REQUEST['email']);
    $scnsc = safe($_REQUEST['scnsc']);
    $allergien = safe($_REQUEST['allergien']);
    $krankheiten = safe($_REQUEST['krankheiten']);
    $phobien = safe($_REQUEST['phobien']);
    $bemerkungen = safe($_REQUEST['bemerkungen']);
    $charaktername = safe($_REQUEST['charaktername']);
    $klasse = safe($_REQUEST['klasse']);
    $contage = safe($_REQUEST['contage']);
    $spielergruppe = safe($_REQUEST['spielergruppe']);
    $siegel = safe($_REQUEST['siegel']);
    $legenden = safe($_REQUEST['legenden']);
    $dieben = safe($_REQUEST['dieben']);
    $magiekundig = safe($_REQUEST['magiekundig']);
    $alchemist = safe($_REQUEST['alchemist']);
    $freundschaft = safe($_REQUEST['freundschaft']);
    $skundig = safe($_REQUEST['skundig']);
    $rituale = safe($_REQUEST['rituale']);
    $artefakte = safe($_REQUEST['artefakte']);
    $agb = safe($_REQUEST['agb']);
    $datenschutz = safe($_REQUEST['datenschutz']);
    $anmelden = safe($_REQUEST['anmelden']);
    $kennzeichen = safe($_REQUEST['kennzeichen']);
    $stimmung = safe($_REQUEST['stimmung']);
    $wiedraufgekommen = safe($_REQUEST['wiedraufgekommen']);
    $slcharnotes = safe($_REQUEST['slcharnotes']);
    $slpersnotes = safe($_REQUEST['slpersnotes']);
    $zimmer = safe($_REQUEST['zimmer']);
    $bauhelfer = safe($_REQUEST['bauhelfer']);
    $freigeschaltet = safe($_REQUEST['freigeschaltet']);
	  $anmeldesprache = safe($_REQUEST['anmeldesprache']);

    $sfa1 = safe($_REQUEST['sfa1']);
    $sfa2 = safe($_REQUEST['sfa2']);
    $sfa3 = safe($_REQUEST['sfa3']);
    $sfa4 = safe($_REQUEST['sfa4']);
    $sfa5 = safe($_REQUEST['sfa5']);

    if($freigeschaltet!="ja")
    {
      $freigeschaltet = "nein";
    }

    $anmeldungen_aendern = "VORNAME='$vorname',";
    $anmeldungen_aendern .= "NACHNAME='$nachname',";
    $anmeldungen_aendern .= "STRASSE='$strasse',";
    $anmeldungen_aendern .= "PLZ='$plz',";
    $anmeldungen_aendern .= "ORT='$ort',";
    $anmeldungen_aendern .= "GEBURTSTAG='$geburtstag',";
    $anmeldungen_aendern .= "GEBURTSMONAT='$geburtsmonat',";
    $anmeldungen_aendern .= "GEBURTSJAHR='$geburtsjahr',";
    $anmeldungen_aendern .= "GESCHLECHT='$geschlecht',";
    $anmeldungen_aendern .= "SCHWANGER='$schwanger',";
    $anmeldungen_aendern .= "VEGETARIER='$vegetarier',";
    $anmeldungen_aendern .= "MED_AUSBILDUNG='$medausbildung',";
    $anmeldungen_aendern .= "TELEFON='$telefon',";
    $anmeldungen_aendern .= "HANDY='$handy',";
    $anmeldungen_aendern .= "EMAIL='$email',";
    $anmeldungen_aendern .= "SCNSC='$scnsc',";
    $anmeldungen_aendern .= "ALLERGIEN='$allergien',";
    $anmeldungen_aendern .= "KRANKHEITEN='$krankheiten',";
    $anmeldungen_aendern .= "PHOBIEN='$phobien',";
    $anmeldungen_aendern .= "KENNZEICHEN='$kennzeichen',";
    $anmeldungen_aendern .= "STIMMUNG='$stimmung',";
    $anmeldungen_aendern .= "BEMERKUNGEN='$bemerkungen',";
    $anmeldungen_aendern .= "WIEZUMCON='$wiedraufgekommen',";
    $anmeldungen_aendern .= "CHARNAME='$charaktername',";
    $anmeldungen_aendern .= "KLASSE='$klasse',";
    $anmeldungen_aendern .= "CONTAGE='$contage',";
    $anmeldungen_aendern .= "SPIELERGRUPPE='$spielergruppe',";
    $anmeldungen_aendern .= "SIEGEL='$siegel',";
        $anmeldungen_aendern .= "LEGENDEN='$legenden',";
    $anmeldungen_aendern .= "DIEBEN='$dieben',";
    $anmeldungen_aendern .= "MAGIEKUNDIG='$magiekundig',";
        $anmeldungen_aendern .= "ALCHEMIST='$alchemist',";
    $anmeldungen_aendern .= "FREUNDSCHAFT='$freundschaft',";
    $anmeldungen_aendern .= "SKUNDIG='$skundig',";
    $anmeldungen_aendern .= "RITUALE='$rituale',";
    $anmeldungen_aendern .= "ARTEFAKTE='$artefakte',";
    $anmeldungen_aendern .= "SPEICHERN='$datenschutz',";
    $anmeldungen_aendern .= "ZIMMER='$zimmer',";
    $anmeldungen_aendern .= "SLCHARNOTES='$slcharnotes',";
    $anmeldungen_aendern .= "SLPERSNOTES='$slpersnotes',";
    $anmeldungen_aendern .= "BAUHELFER='$bauhelfer',";
    $anmeldungen_aendern .= "FREIGESCHALTET='$freigeschaltet',";
	  $anmeldungen_aendern .= "ANMELDESPRACHE='$anmeldesprache',";
    
    $anmeldungen_aendern .= "SFA1='$sfa1',";
    $anmeldungen_aendern .= "SFA2='$sfa2',";
    $anmeldungen_aendern .= "SFA3='$sfa3',";
    $anmeldungen_aendern .= "SFA4='$sfa4',";
    $anmeldungen_aendern .= "SFA5='$sfa5'";    

    $anmeldungen_aendern = str_replace("�", "&auml;", $anmeldungen_aendern);
    $anmeldungen_aendern = str_replace("�", "&ouml;", $anmeldungen_aendern);
    $anmeldungen_aendern = str_replace("�", "&uuml;", $anmeldungen_aendern);
    $anmeldungen_aendern = str_replace("�", "&Auml;", $anmeldungen_aendern);
    $anmeldungen_aendern = str_replace("�", "&Ouml;", $anmeldungen_aendern);
    $anmeldungen_aendern = str_replace("�", "&Uuml;", $anmeldungen_aendern);
    $anmeldungen_aendern = str_replace("�", "&szlig;", $anmeldungen_aendern);
    $bkslashquot = '\"';
    $anmeldungen_aendern = str_replace($bkslashquot, "&quot;", $anmeldungen_aendern);
    $quotzeichen = '"';
    $anmeldungen_aendern = str_replace($quotzeichen, "&quot;", $anmeldungen_aendern);
    $anmeldungen_aendern = str_replace("�", "&euro;", $anmeldungen_aendern);
    $anmeldungen_aendern = str_replace("<", "&lt;", $anmeldungen_aendern);
    $anmeldungen_aendern = str_replace(">", "&gt;", $anmeldungen_aendern);

    connect2mysql();

    $mysql = $mysqltxt = "UPDATE conmanagement_anmeldungen SET $anmeldungen_aendern WHERE id='$id';";
	//$mysqltxt = "";

    $mysql = mysqli_query($link,$mysql);
    $num2 = mysqli_affected_rows($link);

    disconnectmysql();

    if ($num2>0)
    {
      // Aenderung erfolgreich
      print_site(
        '&Auml;nderung erfolgreich',
        "Die &Auml;nderung ist erfolgreich eingetragen worden.<br><br><a href='$scriptadresse?action=showperson&id=$id'>Zur&uuml;ck zur &Uuml;bersicht von $vorname $nachname</a>"
      );
    }
    else
    {
      print_site(
        '&Auml;nderung FEHLGESCHLAGEN!',
        "Die Daten konnten NICHT eingetragen werden. Wenn das Problem l&auml;nger besteht, sag Jonas bescheid.<br>Mysqltext: $mysqltxt<br>"
      );
    }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "delete")
  {
    $mysql = "SELECT vorname, nachname FROM conmanagement_anmeldungen WHERE id='$id'";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $vorname = just_my_result($mysql, 0, "vorname");
    $nachname = just_my_result($mysql, 0, "nachname");

    print_site(
      "L&ouml;schen der Anmeldung von $vorname $nachname",
      "Sollen die Daten von $vorname $nachname wirklich komplett aus der Anmeldungdatenbank gel&ouml;scht werden?<br>(<font color='#FF0000'>Der Teilnehmer wird <b>NICHT</b> &uuml;ber diesen Schritt informiert!</font>)<br><br><form action='$scriptadresse' method='post'><input type='hidden' name='id' value='$id'><input type='hidden' name='action' value='suredelete'><input type='Submit' name='auswahl' value='Ja'></form>&nbsp;<form action='$scriptadresse' method='post'><input type='hidden' name='id' value='$id'><input type='hidden' name='action' value='showperson'><input type='Submit' name='' value='Nein'></form>"
    );
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "suredelete")
  {
    $mysql = "SELECT vorname, nachname, email, conid FROM conmanagement_anmeldungen WHERE id='$id'";
    $mysql = mysqli_query($link,$mysql);

    $vorname = just_my_result($mysql, 0, "vorname");
    $nachname = just_my_result($mysql, 0, "nachname");
    $email = just_my_result($mysql, 0, "email");
    $conid = just_my_result($mysql, 0, "conid");

    $mysql = "SELECT conname, conuntertitel FROM conmanagement_conliste WHERE id='$conid'";
    $mysql = mysqli_query($link,$mysql);

    $conname = just_my_result($mysql, 0, "conname");
    $conuntertitel = just_my_result($mysql, 0, "conuntertitel");

    $mysql = "DELETE FROM conmanagement_anmeldungen WHERE id='$id' LIMIT 1";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_affected_rows($link);

    if($num > 0)
    {
      print_site(
        "Erfolgreich gel&ouml;scht",
        "Die Daten wurden erfolgreich aus der Datenbank gel&ouml;scht.<br><br><a href='./anmeldeverwaltung.php?action=showlist&id=$conid'>Zur&uuml;ck zur Teilnehmerliste</a>"
      );
    }
    else
    {
      print_site(
        "L&ouml;schen FEHLGESCHLAGEN",
        "Der Eintrag konnte nicht aus der Datenbank gel&ouml;scht werden. Wenn dieses Problem wiederholt vorkommt, sag Jonas bescheid."
      );
    }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "eingangsbetrag")
  {
    $mysql = "SELECT vorname, nachname, conid, eingangsbetrag, geldeingang_bestaetigt FROM conmanagement_anmeldungen WHERE id='$id'";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $vorname = just_my_result($mysql, 0, "vorname");
    $nachname = just_my_result($mysql, 0, "nachname");
    $eingangsbetrag = just_my_result($mysql, 0, "eingangsbetrag");

    $eingangsbetrag = str_replace(".", ",", $eingangsbetrag);
    $eingangsbetrag = str_replace(",00", "", $eingangsbetrag);

    $geldeingang = just_my_result($mysql, 0, "geldeingang_bestaetigt");
    $conid = just_my_result($mysql, 0, "conid");

    if($geldeingang == "1")
    {
      $geldeingangsbesttext = "<img src='./western_images/schonbest.gif' alt='Schon best&auml;tigt'><br>&nbsp;";
    }
    else
    {
      $geldeingangsbesttext = "<form action='$scriptadresse' method='post'>
        <input type='hidden' name='action' value='geldbest'>
        <input type='hidden' name='id' value='$id'>
        <input type='Submit' name='anmelden' value='Geldeingang best&auml;tigen'></form>";
    }

    print_site(
      "Geldeingangsbetrag festlegen",
      "Von $vorname $nachname ist bisher folgender Betrag eingegangen:<br>
      <table align='left' width='50%' border='0' cellpadding='2' cellspacing='0'>
      <tr valign='bottom'><td style='vertical-align:bottom;'>
       <form action='$scriptadresse' method='post'>
       <input type='text' name='eingangsbetrag' value='$eingangsbetrag' size='6'>&nbsp;&euro;<br><br>
       <input type='hidden' name='action' value='editeingangsbetrag'>
       <input type='hidden' name='id' value='$id'>
       <input type='hidden' name='conid' value='$conid'>
       <input type='Submit' name='anmelden' value='Eingangsbetrag festlegen'>
       </form>
      </td><td>&nbsp;</td><td valign='bottom'>
       $geldeingangsbesttext
      </td></tr>
      </table>
      <br clear='all'><br>

      <form action='$scriptadresse' method='post'>
      <input type='hidden' name='action' value='showlist'>
      <input type='hidden' name='id' value='$conid'>
      <input type='Submit' name='anmelden' value='Zur&uuml;ck'>
      </form>
      "
    );
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  elseif($action == "editeingangsbetrag")
  {
    $geldeingangsbestopt = $_REQUEST['geldeingangsbestopt'];

    $eingangsbetrag = $_REQUEST['eingangsbetrag'];
    $eingangsbetrag = str_replace(",", ".", $eingangsbetrag);

    $conid = $_REQUEST['conid'];

    $mysql = "SELECT conname, conuntertitel FROM conmanagement_conliste WHERE id='$conid'";
    $mysql = mysqli_query($link,$mysql);

    $conname = just_my_result($mysql, 0, "conname");
    $conuntertitel = just_my_result($mysql, 0, "conuntertitel");

    if($geldeingangsbestopt == "1")
    {
      $jetzt = date("d.m.y H:i:s T");
      $eb_aendern = "EINGANGSBETRAG='$eingangsbetrag',";
      $eb_aendern .= "GELDBESTDATUM='$jetzt'";

      $mysql="SELECT `vorname`, `nachname`, `conid`, `email`, `geldeingang_bestaetigt`  FROM `conmanagement_anmeldungen` WHERE id=$id;";
      $mysql = mysqli_query($link,$mysql);

      $vorname = just_my_result($mysql, 0, "vorname");
      $nachname = just_my_result($mysql, 0, "nachname");
      $email = just_my_result($mysql, 0, "email");
      $conid = just_my_result($mysql, 0, "conid");
      $geldeingang_bestaetigt = just_my_result($mysql, 0, "geldeingang_bestaetigt");

      if($geldeingang_bestaetigt == "1")
      { //Wenn der Eingang des Geldes bereits einmal bestaetigt wurde.
        $fehler = "1";
      }
      else
      {
        $aendern = "geldeingang_bestaetigt='1'";
        $mysql = "UPDATE conmanagement_anmeldungen SET $aendern WHERE id='$id';";
        $mysql = mysqli_query($link,$mysql);

        $num2 = mysqli_affected_rows($link);

        if ($num2>0)
        {
          /*$varspace['to']=$email;
          $varspace['subject']="$conname - $conuntertitel Anmeldebestaetigung";
          $varspace['htmlmessage']="<HTML>
          <BODY TEXT='#000000'>
          Hallo $vorname $nachname!<br>
          <br>
          Vielen Dank f&uuml;r Deine Anmeldung zum $conname - $conuntertitel, $condatum.<br>
          <br>
          Der Conbeitrag ist bei uns eingegangen, damit bist Du vollst&auml;ndig angemeldet<br>
          und hast den Platz sicher.<br>
          Bitte schicke uns (soweit noch nicht geschehen) Charakterbogen, Charaktergeschichte und Charakterbild.<br>
          <br>
          Sp&auml;testens 2 Wochen vor dem Con verschicken wir auch noch ein Info-Paket,<br>
          welches Regelinformationen, Organisatorisches, die Anfahrtsbeschreibung und<br>
          einiges mehr enth&auml;lt.<br>
          <br>
          Wir sehen uns auf dem $conname !!!<br>
          <br>
          Gr&uuml;&szlig;e,<br>
          Deine Westorga
          </BODY>
          </HTML>";
          mail_senden($varspace);
          // Geldeingangs-Bestaetigung an Spieler wurde rausgeschickt

          // Bestaetigungsemail und DBeintrag erfolgreich
		  */
          $Fehler = "2";
        }
        if ($num2<=0)
        {
          $fehler = "3";
        }
      }
    }
    else
    {
      $eb_aendern = "EINGANGSBETRAG='$eingangsbetrag'";
    }

    $mysql = $mysqltxt = "UPDATE conmanagement_anmeldungen SET $eb_aendern WHERE id='$id';";
    $mysql = mysqli_query($link,$mysql);
    $num2 = mysqli_affected_rows($link);

    if ($num2>0)
    {
      // Aenderung erfolgreich
      if($fehler > 1)
      {
        print_site(
          '&Auml;nderung FEHLGESCHLAGEN!',
          "Fehlernummer: $fehler -  Die Daten konnten NICHT eingetragen werden. Wenn das Problem l&auml;nger besteht, sag Jonas bescheid."
        );
      }
      else
      {
        print_site(
          '&Auml;nderung erfolgreich',
          "Die &Auml;nderung ist erfolgreich eingetragen worden.<br><br><a href='$scriptadresse?action=showlist&id=$conid'>Zur&uuml;ck zur $conname &Uuml;bersicht</a>"
        );
      }
    }
    else
    {
      print_site(
        '&Auml;nderung FEHLGESCHLAGEN!',
        "Die Daten konnten NICHT eingetragen werden. Wenn das Problem l&auml;nger besteht, sag Jonas bescheid.<br><br>Fehlertext: $mysqltxt"
      );
    }
  }

//###########################################################################################################################
//########################################### ubersichtlichtkeitstrenner ####################################################
//###########################################################################################################################

  else
  {
    $mysql = "SELECT id, conname FROM conmanagement_conliste";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    $conliste = "";

    for($i=0; $i < $num; $i++)
    {
      $conid = just_my_result($mysql, $i, "id");
      $conname = just_my_result($mysql, $i, "conname");

      $mysql2 = "SELECT id FROM conmanagement_anmeldungen WHERE conid='$conid' AND scnsc='SC'";
      $mysql2 = mysqli_query($link,$mysql2);
      $sczahl = mysqli_num_rows($mysql2);
      $mysql3 = "SELECT id FROM conmanagement_anmeldungen WHERE conid='$conid' AND scnsc='NSC'";
      $mysql3 = mysqli_query($link,$mysql3);
      $nsczahl = mysqli_num_rows($mysql3);
      $mysql4 = "SELECT id FROM conmanagement_anmeldungen WHERE conid='$conid' AND scnsc!='NSC' AND scnsc!='SC'";
      $mysql4 = mysqli_query($link,$mysql4);
      $restzahl = mysqli_num_rows($mysql4);

      $conliste .= "<a href='$scriptadresse?action=showlist&id=$conid'>$conname</a> - SC: $sczahl - NSC: $nsczahl - Andere: $restzahl<br>";
    }

    print_site(
      'Anmeldeverwaltung',
      "Bitte den Con w&auml;hlen:<br><br>$conliste"
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