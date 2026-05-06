<?php

require_once('i/western_design.php');
require_once('i/mysql.php');
require_once('validate.php');

if(session_status() == PHP_SESSION_NONE){session_start();}
$sessionid = session_id();

$scriptadresse = "https://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];

$action = $_REQUEST['action'];
$sortby = $_REQUEST['sortby'];
$id = $_REQUEST['id'];
$page = $_REQUEST['page'];

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

$schwanger = $_REQUEST['schwanger'];
$essgewohnheiten = $_REQUEST['essgewohnheiten'];
$krankheiten = $_REQUEST['krankheiten'];
$allergien = $_REQUEST['allergien'];
$phobien = $_REQUEST['phobien'];

$typ1 = $_REQUEST['typ1'];
$typ2 = $_REQUEST['typ2'];
$typ3 = $_REQUEST['typ3'];
$typ4 = $_REQUEST['typ4'];
$typ5 = $_REQUEST['typ5'];

$typ = $_REQUEST['typ'];
$bemerkung = $_REQUEST['bemerkung'];
$verzug = $_REQUEST['verzug'];
$einladung = $_REQUEST['einladung'];

connect2mysql();

if(validate("Administrator"))
{
  if($action == 'change' && $id != "" && is_numeric($id))
  {
    if($reg == "User loeschen")
    {
      print_site(
        'User wirklich l&ouml;schen?',
        "<p class='block1'>
        Den Benutzer $vname $nname wirklich l&ouml;schen?<br><br>
        <form action='$scriptadresse' method='post'>
            <input type='hidden' name='PHPSESSID' value='$sessionid' />
            <input type='hidden' name='action' value='suredel' />
            <input type='hidden' name='id' value='$id' />
            <input type='submit' id='reg' name='reg' value='Ja, l&ouml;schen'/>&nbsp;&nbsp;&nbsp;<input type='submit' name='action' value='Zur&uuml;ck'>
        </form></p>
        "
      );
    }
    else
    {
      if(($reg == '') || ($vname == '') || ($nname == '') || ($strasse == '') || ($plz == '') || ($ort == '') || ($sex == '-') || ($gebdat1 == '') || ($gebdat2 == '') || ($gebdat3 == ''))
      {
        print_site(
          'Daten unvollst&auml;ndig',
          "Die eingegebenen Daten sind unvollst&auml;ndig. Bitte f&uuml;lle alle mit einem * markierten Felder aus.<br>Dr&uuml;cke &quot;Zur&uuml;ck&quot; im Webbrowser und korrigiere die Angaben."
        );
      }
      else
      {
        $gebdatkomplett = $gebdat3."-".$gebdat2."-".$gebdat1;

        $userid = $id;

        $mysql = "SELECT `vname`, `nname`, `mail` FROM conmanagement_user WHERE id='$userid' LIMIT 1;";
        $mysql = mysqli_query($link,$mysql);

        $oldvname = just_my_result($mysql, 0, "vname");
        $oldnname = just_my_result($mysql, 0, "nname");
        $oldmail = just_my_result($mysql, 0, "mail");

        $typ = $typ1.",".$typ2.",".$typ3.",".$typ4.",".$typ5;

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
        $user_aendern .= "phobien='$phobien',";

        $user_aendern .= "typ='$typ',";
        $user_aendern .= "bemerkung='$bemerkung',";
        $user_aendern .= "verzug='$verzug',";
        $user_aendern .= "einladung='$einladung'";

        $mysql="UPDATE conmanagement_user SET $user_aendern WHERE vname='$oldvname' AND nname='$oldnname' AND mail='$oldmail' LIMIT 1;";
        $mysql = mysqli_query($link,$mysql);
        $num = mysqli_affected_rows($link);

        if($num == '1')
        {
          print_site(
            'Benutzerdaten ge&auml;ndert',
            "Die Benutzerdaten wurden ge&auml;ndert.<br><a href='./usermod.php'>Zur&uuml;ck zur Benutzerverwaltung</a>"
          );
        }
        else
        {
          print_site(
            'Fehler!',
            "Es gab einen Fehler bei dem Versuch, die Daten in die Datenbank einzutragen. Bitte versuche es erneut.<br>Sollte das Problem weiterhin bestehen, sag Jonas bescheid."
          );
        }
      }
    }
  }
  elseif($action == 'suredel' && $id != "" && is_numeric($id))
  {
    $mysql = "DELETE FROM conmanagement_user WHERE id='$id' LIMIT 1;";
    $mysql = mysqli_query($link,$mysql);

    $num2 = mysqli_affected_rows($link);

    if ($num2>0)
    {
      print_site(
      'User erfolgreich gel&ouml;scht',
      'Zur&uuml;ck zur <a href="./usermod.php">Benutzerverwaltung</a>'
      );
    }
    if ($num2<=0)
    {
      print_site(
      'Benutzer konnte NICHT gel&ouml;scht werden',
      'Es gab einen Fehler beim l&ouml;schen. Wenn das Problem l&auml;nger besteht, wende Dich bitte an Jonas.'
      );
    }
  }
  elseif($action == 'show' && $id != "" && is_numeric($id))
  {
    $userid = $id;

    $mysql = "SELECT `vname`, `nname`, `strasse`, `plz`, `ort`, DAYOFMONTH( geb_dat ) AS tag , MONTH( geb_dat ) AS monat , YEAR( geb_dat ) AS jahr, `sex`, `san`, `telefon`, `handy`, `mail`, `info`, `schwanger`, `essgewohnheiten`, `krankheiten`, `allergien`, `phobien`, `typ`, `bemerkung`, `verzug`, `einladung`, FIND_IN_SET('Administrator', `typ`) AS administrator, FIND_IN_SET('SL', `typ`) AS sl, FIND_IN_SET('Teilnehmer', `typ`) AS teilnehmer, FIND_IN_SET('Mitglied', `typ`) AS mitglied, FIND_IN_SET('Anwaerter', `typ`) AS anwaerter FROM conmanagement_user WHERE id='$userid' LIMIT 1;";
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

    $oldtyp = just_my_result($mysql, 0, "typ");
    $oldbemerkung = just_my_result($mysql, 0, "bemerkung");
    $oldverzug = just_my_result($mysql, 0, "verzug");
    $oldeinladung = just_my_result($mysql, 0, "einladung");

    $oldtyp1 = just_my_result($mysql, 0, "administrator");
    $oldtyp2 = just_my_result($mysql, 0, "sl");
    $oldtyp3 = just_my_result($mysql, 0, "teilnehmer");
    $oldtyp4 = just_my_result($mysql, 0, "mitglied");
    $oldtyp5 = just_my_result($mysql, 0, "anwaerter");

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

    if($oldeinladung == 'ja')
    {
      $einladungja = " selected='selected'";
    }
    else
    {
      $einladungnein = " selected='selected'";
    }

    if($oldtyp1 != 0)
    {
      $oldtyp1 = "checked";
    }
    if($oldtyp2 != 0)
    {
      $oldtyp2 = "checked";
    }
    if($oldtyp3 != 0)
    {
      $oldtyp3 = "checked";
    }
    if($oldtyp4 != 0)
    {
      $oldtyp4 = "checked";
    }
    if($oldtyp5 != 0)
    {
      $oldtyp5 = "checked";
    }

    print_site(
      'Benutzerverwaltung',
      "
      <p class='block1'>
        <form action='$scriptadresse' method='post'>
          <fieldset class='login'>
            <legend>Organisatorische Daten</legend>
            <div id='explain'>Hier sind die organisatorischen Daten</div>
            <label for='typ' accesskey='1'>Mitglied in Gruppen:</label>
            <table border='0'>
             <tr><td>Administrator:</td><td>&nbsp;<input type='checkbox' name='typ1' value='Administrator' tabindex='1' $oldtyp1></td></tr>
             <tr><td>SL:</td><td>&nbsp;<input type='checkbox' name='typ2' value='SL' tabindex='2' $oldtyp2></td></tr>
             <tr><td>Teilnehmer:</td><td>&nbsp;<input type='checkbox' name='typ3' value='Teilnehmer' tabindex='3' $oldtyp3></td></tr>
            </table>

            <!-- <label for='verzug' accesskey='2'>Verzug:</label><input type='text' name='verzug' id='verzug' value='$oldverzug' tabindex='4'/><br /> -->
            <!-- <label for='einladung' accesskey='3'>Einladung:</label> -->
            <!--             <p id='med'>
              <select name='einladung' tabindex='5'>
                <option value='ja'$einladungja>Ja</option>
                <option value='nein'$einladungnein>Nein</option>
              </select><br> -->

           </p>
            <label for='bemerkung' accesskey='4'>Bemerkung:</label>
            <textarea name='bemerkung' rows='3' cols='25' wrap='virtual' dir='ltr' tabindex='6'>$oldbemerkung</textarea>
            <br>
          </fieldset>

          <fieldset class='login'>
            <input type='hidden' name='PHPSESSID' value='$sessionid' />
            <input type='hidden' name='action' value='change' />
            <input type='hidden' name='id' value='$id' />
            <legend>Pers&ouml;nliche Daten</legend>
            <div id='explain'>Hier k&ouml;nnen die Daten des Benutzers ge&auml;ndert werden.</div>
            <label for='vorname' accesskey='5'>Vorname:</label><input type='text' name='vname' value='$oldvname' id='vorname' tabindex='7'/>&nbsp;*<br />
            <label for='nachname' accesskey='6'>Nachname:</label><input type='text' name='nname' id='nachname' value='$oldnname' tabindex='8'/>&nbsp;*<br />
            <label for='str' accesskey='7'>Stra&szlig;e + Hausnr.:</label><input type='text' name='stasse' id='str' value='$oldstrasse' tabindex='9'/>&nbsp;*<br />
            <label for='plz' accesskey='8'>Postleitzahl:</label><input type='text' name='plz' id='plz' value='$oldplz' tabindex='10'/>&nbsp;*<br />
            <label for='ort' accesskey='9'>Ort:</label><input type='text' name='ort' id='ort' value='$oldort' tabindex='11'/>&nbsp;*<br />
            <label for='geb' accesskey='10'>Geburtsdatum:</label>
            <p id='med'>
              <select name='gebdat1' tabindex='12'>
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
              <select name='gebdat2' tabindex='13'>
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
              <select name='gebdat3' tabindex='14'>
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
            </p>
            <label for='male' accesskey='11'>Geschlecht:</label>
            <p id='med'>
              <select name='sex' tabindex='15'>
                <option value='female'$female>Weiblich</option>
                <option value='male'$male>M&auml;nnlich</option>
              </select>&nbsp;*
            </p>
            <label for='med' accesskey='12'>Medizinische Ausbildung:</label>
            <p id='med'>
              <select name='san' tabindex='16'>
                <option value='Arzt'$arzt>Arzt</option>
                <option value='Ass'$ass>Rettungsassistent</option>
                <option value='Med'$med>Mediziner / AIP</option>
                <option value='San'$san>Sanit&auml;ter</option>
                <option value='EH'$eh>Ersthelfer</option>
                <option value='-'$keine>keine</option>
              </select>
            </p><br />
            <label for='tel' accesskey='13'>Telefon:</label><input type='text' name='telefon' id='tel' value='$oldtelefon' tabindex='17'/><br />
            <label for='mobil' accesskey='14'>Handy:</label><input type='text' name='handy' id='mobil' value='$oldhandy' tabindex='18'/><br />
            <label for='mail' accesskey='15'>Email:</label><input type='text' name='mail' id='mail' value='$oldmail' tabindex='19'/>&nbsp;*<br />
            <label for='info' accesskey='16'>D&uuml;rfen wir Dich &uuml;ber neue Cons informieren?</label>
            <p id='med'>
              <select name='info' tabindex='20' accesskey='17'>
                <option value='ja'$infoja>Ja</option>
                <option value='nein'$infonein>Nein</option>
              </select>
            </p><br />
            <label for='schwanger' accesskey='16'>Schwanger:</label>
            <p id='med'>
            <select name='schwanger' tabindex='16' accesskey='16'>
              <option value='nein'$schwangernein>Nein</option>
              <option value='ja'$schwangerja>Ja</option>
            </select>
            </p>
            <label for='essgewohnheiten' accesskey='17'>Besondere Essgewohnheiten:</label><input type='text' name='essgewohnheiten' id='essgewohnheiten' value='$oldessgewohnheiten' tabindex='17'/>&nbsp;<br /><br />
            <label for='krankheiten' accesskey='18'>Krankheiten:</label><input type='text' name='krankheiten' id='krankheiten' value='$oldkrankheiten' tabindex='18'/>&nbsp;<br />
            <label for='allergien' accesskey='19'>Allergien:</label><input type='text' name='allergien' id='allergien' value='$oldallergien' tabindex='19'/>&nbsp;<br />
            <label for='phobien' accesskey='20'>Phobien:</label><input type='text' name='phobien' id='phobien' value='$oldphobien' tabindex='20'/>&nbsp;<br />
            <div align='center'><br><br><input type='submit' id='reg' name='reg' value='&Auml;ndern' tabindex='21'/>&nbsp;&nbsp;&nbsp;<input type='submit' id='reg' name='reg' value='User loeschen' tabindex='22'/><br /><br></div>
            <b>Die mit * markierten Felder m&uuml;ssen ausgef&uuml;llt bleiben!</b>
          </fieldset>
        </form>
      </p>
      "
    );
  }
  else
  {
    $vnamesort = "asc";
    $nnamesort = "asc";
    $typsort = "asc";
    $idsort = "desc";
    $sbvname = "&nbsp;&nbsp;&nbsp;";
    $sbnname = "&nbsp;&nbsp;&nbsp;";
    $sbtyp = "&nbsp;&nbsp;&nbsp;";
    $sbid = "&nbsp;&nbsp;&nbsp;";


    if($sortby == "vnameasc")
    {
      $sbvname = "&nbsp;<img src='./western_images/runter.gif'>";
      $sqlsort = " ORDER BY `vname` ASC";
      $vnamesort = "desc";
    }
    elseif($sortby == "nnameasc")
    {
      $sbnname = "&nbsp;<img src='./western_images/runter.gif'>";
      $sqlsort = " ORDER BY `nname` ASC";
      $nnamesort = "desc";
    }
    elseif($sortby == "typasc")
    {
      $sbtyp = "&nbsp;<img src='./western_images/runter.gif'>";
      $sqlsort = " ORDER BY `typ` ASC";
      $typsort = "desc";
    }
    elseif($sortby == "idasc")
    {
      $sbid = "&nbsp;<img src='./western_images/runter.gif'>";
      $sqlsort = " ORDER BY `id` ASC";
      $idsort = "desc";
    }
    elseif($sortby == "vnamedesc")
    {
      $sbvname = "&nbsp;<img src='./western_images/rauf.gif'>";
      $sqlsort = " ORDER BY `vname` DESC";
      $vnamesort = "asc";
    }
    elseif($sortby == "nnamedesc")
    {
      $sbnname = "&nbsp;<img src='./western_images/rauf.gif'>";
      $sqlsort = " ORDER BY `nname` DESC";
      $nnamesort = "asc";
    }
    elseif($sortby == "typdesc")
    {
      $sbtyp = "&nbsp;<img src='./western_images/rauf.gif'>";
      $sqlsort = " ORDER BY `typ` DESC";
      $typsort = "asc";
    }
    elseif($sortby == "iddesc")
    {
      $sbid = "&nbsp;<img src='./western_images/rauf.gif'>";
      $sqlsort = " ORDER BY `id` DESC";
      $idsort = "asc";
    }
    else
    {
      $sbnname = "&nbsp;<img src='./western_images/runter.gif'>";
      $sqlsort = " ORDER BY `nname` ASC";
      $nnamesort = "desc";
    }

    $mysql="SELECT `id`, `vname`, `nname`, `typ` FROM `conmanagement_user`$sqlsort;";
    $mysql = mysqli_query($link,$mysql);
    $num = mysqli_num_rows($mysql);

 //   $userproseite = "100";
    //Hier einstellen, wieviele User auf einer Seite angezeigt werden sollen

/*    $seitenleiste = "";
    if($num > $userproseite)
    {
      $seitenleiste .= "Seite:&nbsp;";

      $counter = '0';
      $seitenzahl = '1';

      for($i='0';$i < $num; $i++)
      {
        $counter++;
        if($counter == $userproseite)
        {
          $seitenzahl++;
          $counter = '0';
        }
      }

      if($page >= '2')
      {
        $seitenleiste .= "<a href='$scriptadresse?page=1&sortby=$sortby'>1</a>";
      }
      else
      {
        $seitenleiste .= "1";
        $page = '1';
      }

      for($i=2;$i <= $seitenzahl; $i++)
      {
        if($page == $i)
        {
          $seitenleiste .= "&nbsp;-&nbsp;$i";
        }
        else
        {
          $seitenleiste .= "&nbsp;-&nbsp;<a href='$scriptadresse?page=$i&sortby=$sortby'>$i</a>";
        }
      }

      $seitenleiste .= "<br><br>";
    }

    */
    //Funktion, die einstellt, ab welchen User angezeigt werden soll, je nachdem welche Seite aufgerufen wurde.
/*
    if($page >= '2')
    {
      $rechenhilfe = $page - '1';
      $startuser = $rechenhilfe * $userproseite;
    }
    else
    {
      $startuser = '0';
    }

    $enduser = $startuser + $userproseite - 1;
*/
    $userlist = "";

    for($i=0; $i < $num; $i++)
    {
      $vorname = just_my_result($mysql, $i, "vname");
      $nachname = just_my_result($mysql, $i, "nname");
      $typ = just_my_result($mysql, $i, "typ");
      $id = just_my_result($mysql, $i, "id");

      $typ = str_replace(",", ", ", $typ);
      $typ = str_replace("Administrator", "Admin", $typ);

      $userlist .= "<tr>";
      $userlist .= "  <td>$id</td>";
      $userlist .= "  <td>$vorname</td>";
      $userlist .= "  <td>$nachname</td>";
      $userlist .= "  <td>$typ</td>";
      $userlist .= "  <td><a href='$scriptadresse?action=show&id=$id'>Edit</a></td>";
      $userlist .= "</tr>";

      /*
      if($i == $enduser)
      {
        break;
      }
      */
    }

    print_site(
      'Benutzerverwaltung',
      "Hier kannst Du die Details der registrierten Nutzer einsehen und &auml;ndern.<br><br>$seitenleiste
      <table border='0' cellspacing='4' width='100%'>
        <tr>
          <td width='10%'><b><a href='$scriptadresse?sortby=id$idsort'>ID</a></b>$sbid</td>
          <td width='25%'><b><a href='$scriptadresse?sortby=vname$vnamesort'>Vorname</a></b>$sbvname</td>
          <td width='25%'><b><a href='$scriptadresse?sortby=nname$nnamesort'>Name</a></b>$sbnname</td>
          <td width='25%'><b><a href='$scriptadresse?sortby=typ$typsort'>Typ</a></b>$sbtyp</td>
          <td width='15%'>&nbsp;</td>
        </tr>
      $userlist
      </table>
      "
    );
  }
}
else
{
  print_site(
    'Zugriff verweigert',
    'Man muss als Administrator angemeldet sein, um Benutzer bearbeiten zu k&ouml;nnen.'
  );
}

disconnectmysql();

?>