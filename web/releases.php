<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
  <title>OPUS-MT - Release History</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"> 
  <link rel="stylesheet" href="index.css" type="text/css">
</head>
<body>
<h1>OPUS-MT - Release History</h1>

<?php

$releases_url = 'https://raw.githubusercontent.com/Helsinki-NLP/OPUS-MT-leaderboard/master/release-history.txt';
$releases = file($releases_url);

$modelhome = 'https://object.pouta.csc.fi/Tatoeba-MT-models';


$lastdate = '';
foreach ($releases as $release){
    rtrim($release);
    list($date,$langpair,$model) = explode("\t",$release);
    if ($lastdate != $date){
        if ($lastdate != ''){
            echo '</ul>';
        }
        echo "<h2>$date</h2><ul>";
        $lastdate = $date;
    }
    $model_url = urlencode("$langpair/$model");
    echo "<li><a href='$modelhome/$langpair/$model'>$langpair/$model</a> (<a href='index.php?model=$model_url'>benchmark results</a>)</li>";
}

?>
</body>
</html>


