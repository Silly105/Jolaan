<?php

require_once('./i/western_design.php');
require_once('validate.php');
require_once('i/sprache.php');

if(session_status() == PHP_SESSION_NONE){session_start();}

if(validate("Registriert"))
{
  die(header('Location: ./logedin.php'));
}
else
{
  $sessionid = session_id();

  if(isset($_SESSION['mail']))
  {
    $sessionmail = " value=".$_SESSION['mail'];
  }

  if(isset($_SESSION['FEHLER']['LOGIN']) AND $_SESSION['FEHLER']['LOGIN']=='ZUGANGSDATEN')
  {
    $loginfehler1 = " class='loginfehler'";
  }

  if(isset($_SESSION['FEHLER']['LOGIN']) AND ($_SESSION['FEHLER']['LOGIN']=='ZUGANGSDATEN' OR $_SESSION['FEHLER']['LOGIN']=='PASSWORD'))
  {
    $loginfehler2 =  " class='loginfehler'";
  }

  //reglink = <a href='./registrieren.php'>Neu registrieren</a><br>

  print_site(
    $s_hpanmeldung_title,
    "<p class='block1'>
       <form action='./login.php' method='post'>
         <fieldset class='login'>
           <input type='hidden' name='PHPSESSID' value='$sessionid' />
           <legend>$s_hpanmeldung_orgalogin</legend>
           <div id='explain'>$s_hpanmeldung_explain.<br><br></div>
           <table border='0' align='left' width='80%'>
            <tr>
             <td>$s_hpanmeldung_mail:</td>
             <td><input type='text' name='m' id='email'$sessionmail$loginfehler1 /></td>
            </tr>
            <tr>
             <td>$s_hpanmeldung_pw:</td>
             <td><input type='password' name='p' id='password'$loginfehler2 /><br /></td>
            </tr>
            <tr>
             <td>&nbsp;</td>
              <td>
               <a href='./vergessen.php'>$s_hpanmeldung_lostpw</a><br>
               <!-- Reglink -->
               <br>
               <input type='submit' id='login' name='l' value='Login' /> <br />
             </td>
            </tr>
           </table>
           <br clear='all' >
         </fieldset>
       </form>
     </p>"
  );
}

?>