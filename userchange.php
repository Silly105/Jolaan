<?php

require_once('./i/western_design.php');
require_once('./i/mysql.php');
require_once('validate.php');

$scriptadresse = "https://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
$serveradresse = "https://".$_SERVER['SERVER_NAME'];

if(session_status() == PHP_SESSION_NONE){session_start();}
$sessionid = session_id();

$reg = $_REQUEST['reg'];
$action = $_REQUEST['action'];
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

$schwanger = $_REQUEST['schwanger'];
$essgewohnheiten = $_REQUEST['essgewohnheiten'];
$krankheiten = $_REQUEST['krankheiten'];
$allergien = $_REQUEST['allergien'];
$phobien = $_REQUEST['phobien'];

//if($_SESSION['user_id']!='' AND $_SESSION['logedin']=true)
if(validate("Registriert"))
{
  if($action == 'change')
  {
    if(($reg == '') || ($vname == '') || ($nname == '') || ($strasse == '') || ($plz == '') || ($ort == '') || ($sex == '-') || ($gebdat1 == '') || ($gebdat2 == '') || ($gebdat3 == '') || ($mail == ''))
    {
      print_site(
        'Daten unvollst&auml;ndig',
        "Die eingegebenen Daten sind unvollst&auml;ndig. Bitte f&uuml;lle alle mit einem * markierten Felder aus.<br>Dr&uuml;cke &quot;Zur&uuml;ck&quot; in Deinem Webbrowser und korrigiere die Angaben."
      );
    }
    else
    {
      require_once('./i/mysql.php');

      $gebdatkomplett = $gebdat3."-".$gebdat2."-".$gebdat1;

      $userid = $_SESSION['user_id'];

      connect2mysql();

      $mysql = "SELECT `vname`, `nname`, `strasse`, `plz`, `ort`, DAYOFMONTH( geb_dat ) AS tag , MONTH( geb_dat ) AS monat , YEAR( geb_dat ) AS jahr, `sex`, `san`, `telefon`, `handy`, `mail`, `info`, `id`, `schwanger`, `essgewohnheiten`, `krankheiten`, `allergien`, `phobien` FROM conmanagement_user WHERE id='$userid' LIMIT 1;";
      $mysql = mysqli_query($link,$mysql);

      $oldvname = just_my_result($mysql, 0, "vname");
      $oldnname = just_my_result($mysql, 0, "nname");
      $oldstrasse = just_my_result($mysql, 0, "strasse");
      $oldplz = just_my_result($mysql, 0, "plz");
      $oldort = just_my_result($mysql, 0, "ort");
      $oldtag = just_my_result($mysql, 0, "tag");
      $oldmonat = just_my_result($mysql, 0, "monat");
      $oldjahr = just_my_result($mysql, 0, "jahr");
      $oldsex = just_my_result($mysql, 0, "sex");
      $oldsan = just_my_result($mysql, 0, "san");
      $oldtelefon = just_my_result($mysql, 0, "telefon");
      $oldhandy = just_my_result($mysql, 0, "handy");
      $oldmail = just_my_result($mysql, 0, "mail");
      $oldinfo = just_my_result($mysql, 0, "info");

      $oldschwanger = just_my_result($mysql, 0, "schwanger");
      $oldessgewohnheiten = just_my_result($mysql, 0, "essgewohnheiten");
      $oldkrankheiten = just_my_result($mysql, 0, "krankheiten");
      $oldallergien = just_my_result($mysql, 0, "allergien");
      $oldphobien = just_my_result($mysql, 0, "phobien");

      $id = just_my_result($mysql, 0, "id");

      if($mail != $oldmail)
      {
        $pwchange = '1';
        $pwchangetext = "<br>Da Du Deine Email Adresse ge&auml;ndert hast, wurde Dir erneut ein zuf&auml;lliges Passwort zugeteilt und dieses per Email an Dich geschickt.<br><br>";

        $String = "Am Anfang war der Nepumuck und die Fuenfe der Fuenf. 5d5 quasi, also 5, nicht 4, auchnicht 6. 5. Genausowenig 123789 oder 0. Naja, Du wei&szlig;t schon!";
        $Length = 10;
        $String = md5($String);
        $StringLength = strlen($String);
        mt_srand ((double) microtime() * 1000000);
        $Begin = random_int(0,($StringLength-$Length-1));
        $newpw = substr($String, $Begin, $Length);

        $mysql = "UPDATE conmanagement_user SET `password`=PASSWORD(\"$newpw\") WHERE id='$id' LIMIT 1;";
        $mysql = mysqli_query($link,$mysql);

        $to=$mail;
        $subject="Neues Passwort zum Einloggen auf con.westliches-siegel.de";
        $message="<HTML><BODY TEXT=#000000>Hallo $vname,<br><br>Du hast Deine Email Adresse ge&auml;ndert, daher bekommst Du ein neues Passwort. Es lautet:<br><br>$newpw<br><br>Bitte melde Dich mit diesem Passwort an.<br>Du kannst es sp&auml;ter wieder &auml;ndern.<br><br>Gr&uuml;&szlig;e<br>Dein Westconsystem</BODY></HTML>";
        $xtra    = "Message-Id: ".time()."@westliches-siegel.de\r\n";
        $xtra   .= "From: con@westliches-siegel.de (Westliches-Siegel.de)\r\n";
        $xtra   .= 'X-Mailer: PHP/' . phpversion() . "\n";
        $xtra   .= "Content-Type: text/html\r\n";
        $xtra   .= "Organization: Westliches-Siegel.de\r\n";
        $xtra   .= "X-Priority: 0\r\n";

        mail((string) $to,
             $subject,
             $message,
             $xtra);
        //Mail mit Passwort ist raus.
      }

      $user_aendern = "vname='$vname',";
      $user_aendern .= "nname='$nname',";
      $user_aendern .= "strasse='$strasse',";
      $user_aendern .= "plz='$plz',";
      $user_aendern .= "ort='$ort',";
      $user_aendern .= "geb_dat='$gebdatkomplett',";
      $user_aendern .= "sex='$sex',";
      $user_aendern .= "san='$san',";
      $user_aendern .= "telefon='$telefon',";
      $user_aendern .= "handy='$handy',";
      $user_aendern .= "mail='$mail',";
      $user_aendern .= "info='$info',";
      $user_aendern .= "schwanger='$schwanger',";
      $user_aendern .= "essgewohnheiten='$essgewohnheiten',";
      $user_aendern .= "krankheiten='$krankheiten',";
      $user_aendern .= "allergien='$allergien',";
      $user_aendern .= "phobien='$phobien'";

      $mysql="UPDATE conmanagement_user SET $user_aendern WHERE vname='$oldvname' AND nname='$oldnname' AND mail='$oldmail' LIMIT 1;";
      $mysql = mysqli_query($link,$mysql);
      $num = mysqli_affected_rows($link);

      if($num == '1')
      {
        if($pwchange == '1')
        {
          session_unset();
        }

        print_site(
          'Benutzerdaten ge&auml;ndert',
          "Deine Benutzerdaten wurden ge&auml;ndert.$pwchangetext Weiterhin viel Spa&szlig;!"
        );
      }
      else
      {
        print_site(
          'Fehler!',
          "Es gab einen Fehler bei dem Versuch, Deine Daten in die Datenbank einzutragen.<br><br>Bitte versuche es erneut.<br>Sollte das Problem weiterhin bestehen, wende Dich bitte an die <a href='https://con.westliches-siegel.de/kontakt.php' target='_new'>Orga</a>."
        );
      }
    }
  }
  else
  {
    $userid = $_SESSION['user_id'];

    connect2mysql();

    $mysql = "SELECT `vname`, `nname`, `strasse`, `plz`, `ort`, DAYOFMONTH( geb_dat ) AS tag , MONTH( geb_dat ) AS monat , YEAR( geb_dat ) AS jahr, `sex`, `san`, `telefon`, `handy`, `mail`, `info`, `schwanger`, `essgewohnheiten`, `krankheiten`, `allergien`, `phobien` FROM conmanagement_user WHERE id='$userid' LIMIT 1;";
    $mysql = mysqli_query($link,$mysql);

    $oldvname = just_my_result($mysql, 0, "vname");
    $oldnname = just_my_result($mysql, 0, "nname");
    $oldstrasse = just_my_result($mysql, 0, "strasse");
    $oldplz = just_my_result($mysql, 0, "plz");
    $oldort = just_my_result($mysql, 0, "ort");
    $oldtag = just_my_result($mysql, 0, "tag");
    $oldmonat = just_my_result($mysql, 0, "monat");
    $oldjahr = just_my_result($mysql, 0, "jahr");
    $oldsex = just_my_result($mysql, 0, "sex");
    $oldsan = just_my_result($mysql, 0, "san");
    $oldtelefon = just_my_result($mysql, 0, "telefon");
    $oldhandy = just_my_result($mysql, 0, "handy");
    $oldmail = just_my_result($mysql, 0, "mail");
    $oldinfo = just_my_result($mysql, 0, "info");

    $oldschwanger = just_my_result($mysql, 0, "schwanger");
    $oldessgewohnheiten = just_my_result($mysql, 0, "essgewohnheiten");
    $oldkrankheiten = just_my_result($mysql, 0, "krankheiten");
    $oldallergien = just_my_result($mysql, 0, "allergien");
    $oldphobien = just_my_result($mysql, 0, "phobien");

    $tagesarray[$oldtag] = " selected='selected'";
    $monatsarray[$oldmonat] = " selected='selected'";
    $jahresarray[$oldjahr] = " selected='selected'";

    if($oldschwanger == 'ja')
    {
      $schwangerja = " selected='selected'";
    }
    else
    {
      $schwangernein = " selected='selected'";
    }

    if($oldsex == 'female')
    {
      $female = " selected='selected'";
    }
    else
    {
      $male = " selected='selected'";
    }

    if($oldsan == 'Arzt')
    {
      $arzt = " selected='selected'";
    }
    elseif($oldsan == 'Ass')
    {
      $ass = " selected='selected'";
    }
    elseif($oldsan == 'Med')
    {
      $med = " selected='selected'";
    }
    elseif($oldsan == 'San')
    {
      $san = " selected='selected'";
    }
    elseif($oldsan == 'EH')
    {
      $eh = " selected='selected'";
    }
    else
    {
      $keine = " selected='selected'";
    }

    if($oldinfo == 'ja')
    {
      $infoja = " selected='selected'";
    }
    else
    {
      $infonein = " selected='selected'";
    }

    print_site(
      'Benutzerdaten &auml;ndern',
      "
      <p class='block1'>
        <form action='$scriptadresse' method='post'>
           <input type='hidden' name='PHPSESSID' value='$sessionid' />
          <input type='hidden' name='action' value='change' />
          <table align=\"left\" width=\"100%\" border=\"0\">
            <tr><td colspan=\"2\">Hier kannst Du Deine bei uns hinterlegten Daten &auml;ndern. Bitte halte die Daten auf dem aktuellen Stand, wenn Du Dich zu unseren Cons anmelden willst.<br>Wenn Du Deine Email-Adresse &auml;nderst, wird Dir erneut eine Email mit einem Zufallspasswort zugesandt, um die Adresse zu &uuml;berpr&uuml;fen.<br><br></td></tr>
            <tr><td width=\"50%\">Vorname:&nbsp;</td><td><input type='text' name='vname' value='$oldvname' id='vorname' tabindex='1'/>&nbsp;*</td></tr>
            <tr><td>Nachname:&nbsp;</td><td><input type='text' name='nname' id='nachname' value='$oldnname' tabindex='2'/>&nbsp;*</td></tr>
            <tr><td>Stra&szlig;e + Hausnr.:&nbsp;</td><td><input type='text' name='stasse' id='str' value='$oldstrasse' tabindex='3'/>&nbsp;*</td></tr>
            <tr><td>Postleitzahl:&nbsp;</td><td><input type='text' name='plz' id='plz' value='$oldplz' tabindex='4'/>&nbsp;*</td></tr>
            <tr><td>Ort:&nbsp;</td><td><input type='text' name='ort' id='ort' value='$oldort' tabindex='5'/>&nbsp;*</td></tr>
            <tr><td>Geburtsdatum:&nbsp;</td><td>
              <select name='gebdat1' tabindex='6'>
                <option value='1'$tagesarray[1]>1</option>
                <option value='2'$tagesarray[2]>2</option>
                <option value='3'$tagesarray[3]>3</option>
                <option value='4'$tagesarray[4]>4</option>
                <option value='5'$tagesarray[5]>5</option>
                <option value='6'$tagesarray[6]>6</option>
                <option value='7'$tagesarray[7]>7</option>
                <option value='8'$tagesarray[8]>8</option>
                <option value='9'$tagesarray[9]>9</option>
                <option value='10'$tagesarray[10]>10</option>
                <option value='11'$tagesarray[11]>11</option>
                <option value='12'$tagesarray[12]>12</option>
                <option value='13'$tagesarray[13]>13</option>
                <option value='14'$tagesarray[14]>14</option>
                <option value='15'$tagesarray[15]>15</option>
                <option value='16'$tagesarray[16]>16</option>
                <option value='17'$tagesarray[17]>17</option>
                <option value='18'$tagesarray[18]>18</option>
                <option value='19'$tagesarray[19]>19</option>
                <option value='20'$tagesarray[20]>20</option>
                <option value='21'$tagesarray[21]>21</option>
                <option value='22'$tagesarray[22]>22</option>
                <option value='23'$tagesarray[23]>23</option>
                <option value='24'$tagesarray[24]>24</option>
                <option value='25'$tagesarray[25]>25</option>
                <option value='26'$tagesarray[26]>26</option>
                <option value='27'$tagesarray[27]>27</option>
                <option value='28'$tagesarray[28]>28</option>
                <option value='29'$tagesarray[29]>29</option>
                <option value='20'$tagesarray[30]>30</option>
                <option value='31'$tagesarray[31]>31</option>
              </select>&nbsp;
              <select name='gebdat2' tabindex='7'>
                <option value='1'$monatsarray[1]>1</option>
                <option value='2'$monatsarray[2]>2</option>
                <option value='3'$monatsarray[3]>3</option>
                <option value='4'$monatsarray[4]>4</option>
                <option value='5'$monatsarray[5]>5</option>
                <option value='6'$monatsarray[6]>6</option>
                <option value='7'$monatsarray[7]>7</option>
                <option value='8'$monatsarray[8]>8</option>
                <option value='9'$monatsarray[9]>9</option>
                <option value='10'$monatsarray[10]>10</option>
                <option value='11'$monatsarray[11]>11</option>
                <option value='12'$monatsarray[12]>12</option>
              </select>&nbsp;
              <select name='gebdat3' tabindex='8'>
                <option value='1920'$jahresarray[1920]>1920</option>
                <option value='1921'$jahresarray[1921]>1921</option>
                <option value='1922'$jahresarray[1922]>1922</option>
                <option value='1923'$jahresarray[1923]>1923</option>
                <option value='1924'$jahresarray[1924]>1924</option>
                <option value='1925'$jahresarray[1925]>1925</option>
                <option value='1926'$jahresarray[1926]>1926</option>
                <option value='1927'$jahresarray[1927]>1927</option>
                <option value='1928'$jahresarray[1928]>1928</option>
                <option value='1929'$jahresarray[1929]>1929</option>
                <option value='1930'$jahresarray[1930]>1930</option>
                <option value='1931'$jahresarray[1931]>1931</option>
                <option value='1932'$jahresarray[1932]>1932</option>
                <option value='1933'$jahresarray[1933]>1933</option>
                <option value='1934'$jahresarray[1934]>1934</option>
                <option value='1935'$jahresarray[1935]>1935</option>
                <option value='1936'$jahresarray[1936]>1936</option>
                <option value='1937'$jahresarray[1937]>1937</option>
                <option value='1938'$jahresarray[1938]>1938</option>
                <option value='1939'$jahresarray[1939]>1939</option>
                <option value='1940'$jahresarray[1940]>1940</option>
                <option value='1941'$jahresarray[1941]>1941</option>
                <option value='1942'$jahresarray[1942]>1942</option>
                <option value='1943'$jahresarray[1943]>1943</option>
                <option value='1944'$jahresarray[1944]>1944</option>
                <option value='1945'$jahresarray[1945]>1945</option>
                <option value='1946'$jahresarray[1946]>1946</option>
                <option value='1947'$jahresarray[1947]>1947</option>
                <option value='1948'$jahresarray[1948]>1948</option>
                <option value='1949'$jahresarray[1949]>1949</option>
                <option value='1950'$jahresarray[1950]>1950</option>
                <option value='1951'$jahresarray[1951]>1951</option>
                <option value='1952'$jahresarray[1952]>1952</option>
                <option value='1953'$jahresarray[1953]>1953</option>
                <option value='1954'$jahresarray[1954]>1954</option>
                <option value='1955'$jahresarray[1955]>1955</option>
                <option value='1956'$jahresarray[1956]>1956</option>
                <option value='1957'$jahresarray[1957]>1957</option>
                <option value='1958'$jahresarray[1958]>1958</option>
                <option value='1959'$jahresarray[1959]>1959</option>
                <option value='1960'$jahresarray[1960]>1960</option>
                <option value='1961'$jahresarray[1961]>1961</option>
                <option value='1962'$jahresarray[1962]>1962</option>
                <option value='1963'$jahresarray[1963]>1963</option>
                <option value='1964'$jahresarray[1964]>1964</option>
                <option value='1965'$jahresarray[1965]>1965</option>
                <option value='1966'$jahresarray[1966]>1966</option>
                <option value='1967'$jahresarray[1967]>1967</option>
                <option value='1968'$jahresarray[1968]>1968</option>
                <option value='1969'$jahresarray[1969]>1969</option>
                <option value='1970'$jahresarray[1970]>1970</option>
                <option value='1971'$jahresarray[1971]>1971</option>
                <option value='1972'$jahresarray[1972]>1972</option>
                <option value='1973'$jahresarray[1973]>1973</option>
                <option value='1974'$jahresarray[1974]>1974</option>
                <option value='1975'$jahresarray[1975]>1975</option>
                <option value='1976'$jahresarray[1976]>1976</option>
                <option value='1977'$jahresarray[1977]>1977</option>
                <option value='1978'$jahresarray[1978]>1978</option>
                <option value='1979'$jahresarray[1979]>1979</option>
                <option value='1980'$jahresarray[1980]>1980</option>
                <option value='1981'$jahresarray[1981]>1981</option>
                <option value='1982'$jahresarray[1982]>1982</option>
                <option value='1983'$jahresarray[1983]>1983</option>
                <option value='1984'$jahresarray[1984]>1984</option>
                <option value='1985'$jahresarray[1985]>1985</option>
                <option value='1986'$jahresarray[1986]>1986</option>
                <option value='1987'$jahresarray[1987]>1987</option>
                <option value='1988'$jahresarray[1988]>1988</option>
                <option value='1989'$jahresarray[1989]>1989</option>
                <option value='1990'$jahresarray[1990]>1990</option>
                <option value='1991'$jahresarray[1991]>1991</option>
                <option value='1992'$jahresarray[1992]>1992</option>
                <option value='1993'$jahresarray[1993]>1993</option>
                <option value='1994'$jahresarray[1994]>1994</option>
                <option value='1995'$jahresarray[1995]>1995</option>
                <option value='1996'$jahresarray[1996]>1996</option>
                <option value='1997'$jahresarray[1997]>1997</option>
                <option value='1998'$jahresarray[1998]>1998</option>
                <option value='1999'$jahresarray[1999]>1999</option>
                <option value='2000'$jahresarray[2000]>2000</option>
                <option value='2001'$jahresarray[2001]>2001</option>
                <option value='2002'$jahresarray[2002]>2002</option>
                <option value='2003'$jahresarray[2003]>2003</option>
                <option value='2004'$jahresarray[2004]>2004</option>
                <option value='2005'$jahresarray[2005]>2005</option>
                <option value='2006'$jahresarray[2006]>2006</option>
                <option value='2007'$jahresarray[2007]>2007</option>
                <option value='2008'$jahresarray[2008]>2008</option>
                <option value='2009'$jahresarray[2009]>2009</option>
                <option value='2010'$jahresarray[2010]>2010</option>
              </select>&nbsp;*
            </td></tr>
            <tr><td>Geschlecht:&nbsp;</td><td>
              <select name='sex' tabindex='9'>
                <option value='female'$female>Weiblich</option>
                <option value='male'$male>M&auml;nnlich</option>
              </select>&nbsp;*
            </td></tr>
            <tr><td>Medizinische Ausbildung:&nbsp;</td><td>
              <select name='san' tabindex='10'>
                <option value='Arzt'$arzt>Arzt</option>
                <option value='Ass'$ass>Rettungsassistent</option>
                <option value='Med'$med>Mediziner / AIP</option>
                <option value='San'$san>Sanit&auml;ter</option>
                <option value='EH'$eh>Ersthelfer</option>
                <option value='-'$keine>keine</option>
              </select>
            </td></tr>
            <tr><td>Telefon:&nbsp;</td><td><input type='text' name='telefon' id='tel' value='$oldtelefon' tabindex='11'/>&nbsp;</td></tr>
            <tr><td>Handy:&nbsp;</td><td><input type='text' name='handy' id='mobil' value='$oldhandy' tabindex='12'/>&nbsp;</td></tr>
            <tr><td>Email:&nbsp;</td><td><input type='text' name='mail' id='mail' value='$oldmail' tabindex='13'/>&nbsp;*</td></tr>
            <tr><td>D&uuml;rfen wir Dich &uuml;ber neue Cons informieren?&nbsp;</td><td>
              <select name='info' tabindex='14' accesskey='15'>
                <option value='ja'$infoja>Ja</option>
                <option value='nein'$infonein>Nein</option>
              </select>
            </td></tr>
            <tr><td>Schwanger:&nbsp;</td><td>
            <select name='schwanger' tabindex='16' accesskey='16'>
              <option value='nein'$schwangernein>Nein</option>
              <option value='ja'$schwangerja>Ja</option>
            </select>
            </td></tr>
            <tr><td>Besondere Essgewohnheiten:&nbsp;</td><td><input type='text' name='essgewohnheiten' id='essgewohnheiten' value='$oldessgewohnheiten' tabindex='17'/>&nbsp;</td></tr>
            <tr><td>Krankheiten:&nbsp;</td><td><input type='text' name='krankheiten' id='krankheiten' value='$oldkrankheiten' tabindex='18'/>&nbsp;</td></tr>
            <tr><td>Allergien:&nbsp;</td><td><input type='text' name='allergien' id='allergien' value='$oldallergien' tabindex='19'/>&nbsp;</td></tr>
            <tr><td>Phobien:&nbsp;</td><td><input type='text' name='phobien' id='phobien' value='$oldphobien' tabindex='20'/>&nbsp;</td></tr>
            <tr><td colspan=\"2\"><br><div align='center'><input type='submit' id='reg' name='reg' value='&Auml;ndern' tabindex='15'/><br /><br></div>
            <b>Die mit * markierten Felder sind Pflichtangaben.</b></td></tr>
            <tr><td colspan=\"2\"><br><br><font size=\"-2\">Anmerkung zum L&ouml;schen von Benutzern: Wenn ein endg&uuml;ltiges L&ouml;schen Deiner Daten gew&uuml;nscht ist, schreibe bitte eine kurze Email an die <a href=\"$serveradresse/kontakt.php\" target=\"_new\">Orga</a>. Wir werden Deine Daten dann vollst&auml;ndig aus unserem System l&ouml;schen.</font></td></tr>
          </table>
        </form>
      </p>
      "
    );
  }
}
else
{
  print_site(
  'Zugriff verweigert',
  'Man muss angemeldet sein, um seine Benutzerdaten &auml;ndern zu k&ouml;nnen.'
  );
}

disconnectmysql();

?>