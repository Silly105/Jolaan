<?php

require_once('./i/mysql.php');
require_once('./i/western_design.php');
require_once('validate.php');

$scriptadresse = "https://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
$serveradresse = "https://".$_SERVER['SERVER_NAME'];
$sessionid = session_id();
if(session_status() == PHP_SESSION_NONE){session_start();}

$reg = $_REQUEST['reg'];
$vname = $_REQUEST['vname'];
$nname = $_REQUEST['nname'];
$strasse = $_REQUEST['stasse'];
$plz = $_REQUEST['plz'];
$ort = $_REQUEST['ort'];
$gebdat1 = $_REQUEST['gebdat1'];
$gebdat2 = $_REQUEST['gebdat2'];
$gebdat3 = $_REQUEST['gebdat3'];
$sex = $_REQUEST['sex'];
$san = $_REQUEST['san'];
$telefon = $_REQUEST['telefon'];
$handy = $_REQUEST['handy'];
$mail = $_REQUEST['mail'];
$info = $_REQUEST['info'];
$datenschutz = $_REQUEST['datenschutz'];

$schwanger = $_REQUEST['schwanger'];
$essgewohnheiten = $_REQUEST['essgewohnheiten'];
$krankheiten = $_REQUEST['krankheiten'];
$allergien = $_REQUEST['allergien'];
$phobien = $_REQUEST['phobien'];

if(validate("Registriert"))
{
  print_site(
  'Zugriff verweigert',
  'Wenn Du angemeldet bist, brauchst Du Dich nicht registrieren.'
  );
}
else
{
  if($reg == 'Registrieren')
  {
    connect2mysql();

    $mysql = "SELECT mail FROM conmanagement_user WHERE mail='$mail'";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

    disconnectmysql();

    if($num <= 0)
    {
      $mailvorhanden = "";
    }
    else
    {
      $mailvorhanden = "1";
    }

    if(($reg == '') || ($vname == '') || ($nname == '') || ($strasse == '') || ($plz == '') || ($ort == '') || ($sex == '-') || ($gebdat1 == '') || ($gebdat2 == '') || ($gebdat3 == '') || ($mail == '') || ($datenschutz != "ja"))
    {
      print_site(
        'Daten unvollst&auml;ndig',
        "Die eingegebenen Daten sind unvollst&auml;ndig. Bitte f&uuml;lle alle mit einem * markierten Felder aus, und akzeptiere die Datenschutzerkl&auml;rung.<br>Dr&uuml;cke &quot;Zur&uuml;ck&quot; in Deinem Webbrowser und korrigiere die Angaben."
      );
    }
    elseif($mailvorhanden == '1')
    {
      print_site(
        'Schon registriert?',
        "Die eingegebene Email-Adresse ist bereits bei uns registriert. Bitte registriere Dich nicht mehrfach.<br><br>Bei Problemen wende Dich an die <a href=\"$serveradresse/kontakt.php\" target=\"_new\">Orga</a>."
      );
    }
    else
    {
      $String = "Am Anfang war der Nepumuck und die Fuenfe der Fuenf. 5d5 quasi, also 5, nicht 4, auchnicht 6. 5. Genausowenig 123789 oder 0. Naja, Du weisst schon!";
      $Length = 10;
      $String = md5($String);
      $StringLength = strlen($String);
      mt_srand ((double) microtime() * 1000000);
      $Begin = random_int(0,($StringLength-$Length-1));
      $freipw = substr($String, $Begin, $Length);

      $gebdatkomplett = $gebdat3."-".$gebdat2."-".$gebdat1;

      $user_eintrag = "vname, nname, strasse, plz, ort, geb_dat, sex, san, telefon, handy, mail, info, schwanger, essgewohnheiten, krankheiten, allergien, phobien";
      $user_eintrag2 = "'$vname', '$nname', '$strasse', '$plz', '$ort', '$gebdatkomplett', '$sex', '$san', '$telefon', '$handy', '$mail', '$info', '$schwanger', '$essgewohnheiten', '$krankheiten', '$allergien', '$phobien'";

      connect2mysql();

      $mysql="INSERT conmanagement_user ($user_eintrag) values($user_eintrag2)";
      $mysql = mysqli_query($link,$mysql);
      $num = mysqli_affected_rows($link);

      $mysql = "UPDATE conmanagement_user SET `password`=PASSWORD(\"$freipw\") WHERE vname='$vname' AND nname='$nname' AND mail='$mail' LIMIT 1;";
      $mysql = mysqli_query($link,$mysql);

      if($num == '1')
      {
        $varspace['to']=$mail;
        $varspace['subject'] = "Passwort fuer das Consystem des Westens";
        $varspace['htmlmessage'] = "<HTML><BODY TEXT=#000000>Hallo $vname,<br><br>Dein Passwort zum Anmelden auf con.westliches-siegel.de lautet:<br><br>$freipw<br><br>Du kannst Dich jetzt mit dem Passwort anmelden.<br>Wenn Du m&ouml;chtest, kannst Du es jederzeit &auml;ndern.<br><br>Gr&uuml;&szlig;e<br>Dein Westconsystem</BODY></HTML>";
        //$message = "Hallo $vname,\n\nDein Passwort zum Anmelden auf con.westliches-siegel.de lautet:\n\n$freipw\n\nDu kannst Dich jetzt mit dem Passwort anmelden.\nWenn Du moechtest, kannst Du es jederzeit aendern.\n\nGruesse\nDeine Westorga";
        
         mail_senden($varspace);
        //Mail mit Passwort ist raus.

        print_site(
          'Registrierung abgeschlossen',
          "Die Daten sind bei uns angekommen. Dir wurde eine Email mit Deinem Passwort geschickt, mit dem Du Dich anmelden kannst.<br><br>
          <b>Bitte suche auch in Deinen Spam-Filter</b>, soweit vorhanden, leider landen unsere Emails immer &ouml;fter dort."
        );
      }
      else
      {
        print_site(
          'Fehler!',
          "Es gab einen Fehler bei dem Versuch, Deine Daten in die Datenbank einzutragen. Bitte versuche es erneut.<br>Sollte das Problem weiterhin bestehen, wende Dich bitte an die <a href='https://con.westliches-siegel.de/kontakt.php' target='_new'>Orga</a>."
        );
      }
    }
  }
  else
  {
    print_site(
      'Registrieren',
      "
      <p class='block1'>
        <form action='$scriptadresse' method='post'>
          <fieldset class='login'>
            <input type='hidden' name='PHPSESSID' value='$sessionid' />
            <legend>Registrieren</legend>
            <div id='explain'>Bitte gib Deine pers&ouml;nlichen Daten ins System ein. Dies vereinfacht der Orga die Arbeit.<br><br></div>
            <label for='vorname' accesskey='1'>Vorname:</label><input type='text' name='vname' id='vorname' tabindex='1'/>&nbsp;*<br />
            <label for='nachname' accesskey='2'>Nachname:</label><input type='text' name='nname' id='nachname' tabindex='2'/>&nbsp;*<br />
            <label for='str' accesskey='3'>Stra&szlig;e + Hausnr.:</label><input type='text' name='stasse' id='str'  tabindex='3'/>&nbsp;*<br />
            <label for='plz' accesskey='4'>Postleitzahl:</label><input type='text' name='plz' id='plz'  tabindex='4'/>&nbsp;*<br />
            <label for='ort' accesskey='5'>Ort:</label><input type='text' name='ort' id='ort' tabindex='5'/>&nbsp;*<br /><br />
            <label for='geb' accesskey='6'>Geburtsdatum:</label>
            <p id='med'>
              <select name='gebdat1' tabindex='6'>
                <option value='1' selected='selected'>1</option>
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
              </select>&nbsp;
              <select name='gebdat2' tabindex='7'>
                <option value='1' selected='selected'>1</option>
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
              </select>&nbsp;
              <select name='gebdat3' tabindex='8'>
                <option value='1940' selected='selected'>1940</option>
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
                <option value='1980'>1980</option>
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
                <option value='2000'>2001</option>
                <option value='2000'>2002</option>
                <option value='2000'>2003</option>
                <option value='2000'>2004</option>
                <option value='2000'>2005</option>
                <option value='2000'>2006</option>
                <option value='2000'>2007</option>
                <option value='2000'>2008</option>
                <option value='2000'>2009</option>
                <option value='2000'>2010</option>
              </select>&nbsp;*
            </p>
            <label for='male' accesskey='10'>Geschlecht:</label>
            <p id='med'>
              <select name='sex' tabindex='9'>
                <option value='-' selected='selected'>Bitte w&auml;hlen</option>
                <option value='female'>Weiblich</option>
                <option value='male'>M&auml;nnlich</option>
              </select>&nbsp;*
            </p>
            <label for='med' accesskey='11'>Medizinische Ausbildung:</label>
            <p id='med'>
              <select name='san' tabindex='10'>
                <option value='Arzt'>Arzt</option>
                <option value='Ass'>Rettungsassistent</option>
                <option value='Med'>Mediziner / AIP</option>
                <option value='San'>Sanit&auml;ter</option>
                <option value='EH'>Ersthelfer</option>
                <option value='-' selected='selected'>keine</option>
              </select>
            </p><br />
            <label for='tel' accesskey='12'>Telefon:</label><input type='text' name='telefon' id='tel' tabindex='11'/><br />
            <label for='handy' accesskey='13'>Handy:</label><input type='text' name='handy' id='handy' tabindex='12'/><br />
            <label for='mail' accesskey='14'>Email:</label><input type='text' name='mail' id='mail' tabindex='13'/>&nbsp;*<br />
            <label for='info' accesskey='15'>D&uuml;rfen wir Dich &uuml;ber neue&nbsp;Cons informieren?&nbsp;</label><br />
            <p id='med'>
              <select name='info' tabindex='14' accesskey='15'>
                <option value='ja' selected='selected'>Ja</option>
                <option value='nein'>Nein</option>
              </select>
            </p>
            <label for='schwanger' accesskey='16'>Schwanger:</label>
            <p id='med'>
            <select name='schwanger' tabindex='16' accesskey='16'>
              <option value='nein' selected='selected'>Nein</option>
              <option value='ja'>Ja</option>
            </select>
            </p>
            <label for='essgewohnheiten' accesskey='17'>Besondere Essgewohnheiten:</label><input type='text' name='essgewohnheiten' id='essgewohnheiten' tabindex='17'/>&nbsp;<br /><br />
            <label for='krankheiten' accesskey='18'>Krankheiten:</label><input type='text' name='krankheiten' id='krankheiten'  tabindex='18'/>&nbsp;<br />
            <label for='allergien' accesskey='19'>Allergien:</label><input type='text' name='allergien' id='allergien'  tabindex='19'/>&nbsp;<br />
            <label for='phobien' accesskey='20'>Phobien:</label><input type='text' name='phobien' id='phobien'  tabindex='20'/>&nbsp;<br /><br />

            <label for='datenschutz' accesskey='21'>Ich habe die <a href='$serveradresse/datenschutz.php' target='_new'>Datenschutzerkl&auml;rung</a> gelesen und akzeptiert.</label><br />
            <p id='med'>
             <select name='datenschutz' tabindex='21' accesskey='21'>
              <option value='nein' selected='selected'>Nein</option>
              <option value='ja'>Ja</option>
             </select>&nbsp;*
            </p><br />
            <label for='reg' accesskey='22'>&nbsp;</label><input type='submit' id='reg' name='reg' value='Registrieren' tabindex='22'/><br><br>
            <b>Die mit * markierten Felder sind Pflichtangaben.</b>
          </fieldset>
        </form>
      </p>
      "
    );
  }
}

disconnectmysql();

?>