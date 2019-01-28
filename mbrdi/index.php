<!-- Dieses Script liest die Videoaufzeichnungen in einem bestimmten Verzeichnis und gibt diese in Form einer HTML Tabelle aus 

Voraussetzungen:
----------------
/videorecordings/nbrdi               => Dorthin werden die Aufzeichnungen abgespeichert
/usr/local/nginx/html/mbrdi          => Softlink von download.mp4 auf /video_recordings/bondorf/aufzeichnung.mp4

-->

<?php 

$server="192.168.0.74";
$titel="MBRDI Video Streams";
$ort="MBRDI";
$streamname="mbrdi";

?>

<!DOCTYPE html>
<html lang="de">
<head>
<!-- meta http-equiv="refresh" content="120" / -->
<title><?php echo $titel ?></title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
<style>
* {
    box-sizing: border-box;
}

body {
  margin: 0;
}

/* Style the header */
.header {
    background-color: #f1f1f1;
    padding: 10px;
    font-size:100%;
}

/* Style the content */
.content {
    background-color: #ddd;
    padding: 10px;
    font-size:80%;
    /* Style the footer */
.footer {
    background-color: #f1f1f1;
    padding: 5px;
}
</style>
</head>

<body>

<div class="header">
  <h2><?php echo $titel ?></h2>

<?php
//exec("/home/rarents/bin/ffprobe -i http:\/\/".$server."/".$streamname."/test/index.m3u8 
//-v quiet -print_format json -show_format -show_streams -hide_banner > temp_file", $output, $res);

$info = json_decode(file_get_contents("temp_file"));

$videocodec = $info->streams[0]->codec_name;

if($videocodec == "h264") 
   {
  echo"<a href=\"livestream.html\"><img src=\"../images/livegreen.png\" title=\"Live Stream\" width=\"20\" height=\"20\"></a> &nbsp;<a href=\"livestream.html\">Live Stream</a> &nbsp; ";
  echo"<a href=\"audio.html\"><img src=\"../images/radio.png\" title=\"Audio Live Stream\" width=\"23\" height=\"23\"></a> 
&nbsp;<a href=\"audio.html\">Audio Stream</a> &nbsp; ";
   }
else 
   {
   echo"<a href=\"livestream.html\"><img src=\"../images/offline.png\" title=\"Momentan findet keine Live &Uuml;bertragung statt
\" width=\"20\" height=\"20\"></a> &nbsp; Momentan keine <a href=\"livestream.html\">Live-&Uuml;bertragung</a>  
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ";
   }
?>
  <a href="aufzeichnung.html"><img src="../images/play.png" title="Letzte Aufzeichnung" 
width="20" height="20"></a>&nbsp; <a href="aufzeichnung.html">Letzte Aufzeichnung</a><p>
</div>

<div class="content">

<?php
// Pfad zur aktuellsten Aufzeichnung festlegen und Lesen der Datei Informationen
$aufzeichnung = "/video_recordings/".$ort."/aufzeichnung.mp4";
# echo date("d.m.Y", filemtime($aufzeichnung));
?>
<h4>Aufnahmen:</h4>
<div class="w3-responsive">
<table class="w3-table-all w3-small w3-hoverable w3-card-4 w3-centered"><tr><th>Tag</th><th>Datum</th><th>Beginn</th><th>Ende</th><th>
Dateigroesse</th><th>Audio<br>Streaming</th><th>Video<br>Streaming</th><th>Download</th></tr></b><br>

<?php 
function byte_ausrechnen($byte) { 
     
    if($byte < 1024) { 
        $ergebnis = round($byte, 2). ' Byte'; 
    }elseif($byte >= 1024 and $byte < pow(1024, 2)) { 
        $ergebnis = round($byte/1024, 2).' KByte'; 
    }elseif($byte >= pow(1024, 2) and $byte < pow(1024, 3)) { 
        $ergebnis = round($byte/pow(1024, 2), 2).' MByte'; 
    }elseif($byte >= pow(1024, 3) and $byte < pow(1024, 4)) { 
        $ergebnis = round($byte/pow(1024, 3), 2).' GByte'; 
    }elseif($byte >= pow(1024, 4) and $byte < pow(1024, 5)) { 
        $ergebnis = round($byte/pow(1024, 4), 2).' TByte'; 
    }elseif($byte >= pow(1024, 5) and $byte < pow(1024, 6)) { 
        $ergebnis = round($byte/pow(1024, 5), 2).' PByte'; 
    }elseif($byte >= pow(1024, 6) and $byte < pow(1024, 7)) { 
        $ergebnis = round($byte/pow(1024, 6), 2).' EByte'; 
    }

return $ergebnis; 
     
}

function wochentag($tag) { 
     
    if($tag=="Sunday") { 
        $weekday = "Sonntag"; 
    }elseif($tag=="Monday") { 
       $weekday = "Montag"; 
    }elseif($tag=="Tuesday") { 
       $weekday = "Dienstag"; 
    }elseif($tag=="Wednesday") { 
       $weekday = "Mittwoch";
    }elseif($tag=="Thursday") { 
       $weekday = "Donnerstag";
    }elseif($tag=="Friday") { 
       $weekday = "Freitag";
    }elseif($tag=="Saturday") { 
       $weekday = "Samstag";
    }

return $weekday; 
     
}

// Pfad zum Ordner mit allen Video-Aufzeichnungen 
$ordner = "/video_recordings/".$ort; //auch komplette Pfade moeglich ($ordner = "download/files";)

// Ordner auslesen und Array in Variable speichern
$alledateien = scandir($ordner, 1); // Sortierung A-Z
// Sortierung Z-A mit scandir($ordner, 1)                                               

// Schleife um Array "$alledateien" aus scandir Funktion auszugeben
// Einzeldateien werden dabei in der Variabel $datei abgelegt
foreach ($alledateien as $datei) {

        // Zusammentragen der Dateiinfo
        $dateiinfo = pathinfo($ordner."/".$datei); 
        //Folgende Variablen stehen nun zur Verfuegung
        // $dateiinfo['filename'] = Dateiname ohne Dateiendung  *erst mit PHP 5.2
        // $dateiinfo['dirname'] = Verzeichnisname
        // $dateiinfo['extension'] = Dateityp -/endung
        // $dateiinfo['basename'] = voller Dateiname mit Dateiendung

        // scandir liest alle Dateien im Ordner aus, zusätzlich noch "." , ".." als Ordner
        // Nur echte Dateien anzeigen lassen und keine "Punkt" Ordner
          // aufzeichnung.mp4 ist die aktuellste Aufzeichnung. Diese soll nicht in der Tabelle erscheinen
        if ($datei != "." && $datei != ".."  && $datei != "aufzeichnung.mp4") {
        if ($dateiinfo['extension'] == "flv") { 
        // Dateigroesse der mp4-Dateien feststellen
        $size = filesize($ordner."/".$dateiinfo['filename'].".mp4");
        // Splitten des Dateinamens
        $dateiname=$dateiinfo['filename'];
        $teile = explode("-", $dateiname);
        // test-1481403588-Saturday-10-12-16-21-59.flv
        // $teile[0] = test
        // $teile[1] = string
        // $teile[2] = Wochentag (Name)
        // $teile[3] = Wochentag (Zahl)
        // $teile[4] = Monat
        // $teile[5] = Jahr
        // $teile[6] = Stunde
        // $teile[7] = Minute
        // ...
        $tag=$teile[2];

// Nur Tabellenzeile fuer Dateien erzeugen die groesser 0 KB sind
if($size > 0) { 
        echo "<tr><td>".wochentag($tag)."</td><td>".$teile[3].".".$teile[4].".".$teile[5]."</td><td>".$teile[6].":".$teile[7]."</td><td>"
.date("H:i", filemtime($ordner."/".$dateiinfo['basename']))."</td><td>".byte_ausrechnen($size)."</td>
<td><a href=\"/".$ort."/playaudio.html?filename=".$dateiinfo['filename'].".mp3\"><img src=\"../images/radio.png\" 
title=\"Audio abspielen\" width=\"20\" height=\"20\"></a></td>
<td><a href=\"/".$ort."/showmpeg.html?filename=".$dateiinfo['filename'].".mp4\"><img src=\"../images/play.png\" 
title=\"Video abspielen\" width=\"20\" height=\"20\"></a></td><td>
<a href=\"/".$ort."streams/".$dateiinfo['filename'].".mp4\" download=\"http\:\/\/".$server."/".$ort."streams/".$dateiinfo['filename'].".mp4\">
<img src=\"../images/download.png\" title=\"Download mp4\" width=\"20\" height=\"20\"><br></a></td></tr>"; 
       } // Ende if-Schleife fuer Datei Groessenabfrage
      }; // Ende if-Schliefe fuer erste Abfrage 
     }; // Ende if-Schleife fuer Datei Extension Abfrage
 };
?>     
</table></div><p>

<div class="w3-panel w3-border-left w3-border-red w3-pale-red">
<b>Hinweis:</b> Um die Videos/ Streams anzuschauen, verwenden Sie am besten den <a href="https://www.mozilla.org/de/firefox/new/">Firefox</a> oder  
<a href="http://www.google.de/chrome">Google Chrome Browser</a>. 
Teilweise werden auch internetf&auml;hige Fernsehger&auml;te unterst&uuml;tzt. 
Bei bestimmten Browsern (z.B. Microsoft Edge Browser in Windows 10 oder bestimmte Versionen des Internet Explorers) 
kann es vorkommen, dass Bild und Ton nicht ganz synchron sind.
</div>

</div>
<div class="footer">
  <!-- p>Footer</p -->
<?php include "counter.php"; ?>
</div>
</body></html>
