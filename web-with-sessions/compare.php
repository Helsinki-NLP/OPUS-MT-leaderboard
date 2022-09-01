<?php session_start(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
  <title>OPUS-MT - Leaderboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"> 
  <link rel="stylesheet" href="index.css" type="text/css">
</head>
<body>

<?php

          
include 'functions.php';


$leaderboard_url = 'https://raw.githubusercontent.com/Helsinki-NLP/OPUS-MT-leaderboard/master/scores';


// get query parameters
$chart     = get_param('chart', 'standard');
$benchmark = get_param('test', 'all');
$metric    = get_param('metric', 'bleu');

list($srclang, $trglang, $langpair) = get_langpair();

$showlang  = get_param('scoreslang', $langpair);


/*
foreach ($_SESSION['params'] as $key => $value){
    echo "$key => $value <br/>";
}
*/



$benchmark_url = urlencode($benchmark);
$langpair_url  = urlencode($langpair);
$showlang_url = urlencode($showlang);
$metric_url = urlencode($metric);
$chart_url = urlencode($chart);


include 'header.php';



echo('<h1>Compare OPUS-MT models</h1>');

if (isset($_GET['model1']) && isset($_GET['model2'])){
    $model1_url = urlencode($_GET['model1']);
    $model2_url = urlencode($_GET['model2']);
    $url_param = "metric=$metric_url&model1=$model1_url&model2=$model2_url&langpair=$langpair_url";
    if ($chart == 'diff'){
        // echo("<div id=\"chart\"><img src=\"diff-barchart.php?$url_param&scoreslang=$showlang_url&test=$benchmark_url\" alt=\"barchart\" /></div>");
        echo("<div id=\"chart\"><img src=\"diff-barchart.php?$url_param&scoreslang=$showlang_url&test=$benchmark_url\" alt=\"barchart\" /><br/><ul>");
        echo('<li>Chart Type: ');
        echo("[<a rel=\"nofollow\" href=\"compare.php?model1=$model1_url&model2=$model2_url&langpair=$langpair_url&scoreslang=$showlang_url&test=$benchmark_url&metric=$metric_url&chart=standard\">standard</a>]");
            echo('[diff]</li>');
    }
    else{
        // echo("<div id=\"chart\"><img src=\"compare-barchart.php?$url_param&scoreslang=$showlang_url&test=$benchmark_url\" alt=\"barchart\" /></div>");
        echo("<div id=\"chart\"><img src=\"compare-barchart.php?$url_param&scoreslang=$showlang_url&test=$benchmark_url\" alt=\"barchart\" /><br/><ul><li>Chart Type: ");
        echo('[standard]');
        echo("[<a rel=\"nofollow\" href=\"compare.php?model1=$model1_url&model2=$model2_url&langpair=$langpair_url&scoreslang=$showlang_url&test=$benchmark_url&metric=$metric_url&chart=diff\">diff</a>]</li>");
    }
    echo('<li>Evaluation Metric: ');
    $metrics = array('bleu', 'chrf');
    foreach ($metrics as $m){
        if ($m == $metric){
            echo("[$m]");
        }
        else{
            echo("[<a rel=\"nofollow\" href=\"compare.php?model1=$model1_url&model2=$model2_url&langpair=$langpair_url&scoreslang=$showlang_url&test=$benchmark_url&metric=$m&chart=$chart_url\">$m</a>]");
        }
    }
    echo('</li></ul></div>');


    
    $url_param .= "&chart=$chart_url";
    $langpairs = print_score_table($_GET['model1'],$_GET['model2'],$showlang,$benchmark);
}




// TODO: do we also want to cache model lists in the SESSION variable?
$models = file(implode('/',[$leaderboard_url,$langpair,'model-list.txt']));


if (isset($_GET['model1'])){
    echo('<div id="scores"><h2>Selected models</h2>');
    echo('<ul>');
    
    list($m1_pkg, $m1_lang, $m1_name) = explode('/',$_GET['model1']);
    $m_url = urlencode($m1_lang.'/'.$m1_name);
    $p_url = urlencode($m1_pkg);
    $m_link = "<a rel=\"nofollow\" href=\"index.php?model=$m_url&pkg=$p_url&langpair=$langpair_url&scoreslang=$showlang_url&test=$benchmark_url\">";

    echo('<li><b>Model 1 (blue):</b> '.$m_link.$_GET['model1'].'</a></li>');

    if (isset($_GET['model2'])){

        list($m2_pkg, $m2_lang, $m2_name) = explode('/',$_GET['model2']);
        $m_url = urlencode($m2_lang.'/'.$m2_name);
        $p_url = urlencode($m2_pkg);
        $m_link = "<a rel=\"nofollow\" href=\"index.php?model=$m_url&pkg=$p_url&langpair=$langpair_url&scoreslang=$showlang_url&test=$benchmark_url\">";
    
        echo('<li><b>Model 2 (orange):</b> '.$m_link.$_GET['model2'].'</a></li>');
        echo('<li><b>Comparable Model Langpair(s):</b> ');
        ksort($langpairs);
        foreach ($langpairs as $lp => $count){
            if ($lp == $showlang){
                echo("[$showlang]");
            }
            else{
                $lp_url = urlencode($lp);
                echo("[<a rel=\"nofollow\" href=\"compare.php?$url_param&scoreslang=$lp&test=$benchmark_url\">$lp_url</a>]");
            }
        }
        if ($showlang != 'all'){
            echo("[<a rel=\"nofollow\" href=\"compare.php?$url_param&scoreslang=all&test=$benchmark_url\">all</a>]");
        }
        echo('</li>');
        echo('</ul><h2>Start with a new model</h2>');
    }
    else{
        echo('</ul>');
        echo('<h2>Select the second model to compare with</h2>');
    }
}
else{
    echo('<h2>Select a model</h2>');
}

$sorted_models = array();
foreach ($models as $model){
    $parts = explode('-',rtrim($model));
    $day = array_pop($parts);
    $month = array_pop($parts);
    $year = array_pop($parts);
    $sorted_models[$model] = "$year$month$day";
}
arsort($sorted_models);


echo("<ul>");
// foreach ($models as $model){
foreach ($sorted_models as $model => $release){
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
            echo("<li><a rel=\"nofollow\" href=\"compare.php?model1=$modelA&model2=$modelB&langpair=$langpair_url&test=$benchmark_url&scoreslang=$showlang_url&metric=$metric_url\">$modellang/$modelbase</a></li>");
        }
    }
    else{
        $modelA = urlencode(implode('/',[$modelpkg, $modellang, $modelbase]));
        echo("<li><a rel=\"nofollow\" href=\"compare.php?model1=$modelA&langpair=$langpair_url&test=$benchmark_url&scoreslang=$showlang_url&metric=$metric_url\">$modellang/$modelbase</a></li>");
    }   
}
echo("</ul></div>");





function print_score_table($model1,$model2,$langpair='all',$benchmark='all'){
    global $metric, $langpair_url, $chart_url;


    $scores = array();
    $langpairs = array();

    list($pkg2, $lang2, $name2) = explode('/',$_GET['model2']);
    $lines = read_scores($langpair, 'all', 'all', implode('/',[$lang2,$name2]), $pkg2);

    foreach($lines as $line) {
        $array = explode("\t", $line);
        $langpairs[$array[0]]++;
        if ($langpair == 'all' || $langpair == $array[0]){
            if ($benchmark == 'all' || $benchmark == $array[1]){
                $key = $array[0].'/'.$array[1];
                $scores[$key] = $line;
            }
        }
    }
    
    list($pkg1, $lang1, $name1) = explode('/',$_GET['model1']);
    $lines = read_scores($langpair, 'all', 'all', implode('/',[$lang1,$name1]), $pkg1);

    $metric_url = urlencode($metric);
    $model1_url = urlencode($_GET['model1']);
    $model2_url = urlencode($_GET['model2']);
    $showlang_url = urlencode($langpair);
    $benchmark_url = urlencode($benchmark);
    $url_param = "metric=$metric_url&model1=$model1_url&model2=$model2_url&langpair=$langpair_url&chart=$chart_url";

    $avg_score1 = 0;
    $avg_score2 = 0;
    $count_scores1 = 0;
    $count_scores2 = 0;

    $common_langs = array();
    $testsets = array();
    
    echo('<div id="scores"><div class="query"><table>');
    echo("<tr><th>ID</th><th>Language</th><th>Benchmark ($metric)</th><th>Model 1</th><th>Model 2</th><th>Diff</th></tr>");
    $id = 0;
    foreach ($lines as $line){
        $parts = explode("\t",$line);
        $score1 = $metric == 'bleu' ? $parts[3] : $parts[2];
        $key = $parts[0].'/'.$parts[1];
        $testsets[$parts[1]]++;
        if (array_key_exists($parts[0],$langpairs)){
            $common_langs[$parts[0]]++;
        }


        if (array_key_exists($key,$scores)){
            $parts2 = explode("\t",$scores[$key]);
            $score2 = $metric == 'bleu' ? $parts2[3] : $parts2[2];
            $score2_exists = true;

            $diff = $score1 - $score2;
            if ($metric == 'bleu'){
                $diff_pretty = sprintf('%4.1f',$diff);
            }
            else{
                $diff_pretty = sprintf('%5.3f',$diff);
            }

            if ($langpair == 'all' || $langpair == $parts[0]){
                if ($benchmark == 'all' || $benchmark == $parts[1]){
                    $avg_score1 += $score1;
                    $count_scores1++;
                    $avg_score2 += $score2;
                    $count_scores2++;

                    $lang_url = urlencode($parts[0]);
                    $test_url = urlencode($parts[1]);
                    $langlink = "<a rel=\"nofollow\" href=\"compare.php?$url_param&scoreslang=$lang_url&test=$benchmark_url\">$parts[0]</a>";
                    $testlink = "<a rel=\"nofollow\" href=\"compare.php?$url_param&scoreslang=$showlang_url&test=$test_url\">$parts[1]</a>";
                    echo("<tr><td>$id</td><td>$langlink</td><td>$testlink</td><td>$score1</td><td>$score2</td><td>$diff_pretty</td></tr>");
                    $id++;
                }
            }
        }
    }
        
    if ($count_scores1 > 1){
        $avg_score1 /= $count_scores1;
    }
    if ($count_scores2 > 1){
        $avg_score2 /= $count_scores2;
    }
    $diff = $avg_score1 - $avg_score2;
    
    if ($metric == 'bleu'){
        $avg1 = sprintf('%4.1f',$avg_score1);
        $avg2 = sprintf('%4.1f',$avg_score2);
        $diff = sprintf('%4.1f',$diff);
    }
    else{
        $avg1 = sprintf('%5.3f',$avg_score1);
        $avg2 = sprintf('%5.3f',$avg_score2);
        $diff = sprintf('%5.3f',$diff);
    }
    echo("<tr><th></th><th></th><th>average</th><th>$avg1</th><th>$avg2</th><th>$diff</th></tr>");

    if ($langpair != 'all' || $benchmark != 'all'){
        $langlink = '';
        $testlink = '';
        if ($langpair != 'all'){
            if (sizeof($common_langs) > 1){
                $langlink = "<a rel=\"nofollow\" href=\"compare.php?$url_param&scoreslang=all&test=$benchmark_url\">show all</a>";
            }
        }
        if ($benchmark != 'all'){
            if (sizeof($testsets) > 1){
                $testlink = "<a rel=\"nofollow\" href=\"compare.php?$url_param&scoreslang=$showlang_url&test=all\">show all</a>";
            }
        }
        if ($langlink != '' || $testlink != ''){
            echo("<tr><td></td><td>$langlink</td><td>$testlink</td><td></td><td></td><td></td></tr>");
        }
    }

    echo('</table></div></div>');
    return $common_langs;
}



?>
</body>
</html>
