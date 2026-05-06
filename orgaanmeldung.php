<?php

if(session_status() == PHP_SESSION_NONE){session_start();}
$sessionid = session_id();

$scriptadresse = "https://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];

require_once('./i/western_design.php');
require_once('./i/mysql.php');
require_once('./i/sprache.php');
require_once('validate.php');
require_once('mailsenden.php');

$entsperrungspasswort_req = $_REQUEST['entsperrungspasswort'];

$id = $_REQUEST['id'];
$action = $_REQUEST['action'];
$code = $_REQUEST['code'];
$trotzdem = $_REQUEST['trotzdem'];

$conid = $_REQUEST['conid'];
$vorname = $_REQUEST['vorname'];
$bestmailpw = $_REQUEST['bestmailpw'];
$nachname = $_REQUEST['nachname'];
$strasse = $_REQUEST['strasse'];
$plz = $_REQUEST['plz'];
$ort = $_REQUEST['ort'];
$geburtstag = $_REQUEST['geburtstag'];
$geburtsmonat = $_REQUEST['geburtsmonat'];
$geburtsjahr = $_REQUEST['geburtsjahr'];
$geschlecht = $_REQUEST['geschlecht'];
$vegetarier = $_REQUEST['vegetarier'];
$schwanger = $_REQUEST['schwanger'];
$medausbildung = $_REQUEST['medausbildung'];
$telefon = $_REQUEST['telefon'];
$handy = $_REQUEST['handy'];
$email = $_REQUEST['email'];
$scnsc = $_REQUEST['scnsc'];
$allergien = $_REQUEST['allergien'];
$krankheiten = $_REQUEST['krankheiten'];
$phobien = $_REQUEST['phobien'];
$bemerkungen = $_REQUEST['bemerkungen'];
$charaktername = $_REQUEST['charaktername'];
$klasse = $_REQUEST['klasse'];
$contage = $_REQUEST['contage'];
$spielergruppe = $_REQUEST['spielergruppe'];
$siegel = $_REQUEST['siegel'];
$legenden = $_REQUEST['legenden'];
$dieben = $_REQUEST['dieben'];
$magiekundig = $_REQUEST['magiekundig'];
$alchemist = $_REQUEST['alchemist'];
$freundschaft = $_REQUEST['freundschaft'];
$skundig = $_REQUEST['skundig'];
$rituale = $_REQUEST['rituale'];
$artefakte = $_REQUEST['artefakte'];
$agb = $_REQUEST['agb'];
$datenschutz = $_REQUEST['datenschutz'];
$anmelden = $_REQUEST['anmelden'];
$kennzeichen = $_REQUEST['kennzeichen'];
$stimmung = $_REQUEST['stimmung'];
$wiedraufgekommen = $_REQUEST['wiedraufgekommen'];
$druckpw = $_REQUEST['druckpw'];

$sfa1 = $_REQUEST['sfa1'];
$sfa2 = $_REQUEST['sfa2'];
$sfa3 = $_REQUEST['sfa3'];
$sfa4 = $_REQUEST['sfa4'];
$sfa5 = $_REQUEST['sfa5'];

$nscausruestung = $_REQUEST['nscausruestung'];
$nscwunsch = $_REQUEST['nscwunsch'];

$zufallszahl = $_REQUEST['zufallszahl'];
$zufallsbeschreibung = $_REQUEST['zufallsbeschreibung'];

if(validate("Administrator"))
{
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

  if($conid != "")
  {
    $datenunvollstaendig = "1";

    if(($vorname != '') && ($nachname != '') && ($email != '') && ($strasse != '') && ($plz != '') && ($ort != '') && ($geburtsjahr != '') && ($agb == "agbja") && ($scnsc != "KeineAuswahl"))
    {
      $datenunvollstaendig = "0";
    }

    if($anmelden == $s_anmeldebutton)
    { // Wenn Anmelden gedrueckt wurde
      if(1)
      {
        connect2mysql();
        $mysql = "SELECT freigeschaltet FROM conmanagement_anmeldungen WHERE email='$email' AND conid='$conid' LIMIT 1";
        $mysql = mysqli_query($link,$mysql);
        $num = mysqli_num_rows($mysql);
        disconnectmysql();


        $num = 0;
        $orgaanmeldung = 1;

        if(1)
        {
          $anmeldedatum = date("d.m.Y H:i T");
          $freistatus = "ja";

          $eintrag = "`conid` , `vorname` , `nachname` , `strasse` , `plz` , `ort` , `geburtstag` , `geburtsmonat` , `geburtsjahr` , `geschlecht` , `schwanger` , `vegetarier` , `med_ausbildung` , `telefon` , `handy` , `email` , `scnsc` , `allergien` , `krankheiten` , `phobien` , `kennzeichen` , `stimmung` , `bemerkungen` , `wiezumcon` , `charname` , `klasse` , `contage` , `spielergruppe` , `siegel`, `legenden` , `dieben` , `magiekundig` , `alchemist` , `freundschaft` , `skundig` , `rituale` , `artefakte` , `speichern` , `anmeldedatum`, `sfa1`, `sfa2`, `sfa3`, `sfa4`, `sfa5`, `nscausruestung`, `nscwunsch`, `code`, `freigeschaltet`";
          $eintrag2 = "'$conid' , '$vorname' , '$nachname' , '$strasse' , '$plz' , '$ort' , '$geburtstag' , '$geburtsmonat' , '$geburtsjahr' , '$geschlecht' , '$schwanger' , '$vegetarier' , '$medausbildung' , '$telefon' , '$handy' , '$email' , '$scnsc' , '$allergien' , '$krankheiten' , '$phobien' , '$kennzeichen' , '$stimmung' , '$bemerkungen' , '$wiedraufgekommen' , '$charaktername' , '$klasse' , '$contage' , '$spielergruppe' , '$siegel', '$legenden' , '$dieben' , '$magiekundig' , '$alchemist' , '$freundschaft' , '$skundig' , '$rituale' , '$artefakte' , '$datenschutz' , '$anmeldedatum', '$sfa1', '$sfa2', '$sfa3', '$sfa4', '$sfa5', '$nscausruestung', '$nscwunsch', '$freipw', '$freistatus'";

          $eintrag2 = str_replace("ä", "&auml;", $eintrag2);
          $eintrag2 = str_replace("ö", "&ouml;", $eintrag2);
          $eintrag2 = str_replace("ü", "&uuml;", $eintrag2);
          $eintrag2 = str_replace("Ä", "&Auml;", $eintrag2);
          $eintrag2 = str_replace("Ö", "&Ouml;", $eintrag2);
          $eintrag2 = str_replace("Ü", "&Uuml;", $eintrag2);
          $eintrag2 = str_replace("ß", "&szlig;", $eintrag2);
          $bkslashquot = '\"';
          $eintrag2 = str_replace($bkslashquot, "&quot;", $eintrag2);
          $quotzeichen = '"';
          $eintrag2 = str_replace($quotzeichen, "&quot;", $eintrag2);
          $eintrag2 = str_replace("€", "&euro;", $eintrag2);
          $eintrag2 = str_replace("<", "&lt;", $eintrag2);
          $eintrag2 = str_replace(">", "&gt;", $eintrag2);

          connect2mysql();

          $mysql="INSERT conmanagement_anmeldungen ($eintrag) VALUES ($eintrag2)";
          $mysql = mysqli_query($link,$mysql);

          $id = mysqli_insert_id();

          $num2 = mysqli_affected_rows($link);

          disconnectmysql();

          if ($num2>0)
          {
            $titel = "Orga-Anmeldung erfolgreich";
            $text = "Die Anmeldung ist eingetragen worden.";

            if($datenunvollstaendig == "1")
            {
              $text .= "<br><br><b>ACHTUNG:</b> Die zwingend ben&ouml;tigten Anmeldungsdaten sind unvollst&auml;ndig. Anmeldung wurde trotzdem gespeichert.";
            }

            $text .= "<br><br><a href=\"./anmeldeverwaltung.php?action=showlist&id=$conid\">Zur&uuml;ck zur Anmeldeverwaltung</a>
            <br><a href=\"$scriptadresse?trotzdem=$trotzdem&conid=$conid\">Weitere Orga-Anmeldung eintragen</a>";

            // Anmeldung erfolgreich
            print_site($titel,$text);

            $varspace['to']=$adminemail;
            //$varspace['bcc']="";
            $varspace['subject']="$connamekurz: Eingehende Online-Anmeldung: $vorname $nachname";
            $varspace['htmlmessage']="<HTML>
            <HEAD>
            <title>Online-Anmeldung</title>
            </HEAD>
            <BODY TEXT=#000000>
            Neue Orga-Anmeldung. Die Daten wurden in der Datenbank gespeichert, hier die Excel-&Uuml;bersicht:<br><br>

            <table border='1' width='100%' cellpadding='3' border='0'>
            <tr bgcolor='#C0C0C0'>
              <td><b>Nr.&nbsp;</b></td>
              <td><b>Nachname&nbsp;</b></td>
              <td><b>Vorname&nbsp;</b></td>
              <td><b>&Uuml;berwiesen (in &euro;)&nbsp;</b></td>
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
              <td><b>Vegetarier&nbsp;</b></td>
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
              <td><b>Sp&auml;hrenkundig&nbsp;</b></td>
              <td><b>Rituale geplant&nbsp;</b></td>
              <td><b>Artefakte / Besonderes&nbsp;</b></td>
              <td><b>Wie&nbsp;zum Con gekommen?&nbsp;</b></td>
              <td><b>Sonderfeld 1&nbsp;</b></td>
              <td><b>Sonderfeld 2&nbsp;</b></td>
              <td><b>Sonderfeld 3&nbsp;</b></td>
              <td><b>Sonderfeld 4&nbsp;</b></td>
              <td><b>Sonderfeld 5&nbsp;</b></td>
              <td><b>NSC Ausr&uuml;stung&nbsp;</b></td>
              <td><b>NSC Wunschrolle&nbsp;</b></td>
            </tr>
            <tr height='18'>
              <td>&nbsp;</td>
              <td>$nachname&nbsp;</td>
              <td>$vorname&nbsp;</td>
              <td><font color='#FF0000'><div align='center'>0&nbsp;</div></font></td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>$strasse&nbsp;</td>
              <td>$plz&nbsp;</td>
              <td>$ort&nbsp;</td>
              <td>$geburtstag.$geburtsmonat.$geburtsjahr&nbsp;</td>
              <td>$geschlecht&nbsp;</td>
              <td>$xschwanger&nbsp;</td>
              <td>$xvegetarier&nbsp;</td>
              <td>$telefon&nbsp;</td>
              <td>$handy&nbsp;</td>
              <td>$email&nbsp;</td>
              <td>$medausbildung&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
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
              <td>$datenschutz&nbsp;</td>

              <td>$charaktername&nbsp;</td>
              <td>$Klasse&nbsp;</td>
              <td>$contage&nbsp;</td>
              <td>$spielergruppe&nbsp;</td>
                          <td>$siegel&nbsp;</td>
              <td>$legenden&nbsp;</td>
              <td>$xdieben&nbsp;</td>
                          <td>$xmagiekundig&nbsp;</td>
                      <td>$xalchemist&nbsp;</td>
              <td>$freundschaft&nbsp;</td>
              <td>$skundig&nbsp;</td>
              <td>$xrituale&nbsp;</td>
              <td>$artefakte&nbsp;</td>
              <td>$wiedraufgekommen&nbsp;</td>

              <td>$sfa1&nbsp;</td>
              <td>$sfa2&nbsp;</td>
              <td>$sfa3&nbsp;</td>
              <td>$sfa4&nbsp;</td>
              <td>$sfa5&nbsp;</td>

              <td>$nscausruestung&nbsp;</td>
              <td>$nscwunsch&nbsp;</td>
            </tr>
            </table>

            <br><br>Zur Schnellansicht noch das Interessanteste:<br>
            $vorname $nachname kommt als <b>$scnsc</b>.<br><br>
            $anrede hat folgende medizinische Ausbildung: $medausbildung<br>
            Krankheiten: $krankheiten<br>
            Allergien: $allergien<br>
            Phobien: $phobien<br>
            Schwanger: $schwanger<br>
            <br><a href=mailto:$email>Dem Teilnehmer direkt eine Email schreiben</a><br>
            ";

            if($scnsc == 'SC')
            {
              $grbesitz = ucfirst($besitz);
              $varspace['htmlmessage'].="$grbesitz Charakter hat $contage Contage<br>";
            }

            if($bemerkungen != '')
            {
              $varspace['htmlmessage'].="<br><b>$vorname hat folgende Bemerkungen zu $besitz2 Anmeldung gemacht:</b><br>$bemerkungen";
            }

            if($wiedraufgekommen != '')
            {
              $varspace['htmlmessage'].="<br><b>$vorname hat so von unserem Con erfahren:</b><br>$wiedraufgekommen";
            }

            if($artefakte != '')
            {
              $varspace['htmlmessage'].="<br><b>Es wurde folgende Besonderheiten und Artefakte angemeldet:</b><br>$artefakte";
            }

            $varspace['htmlmessage'].="</BODY></HTML>";

            mail_senden($varspace);
            //Email an Orga is raus
          }
          if ($num2<=0)
          {
            print_site($s_anmelderror_title, $s_anmelderror_content);
          }
        } //endif emailsgleich?
      } //endif $datenvollstaendig = 1
    }
    //#################################################################################################
    //###########################  Email-Bestaetigungstexte hier rausgenommen #########################
    //#################################################################################################
    else
    {
      if(($confreischaltung == "0") AND $trotzdem != "1")
      {
        print_site(
        'Online-Anmeldung gesperrt',
        "Das war vermutlich Jonas und es ist wohl wichtig sowie von kurzer Dauer. Ansonnsten kommst Du &uuml;ber diesen Link trotzdem zur Anmeldung:<br>
         <br><a href='$scriptadresse?conid=$conid&trotzdem=1'>Ich bin mir bewusst, was ich tue.</a>"
        );
      }
      else
      {
         $kurzfragebogenzumchar = "<br><table align='center' border='0' width='100%' cellpadding='2' cellspacing='0'>
         <tr>
          <td id='eins' colspan='2'>
          <div align='center'><font size='+1'><hr noshade size='2'><b>$s_kurzfragebogenzumchar_ueberschrift</b></font><br>$s_kurzfragebogenzumchar_einleitung<br><hr noshade size='2'></div></td>
         </tr>
         <tr>
          <td id='eins' width='30%'>$s_kurzfragebogenzumchar_cname</td>
          <td id='eins'>
            <input type='text' name='charaktername' value='' size='40' class='textfield'>
          </td>
         </tr>
         <tr>
          <td id='zwei'>$s_kurzfragebogenzumchar_klasse</td>
          <td id='zwei'>
           <input type='text' name='klasse' value='' size='40' class='textfield'>
          </td>
         </tr>
         <tr>
          <td id='eins'>$s_kurzfragebogenzumchar_contage</td>
          <td id='eins'>
           <input type='text' name='contage' value='0' size='10' class='textfield'>
          </td>
         </tr>
         <tr>
          <td id='zwei'>$s_kurzfragebogenzumchar_spielergruppe</td>
          <td id='zwei'>
           <input type='text' name='spielergruppe' value='' size='40' class='textfield'>
          </td>
         </tr>
             <tr>
          <td id='zwei'>$s_kurzfragebogenzumchar_siegel</td>
          <td id='zwei'>
           <input type='text' name='siegel' value='' size='40' class='textfield'>
          </td>
         </tr>
         <tr>
          <td id='eins'>$s_kurzfragebogenzumchar_gundl</td>
          <td id='eins'>
           <input type='text' name='legenden' value='' size='10' class='textfield'>
          </td>
         </tr>

         <tr>
          <td id='zwei'>$s_kurzfragebogenzumchar_dieben</td>
          <td id='zwei'>
            <input type='checkbox' name='dieben' value='1' tabindex=''>
          </td>
         </tr>
             <tr>
          <td id='zwei'>$s_kurzfragebogenzumchar_magiekundig</td>
          <td id='zwei'>
            <input type='checkbox' name='magiekundig' value='1' tabindex=''>
          </td>
         </tr>
             <tr>
          <td id='zwei'>$s_kurzfragebogenzumchar_alchemiekundig</td>
          <td id='zwei'>
            <input type='checkbox' name='alchemist' value='1' tabindex=''>
          </td>
         </tr>
         <tr>
          <td id='zwei'>$s_kurzfragebogenzumchar_fde</td>
          <td id='zwei'>
            <input type='text' name='freundschaft' value='' size='40' class='textfield'>
          </td>
         </tr>
         <tr>
          <td id='zwei'>$s_kurzfragebogenzumchar_spaehrenk</td>
          <td id='zwei'>
            <input type='text' name='skundig' value='' size='40' class='textfield'>
          </td>
         </tr>
         <tr>
          <td id='zwei'>$s_kurzfragebogenzumchar_rituale</td>
          <td id='zwei'>
            <input type='checkbox' name='rituale' value='1' tabindex=''>
          </td>
         </tr>
         <tr>
          <td id='zwei'>$s_kurzfragebogenzumchar_artefakte</td>
          <td id='zwei'>
            <textarea name='artefakte' rows='4' cols='30' wrap='virtual' dir='ltr' id='field_24_3'></textarea>
          </td>
         </tr>
         </table>
        <br><br>";

        $nsctext = "<br><table align='center' border='0' width='100%' cellpadding='2' cellspacing='0'>
        <tr>
          <td id='eins' colspan='2'>
          <div align='center'><font size='+1'><hr noshade size='2'><b>$s_nsctext_ueberschrift</b></font><br>$s_nsctext_einleitung<br><hr noshade size='2'></div></td>
         </tr>
         <tr>
          <td id='eins' width='30%'>$s_nsctext_ausruestung</td>
          <td id='eins'>
            <input type='text' name='nscausruestung' value='' size='40' class='textfield'><br>$s_nsctext_ausruestung_text
          </td>
         </tr>
         <tr>
          <td id='eins' width='30%'>$s_nsctext_wunschrolle</td>
          <td id='eins'>
            <input type='text' name='nscwunsch' value='' size='40' class='textfield'><br>$s_nsctext_wunschrolle_text
          </td>
         </tr>
        </table>
        <br><br>";

        $scnscauswahlfeld = "<td id='eins'>$s_teilnehmerdaten_sc/$s_teilnehmerdaten_nsc*</td><td id='eins'><select name='scnsc'><option value='KeineAuswahl' selected='selected'>$s_teilnehmerdaten_waehlen</option><option value='SC'>$s_teilnehmerdaten_spieler</option><option value='NSC'>$s_teilnehmerdaten_nsc</option></select></td>";

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

        print_site(
        "Orga-Anmeldung<br>$conname - $conuntertitel",
        "$condatum &bull; $conlocation<br>
        <b>Dieses Script tr&auml;gt ohne Sicherheitsfragen oder Logikcheck in die DB ein, besondere Sorgfalt ist n&ouml;tig!</b><br>&nbsp;
        <form action='$scriptadresse' method='post'>
        <table align='center' border='0' width='100%' cellpadding='2' cellspacing='0'>
         <tr>
          <td id='eins' colspan='2'><div align='center'><font size='+1'><hr noshade size='2'><b>$s_teilnehmerdaten_ueberschrift</b><br><hr noshade size='2'></font></div></td>
         </tr>
         <tr>
          <td id='eins' width='30%'>$s_teilnehmerdaten_vorname*</td>
          <td id='eins'>
            <input type='text' name='vorname' value='' size='40' class='textfield'>
          </td>
         </tr>
         <tr>
          <td id='eins'>$s_teilnehmerdaten_nachname*</td>
          <td id='eins'>
            <input type='text' name='nachname' value='' size='40' class='textfield'>
          </td>
         </tr>
         <tr>
          <td id='eins'>$s_teilnehmerdaten_anschrift*</td>
          <td id='eins'>
            <input type='text' name='strasse' value='' size='40' class='textfield'>
          </td>
         </tr>
         <tr>
          <td id='eins'>$s_teilnehmerdaten_plz*</td>
          <td id='eins'>
            <input type='text' name='plz' value='' size='10' class='textfield'>
          </td>
         </tr>
         <tr>
          <td id='eins'>$s_teilnehmerdaten_ort*</td>
          <td id='eins'>
            <input type='text' name='ort' value='' size='40' class='textfield'>
          </td>
         </tr>
         <tr>
          <td id='eins'>$s_teilnehmerdaten_geburt*</td>
          <td id='eins'>
            <select name='geburtstag'>
              <option value='1'>1</option>
              <option value='2'>2</option>
              <option value='3'>3</option>
              <option value='4'>4</option>
              <option value='5'>5</option>
              <option value='6'>6</option>
              <option value='7'>7</option>
              <option value='8'>8</option>
              <option value='9'>9</option>
              <option value='10'>10</option>
              <option value='11'>11</option>
              <option value='12'>12</option>
              <option value='13'>13</option>
              <option value='14'>14</option>
              <option value='15'>15</option>
              <option value='16'>16</option>
              <option value='17'>17</option>
              <option value='18'>18</option>
              <option value='19'>19</option>
              <option value='20'>20</option>
              <option value='21'>21</option>
              <option value='22'>22</option>
              <option value='23'>23</option>
              <option value='24'>24</option>
              <option value='25'>25</option>
              <option value='26'>26</option>
              <option value='27'>27</option>
              <option value='28'>28</option>
              <option value='29'>29</option>
              <option value='20'>30</option>
              <option value='31'>31</option>
            </select>.
            <select name='geburtsmonat'>
              <option value='1'>1</option>
              <option value='2'>2</option>
              <option value='3'>3</option>
              <option value='4'>4</option>
              <option value='5'>5</option>
              <option value='6'>6</option>
              <option value='7'>7</option>
              <option value='8'>8</option>
              <option value='9'>9</option>
              <option value='10'>10</option>
              <option value='11'>11</option>
              <option value='12'>12</option>
            </select>.
            <select name='geburtsjahr'>
              <option value='1920'>1920</option>
              <option value='1921'>1921</option>
              <option value='1922'>1922</option>
              <option value='1923'>1923</option>
              <option value='1924'>1924</option>
              <option value='1925'>1925</option>
              <option value='1926'>1926</option>
              <option value='1927'>1927</option>
              <option value='1928'>1928</option>
              <option value='1929'>1929</option>
              <option value='1930'>1930</option>
              <option value='1931'>1931</option>
              <option value='1932'>1932</option>
              <option value='1933'>1933</option>
              <option value='1934'>1934</option>
              <option value='1935'>1935</option>
              <option value='1936'>1936</option>
              <option value='1937'>1937</option>
              <option value='1938'>1938</option>
              <option value='1939'>1939</option>
              <option value='1940'>1940</option>
              <option value='1941'>1941</option>
              <option value='1942'>1942</option>
              <option value='1943'>1943</option>
              <option value='1944'>1944</option>
              <option value='1945'>1945</option>
              <option value='1946'>1946</option>
              <option value='1947'>1947</option>
              <option value='1948'>1948</option>
              <option value='1949'>1949</option>
              <option value='1950'>1950</option>
              <option value='1951'>1951</option>
              <option value='1952'>1952</option>
              <option value='1953'>1953</option>
              <option value='1954'>1954</option>
              <option value='1955'>1955</option>
              <option value='1956'>1956</option>
              <option value='1957'>1957</option>
              <option value='1958'>1958</option>
              <option value='1959'>1959</option>
              <option value='1960'>1960</option>
              <option value='1961'>1961</option>
              <option value='1962'>1962</option>
              <option value='1963'>1963</option>
              <option value='1964'>1964</option>
              <option value='1965'>1965</option>
              <option value='1966'>1966</option>
              <option value='1967'>1967</option>
              <option value='1968'>1968</option>
              <option value='1969'>1969</option>
              <option value='1970'>1970</option>
              <option value='1971'>1971</option>
              <option value='1972'>1972</option>
              <option value='1973'>1973</option>
              <option value='1974'>1974</option>
              <option value='1975'>1975</option>
              <option value='1976'>1976</option>
              <option value='1977'>1977</option>
              <option value='1978'>1978</option>
              <option value='1979'>1979</option>
              <option value='1980' selected='selected'>1980</option>
              <option value='1981'>1981</option>
              <option value='1982'>1982</option>
              <option value='1983'>1983</option>
              <option value='1984'>1984</option>
              <option value='1985'>1985</option>
              <option value='1986'>1986</option>
              <option value='1987'>1987</option>
              <option value='1988'>1988</option>
              <option value='1989'>1989</option>
              <option value='1990'>1990</option>
              <option value='1991'>1991</option>
              <option value='1992'>1992</option>
              <option value='1993'>1993</option>
              <option value='1994'>1994</option>
              <option value='1995'>1995</option>
              <option value='1996'>1996</option>
              <option value='1997'>1997</option>
              <option value='1998'>1998</option>
              <option value='1999'>1999</option>
              <option value='2000'>2000</option>
              <option value='2001'>2001</option>
              <option value='2002'>2002</option>
              <option value='2003'>2003</option>
              <option value='2004'>2004</option>
              <option value='2005'>2005</option>
              <option value='2006'>2006</option>
              <option value='2007'>2007</option>
              <option value='2008'>2008</option>
              <option value='2009'>2009</option>
              <option value='2010'>2010</option>
            </select>
          </td>
         </tr>
         <tr>
          <td id='eins'>$s_teilnehmerdaten_geschlecht</td>
          <td id='eins'>
           <select name='geschlecht'>
            <option value='KeineAuswahl' selected='selected'>$s_teilnehmerdaten_waehlen</option>
            <option value='weiblich'>$s_teilnehmerdaten_geschlecht_w</option>
            <option value='maennlich'>$s_teilnehmerdaten_geschlecht_m</option>
           </select>
          </td>
         </tr>
         <tr>
          <td id='eins'>$s_teilnehmerdaten_schwanger</td>
          <td id='eins'>
           <input type='checkbox' name='schwanger' value='1' tabindex=''>
          </td>
         </tr>
         <tr>
          <td id='eins'>$s_teilnehmerdaten_essgewohnheiten</td>
          <td id='eins'>
           <input type='text' name='vegetarier' size='40' class='textfield'>
          </td>
         </tr>
         <tr>
          <td id='eins'>$s_teilnehmerdaten_medausbildung</td>
          <td id='eins'>
           <select name='medausbildung'>
            <option value='Arzt'>$s_teilnehmerdaten_medausbildung_arzt*</option>
            <option value='Rettungsassistent'>$s_teilnehmerdaten_medausbildung_rettungsassi*</option>
            <option value='Mediziner'>$s_teilnehmerdaten_medausbildung_mediziner*</option>
            <option value='Sanitaeter'>$s_teilnehmerdaten_medausbildung_sani</option>
            <option value='Ersthelfer'>$s_teilnehmerdaten_medausbildung_ersthelfer</option>
            <option value='keine' selected='selected'>$s_teilnehmerdaten_medausbildung_keine</option>
           </select>
          </td>
         </tr>
         <tr>
          <td id='eins'>$s_teilnehmerdaten_telefon</td>
          <td id='eins'>
            <input type='text' name='telefon' value='' size='40' class='textfield'>
          </td>
         </tr>
         <tr>
          <td id='eins'>$s_teilnehmerdaten_handy</td>
          <td id='eins'>
            <input type='text' name='handy' value='' size='40' class='textfield'>
          </td>
         </tr>
         <tr>
          <td id='eins'>$s_teilnehmerdaten_email</td>
          <td id='eins'>
            <input type='text' name='email' value='' size='40' class='textfield'><br>
          </td>
         </tr>
         <tr>
  $scnscauswahlfeld
         </tr>
         <tr>
          <td id='eins'>$s_teilnehmerdaten_allergien</td>
          <td id='eins'>
            <input type='text' name='allergien' value='' size='40' class='textfield'><br>
          </td>
         </tr>
         <tr>
          <td id='eins'>$s_teilnehmerdaten_krankheiten</td>
          <td id='eins'>
            <input type='text' name='krankheiten' value='' size='40' class='textfield'>
          </td>
         </tr>
         <tr>
          <td id='eins'>$s_teilnehmerdaten_phobien</td>
          <td id='eins'>
            <input type='text' name='phobien' value='' size='40' class='textfield'>
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
           <textarea name='bemerkungen' rows='3' cols='30' wrap='virtual' dir='ltr'></textarea>
          </td>
         </tr>

         <tr>
          <td id='fehler' width='30%'>$s_diverses_wiedraufgekommen</td>
          <td id='fehler'>
           <textarea name='wiedraufgekommen' rows='3' cols='30' wrap='virtual' dir='ltr'></textarea>
          </td>
         </tr>

         <tr>
          <td id='eins'>$s_diverses_kennzeichen</td>
          <td id='eins'>
            <input type='text' name='kennzeichen' value='' size='40' class='textfield'>
          </td>
         </tr>

         <tr>
          <td id='eins'>$s_diverses_stimmungsbild</td>
          <td id='eins'>
           <select name='stimmung'>
            <option value='' selected='selected'>$s_teilnehmerdaten_waehlen</option>
            <option value='kaempfen'>$s_diverses_stimmung_k</option>
            <option value='plotten'>$s_diverses_stimmung_p</option>
            <option value='zaubern'>$s_diverses_stimmung_z</option>
           </select>
          </td>
         </tr>
        </table>
        <br>
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
          <td id='fehler' colspan='2'><div align='center'><br><font size='+1'>Orga-Anmeldung ohne Pr&uuml;fung eintragen</font><br>
           <input type='hidden' name='conid' value='$conid'>
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
    print_site($s_freischaltung_idnichtgefunden_title,$s_freischaltung_idnichtgefunden_content);
  }
}
else
{
  print_site(
    'Zugriff verweigert',
    'Man muss als Administrator angemeldet sein, um auf diese Seite zugreifen zu k&ouml;nnen.'
  );
}
?>