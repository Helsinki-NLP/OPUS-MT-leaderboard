<?php
session_start();

// adapted from https://www.infscripts.com/how-to-create-a-bar-chart-in-php

include 'functions.php';

// get query parameters
$benchmark = get_param('test', 'all');
$metric    = get_param('metric', 'bleu');
$model1    = get_param('model1', 'unknown');
$model2    = get_param('model2', 'unknown');

list($srclang, $trglang, $langpair) = get_langpair();

$showlang  = get_param('scoreslang', $langpair);


if ($model1 != 'unknown'){
    list($pkg1, $lang1, $name1) = explode('/',$model1);
    $lines1 = read_scores($langpair, 'all', $metric, implode('/',[$lang1,$name1]), $pkg1);
}

if ($model2 != 'unknown'){
    list($pkg2, $lang2, $name2) = explode('/',$model2);
    $lines2 = read_scores($langpair, 'all', $metric, implode('/',[$lang2,$name2]), $pkg2);
}


$data = array();
$model = array();


$maxscore = 0;

// read model-specific scores
$scores1 = array();
foreach($lines1 as $line1) {
    $array = explode("\t", $line1);
    if ($showlang == 'all' || $showlang == $array[0]){
        if ($benchmark == 'all' || $benchmark == $array[1]){
            // $score = $metric == 'bleu' ? $array[3] : $array[2];
            $score = (float) $array[2];
            $key = $array[0].'/'.$array[1];
            $scores1[$key] = $score;
            if ( $maxscore < $score ){
                $maxscore = $score;
            }
        }
    }
}

foreach($lines2 as $line2) {
    $array = explode("\t", $line2);
    if ($showlang == 'all' || $showlang == $array[0]){
        if ($benchmark == 'all' || $benchmark == $array[1]){
            // $score = $metric == 'bleu' ? $array[3] : $array[2];
            $score = (float) $array[2];
            $key = $array[0].'/'.$array[1];
            $scores2[$key] = $score;
            if ( $maxscore < $score ){
                $maxscore = $score;
            }
        }
    }
}

foreach($scores1 as $key => $value) {
    if (array_key_exists($key,$scores2)){
        array_push($data,$value);
        array_push($model,'model1');
        array_push($data,$scores2[$key]);
        array_push($model,'model2');
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
    $yLabelSpan = ceil($maxscore/5);
}
else{
    $yLabelSpan = ceil($maxscore*20)/100;
}

// Init image
$chart = imagecreate($imageWidth, $imageHeight);

// Setup colors
$backgroundColor = imagecolorallocate($chart, 255, 255, 255);
$axisColor = imagecolorallocate($chart, 85, 85, 85);
$labelColor = $axisColor;
$gridColor = imagecolorallocate($chart, 212, 212, 212);
$barColor = imagecolorallocate($chart, 47, 133, 217);

$barColors = array('model1' => imagecolorallocate($chart, 47, 133, 217),
                   'model2' => imagecolorallocate($chart, 217, 133, 47));

imagefill($chart, 0, 0, $backgroundColor);

imagesetthickness($chart, $lineWidth);

/*
 * Print grid lines bottom up
 */

if ($yMaxValue > 0 && $yLabelSpan > 0){
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

$metricLabelX = ceil($gridLeft - $labelMargin);
imagettftext($chart, $fontSize, 90, $metricLabelX, $gridTop+20, $labelColor, $font, $metric);
imagettftext($chart, $fontSize, 0, 200, $imageHeight-20, $labelColor, $font, 'model index (see ID in table of scores)');


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

$count = 0;
foreach($data as $key => $value) {
    // Draw the bar
    $x1 = floor($itemX - $barWidth / 2);
    $y1 = $yMaxValue > 0 ? floor($gridBottom - $value / $yMaxValue * $gridHeight) : $gridBottom;
    // $y1 = $gridBottom - $value / $yMaxValue * $gridHeight;
    $x2 = floor($itemX + $barWidth / 2);
    $y2 = $gridBottom - 1;

    if ($x2 != $x1 and $y2 != $y1){
        imagefilledrectangle($chart, $x1, $y1, $x2, $y2, $barColors[$model[$key]]);
    }

    // special for this comparison: only label every second bar
    // and adjust the ID to increment every second bar
    $label = floor($key/2);
    if ($label == ceil($key/2)){
        // Draw the label
        $labelBox = imagettfbbox($fontSize, 0, $font, $key);
        $labelWidth = $labelBox[4] - $labelBox[0];

        $labelX = floor($itemX - $labelWidth / 2);
        $labelX = $itemX;
        $labelY = $gridBottom + $labelMargin + $fontSize;

        imagettftext($chart, $fontSize, 0, $labelX, $labelY, $labelColor, $font, $label);
    }

    $itemX += $barSpacing;
}

/*
 * Output image to browser
 */

header('Content-Type: image/png');
imagepng($chart);
