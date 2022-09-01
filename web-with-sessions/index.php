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

// $_SESSION = array();
     
include 'functions.php';


// get query parameters
$package   = get_param('pkg', 'Tatoeba-MT-models');
$benchmark = get_param('test', 'all');
$metric    = get_param('metric', 'bleu');
$showlang  = get_param('scoreslang', 'all');
$model     = get_param('model', 'all');

list($srclang, $trglang, $langpair) = get_langpair();


/*
foreach ($_SESSION['params'] as $key => $value){
    echo "$key => $value <br/>";
}
*/


// url-encoded parameters
$srclang_url = urlencode($srclang);
$trglang_url = urlencode($trglang);
$benchmark_url = urlencode($benchmark);
$metric_url = urlencode($metric);


include 'header.php';





echo '<br/><table><tr><td>';
echo('<div id="chart">');
echo("<h1>OPUS-MT leaderboard</h1>");

$metrics = array('bleu', 'chrf');
$metriclinks = array();
foreach ($metrics as $m){
    if ($m != $metric){
        $query = make_query(array('metric' => $m));
        $metriclinks[$m] = $_SERVER['PHP_SELF'].'?'.$query;
    }
}

$testset_url = 'https://github.com/Helsinki-NLP/OPUS-MT-testsets/tree/master/testsets';
if ($benchmark == 'flores101-dev'){
    $testset_src = implode('/',[$testset_url,'flores101_dataset','dev',$srclang]).".dev";
    $testset_trg = implode('/',[$testset_url,'flores101_dataset','dev',$trglang]).".dev";
}
elseif ($benchmark == 'flores101-devtest'){
    $testset_src = implode('/',[$testset_url,'flores101_dataset','devtest',$srclang]).".devtest";
    $testset_trg = implode('/',[$testset_url,'flores101_dataset','devtest',$trglang]).".devtest";
}
else{
    $testset_src = implode('/',[$testset_url,$langpair,$benchmark]).".$srclang";
    $testset_trg = implode('/',[$testset_url,$langpair,$benchmark]).".$trglang";
}




echo("<ul>");
if ($model != 'all'){
    $parts = explode('/',$model);
    $modelfile = array_pop($parts);
    $modellang = array_pop($parts);
    $model_url = urlencode($model);
    $langpair_url = urlencode($langpair);

    $model1 = implode('/',[$package, $model]);
    
    $url_param = make_query(['model1' => $model1]);
    $comparelink = "[<a rel=\"nofollow\" href=\"compare.php?". SID . '&'.$url_param."\">compare</a>]";
    echo("<li>model: $modellang/$modelfile $comparelink</li>");
    if ($modellang == $langpair){
        echo("<li>language pair: $langpair</li>");
    }
    else{
        if ($showlang != 'all'){
            $url_param = make_query(['scoreslang' => 'all']);
            $lang_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">all languages</a>";
            echo("<li>language pair: $langpair [$lang_link]</li>");
        }
        else{
            $url_param = make_query(['scoreslang' => $langpair]);
            $lang_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$langpair</a>";
            echo("<li>language pair: [$lang_link] all languages</li>");
        }
    }
}
elseif ($benchmark != 'all'){
    $testset_srclink = "<a rel=\"nofollow\" href=\"$testset_src\">$srclang</a>";
    $testset_trglink = "<a rel=\"nofollow\" href=\"$testset_trg\">$trglang</a>";
    echo("<li>language pair: $testset_srclink - $testset_trglink</li>");
    echo("<li>benchmark: $benchmark");
    $url_param = make_query(['test' => 'all']);
    echo(" [<a rel=\"nofollow\" href=\"index.php?$url_param\">all benchmarks</a>]</li>");
}
else{
    echo("<li>language pair: $langpair");
}
echo("<li>metrics: $metric");
foreach ($metriclinks as $m => $l){
    echo(" | <a rel=\"nofollow\" href=\"$l\">$m</a>");
}
echo("</li></ul>");



$lines = read_scores($langpair, $benchmark, $metric);
if ($lines == false){
    $lines = array();
}
$id    = sizeof($lines);

// if ($id>0 and $lines[0]){
if ($id>0){
    $url_param = "metric=$metric_url";    
    if ($model != 'model'){
        $model_url = urlencode($model);
        $package_url = urlencode($package);
        $url_param .= "&model=$model_url&pkg=$package_url";
        if ($showlang != 'all'){
            $langpair_url = urlencode($showlang);
            $url_param .= "&scoreslang=$langpair_url";
        }
    }
    else{
        $url_param .= "&src=$srclang_url&trg=$trglang_url";
        if ($benchmark != 'all'){
            $url_param .= "&test=$benchmark_url";
        }
    }
    if ( isset( $_COOKIE['PHPSESSID'] ) ) {
        echo("<img src=\"barchart.php?". SID ."\" alt=\"barchart\" />");
    }
    else{
        echo("<img src=\"barchart.php?$url_param\" alt=\"barchart\" />");
    }
    echo '</div><div id="scores">';
    // echo '</td><td><div class="query">';
    echo '<div class="query">';

    if ($model != 'all'){
        print_score_table($model,$showlang,$package);
    }
    else{

    echo('<table><tr><th>ID</th>');
    if ( $benchmark == 'all'){
        echo("<th>Benchmark</th>");
    }
    echo("<th>$metric</th><th>other</th><th>output</th><th>model</th></tr>");
    $langpair_url = urlencode($langpair);
    $url_param = "src=$srclang_url&trg=$trglang_url&metric=$metric_url";
    $count=0;
    foreach ($lines as $line){
        $id--;
        $parts = explode("\t",rtrim($line));
        if ( $benchmark == 'all'){
            $test = array_shift($parts);
            $benchmark_url = urlencode($test);
        }
        $model = explode('/',$parts[1]);
        $modelzip = array_pop($model);
        $modellang = array_pop($model);
        $modelpkg = array_pop($model);
        $modelbase = substr($modelzip, 0, -4);
        $baselink = substr($parts[1], 0, -4);
        // $link = "<a href=\"$parts[1]\">$modelpkg/$modellang/$modelzip</a>";
        $link = "<a rel=\"nofollow\" href=\"$parts[1]\">$modellang/$modelzip</a>";
        $evallink = "<a rel=\"nofollow\" href=\"$baselink.eval.zip\">zipfile</a>";
        $model_url = urlencode("$modellang/$modelbase");
        $scoreslink = "<a rel=\"nofollow\" href=\"index.php?$url_param&model=$model_url&scoreslang=$langpair_url&pkg=$modelpkg\">scores</a>";
        if ( $benchmark == 'all'){
            echo("<tr><td>$count</td><td><a rel=\"nofollow\" href=\"index.php?$url_param&test=$benchmark_url\">$test</a></td>");
        }
        else{
            echo("<tr><td>$id</td>");
        }
        echo("<td>$parts[0]</td><td>$scoreslink</td><td>$evallink</td><td>$link</td></tr>");
        $count++;
    }
    }
    echo('</table>');
    echo('</div>');
}
else{
    echo "No results available for this dataset.";
}

echo '</td></tr></table>';


function print_score_table($model,$langpair='all',$pkg='Tatoeba-MT-models'){
    
    $lines = read_scores($langpair, 'all', 'all', $model, $pkg);
    echo('<table>');
    echo("<tr><th>ID</th><th>Language</th><th>Benchmark</th><th>ChrF</th><th>BLEU</th></tr>");
    $id = 0;
    // $url_param = "metric=$metric_url&src=$srclang_url&trg=$trglang_url";
    $langlinks = array();
    foreach ($lines as $line){
        $parts = explode("\t",$line);
        if ($langpair != 'all'){
            if ($parts[0] != $langpair){
                continue;
            }
        }
        if (array_key_exists($parts[0],$langlinks)){
            $langlink = $langlinks[$parts[0]];
        }
        else{            
            $langs = explode('-',$parts[0]);
            $srclang_url = urlencode($langs[0]);
            $trglang_url = urlencode($langs[1]);
            $langlink = "<a rel=\"nofollow\" href=\"index.php?src=$srclang_url&trg=$trglang_url&model=all&test=all\">$parts[0]</a>";
            $langlinks[$parts[0]] = $langlink;
        }
        echo("<tr><td>$id</td><td>$langlink</td><td>$parts[1]</td><td>$parts[2]</td><td>$parts[3]</td></tr>");
        $id++;
    }
    echo('</table>');
}


?>
</body>
</html>
