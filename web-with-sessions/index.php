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

// DEBUGGING: reset session variable:
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
// DEBUGGING: show parameters in session variable
foreach ($_SESSION['params'] as $key => $value){
    echo "$key => $value <br/>";
}
*/



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

// links to the test sets
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

    $model1 = implode('/',[$package, $model]);
    
    $url_param = make_query(['model1' => $model1, 'model2' => 'unknown']);
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
    if ($benchmark != 'all'){
        $url_param = make_query(['test' => 'all']);
        $test_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">all benchmarks</a>";
        echo("<li>benchmark: $benchmark [$test_link]</li>");
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
    if ( isset( $_COOKIE['PHPSESSID'] ) ) {
        echo("<img src=\"barchart.php?". SID ."\" alt=\"barchart\" />");
    }
    else{
        $url_param = make_query([]);
        echo("<img src=\"barchart.php?$url_param\" alt=\"barchart\" />");
    }
    echo '</div><div id="scores">';
    // echo '</td><td><div class="query">';
    echo '<div class="query">';

    if ($model != 'all'){
        print_score_table($model,$showlang,$benchmark,$package);
    }
    else{

    echo('<table><tr><th>ID</th>');
    if ( $benchmark == 'all'){
        echo("<th>Benchmark</th>");
    }
    echo("<th>$metric</th><th>other</th><th>output</th><th>model</th></tr>");
    $count=0;
    foreach ($lines as $line){
        $id--;
        $parts = explode("\t",rtrim($line));
        $test = $benchmark == 'all' ? array_shift($parts) : $benchmark;
        $model = explode('/',$parts[1]);
        $modelzip = array_pop($model);
        $modellang = array_pop($model);
        $modelpkg = array_pop($model);
        $modelbase = substr($modelzip, 0, -4);
        $baselink = substr($parts[1], 0, -4);
        $link = "<a rel=\"nofollow\" href=\"$parts[1]\">$modellang/$modelzip</a>";
        $evallink = "<a rel=\"nofollow\" href=\"$baselink.eval.zip\">zip</a>";
        
        $url_param = make_query(['model' => implode('/',[$modellang,$modelbase]),'pkg' => $modelpkg,'test' => $test,'langpair' => $langpair ]);
        $translink = "<a rel=\"nofollow\" href=\"translations.php?".SID.'&'.$url_param."\">txt</a>";
        
        $url_param = make_query(['model' => implode('/',[$modellang,$modelbase]), 'pkg' => $modelpkg, 'scoreslang' => $langpair, 'test' => 'all' ]);
        $scoreslink = "<a rel=\"nofollow\" href=\"index.php?$url_param\">scores</a>";

        if ( $benchmark == 'all'){
            $url_param = make_query(['test' => $test, 'scoreslang' => $langpair ]);
            echo("<tr><td>$count</td><td><a rel=\"nofollow\" href=\"index.php?$url_param\">$test</a></td>");
        }
        else{
            echo("<tr><td>$id</td>");
        }
        echo("<td>$parts[0]</td><td>$scoreslink</td><td>$translink, $evallink</td><td>$link</td></tr>");
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


function print_score_table($model,$langpair='all',$benchmark='all', $pkg='Tatoeba-MT-models'){
    
    $lines = read_scores($langpair, 'all', 'all', $model, $pkg);
    echo('<table>');
    echo("<tr><th>ID</th><th>Language</th><th>Benchmark</th><th>output</th><th>ChrF</th><th>BLEU</th></tr>");
    $id = 0;
    $langlinks = array();
    foreach ($lines as $line){
        $parts = explode("\t",$line);
        if ($langpair != 'all'){
            if ($parts[0] != $langpair){
                continue;
            }
        }
        if ($benchmark != 'all'){
            if ($parts[1] != $benchmark){
                continue;
            }
        }
        if (array_key_exists($parts[0],$langlinks)){
            $langlink = $langlinks[$parts[0]];
        }
        else{            
            $langs = explode('-',$parts[0]);
            $query = make_query(['src' => $langs[0], 'trg' => $langs[1],'model' => 'all', 'test' => 'all']);
            $langlink = "<a rel=\"nofollow\" href=\"index.php?$query\">$parts[0]</a>";
            $langlinks[$parts[0]] = $langlink;
        }

        $modelhome = 'https://object.pouta.csc.fi/'.$pkg;
        $evallink = "<a rel=\"nofollow\" href=\"$modelhome/$model.eval.zip\">zip</a>";
        
        $url_param = make_query(['test' => $parts[1],'langpair' => $parts[0]]);
        $translink = "<a rel=\"nofollow\" href=\"translations.php?".SID.'&'.$url_param."\">txt</a>";

        $url_param = make_query(['test' => $parts[1]]);
        $testlink = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$parts[1]</a>";

        echo("<tr><td>$id</td><td>$langlink</td><td>$testlink</td><td>$translink, $evallink</td><td>$parts[2]</td><td>$parts[3]</td></tr>");
        $id++;
    }
    echo('</table>');
}


?>
</body>
</html>
