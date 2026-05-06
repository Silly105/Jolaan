<?php
//Bei Aenderungen Error.html nicht vergessen!

function print_site($title,$content){
  ini_set ("expose_php", "off");   // versuche die php herkunft zu verschweigen

  header("Content-type: text/html; ; charset=UTF-8");
  header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
  header("Cache-Control: no-cache, must-revalidate");
  header("Pragma: no-cache");

  if(session_status() == PHP_SESSION_NONE){session_start();}

  require_once('validate.php');
  require 'sprache.php';

  $sidemenu = "<div class='side-label'><strong>$s_design_menu_nav</strong></div>
               <span class='bullet'>&middot;</span> <a href='index.php' class='side'>$s_design_menu_hp</a><br />
               <span class='bullet'>&middot;</span> <a href='http://www.westliches-siegel.de' class='side'>$s_design_menu_back</a><br />
               <span class='bullet'>&middot;</span> <a href='http://www.westliches-siegel.de/forum/index.php' class='side'>$s_design_menu_forum</a><br />
               <span class='bullet'>&middot;</span> <a href='http://westliches-siegel.de/articles.php?article_id=74' class='side'>$s_design_menu_datenschutzerklaerung</a><br />			   
			   <span class='bullet'>&middot;</span> <a href='http://www.westliches-siegel.de/infusions/impressum/impressum.php' class='side'>$s_design_menu_impressum</a><br />";

  if(validate("Administrator"))
  {
    //Navigation, Nutzerverwaltung und Administration
    $sidemenu .= "<br /><div class='side-label'><strong>Nutzerfunktionen</strong></div>
                  <span class='bullet'>&middot;</span> <a href='userchange.php' class='side'>Mein Profil</a><br />
                  <span class='bullet'>&middot;</span> <a href='passchange.php' class='side'>Passwort &auml;ndern</a><br />
                  <span class='bullet'>&middot;</span> <a href='logout.php' class='side'>Abmelden</a><br />

                  <br /><div class='side-label'><strong>Administration</strong></div>
                  <span class='bullet'>&middot;</span> <a href='anmeldeverwaltung.php' class='side'>Anmeldeverwaltung</a><br /><br />
                  <span class='bullet'>&middot;</span> <a href='usermod.php' class='side'>Nutzerverwaltung</a><br />
                  <span class='bullet'>&middot;</span> <a href='converwaltung.php' class='side'>Converwaltung</a><br />
                  <span class='bullet'>&middot;</span> <a href='newqi.php' class='side'>Neue Schnellinfo</a><br />";
  }
  elseif(validate("Registriert"))
  {
    //Navigation und Nutzerverwaltung
    $sidemenu .= "<br /><div class='side-label'><strong>Nutzerfunktionen</strong></div>
                  <span class='bullet'>&middot;</span> <a href='userchange.php' class='side'>Mein Profil</a><br />
                  <span class='bullet'>&middot;</span> <a href='passchange.php' class='side'>Passwort &auml;ndern</a><br />
                  <span class='bullet'>&middot;</span> <a href='logout.php' class='side'>Abmelden</a><br />";
  }
  else
  {
    //nur Navigation mit Loginfeldern
    $sidemenu .= "<br /><div class='side-label'><strong>$s_design_menu_orga</strong></div>
                  <span class='bullet'>&middot;</span> <a href='hpanmeldung.php' class='side'>$s_design_menu_login</a><br />";
  }

  echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='de' lang='de'>
<head>
<title>Westliches-Siegel.de</title>
<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
<meta name='description' content='Seiten des westlichen Siegels von Mythodea' />
<link rel='stylesheet' href='./css/styles.css' type='text/css' media='screen' />
<link rel='shortcut icon' href='favicon.ico' type='image/x-icon' />
</head>
<body>
<center><table cellpadding='0' cellspacing='0' width='90%' align='center'>
<tr>
<td class='full-header' align='center' valign='middle'><a href='index.php'><img src='western_images/logo.png' alt='' border='0' width='100%' /></a></td>
</tr>
</table>
<table cellpadding='0' cellspacing='0' width='90% ' align='center'>
<tr>
<td class='sub-header-left'></td>
<td class='sub-header'><ul>";

/* ###################################################################### */
$topmenu = "<li class='first-link white'> <span class='bullet'>&middot;</span> <a href='index.php'><span>$s_design_menu_hp</span></a></li>
<li class='white'> <span class='bullet'>&middot;</span> <a href='http://www.westliches-siegel.de'><span>$s_design_menu_back</span></a></li>
<li class='white'> <span class='bullet'>&middot;</span> <a href='http://www.westliches-siegel.de/forum/index.php'><span>$s_design_menu_forum</span></a></li>
<li class='white'> <span class='bullet'>&middot;</span> <a href='http://www.westliches-siegel.de/infusions/impressum/impressum.php'><span>$s_design_menu_impressum</span></a></li>";
/* ###################################################################### */
echo $topmenu;
/* ###################################################################### */

echo "</ul>
</td>
<td class='sub-header-right'></td>
</tr>
</table><br />";

//-------------------------
//Sprachauswahl
//-------------------------
$linktext = $_SERVER['REQUEST_URI'];
$linktext = str_replace("&s=de", "", $linktext);
$linktext = str_replace("&s=en", "", $linktext);
$linktext = str_replace("?s=de", "", $linktext);
$linktext = str_replace("?s=en", "", $linktext);

$linker = "";

if (strpbrk($linktext, '?'))
{
  $linker = "&";
}
else
{
  $linker .= "?";
}

$dateiname = $_SERVER['PHP_SELF'];
$dateiname = str_replace("/", "", $dateiname);

if (($dateiname == "conanmeldung.php")||($dateiname == "hpanmeldung.php")||($dateiname == "index.php")||($dateiname == "tierliste.php")) //ist english fertig uebersetzt
{
  if ($GLOBALS["s_lang"] == "en")
  {
    $flaguk = "<img src='./western_images/flagge_uk.jpg' alt='Flag UK' border='0' width='30' height='18'>";
  }
  else
  {
    $flaguk = "<a href='".$linktext.$linker."s=en'><img src='./western_images/flagge_uk.jpg' alt='Flag UK' border='0' width='20' height='12' title='View in English.'></a>";
  }
}
else
{
  if ($GLOBALS["s_lang"] == "uk") { redirect($linktext); }
  $flaguk = "<img src='./western_images/flagge_uk_dis.jpg' alt='Flag UK' border='0' width='20' height='12' title='Sorry, no English version yet.'>";
}

//------------------------------------

if ($GLOBALS["s_lang"] != "en" && $GLOBALS["s_lang"] != "nl")
{
  $flagde = "<img src='./western_images/flagge_de.jpg' alt='Flag DE' border='0' width='30' height='18'>";
}
else
{
  $flagde = "<a href='".$linktext.$linker."s=de'><img src='./western_images/flagge_de.jpg' alt='Flag DE' border='0' width='20' height='12' title='Auf Deutsch anzeigen.'></a>";
}

//echo "<table border='0' width='90%' align='center' cellspacing='0' cellpadding='0'><tr><td><table border='0' align='right' cellspacing='0' cellpadding='0'><tr><td>$flagnl&nbsp;</td><td>$flagde&nbsp;</td><td>$flaguk</td></tr></table></td></tr></table><br>";
echo "<table border='0' width='90%' align='center' cellspacing='0' cellpadding='0'><tr><td><table border='0' align='right' cellspacing='0' cellpadding='0'><tr><td>$flagde&nbsp;</td><td>$flaguk</td></tr></table></td></tr></table><br>";

//-------------------------
//Sprachauswahl Ende
//-------------------------


echo "<table cellpadding='0' cellspacing='0' width='90%' align='center' class='side-left'>
<tr>
<td class='side-border-left' valign='top'><table cellpadding='0' cellspacing='0' width='100%'>
<tr>
<td class='scapmain-left'></td>
<td class='scapmain' align='center'>$s_design_menu_title</td>
<td class='scapmain-right'></td>
</tr>
</table>
<table cellpadding='0' cellspacing='0' width='100%' class='spacer'>
<tr>
<td class='side-body'>";

/* ###################################################################### */
echo $sidemenu;
/* ###################################################################### */

echo "</td>
</tr>
</table>

</td><td class='main-bg' valign='top'><a id='content' name='content'></a>
<!--pre_article--><table width='100%' cellpadding='0' cellspacing='0'>
<tr>
<td class='capmain-left'></td>
<td class='capmain'>";

/* ###################################################################### */
echo $title;
/* ###################################################################### */

echo "</td><td class='capmain-right'></td></tr></table><table width='100%' cellpadding='0' cellspacing='0' class='spacer'><tr><td class='main-body middle-border'>";

/* ###################################################################### */
echo $content;
/* ###################################################################### */

echo "</td>
</tr>
<tr>
<td align='center' class='news-footer'>
<span class='bullet'>&nbsp;<!-- &middot; Fusszeile &middot; -->&nbsp;</span>
</td>
</tr>
</table>
<!--sub_article--></td></tr>
</table>
<table cellpadding='0' cellspacing='0' width='90% ' align='center' style='margin-top: 10px;'>
<tr>
<td class='sub-header-left'></td>
<td class='sub-header'><ul>";

/* ###################################################################### */
echo $topmenu;
/* ###################################################################### */

echo "</td>
<td class='sub-header-right'></td>
</tr>
</table><br />
<table cellpadding='0' cellspacing='0' width='90%' align='center'>
<tr>
<td class='full-header' align='center' valign='middle'><img src='western_images/fusslogo.png' border='0' width='100%' title='$s_design_easteregg' /></td>
</tr>
</table>
</center>
</body>
</html>


  ";
}
?>