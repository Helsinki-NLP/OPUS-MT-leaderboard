<?php

// adapted from https://www.infscripts.com/how-to-create-a-bar-chart-in-php

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

$srclang   = isset($_GET['src'])    ? test_input($_GET['src'])    : 'deu';
$trglang   = isset($_GET['trg'])    ? test_input($_GET['trg'])    : 'eng';
$benchmark = isset($_GET['test'])   ? test_input($_GET['test'])   : 'flores101-devtest';
$metric    = isset($_GET['metric']) ? test_input($_GET['metric']) : 'bleu';

if (isset($_GET['model'])){
    $modelhome = 'https://object.pouta.csc.fi/Tatoeba-MT-models';
    // $modelbase = substr($_GET['model'], 0, -5);
    $file = implode('/',[$modelhome,$_GET['model']]).'.scores.txt';
    $lines = file($file);
    // $lines = file($_GET['scores']);
}
else{
    $leaderboard_url = 'https://raw.githubusercontent.com/Helsinki-NLP/OPUS-MT-leaderboard/master/scores';
    $langpair = implode('-',[$srclang,$trglang]);
    if (isset($_GET['test'])){
        $file     = implode('/',[$leaderboard_url,$langpair,$benchmark,$metric.'-scores.txt']);
    }
    else{
        $file     = implode('/',[$leaderboard_url,$langpair,'top-'.$metric.'-scores.txt']);
    }
    $lines = file($file);
}


// $lines = file("https://raw.githubusercontent.com/Helsinki-NLP/OPUS-MT-leaderboard/master/scores/deu-eng/top-bleu-scores.txt");
// $lines = file("https://raw.githubusercontent.com/Helsinki-NLP/OPUS-MT-leaderboard/master/scores/deu-eng/newstest2018/bleu-scores.txt");
// https://object.pouta.csc.fi/Tatoeba-MT-models/fin-eng/opusTCv20210807+bt-2021-11-09.scores.txt


$data = array();
// read model-specific scores
if (isset($_GET['model'])){
    $maxscore = 0;
    if (isset($_GET['scoreslang'])){
        $langpair = $_GET['scoreslang'];
    }
    foreach($lines as $line) {
        $array = explode("\t", $line);
        if (isset($langpair)){
            if ($langpair != $array[0]){
                continue;
            }
        }
        $score = $metric == 'bleu' ? $array[3] : $array[2];
        array_push($data,$score);
        if ( $maxscore < $score ){
            $maxscore = $score;
        }
    }
}
// read from benchmark-specific leaderboard
elseif (isset($_GET['test'])){
    foreach($lines as $line) {
        $array = explode("\t", $line);
        array_unshift($data,$array[0]);
    }
    $maxscore = end($data);
}
// read from top-score files
else{
    $maxscore = 0;
    foreach($lines as $line) {
        $array = explode("\t", $line);
        array_push($data,$array[1]);
        if ( $maxscore < $array[1] ){
            $maxscore = $array[1];
        }
    }
}

if (sizeof($data) == 0){
    $data[0] = 0;
}
$nrscores = sizeof($data);


/*
 * Chart settings and create image
 */

// Image dimensions
$imageWidth = 680;
$imageHeight = 400;

// Grid dimensions and placement within image
$gridTop = 40;
$gridLeft = 50;
$gridBottom = 340;
$gridRight = 650;
$gridHeight = $gridBottom - $gridTop;
$gridWidth = $gridRight - $gridLeft;

// Bar and line width
$lineWidth = 1;
if ($nrscores > 0){
    $barWidth = floor(450/$nrscores);
}
else {
    $barWidth = 20;
}

// Font settings
$font = './OpenSans-Regular.ttf';
$fontSize = 10;

// Margin between label and axis
$labelMargin = 8;

// Max value on y-axis
$yMaxValue = $maxscore;

// Distance between grid lines on y-axis
// $yLabelSpan = 40;
if ($metric == 'bleu'){
    $yLabelSpan = floor($maxscore/4);
}
else{
    $yLabelSpan = floor($maxscore*25)/100;
}

// Init image
$chart = imagecreate($imageWidth, $imageHeight);

// Setup colors
$backgroundColor = imagecolorallocate($chart, 255, 255, 255);
$axisColor = imagecolorallocate($chart, 85, 85, 85);
$labelColor = $axisColor;
$gridColor = imagecolorallocate($chart, 212, 212, 212);
$barColor = imagecolorallocate($chart, 47, 133, 217);

imagefill($chart, 0, 0, $backgroundColor);

imagesetthickness($chart, $lineWidth);

/*
 * Print grid lines bottom up
 */

if ($yMaxValue > 0){
    for($i = 0; $i <= $yMaxValue; $i += $yLabelSpan) {
        $y = ceil($gridBottom - $i * $gridHeight / $yMaxValue);
        
        // draw the line
        imageline($chart, $gridLeft, $y, $gridRight, $y, $gridColor);

        // draw right aligned label
        $labelBox = imagettfbbox($fontSize, 0, $font, strval($i));
        $labelWidth = $labelBox[4] - $labelBox[0];

        $labelX = ceil($gridLeft - $labelWidth - $labelMargin);
        $labelY = ceil($y + $fontSize / 2);

        imagettftext($chart, $fontSize, 0, $labelX, $labelY, $labelColor, $font, strval($i));
    }
}

/*
 * Draw x- and y-axis
 */

imageline($chart, $gridLeft, $gridTop, $gridLeft, $gridBottom, $axisColor);
imageline($chart, $gridLeft, $gridBottom, $gridRight, $gridBottom, $axisColor);

/*
 * Draw the bars with labels
 */

$barSpacing = $gridWidth / count($data);
$itemX = $gridLeft + $barSpacing / 2;

foreach($data as $key => $value) {
    // Draw the bar
    $x1 = $itemX - $barWidth / 2;
    $y1 = $gridBottom - $value / $yMaxValue * $gridHeight;
    $x2 = $itemX + $barWidth / 2;
    $y2 = $gridBottom - 1;

    imagefilledrectangle($chart, $x1, $y1, $x2, $y2, $barColor);

    // Draw the label
    $labelBox = imagettfbbox($fontSize, 0, $font, $key);
    $labelWidth = $labelBox[4] - $labelBox[0];

    $labelX = $itemX - $labelWidth / 2;
    $labelY = $gridBottom + $labelMargin + $fontSize;

    imagettftext($chart, $fontSize, 0, $labelX, $labelY, $labelColor, $font, $key);

    $itemX += $barSpacing;
}

/*
 * Output image to browser
 */

header('Content-Type: image/png');
imagepng($chart);
