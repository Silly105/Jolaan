<?php

function minisafe($value){
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

require_once('i/mysql.php');

function validate($anfragetyp)
{
  connect2mysql();
  if(session_status() == PHP_SESSION_NONE){session_start();}

  $usermail = minisafe($_SESSION['nutzeremailadresse']);
  $userpw = minisafe($_SESSION['pw']);

  if(($usermail != "") AND ($userpw != ""))
  {
    $mysql = $mysqltext = "SELECT typ FROM conmanagement_user WHERE mail=\"".$usermail."\" AND password=\"".$userpw."\" LIMIT 1;";
    $mysql = mysqli_query($GLOBALS["link"],$mysql);

    if(mysqli_num_rows($mysql) != 1) // wenn abfrage fehlgeschlagen
    {      
      return 0;
    }
    else
    {
      $typ = explode(",",just_my_result($mysql, 0, "typ"));

      if(in_array($anfragetyp,$typ))
      {
        return true;
      }
      elseif($anfragetyp == "Registriert")
      {
        return true;
      }
      else
      {
        return 0;
      }
    }
  }
  else
  {
    return 0;
  }

  disconnectmysql();
}

?>