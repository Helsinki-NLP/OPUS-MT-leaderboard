<?php
session_start();

// adapted from https://www.infscripts.com/how-to-create-a-bar-chart-in-php

include 'functions.php';

// get query parameters
$package   = get_param('pkg', 'Tatoeba-MT-models');
$benchmark = get_param('test', 'all');
$metric    = get_param('metric', 'bleu');
$showlang  = get_param('scoreslang', 'all');
$model     = get_param('model', 'all');

list($srclang, $trglang, $langpair) = get_langpair();


$lines = read_scores($langpair, $benchmark, $metric, $model, $package);
$filename = get_score_filename($langpair, $benchmark, $metric, $model, $package);

if ($benchmark == 'avg'){
    $averaged_benchmarks = array_shift($lines);
}

$data = array();
$pkg = array();
// get model-specific scores
if ($model != 'all'){
    $maxscore = 0;
    foreach($lines as $line) {
        $array = explode("\t", $line);
        if ($showlang != 'all'){
            if ($showlang != $array[0]){
                continue;
            }
        }
        if ($benchmark != 'all'){
            if ($array[1] != $benchmark){
                continue;
            }
        }
        // $score = $metric == 'bleu' ? $array[3] : $array[2];
        $score = (float) $array[2];
        array_push($data,$score);
        array_push($pkg,$package);
        if ( $maxscore < $score ){
            $maxscore = $score;
        }
    }
}
// get scores from benchmark-specific leaderboard
elseif ($benchmark != 'all'){
    foreach($lines as $line) {
        $array = explode("\t", $line);
        array_unshift($data,$array[0]);
        /*
        if (strpos($array[1],'transformer-big') !== false){
            array_unshift($pkg,'transformer-big');
        }
        */
        if (strpos($array[1],'transformer-small') !== false){
            array_unshift($pkg,'transformer-small');
        }
        elseif (strpos($array[1],'transformer-tiny') !== false){
            array_unshift($pkg,'transformer-tiny');
        }
        else{
            $modelparts = explode('/',$array[1]);
            array_unshift($pkg,$modelparts[count($modelparts)-3]);
        }
    }
    $maxscore = end($data);
}
// get top-scores
else{
    $maxscore = 0;
    foreach($lines as $line) {
        $array = explode("\t", $line);
        array_push($data,$array[1]);
        if (strpos($array[2],'transformer-small') !== false){
            array_push($pkg,'transformer-small');
        }
        elseif (strpos($array[2],'transformer-tiny') !== false){
            array_push($pkg,'transformer-tiny');
        }
        else{
            $modelparts = explode('/',$array[2]);
            array_push($pkg,$modelparts[count($modelparts)-3]);
        }
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

$barColors = array('Tatoeba-MT-models' => imagecolorallocate($chart, 47, 133, 217),
                   'OPUS-MT-models' => imagecolorallocate($chart, 217, 133, 47),
                   // 'transformer-small' => imagecolorallocate($chart, 133, 217, 47),
                   'transformer-small' => imagecolorallocate($chart, 47, 196, 47),
                   'transformer-tiny' => imagecolorallocate($chart, 47, 196, 47),
                   'transformer-big' => imagecolorallocate($chart, 217, 47, 47));

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

// imagettftext($chart, $fontSize, 0, 10, 10, $labelColor, $font, $maxscore);
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

foreach($data as $key => $value) {
    // Draw the bar
    $x1 = floor($itemX - $barWidth / 2);
    $y1 = $yMaxValue > 0 ? floor($gridBottom - $value / $yMaxValue * $gridHeight) : floor($gridBottom);
    $x2 = floor($itemX + $barWidth / 2);
    $y2 = floor($gridBottom - 1);

    if ($x2 != $x1 and $y2 != $y1){
        $modelPkg = array_key_exists($key, $pkg) ? $pkg[$key] : 'Tatoeba-MT-models';
        $modelColor = array_key_exists($modelPkg, $barColors) ? $barColors[$modelPkg] : $barColors['Tatoeba-MT-models'];
        imagefilledrectangle($chart, $x1, $y1, $x2, $y2, $modelColor);
    }
    
    // Draw the label
    $labelBox = imagettfbbox($fontSize, 0, $font, $key);
    $labelWidth = $labelBox[4] - $labelBox[0];

    $labelX = floor($itemX - $labelWidth / 2);
    $labelY = $gridBottom + $labelMargin + $fontSize;

    imagettftext($chart, $fontSize, 0, $labelX, $labelY, $labelColor, $font, $key);

    $itemX += $barSpacing;
}

/*
 * Output image to browser
 */

header('Content-Type: image/png');
imagepng($chart);
