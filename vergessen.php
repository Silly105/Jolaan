<?php
$scriptadresse = "https://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];

require_once('./i/mysql.php');
require_once('./i/western_design.php');
require_once('./mailsenden.php');

$action = $_REQUEST['action'];
$vorname = $_REQUEST['vorname'];
$nachname = $_REQUEST['nachname'];
$plz = $_REQUEST['plz'];
$mail = $_REQUEST['mail'];
$absenden = $_REQUEST['absenden'];

if($action == 'mailpassword')
{
  if(($vorname == '') || ($nachname == '') || ($plz == '') || ($mail == ''))
  {
    print_site(
      "Daten unvollst&auml;ndig",
      "Bitte gib im alle Daten vollst&auml;ndig ein, damit wir Dich sicher zuordnen k&ouml;nnen.<br>Dr&uuml;cke &quot;Zur&uuml;ck&quot; im Webbrowser und vervollst&auml;ndige die Daten."
    );
  }
  else
  { //daten passen nicht zur DB
    connect2mysql();
    $mysql="SELECT `id`, `vname`, `nname`, `plz`, `mail` FROM conmanagement_user WHERE mail='$mail' LIMIT 1;";
    $mysql = mysqli_query($link,$mysql);
    $num0 = mysqli_affected_rows($link);

    if($num0 != '0')
    {
      $dbid = just_my_result($mysql, 0, "id");
      $dbvname = just_my_result($mysql, 0, "vname");
      $dbnname = just_my_result($mysql, 0, "nname");
      $dbplz = just_my_result($mysql, 0, "plz");
      $dbmail = just_my_result($mysql, 0, "mail");
    }

    if(($dbvname == $vorname) && ($dbnname == $nachname) && ($dbplz == $plz) && ($dbmail == $mail))
    {
      $String = "Am Anfang war der Nepumuck und die Fuenfe der Fuenf. 5d5 quasi, also 5, nicht 4, auchnicht 6. 5. Genausowenig 123789 oder 0. Naja, Du weisst schon!";
      $Length = 10;
      $String = md5($String);
      $StringLength = strlen($String);
      mt_srand ((double) microtime() * 1000000);
      $Begin = random_int(0,($StringLength-$Length-1));
      $newpw = substr($String, $Begin, $Length);

      $mysql = "UPDATE conmanagement_user SET `password`=PASSWORD(\"$newpw\") WHERE id='$dbid' LIMIT 1;";
      $mysql = mysqli_query($link,$mysql);

      $num = mysqli_affected_rows($link);	  

      if($num == '1')
      {
        $varspace['to']=$dbmail;
        $varspace['subject']="Neues Passwort zum Einloggen auf con.westliches-siegel.de";
        $varspace['htmlmessage']="<HTML><BODY TEXT=#000000>Hallo $vorname $nachname,<br><br>Dein neues Passwort f&uuml;r con.westliches-siegel.de lautet:<br><br>$newpw<br><br>Bitte melde mit diesem Passwort an.<br>Du kannst es sp&auml;ter wieder &auml;ndern.<br><br>Gr&uuml;&szlig;e<br>Dein Westconsystem</BODY></HTML>";
        
        mail_senden($varspace);        
        //Mail mit Passwort ist raus.

        print_site(
          "Email verschickt",
          "Es wurde Eine Email an Dich geschickt, die ein neu generiertes Passwort f&uuml;r Dich enth&auml;lt.<br>Bitte melde mit diesem Passwort an, Du kannst es sp&auml;ter wieder &auml;ndern."
        );
      }
      else
      {
        print_site(
          "Fehler!",
          "Es gab einen Fehler bei dem Versuch, Dein neues Passwort in die Datenbank einzutragen. Bitte versuche es erneut.<br>Sollte das Problem weiterhin bestehen, wende Dich bitte an die <a href='https://con.westliches-siegel.de/kontakt.php' target='_new'>Orga</a>."
        );
      }
    }
    else
    {
      print_site(
        "Kein passender User gefunden",
        "Es wurde kein passender User in der Datenbank gefunden. Entweder sind die eingegebenen Daten inkorrekt oder Du bist noch nicht registriert.<br>Sollte das Problem weiterhin bestehen, wende Dich bitte an die <a href='https://con.westliches-siegel.de/kontakt.php' target='_new'>Orga</a>."
      );
    }
  }
}
else
{
  print_site(
    'Passwort vergessen',   
    "
    <p class='block1'>
      <form action='$scriptadresse' method='post'>
        <fieldset class='login'>
          <legend>Ich habe mein Passwort vergessen!</legend>
          <div id='explain'>Bitte f&uuml;llt alle Felder korrekt aus.</div><br>
          <label for='vorname' accesskey='1'>Vorname:</label><input type='text' name='vorname' id='vorname' /><br />
          <label for='nachname' accesskey='2'>Nachname:</label><input type='text' name='nachname' id='nachname' /><br />
          <label for='plz' accesskey='3'>Postleitzahl:</label><input type='text' name='plz' id='plz' /><br />
          <label for='mail' accesskey='4'>Email Adresse:</label><input type='text' name='mail' id='mail' /><br /><br />
          <label for='login' accesskey='5'></label><input type='submit' id='login' name='absenden' value='Absenden' />
          <input type='hidden' name='action' value='mailpassword' />
        </fieldset>
      </form>
     </p>"
  );
}

disconnectmysql();