<?php
$scriptadresse = "https://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];

require_once('./i/mysql.php');
require_once('./i/western_design.php');
require_once('validate.php');

if(session_status() == PHP_SESSION_NONE){session_start();}

$action = $_REQUEST['action'];
$altespw = $_REQUEST['altespw'];
$neuespw1 = $_REQUEST['neuespw1'];
$neuespw2 = $_REQUEST['neuespw2'];

//if($_SESSION['user_id']!='' AND $_SESSION['logedin']=true)
if(validate("Registriert"))
{
  if($action == 'passchange')
  {
    connect2mysql();
    $usersession = $_SESSION['user_id'];
    $mysql = "SELECT `id`, `password` FROM conmanagement_user WHERE password=PASSWORD(\"$altespw\") AND id='$usersession' LIMIT 1;";
    $mysql = mysqli_query($link,$mysql);

    $dbpassword = just_my_result($mysql, 0, "password");
    $dbid = just_my_result($mysql, 0, "id");

    $num = mysqli_affected_rows($link);

    if($num == '0')
    {
      print_site(
        "Fehler!",
        "Es gab einen Fehler. Dein altes Passwort passt nicht zu Deiner User-ID.<br>K&ouml;nnte es sein, das Du Dich verschrieben hast?<br>Bitte versuche es erneut.<br><br>Sollte das Problem weiterhin bestehen, wende Dich bitte an die <a href='https://con.westliches-siegel.de/kontakt.php' target='_new'>Orga</a>."
      );
    }
    elseif($num == '1')
    {
      if($neuespw1 != $neuespw2)
      {
        print_site(
          "Passw&ouml;rter nicht gleich",
          "Die Passwortwiederholung war nicht gleich dem ersten Passwort. Bitte dr&uuml;cke auf &quot;Zur&uuml;ck&quot; in Deinem Browser und versuche es erneut."
        );
      }
      else
      {
        $mysql = "UPDATE conmanagement_user SET `password`=PASSWORD(\"$neuespw2\") WHERE id='$dbid' LIMIT 1;";
        $mysql = mysqli_query($link,$mysql);

        $num = mysqli_affected_rows($link);

        if($num == '1')
        {
          session_unset();
          print_site(
            "Passwort ge&auml;ndert",
            "Dein Passwort wurde ge&auml;ndert. Bitte melde Dich mit dem neuen Passwort an."
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
    }
  }
  else
  {
    print_site(
      "Passwort &auml;ndern",
      "<p class='block1'>
         <form action='$scriptadresse' method='post'>
           <fieldset class='login'>
             <legend>Passwort &auml;ndern</legend>
             <input type='hidden' name='action' value='passchange' />
             <div id='explain'>Hier kannst Du Dein Passwort &auml;ndern.</div><br>
             <label for='altespw' accesskey='1'> Altes Passwort:&nbsp;</label><input type='password' name='altespw' id='altespw' /><br />
             <label for='neuespw1' accesskey='1'>Passwort:&nbsp;</label><input type='password' name='neuespw1' id='plz' /><br />
             <label for='neuespw2' accesskey='2'>Passwortwiederholung:</label><input type='password' name='neuespw2' id='neuespw2' /><br /><br />
             <label for='login' accesskey='3'>&nbsp;</label><input type='submit' id='login' name='l' value='Absenden' />
           </fieldset>
         </form>
       </p>"
    );
  }
}
else
{
  print_site(
  'Zugriff verweigert',
  'Man muss angemeldet sein, um sein Passwort &auml;ndern zu k&ouml;nnen.'
  );
}

disconnectmysql();

?>