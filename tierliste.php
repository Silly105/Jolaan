<?php
ini_set ("expose_php", "off");        // versuche die php herkunft zu verschweigen
ini_set ("url_rewriter.tags", "a=href"); // verhindert das automatische einfuegen eines "<input SESSID" nach dem "<form"

header("Content-type: text/html");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

if(session_status() == PHP_SESSION_NONE){session_start();}

require_once('i/western_design.php');
require_once('i/sprache.php');

print_site(
"$s_tierliste_title",
"$s_tierliste_explainer<br><br>
<table border='0' width='100%'>
<tr>
  <td width='33%'>$s_tierliste_hund</td>
  <td width='33%'>$s_tierliste_dachs</td>
  <td>$s_tierliste_frosch</td>
</tr>
<tr>
  <td>$s_tierliste_tiger</td>
  <td>$s_tierliste_igel</td>
  <td>$s_tierliste_biber</td>
</tr>
<tr>
  <td>$s_tierliste_katze</td>
  <td>$s_tierliste_schlange</td>
  <td>$s_tierliste_zergling</td>
</tr>
<tr>
  <td>$s_tierliste_muecke</td>
  <td>$s_tierliste_regenwurm</td>
  <td>$s_tierliste_hoellenhund</td>
</tr>
<tr>
  <td>$s_tierliste_affe</td>
  <td>$s_tierliste_nilpferd</td>
  <td>$s_tierliste_drache</td>
</tr>
<tr>
  <td>$s_tierliste_einhorn</td>
  <td>$s_tierliste_vogel</td>
  <td>$s_tierliste_pferd</td>
</tr>
<tr>
  <td>$s_tierliste_baer</td>
  <td>$s_tierliste_maus</td>
  <td>$s_tierliste_faultier</td>
</tr>
<tr>
  <td>$s_tierliste_nashorn</td>
  <td>$s_tierliste_hamster</td>
  <td>$s_tierliste_schmetterling</td>
</tr>
<tr>
  <td>$s_tierliste_kaefer</td>
  <td>$s_tierliste_fuchs</td>
  <td>$s_tierliste_fisch</td>
</tr>
</table>


");