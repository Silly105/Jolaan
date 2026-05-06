<?php

require_once('i/western_design.php');
require_once('i/mysql.php');
require_once('validate.php');

if(session_status() == PHP_SESSION_NONE){session_start();}
$sessionid = session_id();

$scriptadresse = "https://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];

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
  if($action == "showcon")
  {
    if($id != "")
    {
      $mysql = "SELECT * FROM conmanagement_conliste WHERE id=$id";
      $mysql = mysqli_query($link,$mysql);
      $num = mysqli_num_rows($mysql);

      $conid = just_my_result($mysql, 0, "id");
      $conname = just_my_result($mysql, 0, "conname");
      $connamekurz = just_my_result($mysql, 0, "connamekurz");
      $conuntertitel = just_my_result($mysql, 0, "conuntertitel");
      $condatum = just_my_result($mysql, 0, "condatum");
      $conlocationvorsatz = just_my_result($mysql, 0, "conlocationvorsatz");
      $conlocation = just_my_result($mysql, 0, "conlocation");
      $adminemail = just_my_result($mysql, 0, "adminemail");
      $flyeradresse = just_my_result($mysql, 0, "flyeradresse");
      $agbadresse = just_my_result($mysql, 0, "agbadresse");
      $confreischaltung = just_my_result($mysql, 0, "confreischaltung");
      $minscbetrag = just_my_result($mysql, 0, "minscbetrag");
      $minnscbetrag = just_my_result($mysql, 0, "minnscbetrag");
      $teilsperrung = just_my_result($mysql, 0, "teilsperrung");
      $entsperrungspasswort = just_my_result($mysql, 0, "entsperrungspasswort");

      $bahnhof = just_my_result($mysql, 0, "bahnhof");
      $zugtermin = just_my_result($mysql, 0, "zugtermin");

      $sf1 = just_my_result($mysql, 0, "sf1"); //Sonderfelder 1-5 fuer conspezifische Fragen
      $sf2 = just_my_result($mysql, 0, "sf2");
      $sf3 = just_my_result($mysql, 0, "sf3");
      $sf4 = just_my_result($mysql, 0, "sf4");
      $sf5 = just_my_result($mysql, 0, "sf5");

      if(($confreischaltung == "1") AND ($teilsperrung == "frei"))
      {
        $optionfrei = " selected='selected'";
      }
      if(($confreischaltung == "1") AND ($teilsperrung == "scvoll"))
      {
        $optionscvoll = " selected='selected'";
      }
      if(($confreischaltung == "1") AND ($teilsperrung == "nscvoll"))
      {
        $optionnscvoll = " selected='selected'";
      }
      if($confreischaltung == "0")
      {
        $optionsperre = " selected='selected'";
      }

      $zugtermin = str_replace("&", "&amp;", $zugtermin);
      $zugtermin = str_replace("<", "&lt;", $zugtermin);
      $zugtermin = str_replace(">", "&gt;", $zugtermin);
      $zugtermin = str_replace("\n", "", $zugtermin);

      print_site(
      "Converwaltung $conname",
      "<a href='$scriptadresse'>Zur&uuml;ck zur Auswahl</a><br><br>Hier k&ouml;nnen die Daten bereits eingetragener Cons ge&auml;ndert werden. Dies hat nur Auswirkungen auf die Online-Anmeldung, nicht auf die Schnellinfo.<br><b><br>Die Anmeldungsadresse dieses Cons lautet:</b><br><font size='-2'>https://".$_SERVER['SERVER_NAME']."/conanmeldung.php?conid=$id</font><br><br><b>Der &quot;Geheim-Link&quot; zum Anmelden trotz Teilsperrung lautet:</b><br><font size='-2'>https://".$_SERVER['SERVER_NAME']."/conanmeldung.php?conid=$id&entsperrungspasswort=$entsperrungspasswort</font><br><br>
      <form action='$scriptadresse' method='post'>
      <table align='left' border='0' width='100%'>
      <tr><td>Conname: (z.B.: 'Roots VII')<br><!--x--><input type='Text' name='conname' size='60' value='$conname'></td></tr>
      <tr><td>Conname, kurzform: (z.B.: 'Roots VII')<br><!--x--><input type='Text' name='connamekurz' size='60' value='$connamekurz'></td></tr>
      <tr><td>Conuntertitel: (z.B.: 'Das Blut des Lebens')<br><!--x--><input type='Text' name='conuntertitel' size='60' value='$conuntertitel'></td></tr>
      <tr><td>Condatum: (z.B.: '27.12.2007 - 01.01.2008')<br><!--x--><input type='Text' name='condatum' size='60' value='$condatum'></td></tr>
      <tr><td>Conlocationvorsatz: (z.B.: 'in der')<br><!--x--><input type='Text' name='conlocationvorsatz' size='60' value='$conlocationvorsatz'></td></tr>
      <tr><td>Conlocation: (z.B.: 'Jugendherberge Inzm&uuml;hlen')<br><!--x--><input type='Text' name='conlocation' size='60' value='$conlocation'></td></tr>
      <tr><td>Orga-Email: (z.B.: 'roots@lost-ages.de')<br><!--x--><input type='Text' name='adminemail' size='60' value='$adminemail'><br><b>(ACHTUNG: Eine &Auml;nderung bezieht sich hier auf jede Email, die von der Online-Anmeldung verschickt wird.)</b></td></tr>
      <tr><td>Flyeradresse: (Komplette URL eintragen)<br><!--x--><input type='Text' name='flyeradresse' size='60' value='$flyeradresse'></td></tr>
      <tr><td>AGB-Adresse: (Komplette URL eintragen)<br><!--x--><input type='Text' name='agbadresse' size='60' value='$agbadresse'></td></tr>
      <tr><td>Anmeldesperre:<br><!--x-->
          <select name='sperre'>
            <option value='frei'$optionfrei>Freigeschaltet</option>
            <option value='scvoll'$optionscvoll>SC gesperrt</option>
            <option value='nscvoll'$optionnscvoll>NSC gesperrt</option>
            <option value='sperre'$optionsperre>Vollsperre</option>
          </select>
      </td></tr>
      <tr><td>SC-Betrag: (minimaler SC-Betrag in Euro)<br><!--x--><input type='Text' name='minscbetrag' size='60' value='$minscbetrag'></td></tr>
      <tr><td>NSC-Betrag: (minimaler NSC-Betrag in Euro)<br><!--x--><input type='Text' name='minnscbetrag' size='60' value='$minnscbetrag'></td></tr>
      <tr><td>Entsperrungspasswort: (Zum Anmelden bei Teilsperrung)<br><!--x--><input type='Text' name='entsperrungspasswort' size='60' value='$entsperrungspasswort'><br>(Dieses Feld darf nicht leer sein!)</td></tr>

      <tr><td>N&auml;chster Bahnhof: (z.B.: 'Handeloh')<br><input type='Text' name='bahnhof' size='60' value='$bahnhof'></td></tr>
      <tr><td>Zugtermin: (Jonas Fragen, wird noch bearbeitet)<br><input type='Text' name='zugtermin' size='60' value=\"$zugtermin\"><br>(z.B.: '&lt;select name='abholtag'&gt;&lt;option value='-' selected='selected'&gt;-&lt;/option&gt;&lt;option value='26.12.'&gt;26.12.&lt;/option&gt;&lt;option value='27.12.'&gt;27.12.&lt;/option&gt;&lt;/select&gt;&amp;nbsp;')</td></tr>

      <tr><td>Sonderfrage 1:<br><textarea name='sf1' cols='30' rows='2'>$sf1</textarea></td></tr>
      <tr><td>Sonderfrage 2:<br><textarea name='sf2' cols='30' rows='2'>$sf2</textarea></td></tr>
      <tr><td>Sonderfrage 3:<br><textarea name='sf3' cols='30' rows='2'>$sf3</textarea></td></tr>
      <tr><td>Sonderfrage 4:<br><textarea name='sf4' cols='30' rows='2'>$sf4</textarea></td></tr>
      <tr><td>Sonderfrage 5:<br><textarea name='sf5' cols='30' rows='2'>$sf5</textarea></td></tr>

      <tr><td colspan='2'><br><br><input type='hidden' name='action' value='editcon'><input type='hidden' name='id' value='$id'><input type='Submit' name='senden' value='Abschicken'></form>
	  <form action='$scriptadresse' method='post'><input type='hidden' name='action' value='deletecon'><input type='hidden' name='id' value='$id'><input type='Submit' name='senden' value='Veranstaltung l&ouml;schen'></form>
	  
	  </td></tr>
      </table>
      
      "
      );
    }
    else
    {
      print_site(
      "Fehler",
      "<a href='$scriptadresse'>Zur&uuml;ck zur Auswahl</a><br><br>Es wurde kein Con mit dieser ID gefunden."
    );
    }
  }
  elseif($action == "newcon")
  {
    print_site(
    "Neuen Con anlegen",
    "<a href='$scriptadresse'>Zur&uuml;ck zur Auswahl</a><br><br>Hier k&ouml;nnen neue Cons f&uuml;r die Online-Anmeldung angelegt werden. Dies hat keinerlei Verbindung mit dem Eintragen einer Schnellinfo auf der Startseite.<br><br>
    <form action='$scriptadresse' method='post'>
    <table align='left' border='0' width='100%'>
    <tr><td>Conname: (z.B.: 'Roots VII')<br><!--x--><input type='Text' name='conname' size='60' value=''></td></tr>
    <tr><td>Conname, kurzform: (z.B.: 'Roots VII')<br><!--x--><input type='Text' name='connamekurz' size='60' value=''></td></tr>
    <tr><td>Conuntertitel: (z.B.: 'Das Blut des Lebens')<br><!--x--><input type='Text' name='conuntertitel' size='60' value=''></td></tr>
    <tr><td>Condatum: (z.B.: '27.12.2007 - 01.01.2008')<br><!--x--><input type='Text' name='condatum' size='60' value=''></td></tr>
    <tr><td>Conlocationvorsatz: (z.B.: 'in der')<br><!--x--><input type='Text' name='conlocationvorsatz' size='60' value=''></td></tr>
    <tr><td>Conlocation: (z.B.: 'Jugendherberge Inzm&uuml;hlen')<br><!--x--><input type='Text' name='conlocation' size='60' value=''></td></tr>
    <tr><td>Orga-Email: (z.B.: 'roots@lost-ages.de')<br><!--x--><input type='Text' name='adminemail' size='60' value=''></td></tr>
    <tr><td>Flyeradresse: (Komplette URL eintragen)<br><!--x--><input type='Text' name='flyeradresse' size='60' value=''></td></tr>
    <tr><td>AGB-Adresse: (Komplette URL eintragen)<br><!--x--><input type='Text' name='agbadresse' size='60' value=''></td></tr>
    <tr><td>Anmeldesperre:<br><!--x-->
        <select name='sperre'>
          <option value='frei' selected='selected'>Freigeschaltet</option>
          <option value='scvoll'>SC gesperrt</option>
          <option value='nscvoll'>NSC gesperrt</option>
          <option value='sperre'>Vollsperre</option>
        </select>
    </td></tr>
    <tr><td>SC-Betrag: (minimaler SC-Betrag in Euro)<br><!--x--><input type='Text' name='minscbetrag' size='60' value=''></td></tr>
    <tr><td>NSC-Betrag: (minimaler NSC-Betrag in Euro)<br><!--x--><input type='Text' name='minnscbetrag' size='60' value=''></td></tr>
    <tr><td>Entsperrungspasswort: (Zum Anmelden bei Teilsperrung)<br><!--x--><input type='Text' name='entsperrungspasswort' size='60' value=''><br>(Dieses Feld darf nicht leer sein!)</td></tr>

    <tr><td>N&auml;chster Bahnhof: (z.B.: 'Handeloh')<br><input type='Text' name='bahnhof' size='60' value=''></td></tr>
    <tr><td>Zugtermin: (Jonas Fragen, wird noch bearbeitet)<br><input type='Text' name='zugtermin' size='60' value=''><br>(z.B.: '&lt;select name='abholtag'&gt;&lt;option value='-' selected='selected'&gt;-&lt;/option&gt;&lt;option value='26'&gt;26.&lt;/option&gt;&lt;option value='27'&gt;27.&lt;/option&gt;&lt;/select&gt;&amp;nbsp;12.')</td></tr>

    <tr><td>Sonderfrage 1:<br><textarea name='sf1' cols='30' rows='2'></textarea></td></tr>
    <tr><td>Sonderfrage 2:<br><textarea name='sf2' cols='30' rows='2'></textarea></td></tr>
    <tr><td>Sonderfrage 3:<br><textarea name='sf3' cols='30' rows='2'></textarea></td></tr>
    <tr><td>Sonderfrage 4:<br><textarea name='sf4' cols='30' rows='2'></textarea></td></tr>
    <tr><td>Sonderfrage 5:<br><textarea name='sf5' cols='30' rows='2'></textarea></td></tr>

    <tr><td colspan='2'><br><br><input type='hidden' name='action' value='newcondb'><input type='Submit' name='senden' value='Abschicken'></td></tr>
    </table>
    </form>"
    );
  }
  elseif($action == "newcondb")
  {
    if($sperre == "sperre")
    {
      $confreischaltung = "0";
      $teilsperrung = "frei";
    }
    elseif($sperre == "scvoll")
    {
      $confreischaltung = "1";
      $teilsperrung = "scvoll";
    }
    elseif($sperre == "nscvoll")
    {
      $confreischaltung = "1";
      $teilsperrung = "nscvoll";
    }
    else
    {
      $confreischaltung = "1";
      $teilsperrung = "frei";
    }

    $eintrag = "conname, connamekurz, conuntertitel, condatum, conlocationvorsatz, conlocation, adminemail, flyeradresse, agbadresse, confreischaltung, minscbetrag, minnscbetrag, teilsperrung, entsperrungspasswort, bahnhof, zugtermin, sf1, sf2, sf3, sf4, sf5";
    $eintrag2 = "'$conname', '$connamekurz', '$conuntertitel', '$condatum', '$conlocationvorsatz', '$conlocation', '$adminemail', '$flyeradresse', '$agbadresse', '$confreischaltung', '$minscbetrag', '$minnscbetrag', '$teilsperrung', '$entsperrungspasswort', '$bahnhof', '$zugtermin', '$sf1', '$sf2', '$sf3', '$sf4', '$sf5'";

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

    $mysql = $mysqltext = "INSERT `conmanagement_conliste` ($eintrag) values($eintrag2)";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_affected_rows($link);
    $newid = mysqli_insert_id();


    if ($num>0)
    {
      print_site(
      'Coneintrag erstellt',
      "Der Con wurde in die Datenbank eingetragen.<br><br>Er ist nun als Online-Anmeldung verf&uuml;gbar.<br><br>Die Anmeldungsadresse lautet:<br>https://".$_SERVER['SERVER_NAME']."/conanmeldung.php?conid=$newid<br><br><a href='$scriptadresse'>Zur&uuml;ck zur Converwaltung</a>"
      );
    }
    if ($num<=0)
    {
      print_site(
      'Coneintrag FEHLGESCHLAGEN!',
      "Die Daten konnten NICHT eingetragen werden. Wenn das Problem l&auml;nger besteht, sag Jonas Bescheid."
      );
    }
  }
  elseif($action == "editcon")
  {
    if($sperre == "sperre")
    {
      $confreischaltung = "0";
      $teilsperrung = "frei";
    }
    elseif($sperre == "scvoll")
    {
      $confreischaltung = "1";
      $teilsperrung = "scvoll";
    }
    elseif($sperre == "nscvoll")
    {
      $confreischaltung = "1";
      $teilsperrung = "nscvoll";
    }
    else
    {
      $confreischaltung = "1";
      $teilsperrung = "frei";
    }

    $aendern = "CONNAME='$conname',";
    $aendern .= "CONNAMEKURZ='$connamekurz',";
    $aendern .= "CONUNTERTITEL='$conuntertitel',";
    $aendern .= "CONDATUM='$condatum',";
    $aendern .= "CONLOCATIONVORSATZ='$conlocationvorsatz',";
    $aendern .= "CONLOCATION='$conlocation',";
    $aendern .= "ADMINEMAIL='$adminemail',";
    $aendern .= "FLYERADRESSE='$flyeradresse',";
    $aendern .= "AGBADRESSE='$agbadresse',";
    $aendern .= "CONFREISCHALTUNG='$confreischaltung',";
    $aendern .= "MINSCBETRAG='$minscbetrag',";
    $aendern .= "MINNSCBETRAG='$minnscbetrag',";
    $aendern .= "TEILSPERRUNG='$teilsperrung',";
    $aendern .= "ENTSPERRUNGSPASSWORT='$entsperrungspasswort',";

    $aendern .= "BAHNHOF='$bahnhof',";
    $aendern .= "ZUGTERMIN='$zugtermin',";
    $aendern .= "SF1='$sf1',";
    $aendern .= "SF2='$sf2',";
    $aendern .= "SF3='$sf3',";
    $aendern .= "SF4='$sf4',";
    $aendern .= "SF5='$sf5'";

    $aendern = str_replace("ä", "&auml;", $aendern);
    $aendern = str_replace("ö", "&ouml;", $aendern);
    $aendern = str_replace("ü", "&uuml;", $aendern);
    $aendern = str_replace("Ä", "&Auml;", $aendern);
    $aendern = str_replace("Ö", "&Ouml;", $aendern);
    $aendern = str_replace("Ü", "&Uuml;", $aendern);
    $aendern = str_replace("ß", "&szlig;", $aendern);
    $bkslashquot = '\"';
    $aendern = str_replace($bkslashquot, "&quot;", $aendern);
    $quotzeichen = '"';
    $aendern = str_replace($quotzeichen, "&quot;", $aendern);
    $aendern = str_replace("€", "&euro;", $aendern);

    $mysql = "UPDATE `conmanagement_conliste` SET $aendern WHERE id='$id';";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_affected_rows($link);

    if ($num>0)
    {
      print_site(
      'Coneintrag ge&auml;ndert',
      "Der Con wurde in der Datenbank abge&auml;ndert.<br><br>Die Online-Anmeldung zeigt nun die neuen Daten an.<br><br><a href='$scriptadresse'>Zur&uuml;ck zur Converwaltung</a>"
      );
    }
    if ($num<=0)
    {
      print_site(
      '&Auml;nderung FEHLGESCHLAGEN!',
      "Die Daten konnten NICHT eingetragen werden. Wenn das Problem l&auml;nger besteht, sag Jonas Bescheid."
      );
    }
  }
  elseif($action == "deletecon")
  {
	if($id != "")
    {
      $mysql = "SELECT * FROM conmanagement_conliste WHERE id=$id";
      $mysql = mysqli_query($link,$mysql);
      $num = mysqli_num_rows($mysql);

      $conname = just_my_result($mysql, 0, "conname");      
      $condatum = just_my_result($mysql, 0, "condatum");
      
      print_site(
      "Wirklich l&ouml;schen?",	  
      "Soll die Veranstaltung <b>&quot;$conname&quot; ($condatum)</b> wirklich samt aller Anmeldungsdaten aus der Datenbank entfernt werden? Bitte sei Dir sicher, bevor Du klickst.<br><br>	  
      <form action='$scriptadresse' method='post'>
      Gib hier: &quot;ICH BIN MIR SICHER&quot; ein, ohne Anf&uuml;hrungszeichen:<br>
	  <input type='text' name='sicherheitsfrage'><br><br>
	  <input type='hidden' name='action' value='suredeletecon'>
	  <input type='hidden' name='deleteid' value='$id'>
	  <input type='Submit' name='senden' value='L&ouml;schen'>      
      </form>
      "
      );
    }
    else
    {
      print_site(
      "Fehler",
      "<a href='$scriptadresse'>Zur&uuml;ck zur Auswahl</a><br><br>Es wurde kein Con mit dieser ID gefunden."
    );
    } 
  }
  elseif($action == "suredeletecon" && $deleteid != "" && is_numeric($deleteid))
  {
    if($sicherheitsfrage == "ICH BIN MIR SICHER")
	{
	  $mysql = "DELETE FROM conmanagement_conliste WHERE id='$deleteid' LIMIT 1;";
      $mysql = mysqli_query($link,$mysql);
      $num = mysqli_affected_rows($link);
	
	  $mysql2 = "DELETE FROM conmanagement_anmeldungen WHERE conid='$deleteid';";
      $mysql2 = mysqli_query($link,$mysql2);
      $num2 = mysqli_affected_rows($link);
	  
      if($num == 1 && $num2 >= 1)
      {
		  print_site(
          'Veranstaltung erfolgreich gel&ouml;scht',
          'Es wurden &quot;'.$num.'&quot; Eintr&auml;ge in der Conliste gel&ouml;scht und &quot;'.$num2.'&quot; Anmeldungen zu dieser Veranstaltung gel&ouml;scht.
		  <br><br>
		  <a href="'.$scriptadresse.'">Zur&uuml;ck zur &Uuml;bersicht</a>'
          );
	  }		
	  else
	  {
		  print_site(
          'Fehler beim L&ouml;schen',
          'Es wurde scheinbar nicht alles gel&ouml;scht. Es wurden &quot;'.$num.'&quot; Eintr&auml;ge in der Conliste gel&ouml;scht und &quot;'.$num2.'&quot; Anmeldungen zu dieser Veranstaltung gel&ouml;scht.
          <br><br>
		  <a href="'.$scriptadresse.'">Zur&uuml;ck zur &Uuml;bersicht</a>'
          );
	  }	
	}
	else
	{
	  print_site(
        'Best&auml;tigung falsch eingegeben',
        'Der Best&auml;tigungstext muss genau &quot;ICH BIN MIR SICHER&quot; ohne Anf&uuml;hrungszeichen sein. Gehe auf &quot;Zur&uuml;ck&quot; in dem Webbrowser und versuche es ggf. nochmal.'
      );		
	}
  }
  else
  {
    $mysql = "SELECT id, conname FROM conmanagement_conliste";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    if($num >= 1)
    {
      $conliste = "";

      for($i=0; $i < $num; $i++)
      {
        $conid = just_my_result($mysql, $i, "id");
        $conname = just_my_result($mysql, $i, "conname");
        $conliste .= "<a href='$scriptadresse?action=showcon&id=$conid'>$conname</a><br>";
      }
    }

    print_site(
    'Converwaltung',
    "<a href='$scriptadresse?action=newcon'>Neuen Con anlegen</a><br><br>Oder einen bestehenden Con zum &auml;ndern/l&ouml;schen ausw&auml;hlen:<br><br>$conliste"
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