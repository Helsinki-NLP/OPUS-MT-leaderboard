<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
  <title>OPUS-MT - Leaderboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"> 
  <link rel="stylesheet" href="index.css" type="text/css">
</head>
<body>

<?php

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

$leaderboard_url = 'https://raw.githubusercontent.com/Helsinki-NLP/OPUS-MT-leaderboard/master/scores';

$srclang   = isset($_GET['src'])    ? test_input($_GET['src'])    : 'deu';
$trglang   = isset($_GET['trg'])    ? test_input($_GET['trg'])    : 'eng';
$benchmark = isset($_GET['test'])   ? test_input($_GET['test'])   : 'all';
if (isset($_GET['langpair'])){
    list($srclang,$trglang) = explode('-',$_GET['langpair']);
}
$metric    = isset($_GET['metric']) ? test_input($_GET['metric']) : 'bleu';
$langpair  = implode('-',[$srclang,$trglang]);
$showlang  = isset($_GET['scoreslang']) ? test_input($_GET['scoreslang'])    : $langpair;

$langpair_url = urlencode($langpair);



echo '<div class="header">';
echo "<form action=\"compare.php\" method=\"get\">";
if (($benchmark == "all")){
    $langpairs = array_map('rtrim', file(implode('/',[$leaderboard_url,'langpairs.txt'])));
    unset($_GET['test']);
}
else{
    $langpairs = explode(' ',$testlangs);
}


echo '  select language pair: <select name="langpair" id="langpair" onchange="this.form.submit()">';
foreach ($langpairs as $l){
    if ($l == $langpair){
        echo "<option value=\"$l\" selected>$l</option>";
        $selected = $l;
    }
    else{
        echo "<option value=\"$l\">$l</option>";
    }
}
echo '</select>';
echo '  [<a href="releases.php">show release history<a/>]';
echo '</form>';
echo '<hr/></div>';



echo('<h1>Compare OPUS-MT models</h1>');

if (isset($_GET['model1']) && isset($_GET['model2'])){
    $metric_url = urlencode($metric);
    $model1_url = urlencode($_GET['model1']);
    $model2_url = urlencode($_GET['model2']);
    $showlang_url = urlencode($showlang);
    $benchmark_url = urlencode($benchmark);
    $url_param = "metric=$metric_url&model1=$model1_url&model2=$model2_url&langpair=$langpair_url";
    echo("<img src=\"compare-barchart.php?$url_param&scoreslang=$showlang_url&test=$benchmark_url\" alt=\"barchart\" />");

    $modelhome = 'https://object.pouta.csc.fi';
    $file1  = implode('/',[$modelhome,$_GET['model1']]).'.scores.txt';
    $file2  = implode('/',[$modelhome,$_GET['model2']]).'.scores.txt';
    $lines1 = file($file1);
    $lines2 = file($file2);

    $langpairs1 = array();
    $testsets1 = array();
    // $benchmarks1 = array();
    $scores1 = array();

    $count = -1;
    $avg_score1 = 0;
    foreach($lines1 as $line1) {
        $array = explode("\t", $line1);
        $langpairs1[$array[0]]++;
        // $benchmarks1[$array[1]]++;
        if ($showlang == 'all' || $showlang == $array[0]){
            if ($benchmark == 'all' || $benchmark == $array[1]){
                $count++;
                if (array_key_exists($array[1],$testsets1)){
                    $testsets1[$array[1]].=",$count,";
                }
                else{
                    $testsets1[$array[1]]="$count,";
                }
                $count++;
                $testsets1[$array[1]].=$count;
                $score = $metric == 'bleu' ? $array[3] : $array[2];
                $key = $array[0].'/'.$array[1];
                $scores1[$key] = $score;
                $avg_score1 += $score;
                // echo("$score</br>");
            }
        }
    }

    $langpairs2 = array();
    $testsets2 = array();
    // $benchmarks2 = array();

    $avg_score2 = 0;
    foreach($lines2 as $line2) {
        $array = explode("\t", $line2);
        $langpairs2[$array[0]]++;
        // $benchmarks2[$array[1]]++;
        if ($showlang == 'all' || $showlang == $array[0]){
            if ($benchmark == 'all' || $benchmark == $array[1]){
                $testsets2[$array[1]]++;
                $score = $metric == 'bleu' ? $array[3] : $array[2];
                $key = $array[0].'/'.$array[1];
                // only count the scores for which we also have model1 scores
                // (because only those will be shown in the chart!)
                if (array_key_exists($key,$scores1)){
                    $scores2[$key] = $score;
                    $avg_score2 += $score;
                }
            }
        }
    }


    $nrscores = sizeof($scores1);
    if ($nrscores > 1){
        // echo("..... $nrscores</br>");
        $avg_score1 /= $nrscores;
    }

    $nrscores = sizeof($scores2);
    if ($nrscores > 1){
        // echo("_____ $nrscores</br>");
        $avg_score2 /= $nrscores;
    }

}






$models = file(implode('/',[$leaderboard_url,$langpair,'model-list.txt']));

if (isset($_GET['model1'])){
    echo('<h2>Selected models</h2>');
    echo('<ul>');
    echo('<li><b>Model 1 (blue):</b> '.$_GET['model1'].'</li>');
    if (isset($_GET['model2'])){
        echo('<li><b>Model 2 (orange):</b> '.$_GET['model2'].'</li>');
        echo('<li><b>Average Score (Model 1):</b> '.$avg_score1.'</li>');
        echo('<li><b>Average Score (Model 2):</b> '.$avg_score2.'</li>');
        echo('<li><b>Langpair(s):</b> ');
        ksort($langpairs1);
        foreach ($langpairs1 as $lp => $count){
            if (array_key_exists($lp,$langpairs2)){
                if ($lp == $showlang){
                    echo("[$showlang]");
                }
                else{
                    $lp_url = urlencode($lp);
                    echo("[<a href=\"compare.php?$url_param&scoreslang=$lp&test=$benchmark_url\">$lp_url</a>]");
                }
            }
        }
        if ($showlang != 'all'){
            echo("[<a href=\"compare.php?$url_param&scoreslang=all&test=$benchmark_url\">all</a>]");
        }
        echo('</li>');
        echo('<li><b>Benchmark(s) in the Legend:</b> <ul>');
        foreach ($testsets1 as $ts => $legend){
            if ($ts == $benchmark){
                echo("<li>$ts ($legend)</li>");
            }
            else{
                $testset_url = urlencode($ts);
                echo("<li><a href=\"compare.php?$url_param&scoreslang=$showlang_url&test=$ts\">$ts</a> ($legend)</li>");
            }
        }
        if ($benchmark != 'all'){
            echo("<li><a href=\"compare.php?$url_param&scoreslang=$showlang_url&test=all\">activate all</a></li>");
        }
        echo('</ul></li>');
        $model1 = urlencode($_GET['model1']);
        $model2 = urlencode($_GET['model2']);
        $url_param = "model1=$model1&model2=$model2&langpair=$langpair_url";
        echo('</ul><h2>Start with a new model</h2>');
    }
    else{
        echo('</ul>');
        echo('<h2>Select the second model</h2>');
    }
}
else{
    echo('<h2>Select a model to compare with</h2>');
}

echo("<ul>");
foreach ($models as $model){
    $parts = explode('/',rtrim($model));
    $modelzip = array_pop($parts);
    $modellang = array_pop($parts);
    $modelpkg = array_pop($parts);
    $modelbase = substr($modelzip, 0, -4);

    if (isset($_GET['model1']) && ! isset($_GET['model2'])){
        $modelA = urlencode($_GET['model1']);
        $modelB = urlencode(implode('/',[$modelpkg, $modellang, $modelbase]));
        if ($modelA == $modelB){
            echo("<li>$modellang/$modelbase</li>");
        }
        else{
            echo("<li><a href=\"compare.php?model1=$modelA&model2=$modelB&langpair=$langpair_url\">$modellang/$modelbase</a></li>");
        }
    }
    else{
        $modelA = urlencode(implode('/',[$modelpkg, $modellang, $modelbase]));
        echo("<li><a href=\"compare.php?model1=$modelA&langpair=$langpair_url\">$modellang/$modelbase</a></li>");
    }   
}
echo("</ul>");


?>
</body>
</html>
