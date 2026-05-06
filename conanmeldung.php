<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

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

function unsafe($value){

  $value = str_replace("&quot;", '"', $value);
  $value = str_replace("&apos;", "'", $value);

  $value = str_replace("&euro;", "�", $value);

  $value = str_replace("&lt;", "<", $value);
  $value = str_replace("&gt;", ">", $value);

  $value = str_replace('&auml;', 'ä', $value);
  $value = str_replace('&ouml;', 'ö', $value);
  $value = str_replace('&uuml;', 'ü', $value);
  $value = str_replace('&Auml;', 'Ä', $value);
  $value = str_replace('&Ouml;', 'Ö', $value);
  $value = str_replace('&Uuml;', 'Ü', $value);
  $value = str_replace('&szlig;', 'ß', $value);

  $value = str_replace('&Agrave;', 'À', $value);
  $value = str_replace('&agrave;', 'à', $value);
  $value = str_replace('&Aacute;', 'Á', $value);
  $value = str_replace('&aacute;', 'á', $value);
  $value = str_replace('&Acirc;', 'Â', $value);
  $value = str_replace('&acirc;', 'â', $value);

  $value = str_replace('&AElig;', 'Æ', $value);
  $value = str_replace('&aelig;', 'æ', $value);

  $value = str_replace('&Egrave;', 'È', $value);
  $value = str_replace('&egrave;', 'è', $value);
  $value = str_replace('&Eacute;', 'É', $value);
  $value = str_replace('&eacute;', 'é', $value);
  $value = str_replace('&Ecirc;', 'Ê', $value);
  $value = str_replace('&ecirc;', 'ê', $value);

  $value = str_replace('&Igrave;', 'Ì', $value);
  $value = str_replace('&igrave;', 'ì', $value);
  $value = str_replace('&Iacute;', 'Í', $value);
  $value = str_replace('&iacute;', 'í', $value);
  $value = str_replace('&Icirc;', 'Î', $value);
  $value = str_replace('&icirc;', 'î', $value);

  $value = str_replace('&Ntilde;', 'Ñ', $value);
  $value = str_replace('&ntilde;', 'ñ', $value);
  $value = str_replace('&Ograve;', 'Ò', $value);
  $value = str_replace('&ograve;', 'ò', $value);
  $value = str_replace('&Oacute;', 'Ó', $value);
  $value = str_replace('&oacute;', 'ó', $value);
  $value = str_replace('&Ocirc;', 'Ô', $value);
  $value = str_replace('&ocirc;', 'ô', $value);

  $value = str_replace('&Oslash;', 'Ø', $value);
  $value = str_replace('&oslash;', 'ø', $value);

  $value = str_replace('&Ugrave;', 'Ù', $value);
  $value = str_replace('&ugrave;', 'ù', $value);
  $value = str_replace('&Uacute;', 'Ú', $value);
  $value = str_replace('&uacute;', 'ú', $value);
  $value = str_replace('&Ucirc;', 'Û', $value);
  $value = str_replace('&ucirc;', 'û', $value);

  $value = str_replace("&amp;", "&", $value);

  return $value;
}

$scriptadresse = "https://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];

require_once('./i/western_design.php');
require_once('./i/mysql.php');
require_once('./i/sprache.php');
require_once('./mailsenden.php');


$entsperrungspasswort_req = safe($_REQUEST['entsperrungspasswort']);

$id = safe($_REQUEST['id']);
$action = safe($_REQUEST['action']);
$code = safe($_REQUEST['code']);

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
$email_nochmal = safe($_REQUEST['email_nochmal']);
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
$druckpw = safe($_REQUEST['druckpw']);
$anmeldesprache = safe($_REQUEST['anmeldesprache']);

$sfa1 = safe($_REQUEST['sfa1']);
$sfa2 = safe($_REQUEST['sfa2']);
$sfa3 = safe($_REQUEST['sfa3']);
$sfa4 = safe($_REQUEST['sfa4']);
$sfa5 = safe($_REQUEST['sfa5']);

$nscausruestung = safe($_REQUEST['nscausruestung']);
$nscwunsch = safe($_REQUEST['nscwunsch']);

$zufallszahl = safe($_REQUEST['zufallszahl']);
$zufallsbeschreibung = safe($_REQUEST['zufallsbeschreibung']);

if($conid != "")
{
  connect2mysql();
  $mysql = "SELECT * FROM conmanagement_conliste WHERE id='$conid' LIMIT 1";
  $mysql = mysqli_query($link,$mysql);
  $num = mysqli_num_rows($mysql);
  disconnectmysql();
}

if($num <= 0)
{
  $conid = "";
}
else
{
  $conname = just_my_result($mysql, 0, "conname");
  $connamekurz = just_my_result($mysql, 0, "connamekurz");
  $conuntertitel = just_my_result($mysql, 0, "conuntertitel");
  $condatum = just_my_result($mysql, 0, "condatum");
  $conlocationvorsatz = just_my_result($mysql, 0, "conlocationvorsatz");
  $conlocation = just_my_result($mysql, 0, "conlocation");
  $adminemail =  just_my_result($mysql, 0, "adminemail");
  $adminname = "Westorga";
  $flyeradresse = just_my_result($mysql, 0, "flyeradresse");
  $agbadresse = just_my_result($mysql, 0, "agbadresse");
  $confreischaltung = just_my_result($mysql, 0, "confreischaltung");
  $teilsperrung = just_my_result($mysql, 0, "teilsperrung");
  $entsperrungspasswort = just_my_result($mysql, 0, "entsperrungspasswort");

  $sf1 = just_my_result($mysql, 0, "sf1"); //Sonderfelder 1-5 fuer conspezifische Fragen
  $sf2 = just_my_result($mysql, 0, "sf2");
  $sf3 = just_my_result($mysql, 0, "sf3");
  $sf4 = just_my_result($mysql, 0, "sf4");
  $sf5 = just_my_result($mysql, 0, "sf5");
}

$eingegangen = date("d.m.y H:i:s T");

/* Scheinbar nicht mehr benutzt: 07.02.2019. Kann weg?

if($geschlecht == 'weiblich')
{
  $besitz = "ihr";
  $besitz2 = "ihrer";
  $anrede = "Sie";
}
else
{
  $besitz = "sein";
  $besitz2 = "seiner";
  $anrede = "Er";
}
*/

if(($schwanger=='v') || ($schwanger=='1'))
  $xschwanger = "X";
if(($vegetarier=='v') || ($vegetarier=='1'))
  $xvegetarier = "X";
if(($dieben=='v') || ($dieben=='1'))
    $xdieben = "X";
if(($magiekundig=='v') || ($magiekundig=='1'))
    $xmagiekundig = "X";
if(($alchemist=='v') || ($alchemist=='1'))
    $xalchemist = "X";
if(($rituale=='v') || ($rituale=='1'))
    $xrituale = "X";

$datenvollstaendig = "0";

if(($conid != "") && ($action != "mailbest"))
{
  if(($vorname != '') && ($nachname != '') && ($email != '') && ($strasse != '') && ($plz != '') && ($ort != '') && ($geburtsjahr != '') && ($agb == "agbja") && ($datenschutz == "1") && ($scnsc != "KeineAuswahl"))
  {
    $datenvollstaendig = "1";
  }

  if($zufallszahl == "1")
  {
    $darstellung = $s_darstellung1;
  }
  elseif($zufallszahl == "2")
  {
    $darstellung = $s_darstellung2;
  }
  elseif($zufallszahl == "3")
  {
    $darstellung = $s_darstellung3;
  }
  elseif($zufallszahl == "4")
  {
    $darstellung = $s_darstellung4;
  }
  elseif($zufallszahl == "5")
  {
    $darstellung = $s_darstellung5;
  }
  elseif($zufallszahl == "6")
  {
    $darstellung = $s_darstellung6;
  }
  elseif($zufallszahl == "7")
  {
    $darstellung = $s_darstellung7;
  }
  elseif($zufallszahl == "8")
  {
    $darstellung = $s_darstellung8;
  }
  elseif($zufallszahl == "9")
  {
    $darstellung = $s_darstellung9;
  }
  else
  {
    $darstellung = $s_darstellung10;
  }

  if($anmelden == $s_anmeldebutton)
  { //Wenn anmeldung erfolgt
    $zufallsbeschreibung = strtolower(str_replace('\"', '', $zufallsbeschreibung));

    if($zufallsbeschreibung != $darstellung)
    { //wenn Menschentest fehlgeschlagen
      $datenvollstaendig = "0";
      $anmeldefehlertext .= "$s_anmeldefehler_menschentest<br>";
      $fehler_menschentest = "style='border: 3px solid red;'";
    }
    else
    {
      $menschentest_bestanden = 1;
    }

    connect2mysql();
    $mysql = "SELECT freigeschaltet FROM conmanagement_anmeldungen WHERE email='$email' AND conid='$conid' LIMIT 1";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);
    disconnectmysql();

    if($num >= 1)
    {
      $datenvollstaendig = "0";
      //$anmelden = "Fehler";
      $anmeldefehlertext .= "$s_anmeldefehler_email_bereitsreg<br>";
      $fehler_email = "style='border: 3px solid red;'";
      $fehler_email_nochmal = "style='border: 3px solid red;'";
    }
  }

  if($anmelden == $s_anmeldebutton)
  { // Wenn Anmelden gedrueckt wurde
    if($datenvollstaendig == "1")
    {
      connect2mysql();
      $mysql = "SELECT freigeschaltet FROM conmanagement_anmeldungen WHERE email='$email' AND conid='$conid' LIMIT 1";
      $mysql = mysqli_query($link,$mysql);
      $num = mysqli_num_rows($mysql);
      disconnectmysql();

      if($num >= 1)
      {
        $anmelden = "Fehler";

        $anmeldefehlertext .= "$s_anmeldefehler_email_bereitsreg<br>";
        $fehler_email = "style='border: 3px solid red;'";
      }
      else
      {
        $anmeldedatum = date("d.m.Y H:i T");

        $String = "Am Anfang war der Imperator. Am Ende war der Imperator. Dazwischen gab es kurz den Nepumuk und die Fuenfe der Fuenf. 5d5 quasi, also 5, nicht 4, auchnicht 6. 5. Genausowenig 123789 oder 0. Naja, Du wei&szlig;t schon!";
        $Length = 15;
        $String = md5($String);
        $StringLength = strlen($String);
        mt_srand ((double) microtime() * 1000000);
        $Begin = random_int(0,($StringLength-$Length-1));
        $freipw = substr($String, $Begin, $Length);

        $freistatus = "nein";


        $eintrag = "`conid` , `vorname` , `nachname` , `strasse` , `plz` , `ort` , `geburtstag` , `geburtsmonat` , `geburtsjahr` , `geschlecht` , `schwanger` , `vegetarier` , `med_ausbildung` , `telefon` , `handy` , `email` , `scnsc` , `allergien` , `krankheiten` , `phobien` , `kennzeichen` , `stimmung` , `bemerkungen` , `wiezumcon` , `charname` , `klasse` , `contage` , `spielergruppe` , `siegel`, `legenden` , `dieben` , `magiekundig` , `alchemist` , `freundschaft` , `skundig` , `rituale` , `artefakte` , `speichern` , `anmeldedatum`, `sfa1`, `sfa2`, `sfa3`, `sfa4`, `sfa5`, `nscausruestung`, `nscwunsch`, `code`, `freigeschaltet`, `anmeldesprache`";
        $eintrag2 = "'$conid' , '$vorname' , '$nachname' , '$strasse' , '$plz' , '$ort' , '$geburtstag' , '$geburtsmonat' , '$geburtsjahr' , '$geschlecht' , '$schwanger' , '$vegetarier' , '$medausbildung' , '$telefon' , '$handy' , '$email' , '$scnsc' , '$allergien' , '$krankheiten' , '$phobien' , '$kennzeichen' , '$stimmung' , '$bemerkungen' , '$wiedraufgekommen' , '$charaktername' , '$klasse' , '$contage' , '$spielergruppe' , '$siegel', '$legenden' , '$dieben' , '$magiekundig' , '$alchemist' , '$freundschaft' , '$skundig' , '$rituale' , '$artefakte' , '$datenschutz' , '$anmeldedatum', '$sfa1', '$sfa2', '$sfa3', '$sfa4', '$sfa5', '$nscausruestung', '$nscwunsch', '$freipw', '$freistatus', '$anmeldesprache'";

        connect2mysql();

        $mysql = $mysqlqry ="INSERT INTO conmanagement_anmeldungen ($eintrag) VALUES ($eintrag2)";
        $sqltxt = $mysql;
        $mysql = mysqli_query($link,$mysql);

        $id = mysqli_insert_id($link);

        $num2 = mysqli_affected_rows($link);

        disconnectmysql();

        if ($num2>0)
        {
          $varspace['to']=$email;
          $varspace['subject']=$connamekurz.$s_mailbest_mailsubject;
          $varspace['htmlmessage']="<HTML>
          <HEAD>
          <title>$s_mailbest_mailtext1 $vorname $nachname,<br><br>
          $s_mailbest_mailtext2<br><br>
          <a href='$scriptadresse?action=mailbest&id=$id&conid=$conid&code=$freipw'><b><font size='-1'>$scriptadresse?action=mailbest&id=$id&conid=$conid&code=$freipw</font></b></a><br>
          <br>$s_mailbest_mailtext3<br><br>
          </BODY></HTML>";

          //$varspace['subject'] = "=?utf-8?b?".base64_encode((string) unsafe($varspace['subject']))."?=";
          $varspace['subject'] = mb_encode_mimeheader($varspace['subject'], "UTF-8", "B");

          mail_senden($varspace);

          // Anmeldung erfolgreich
          print_site($s_mailbest_pagetitle,$s_mailbest_pagecontent);

          //----------------------------------------------------------------------------

          $varspace['to']=$adminemail;
          $varspace['subject']="$connamekurz: Eingehende Online-Anmeldung.";

          //##########################################################################################################
          //##########################################################################################################
          $hotfix = "<br>Der Corona-Status ist: $sfa1.";
          //##########################################################################################################
          //##########################################################################################################

          $varspace['htmlmessage']="<HTML>
          <HEAD>
          <title>Online-Anmeldung</title>
          </HEAD>
          <BODY TEXT=#000000>
          Und wieder eine neue Anmeldung. Die Daten wurden in der Datenbank gespeichert.$hotfix<br><br>";

		  if($bemerkungen != "")
		  {
			$varspace['htmlmessage'].="Als Bemerkung stand dabei: <i>".$bemerkungen."</i><br><br>";
		  }

		  $varspace['htmlmessage'].="<a href=\"https://".$_SERVER['SERVER_NAME']."/anmeldeverwaltung.php?action=showperson&id=$id\">Die Anmeldung online ansehen.</a>"; 
          $varspace['htmlmessage'].="</BODY></HTML>";

          //$varspace['subject'] = "=?utf-8?b?".base64_encode((string) unsafe($varspace['subject']))."?=";
          $varspace['subject'] = mb_encode_mimeheader($varspace['subject'], "UTF-8", "B");          

          mail_senden($varspace);
          //Email an Orga is raus
        }
        if ($num2<=0)
        {
          print_site($s_anmelderror_title, $s_anmelderror_content);
        }
      } //endif emailsgleich?
    } //endif $datenvollstaendig = 1
    else
    { // Anmeldung fehlgeschlagen
      $anmelden = "Fehler";

      if($vorname == '')
      {
        $anmeldefehlertext .= "$s_anmeldefehler_vorname<br>";
        $fehler_vorname = "style='border: 3px solid red;'";
      }

      if($nachname == '')
      {
        $anmeldefehlertext .= "$s_anmeldefehler_nachname<br>";
        $fehler_nachname = "style='border: 3px solid red;'";
      }

      if($email == '')
      {
        $anmeldefehlertext .= "$s_anmeldefehler_email_leer<br>";
        $fehler_email = "style='border: 3px solid red;'";
      }

      if($email_nochmal == '')
      {
        $anmeldefehlertext .= "$s_anmeldefehler_email_nochmal_leer<br>";
        $fehler_email_nochmal = "style='border: 3px solid red;'";
      }
      else
      {
        if($email_nochmal != $email)
        {
          $anmeldefehlertext .= "$s_anmeldefehler_email_nochmal_falsch<br>";
          $fehler_email_nochmal = "style='border: 3px solid red;'";
        }
      }

      if($strasse == '')
      {
        $anmeldefehlertext .= "$s_anmeldefehler_strasse<br>";
        $fehler_strasse = "style='border: 3px solid red;'";
      }

      if($plz == '')
      {
        $anmeldefehlertext .= "$s_anmeldefehler_plz<br>";
        $fehler_plz = "style='border: 3px solid red;'";
      }

      if($ort == '')
      {
        $anmeldefehlertext .= "$s_anmeldefehler_wohnort<br>";
        $fehler_ort = "style='border: 3px solid red;'";
      }

      if($geburtsjahr == '')
      {
        $anmeldefehlertext .= "$s_anmeldefehler_geburtsjahr<br>";
        $fehler_geburtsjahr = "style='border: 3px solid red;'";
      }

      if($agb != "agbja")
      {
        $anmeldefehlertext .= "$s_anmeldefehler_agb<br>";
        $fehler_agb = "style='border: 3px solid red;'";
      }

      if($datenschutz != "1")
      {
        $anmeldefehlertext .= "$s_anmeldefehler_mailspeichern<br>";
        $fehler_mailspeichern = "style='border: 3px solid red;'";
      }	  

      if($scnsc == "KeineAuswahl")
      {
        $anmeldefehlertext .= "$s_anmeldefehler_scnsc<br>";
        $fehler_scnsc = "style='border: 3px solid red;'";
      }
    }
  } //endif ($anmelden == $s_anmeldebutton)

  if($anmelden != $s_anmeldebutton)
  {
    if($entsperrungspasswort_req == $entsperrungspasswort)
    {
      $teilsperrung = "frei";
    }

    if($confreischaltung == "0")
    {
      print_site($s_anmeldsperre_title,$s_anmeldsperre_content);
    }
    else
    {
       //##############################################################################################
       //##############################################################################################
       //##############################################################################################

       if($dieben){$dieben = "checked";}
       if($magiekundig){$magiekundig = "checked";}
       if($alchemist){$alchemist = "checked";}
       if($rituale){$rituale = "checked";}
       if($schwanger){$schwanger = "checked";}

       //----------------------

       if($datenschutz == "1")
       {
         $jdatenschutzsel = " selected='selected'";
       }
       else
       {
         $ndatenschutzsel = " selected='selected'";
       }

       //----------------------

       if($scnsc == "SC")
       {
         $sscnscsel = " selected='selected'";
       }
       elseif($scnsc == "NSC")
       {
         $nscnscsel = " selected='selected'";
       }
       else
       {
         $uscnscsel = " selected='selected'";
       }

       //----------------------

       if($medausbildung == "Arzt")
       {
         $medausbildungsel1 = " selected='selected'";
       }
       elseif($medausbildung == "Rettungsassistent")
       {
         $medausbildungsel2 = " selected='selected'";
       }
       elseif($medausbildung == "Mediziner")
       {
         $medausbildungsel3 = " selected='selected'";
       }
       elseif($medausbildung == "Sanitaeter")
       {
         $medausbildungsel4 = " selected='selected'";
       }
       elseif($medausbildung == "Ersthelfer")
       {
         $medausbildungsel5 = " selected='selected'";
       }
       else
       {
         $medausbildungsel6 = " selected='selected'";
       }

       //----------------------

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

       //----------------------

       if($geschlecht == "maennlich")
       {
         $mgeschlechtsel = " selected='selected'";
       }
       elseif($geschlecht == "weiblich")
       {
         $wgeschlechtsel = " selected='selected'";
       }
	   elseif($geschlecht == "divers")
       {
         $dgeschlechtsel = " selected='selected'";
       }
       else
       {
         $kgeschlechtsel = " selected='selected'";
       }

       //----------------------

	   if($geburtsjahr == "")
	   {
		   $geburtsjahr = "1980";
	   }

       $geburtstagsel[$geburtstag] = " selected='selected'";
       $geburtsmonatsel[$geburtsmonat] = " selected='selected'";
       $geburtsjahrsel[$geburtsjahr] = " selected='selected'";

       //----------------------

       if($agb == "agbja")
       {
         $jagbsel = " selected='selected'";
       }
       else
       {
         $nagbsel = " selected='selected'";
       }

       //##############################################################################################
       //##############################################################################################
       //##############################################################################################


       $kurzfragebogenzumchar = "<br><table align='center' border='0' width='100%' cellpadding='2' cellspacing='0'>
       <tr>
        <td id='eins' colspan='2'>
        <div align='center'><font size='+1'><hr noshade size='2'><b>$s_kurzfragebogenzumchar_ueberschrift</b></font><br>$s_kurzfragebogenzumchar_einleitung<br><hr noshade size='2'></div></td>
       </tr>
       <tr>
        <td id='eins' width='30%'>$s_kurzfragebogenzumchar_cname</td>
        <td id='eins'>
          <input type='text' name='charaktername' value='$charaktername' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='zwei'>$s_kurzfragebogenzumchar_klasse</td>
        <td id='zwei'>
         <input type='text' name='klasse' value='$klasse' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='eins'>$s_kurzfragebogenzumchar_contage</td>
        <td id='eins'>
         <input type='text' name='contage' value='$contage' size='10' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='zwei'>$s_kurzfragebogenzumchar_spielergruppe</td>
        <td id='zwei'>
         <input type='text' name='spielergruppe' value='$spielergruppe' size='40' class='textfield'>
        </td>
       </tr>
           <tr>
        <td id='zwei'>$s_kurzfragebogenzumchar_siegel</td>
        <td id='zwei'>
         <input type='text' name='siegel' value='$siegel' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='eins'>$s_kurzfragebogenzumchar_gundl</td>
        <td id='eins'>
         <input type='text' name='legenden' value='$legenden' size='10' class='textfield'>
        </td>
       </tr>

       <tr>
        <td id='zwei'>$s_kurzfragebogenzumchar_dieben</td>
        <td id='zwei'>
          <input type='checkbox' name='dieben' value='1' tabindex='' $dieben>
        </td>
       </tr>
           <tr>
        <td id='zwei'>$s_kurzfragebogenzumchar_magiekundig</td>
        <td id='zwei'>
          <input type='checkbox' name='magiekundig' value='1' tabindex='' $magiekundig>
        </td>
       </tr>
           <tr>
        <td id='zwei'>$s_kurzfragebogenzumchar_alchemiekundig</td>
        <td id='zwei'>
          <input type='checkbox' name='alchemist' value='1' tabindex='' $alchemist>
        </td>
       </tr>
       <tr>
        <td id='zwei'>$s_kurzfragebogenzumchar_fde</td>
        <td id='zwei'>
          <input type='text' name='freundschaft' value='$freundschaft' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='zwei'>$s_kurzfragebogenzumchar_spaehrenk</td>
        <td id='zwei'>
          <input type='text' name='skundig' value='$skundig' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='zwei'>$s_kurzfragebogenzumchar_rituale</td>
        <td id='zwei'>
          <input type='checkbox' name='rituale' value='1' tabindex='' $rituale> $s_kurzfragebogenzumchar_rituale_text
        </td>
       </tr>
       <tr>
        <td id='zwei'>$s_kurzfragebogenzumchar_artefakte</td>
        <td id='zwei'>
          <textarea name='artefakte' rows='4' cols='30' wrap='virtual' dir='ltr' id='field_24_3'>$artefakte</textarea>
        </td>
       </tr>
       </table>
      <br><br>";

      //##########################################################################################

      $nsctext = "<br><table align='center' border='0' width='100%' cellpadding='2' cellspacing='0'>
      <tr>
        <td id='eins' colspan='2'>
        <div align='center'><font size='+1'><hr noshade size='2'><b>$s_nsctext_ueberschrift</b></font><br>$s_nsctext_einleitung<br><hr noshade size='2'></div></td>
       </tr>
       <tr>
        <td id='eins' width='30%'>$s_nsctext_ausruestung</td>
        <td id='eins'>
          <input type='text' name='nscausruestung' value='$nscausruestung' size='40' class='textfield'><br>$s_nsctext_ausruestung_text
        </td>
       </tr>
       <tr>
        <td id='eins' width='30%'>$s_nsctext_wunschrolle</td>
        <td id='eins'>
          <input type='text' name='nscwunsch' value='$nscwunsch' size='40' class='textfield'><br>$s_nsctext_wunschrolle_text
        </td>
       </tr>
      </table>
      <br><br>";

      if($teilsperrung == 'scvoll')
      {
        $scnscauswahlfeld = "<td colspan='2' bgcolor='#000000'></td></tr><tr><td id='eins'>$s_teilnehmerdaten_sc/$s_teilnehmerdaten_nsc*</td><td id='eins'><select name='scnsc'><option value='NSC' $nscnscsel>$s_teilnehmerdaten_nsc</option></select><br>$s_teilsperrung_keinewahl $s_teilsperrung_scvoll</td></tr><tr><td colspan='2' bgcolor='#000000'></td>";
        $teilsperrunginfotext = "<br><br><b><font size='+1' color='#FF2020'>$s_teilsperrung_scvoll2</font></b><br>";
        $kurzfragebogenzumchar = "";
      }
      elseif($teilsperrung == 'nscvoll')
      {
        $scnscauswahlfeld = "<td colspan='2' bgcolor='#000000'></td></tr><tr><td id='eins'>$s_teilnehmerdaten_sc/$s_teilnehmerdaten_nsc*</td><td id='eins'><select name='scnsc'><option value='SC' $sscnscsel>$s_teilnehmerdaten_spieler</option></select><br>$s_teilsperrung_keinewahl $s_teilsperrung_nscvoll</td></tr><tr><td colspan='2' bgcolor='#000000'></td>";
        $teilsperrunginfotext = "<br><br><b><font size='+1' color='#FF2020'>$s_teilsperrung_nscvoll2</font></b><br>";
        $nsctext = "";
      }
      else
      {
        $scnscauswahlfeld = "<td id='eins'>$s_teilnehmerdaten_sc/$s_teilnehmerdaten_nsc*</td><td id='eins'><select name='scnsc' $fehler_scnsc><option value='KeineAuswahl' $uscnscsel>$s_teilnehmerdaten_waehlen</option><option value='SC' $sscnscsel>$s_teilnehmerdaten_spieler</option><option value='NSC' $nscnscsel>$s_teilnehmerdaten_nsc</option></select></td>";
      }

      //menschentest ########################################################################################


      if($menschentest_bestanden)
      {
        $zufall = $zufallszahl;
        $zufallbeschreibungvalue = $zufallsbeschreibung;
      }
      else
      {
        do { //Schleife wird mindestens einmal ausgefuehrt, hoechstens so lange bis ein neues tier gefunden ist
          mt_srand ( (double)microtime () * 1000000 );
          $zufall = round(random_int (1,10));
        } while ($zufall == $zufallszahl);
      }

      if($zufall == "1")
      {
        $zufallsbild = "./western_images/rndpics/1.jpg";
      }
      elseif($zufall == "2")
      {
        $zufallsbild = "./western_images/rndpics/2.jpg";
      }
      elseif($zufall == "3")
      {
        $zufallsbild = "./western_images/rndpics/3.jpg";
      }
      elseif($zufall == "4")
      {
        $zufallsbild = "./western_images/rndpics/4.jpg";
      }
      elseif($zufall == "5")
      {
        $zufallsbild = "./western_images/rndpics/5.jpg";
      }
      elseif($zufall == "6")
      {
        $zufallsbild = "./western_images/rndpics/6.jpg";
      }
      elseif($zufall == "7")
      {
        $zufallsbild = "./western_images/rndpics/7.jpg";
      }
      elseif($zufall == "8")
      {
        $zufallsbild = "./western_images/rndpics/8.jpg";
      }
      elseif($zufall == "9")
      {
        $zufallsbild = "./western_images/rndpics/9.jpg";
      }
      else
      {
        $zufallsbild = "./western_images/rndpics/10.jpg";
      }

      $menschentest = "<br><br>
      <table align='center' border='0' width='100%' cellpadding='2' cellspacing='0'>
       <tr>
        <td id='eins' colspan='2'>
        <div align='center'><font size='+1'><hr noshade size='2'><b>$s_menschentest_ueberschrift</b></font><br><hr noshade size='2'></div></td>
       </tr>
       <tr>
        <td id='eins' colspan='2'>$s_menschentest_1 <a href='./tierliste.php' target='_new'>$s_menschentest_2</td>
       </tr>
       <tr>
        <td id='zwei' width='30%'><br>$s_menschentest_3</td>
        <td id='zwei'>
         <input type='text' name='zufallsbeschreibung' value='$zufallbeschreibungvalue' size='40' class='textfield' $fehler_menschentest>&nbsp;*
        </td>
       </tr>
       <tr>
        <td id='eins' colspan='2'>
          <br><input type='hidden' name='zufallszahl' value='$zufall'><div align='center'><img src='$zufallsbild' alt='' border='0'></div>
        </td>
       </tr>
      </table><br>
      <br>
      ";
//menschentest ende ###################################################################################

//Sonderfelder fuer conspezifische Fragen #############################################################

      if(($sf1 != "") || ($sf2 != "") || ($sf3 != "") || ($sf4 != "") || ($sf5 != ""))
      {
        $sonderfeldtext = "      <br><table align='center' border='0' width='100%' cellpadding='2' cellspacing='0'>
        <tr>
          <td id='eins' colspan='2'>
          <div align='center'><font size='+1'><hr noshade size='2'><b>$s_sonderfelder_ueberschrift</b><br><hr noshade size='2'></font></div></td>
         </tr>";

        if($sf1 != "")
        {
          $sonderfeldtext .= "<tr>
          $sf1
          </tr>";
        }

        if($sf2 != "")
        {
          $sonderfeldtext .= "<tr>
          $sf2
          </tr>";
        }

        if($sf3 != "")
        {
          $sonderfeldtext .= "<tr>
          $sf3
          </tr>";
        }

        if($sf4 != "")
        {
          $sonderfeldtext .= "<tr>
          $sf4
          </tr>";
        }

        if($sf5 != "")
        {
          $sonderfeldtext .= "<tr>
          $sf5
          </tr>";
        }

        $sonderfeldtext .= "</table>
        <br><br>";
      }

//Sonderfelder ende ###################################################################################

      if($anmeldefehlertext != "")
      {
        $anmeldefehlertext = "<div class='loginfehler'><b>$s_anmeldefehler_hinweistext:</b><br><br>".$anmeldefehlertext."<br><b>$s_anmeldefehler_ende</b></div><br>";
      }

      print_site(
      "$s_anmeldebogen_ueberschrift<br>$conname - $conuntertitel",
      //$condatum $conlocationvorsatz $conlocation<br>
      "$condatum &bull; $conlocation<br>
      <a href='$flyeradresse' target='_new'>$s_anmeldebogen_flyersehen</a><br><b><div align='center'>$teilsperrunginfotext</div></b><br>&nbsp;

      $anmeldefehlertext

      <form action='$scriptadresse?conid=$conid' method='post'>
      <table align='center' border='0' width='100%' cellpadding='2' cellspacing='0'>
       <tr>
        <td id='eins' colspan='2'><div align='center'><font size='+1'><hr noshade size='2'><b>$s_teilnehmerdaten_ueberschrift</b><br><hr noshade size='2'></font></div></td>
       </tr>
       <tr>
        <td id='eins' width='30%'>$s_teilnehmerdaten_vorname*</td>
        <td id='eins'>
          <input type='text' name='vorname' value='$vorname' size='40' class='textfield' $fehler_vorname>
        </td>
       </tr>
       <tr>
        <td id='eins'>$s_teilnehmerdaten_nachname*</td>
        <td id='eins'>
          <input type='text' name='nachname' value='$nachname' size='40' class='textfield' $fehler_nachname>
        </td>
       </tr>
       <tr>
        <td id='eins'>$s_teilnehmerdaten_anschrift*</td>
        <td id='eins'>
          <input type='text' name='strasse' value='$strasse' size='40' class='textfield' $fehler_strasse>
        </td>
       </tr>
       <tr>
        <td id='eins'>$s_teilnehmerdaten_plz*</td>
        <td id='eins'>
          <input type='text' name='plz' value='$plz' size='10' class='textfield' $fehler_plz>
        </td>
       </tr>
       <tr>
        <td id='eins'>$s_teilnehmerdaten_ort*</td>
        <td id='eins'>
          <input type='text' name='ort' value='$ort' size='40' class='textfield' $fehler_ort>
        </td>
       </tr>
       <tr>
        <td id='eins'>$s_teilnehmerdaten_geburt*</td>
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
        <td id='eins'>$s_teilnehmerdaten_geschlecht</td>
        <td id='eins'>
         <select name='geschlecht'>
          <option value='-' $kgeschlechtsel>$s_teilnehmerdaten_geschlecht_k</option>
          <option value='weiblich' $wgeschlechtsel>$s_teilnehmerdaten_geschlecht_w</option>
          <option value='maennlich' $mgeschlechtsel>$s_teilnehmerdaten_geschlecht_m</option>
		  <option value='divers' $dgeschlechtsel>$s_teilnehmerdaten_geschlecht_d</option>
         </select>
        </td>
       </tr>".
       /*
	   <tr>
        <td id='eins'>$s_teilnehmerdaten_schwanger</td>
        <td id='eins'>
         <input type='checkbox' name='schwanger' value='1' tabindex='' $schwanger>
        </td>
       </tr>
	   */
       "<tr>
        <td id='eins'>$s_teilnehmerdaten_essgewohnheiten</td>
        <td id='eins'>
         <input type='text' name='vegetarier' value='$vegetarier' size='40' class='textfield'><br>
         <font size='-2'>$s_teilnehmerdaten_essgewohnheiten2</font>
        </td>
       </tr>
       <tr>
        <td id='eins'>$s_teilnehmerdaten_medausbildung</td>
        <td id='eins'>
         <select name='medausbildung'>
          <option value='Arzt' $medausbildungsel1>$s_teilnehmerdaten_medausbildung_arzt*</option>
          <option value='Rettungsassistent' $medausbildungsel2>$s_teilnehmerdaten_medausbildung_rettungsassi*</option>
          <option value='Mediziner' $medausbildungsel3>$s_teilnehmerdaten_medausbildung_mediziner*</option>
          <option value='Sanitaeter' $medausbildungsel4>$s_teilnehmerdaten_medausbildung_sani</option>
          <option value='Ersthelfer' $medausbildungsel5>$s_teilnehmerdaten_medausbildung_ersthelfer</option>
          <option value='keine' $medausbildungsel6>$s_teilnehmerdaten_medausbildung_keine</option>
         </select><br>
         <font size='-1'>$s_teilnehmerdaten_medausbildung_wirmeldenuns</font>
        </td>
       </tr>
       <tr>
        <td id='eins'>$s_teilnehmerdaten_telefon</td>
        <td id='eins'>
          <input type='text' name='telefon' value='$telefon' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='eins'>$s_teilnehmerdaten_handy</td>
        <td id='eins'>
          <input type='text' name='handy' value='$handy' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='eins'>$s_teilnehmerdaten_email*</td>
        <td id='eins'>
          <input type='text' name='email' value='$email' size='40' class='textfield' $fehler_email><br>
          <font size='-2'>$s_teilnehmerdaten_email3</font>
        </td>
       </tr>
       <tr>
        <td id='eins'>$s_teilnehmerdaten_email_nochmal*</td>
        <td id='eins'>
          <input type='text' name='email_nochmal' value='$email_nochmal' size='40' class='textfield' $fehler_email_nochmal><br>
          <font size='-2'>$s_teilnehmerdaten_email_nochmal2</font>
        </td>
       </tr>
       <tr>
$scnscauswahlfeld
       </tr>".
       /*
	   <tr>
        <td id='eins'>$s_teilnehmerdaten_allergien</td>
        <td id='eins'>
          <input type='text' name='allergien' value='$allergien' size='40' class='textfield'><br>
          <font size='-2'>$s_teilnehmerdaten_allergien2</font>
        </td>
       </tr>
       <tr>
        <td id='eins'>$s_teilnehmerdaten_krankheiten</td>
        <td id='eins'>
          <input type='text' name='krankheiten' value='$krankheiten' size='40' class='textfield'>
        </td>
       </tr>
       <tr>
        <td id='eins'>$s_teilnehmerdaten_phobien</td>
        <td id='eins'>
          <input type='text' name='phobien' value='$phobien' size='40' class='textfield'>
        </td>
       </tr>
	   */
	   "
       <tr>
         <td colspan='2'>
           <br><b>$s_teilnehmerdaten_pflichtangaben</b>
         </td>
       </tr>
      </table>
      <br>
      <!-- ####################################### Uebersichtlichkeitstrenner ####################################### -->
      <br>
      <table align='center' border='0' width='100%' cellpadding='2' cellspacing='0'>
       <tr>
        <td id='eins' colspan='2'>
        <div align='center'><font size='+1'><hr noshade size='2'><b>$s_diverses_ueberschrift</b><br><hr noshade size='2'></font></div></td>
       </tr>
       <tr>
        <td id='fehler' width='30%'>$s_diverses_bemerkungen</td>
        <td id='fehler'>
         <textarea name='bemerkungen' rows='3' cols='30' wrap='virtual' dir='ltr'>$bemerkungen</textarea>
        </td>
       </tr>

       <tr>
        <td id='fehler' width='30%'>$s_diverses_wiedraufgekommen</td>
        <td id='fehler'>
         <textarea name='wiedraufgekommen' rows='3' cols='30' wrap='virtual' dir='ltr'>$wiedraufgekommen</textarea>
        </td>
       </tr>

       <tr>
        <td id='eins'>$s_diverses_kennzeichen</td>
        <td id='eins'>
          <input type='text' name='kennzeichen' value='$kennzeichen' size='40' class='textfield'>
        </td>
       </tr>

       <tr>
        <td id='eins'>$s_diverses_stimmungsbild</td>
        <td id='eins'>
         <select name='stimmung'>
          <option value='' $stimmungnixsel>$s_teilnehmerdaten_waehlen</option>
          <option value='kaempfen' $stimmungksel>$s_diverses_stimmung_k</option>
          <option value='plotten' $stimmungpsel>$s_diverses_stimmung_p</option>
          <option value='zaubern' $stimmungzsel>$s_diverses_stimmung_z</option>
         </select>
        </td>
       </tr>
      </table>
      <br>
      <!-- ####################################### Uebersichtlichkeitstrenner ####################################### -->
      $menschentest
      <!-- ####################################### Uebersichtlichkeitstrenner ####################################### -->
      $kurzfragebogenzumchar
      <!-- ####################################### Uebersichtlichkeitstrenner ####################################### -->
      $nsctext
      <!-- ####################################### Uebersichtlichkeitstrenner ####################################### -->
      $sonderfeldtext
      <!-- ####################################### Uebersichtlichkeitstrenner ####################################### -->
      <br>
      <table align='center' border='0' width='100%' cellpadding='2' cellspacing='0'>
       <tr>
        <td id='eins' colspan='2'>
        <div align='center'><font size='+1'><hr noshade size='2'><b>$s_abschluss_ueberschrift</b></font><br><hr noshade size='2'></div></td>
       </tr>
       <tr>
        <td id='fehler' colspan='2'>$s_abschluss_agb1 <a href='$agbadresse' target='_new'>$s_abschluss_agb2
         <select name='agb' $fehler_agb>
          <option value='agbnein' $nagbsel>$s_abschluss_agb_n</option>
          <option value='agbja' $jagbsel>$s_abschluss_agb_j</option>
         </select><br>
         <b>$s_abschluss_agb3</b>
         </td>
       </tr>
       <tr>
        <td id='fehler' colspan='2'><br>$s_abschluss_mailspeichern1<br>
        $s_abschluss_mailspeichern2<br>
         <select name='datenschutz' $fehler_mailspeichern>
          <option value='0' $ndatenschutzsel>$s_abschluss_mailspeichern_n</option>
          <option value='1' $jdatenschutzsel>$s_abschluss_mailspeichern_j</option>
         </select>
         </td>
       </tr>
       <tr>
        <td id='fehler' colspan='2'><br>$s_abschluss_flyerverweis <a href='$flyeradresse' target='_new'>$s_abschluss_flyer</a>.<br><br>$s_abschluss_beifragen <a href='mailto:$adminemail'>$adminemail</a>
         </td>
       </tr>
       <tr>
        <td id='fehler' colspan='2'><div align='center'><br><font size='+1'>$s_abschluss_anmeldverbind</font><br>
         <input type='hidden' name='conid' value='$conid'>
         <input type='hidden' name='anmeldesprache' value='".$GLOBALS["s_lang"]."'>
         <input type='Submit' name='anmelden' value='$s_anmeldebutton'></form><br>
         <font size='-1'>$s_abschluss_kurzdruck</font>
         </div>
         </td>
       </tr>
      </table>
      "
      );
    }
  } // endif
}
else
{
  if(($action == "mailbest") && ($conid != ""))
  {
    if($id != "")
    {
      connect2mysql();
      $mysql = "SELECT vorname, nachname, freigeschaltet, code FROM conmanagement_anmeldungen WHERE id='$id' LIMIT 1";
      $mysql = mysqli_query($link,$mysql);
      $num = mysqli_num_rows($mysql);
      disconnectmysql();
    }

    if($num <= 0)
    {
      print_site($s_freischaltung_idfehler_title,$s_freischaltung_idfehler_content);
    }
    else
    {
      $richtigercode = just_my_result($mysql, 0, "code");
      $freigeschaltet = just_my_result($mysql, 0, "freigeschaltet");
      $vorname = just_my_result($mysql, 0, "vorname");
      $nachname = just_my_result($mysql, 0, "nachname");

      if($richtigercode == $code)
      {
        if($freigeschaltet == "nein")
        {
          connect2mysql();
          $mysql = "UPDATE conmanagement_anmeldungen SET `freigeschaltet`=\"ja\" WHERE id='$id' LIMIT 1;";
          $mysql = mysqli_query($link,$mysql);
          $num = mysqli_affected_rows($link);
          disconnectmysql();

          if($num <= 0)
          {
            print_site($s_freischaltung_dbfehler_title,$s_freischaltung_dbfehler_content);
          }
          else
          {
            print_site($s_freischaltung_ok_title,"$s_freischaltung_ok_content1 $vorname $nachname"."$s_freischaltung_ok_content2 $conname - $conuntertitel $s_freischaltung_ok_content3<a href='$flyeradresse' target='_new'>$s_abschluss_flyer</a>. $s_abschluss_beifragen <a href='mailto:$adminemail'>$adminemail</a><br><br>$s_freischaltung_ok_content4");
          }
        }
        else
        {
            print_site($s_freischaltung_bereitsfrei_title,"$s_freischaltung_bereitsfrei_content <a href='mailto:$adminemail'>$adminemail</a>");
        }
      }
      else
      {
        print_site($s_freischaltung_codefalsch_title,"$s_freischaltung_codefalsch_content <a href='mailto:$adminemail'>$adminemail</a>");
      }
    }
  }
  else
  {
    print_site($s_freischaltung_idnichtgefunden_title,$s_freischaltung_idnichtgefunden_content);
  }
}
?>