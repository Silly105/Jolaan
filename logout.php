<?php
ini_set ("expose_php", "off");        // versuche die php herkunft zu verschweigen
ini_set ("url_rewriter.tags", "a=href"); // verhindert das automatische einfuegen eines "<input SESSID" nach dem "<form"

header("Content-type: text/html");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

if(session_status() == PHP_SESSION_NONE){session_start();}
session_unset();

require_once('i/western_design.php');

print_site(
"Auf Wiedersehen!",
"Wir hoffen Dich bald wieder hier zu sehen!"
);

?>