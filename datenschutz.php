<?php
ini_set ("expose_php", "off");        // versuche die php herkunft zu verschweigen
ini_set ("url_rewriter.tags", "a=href"); // verhindert das automatische einfuegen eines "<input SESSID" nach dem "<form"

header("Content-type: text/html");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

if(session_status() == PHP_SESSION_NONE){session_start();}

require_once('i/western_design.php');

print_site(
"Datenschutzerkl&auml;rung",
"1. Die Westorga oder durch sie bevollm&auml;chtigte Stellvertreter k&ouml;nnen zu organisatorischen Zwecken Einsicht in alle gespeicherten Daten bekommen.<br><br>
2. Das Passwort wird verschl&uuml;sselt gespeichert, es kann auch von der Seiten der Veranstalter nicht eingesehen werden. Eine Neuvergabe bei Verlust ist &uuml;ber das Anmeldesystem geregelt.<br><br>
3. Die gespeicherten Daten werden einer automatisierten Kartei zugef&uuml;hrt und bis auf Wiederruf gespeichert.<br><br>
4. Die gespeicherten Daten werden ausschlie&szlig;lich im System der Webseite verwendet, der Veranstalter wird die Daten weder weitergeben noch anderweitig nutzen.<br><br>
5. Ausgenommen hiervon ist die Information &uuml;ber weitere Cons, sofern der Teilnehmer bei der Registrierung dieser Nutzung zugestimmt hat.<br><br>
6. Der Veranstalter &uuml;bernimmt trotz der getroffenen Schutzma&szlig;nahmen keine Haftung im Falle eines Datenverlustes.<br>
");