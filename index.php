<?php
if(session_status() == PHP_SESSION_NONE){session_start();}

require_once('i/western_design.php');
require_once('i/mysql.php');
require_once('validate.php');
require_once('i/sprache.php');

$content = "<p class='block1'>";
$content .= $s_index_introtext;
$content .= "   </p>";
$content .= "   <p class='block1'>";

connect2mysql();

$mysql="SELECT `id`, `conname`, `conuntertitel`, `conlocation`, `conlocationvorsatz`, `conlocationnachsatz`, `datumbeginn`, DAYOFMONTH( datumbeginn ) AS beginntag , MONTH( datumbeginn ) AS beginnmonat , YEAR( datumbeginn ) AS beginnjahr, DAYOFMONTH( datumende ) AS endetag , MONTH( datumende ) AS endemonat , YEAR( datumende ) AS endejahr, `datumende`, `freisc`, `freinsc`, `flyeradresse`, `anmeldungsadresse`, `forumsadresse`, DATE_FORMAT( stand, '$s_index_mysqldatestring' ) AS stand2, `countdown`, `starttermin` FROM `conmanagement_aktuelletermine`;";
$mysql = mysqli_query($link,$mysql);
$num = mysqli_num_rows($mysql);

if($num == '1')
{
  $aktuellesdatum = date("Y-m-d");
  $qidatumbeginn = just_my_result($mysql, 0, "datumbeginn");
  $qidatumende = just_my_result($mysql, 0, "datumende");

  if(($qidatumbeginn <= $aktuellesdatum) && ($qidatumende >= $aktuellesdatum))
  { //Wenn der Termin gerade laeuft
    $content .= "<h4>$s_index_veranst_laeuft:</h4>";
  }
  elseif($qidatumbeginn >= $aktuellesdatum)
  { //Wenn der Termin in der Zukunft liegt
    $content .= "<h4>$s_index_veranst_aktuell:</h4>";
  }
}
elseif($num == '0')
{
  $content .= "<h4>$s_index_veranst_keine</h4>";
}
elseif($num >= '2')
{
  $aktuellesdatum = date("Y-m-d");

  $j = 0;
  for($i='0'; $i < $num; $i++)
  {
    $qidatumbeginn = just_my_result($mysql, $i, "datumbeginn");
    $qidatumende = just_my_result($mysql, $i, "datumende");
    if($qidatumende < $aktuellesdatum)
    { // wenn alle termine in der vergangenheit liegen
      $j++;
    }
  }
  if($num == $j)
  {
    $content .= "<h4>$s_index_veranst_keine</h4>";
  }
  else
  {
    $content .= "<h4>$s_index_veranst_aktuell:</h4>";
  }
}

for($i='0'; $i < $num; $i++)
{
  $aktuellesdatum = date("Y-m-d");
  $qicountdown = just_my_result($mysql, $i, "countdown");
  $qistarttermin = just_my_result($mysql, $i, "starttermin");
  $qidatumbeginn = just_my_result($mysql, $i, "datumbeginn");
  $qidatumende = just_my_result($mysql, $i, "datumende");
  $qiid = just_my_result($mysql, $i, "id");
  $qiconname = just_my_result($mysql, $i, "conname");
  $qiconuntertitel = just_my_result($mysql, $i, "conuntertitel");
  $qiconlocation = just_my_result($mysql, $i, "conlocation");
  $qiconlocationvorsatz = just_my_result($mysql, $i, "conlocationvorsatz");
  $qiconlocationnachsatz = just_my_result($mysql, $i, "conlocationnachsatz");
  $qibeginntag = just_my_result($mysql, $i, "beginntag");
  $qibeginnmonat = just_my_result($mysql, $i, "beginnmonat");
  $qibeginnjahr = just_my_result($mysql, $i, "beginnjahr");
  $qiendetag = just_my_result($mysql, $i, "endetag");
  $qiendemonat = just_my_result($mysql, $i, "endemonat");
  $qiendejahr = just_my_result($mysql, $i, "endejahr");
  $qifreisc = just_my_result($mysql, $i, "freisc");
  $qifreinsc = just_my_result($mysql, $i, "freinsc");
  $qiflyeradresse = just_my_result($mysql, $i, "flyeradresse");
  $qianmeldungsadresse = just_my_result($mysql, $i, "anmeldungsadresse");
  $qiforumsadresse = just_my_result($mysql, $i, "forumsadresse");
  $qistand = just_my_result($mysql, $i, "stand2");

  if($qibeginnmonat < '10')
  {
    $qibeginnmonat = "0".$qibeginnmonat;
  }
  if($qibeginntag < '10')
  {
    $qibeginntag = "0".$qibeginntag;
  }

  if($qiendemonat < '10')
  {
    $qiendemonat = "0".$qiendemonat;
  }
  if($qiendetag < '10')
  {
    $qiendetag = "0".$qiendetag;
  }

  if($aktuellesdatum < $qidatumende)
  {
    $beginndatum = date($s_index_datestring, mktime(0, 0, 0, $qibeginnmonat, $qibeginntag, $qibeginnjahr));
    $endedatum = date($s_index_datestring, mktime(0, 0, 0, $qiendemonat, $qiendetag, $qiendejahr));

    $content .= "<p><b>$qiconname";
    if($qiconuntertitel != '')
    {
      $content .= " - $qiconuntertitel";
    }
    //$content .= "</b><br><b>$qibeginntag.$qibeginnmonat.$qibeginnjahr bis $qiendetag.$qiendemonat.$qiendejahr<br>";
    //$content .= "$qiconlocationvorsatz $qiconlocation $qiconlocationnachsatz</b>";
    $content .= "</b><br><b>$beginndatum - $endedatum<br>";
    $content .= "$qiconlocation $qiconlocationnachsatz</b>";
    if(($qiconlocationvorsatz != '') || ($qiconlocation != '') || ($qiconlocationnachsatz != ''))
    {
      $content .= "<br>";
    }
    if($qifreisc != '')
    {
      $content .= "$qifreisc $s_index_scplaetze&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
      $content .= "$qifreinsc $s_index_nscplaetze&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;($s_index_stand: $qistand)<br>";
    }
    if($qiflyeradresse != '')
    {
      $content .= "<a href='$qiflyeradresse'>$s_index_zumflyer!</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    }
    if($qianmeldungsadresse != '')
    {
      $content .= "<a href='$qianmeldungsadresse'>$s_index_zuranmeldung!</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    }
    if($qiforumsadresse != '')
    {
      $content .= "<a href='$qiforumsadresse'>$s_index_zumforum!</a></p>";
    }
  }
  if(validate("Administrator"))
  {
    $content .= "<p class='block2'><a href='editqi.php?qiid=$qiid'>$qiconname - Schnellinfo Bearbeiten</a></p>";
  }

  if(($qidatumbeginn >= $aktuellesdatum) && ($qicountdown == '1'))
  { //Wenn der Termin in der Zukunft liegt
    $content .= "

    <script type='text/javascript'>
    <!--
    var now = new Date();
    var end = new Date('$qistarttermin');


//    document.write('<p class=\"block1\"><fieldset class=\"login\"><legend>Countdown</legend><div id=\"explain\"><b><span id=\"c1\"></span></b></div></fieldset></p><br><br>');
//    document.write('<table border=\"1\" width=\"80%\" cellpadding=\"2\" cellspacing=\"0\"><tr><td><b><div align=\"left\"<span id=\"c1\"></span></div></b></td></tr></table>');
    document.write(' <b>$s_countdown_noch <span id=\"c1\"></span></b>');

    function toSt2(n) {
      s = '';
      if (n < 10) s += '0';
      return (s + n).toString();
    }
    function toSt3(n) {
      s = '';
      if (n < 10) s += '00';
      else if (n < 100) s += '0';
      return (s + n).toString();
    }
    function countdown() {
      d = new Date();
      count = Math.floor(end.getTime() - d.getTime());
      if(count > 0) {
        miliseconds = toSt3(count%1000); count = Math.floor(count/1000);
        seconds = toSt2(count%60); count = Math.floor(count/60);
        minutes = toSt2(count%60); count = Math.floor(count/60);
        hours = toSt2(count%24); count = Math.floor(count/24);
        days = count;

        dps = '$s_countdown_dps'; hps = '$s_countdown_hps'; mps = '$s_countdown_mps'; sps = '$s_countdown_sps';
        if(days == 1) dps ='';
        if(hours == 1) hps ='';
        if(minutes == 1) mps ='';
        if(seconds == 1) sps ='';

        document.getElementById('c1').innerHTML = days + ' $s_countdown_d' + dps + ' ' + hours + ' $s_countdown_h' + hps + ' ' + minutes + ' $s_countdown_m' + mps + ' ' + seconds + ' $s_countdown_s' + sps + '';
//        document.getElementById('c1').innerHTML = days + ' Tag' + dps + ', ' + hours + ' Stunde' + hps + ', ' + minutes + ' Minute' + mps + ' und ' + seconds + ' Sekunde' + sps + '';
        setTimeout('countdown()', 50);
      }
    }
    window.onload=countdown;
    //-->
    </script>
    ";
  }
}

$content .= "<br></p>";

print_site($s_index_title,$content);

disconnectmysql();

?>