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

// get query parameters
$package   = get_param('pkg', 'Tatoeba-MT-models');
$benchmark = get_param('test', 'all');
$metric    = get_param('metric', 'bleu');
$showlang  = get_param('scoreslang', 'all');
$model     = get_param('model', 'all');

list($srclang, $trglang, $langpair) = get_langpair();

if ($showlang != 'all'){
    if ($showlang != $langpair){
        set_langpair($showlang);
        list($srclang, $trglang, $langpair) = get_langpair();
    }
}



// DEBUGGING: show parameters in session variable
/*
foreach ($_SESSION['params'] as $key => $value){
    echo "$key => $value <br/>";
}
*/



include 'header.php';

echo("<h1>OPUS-MT leaderboard</h1>");
echo('<div id="chart">');


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

$url_param = make_query(['test' => 'all', 'model' => 'all']);
$best_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">top models</a>";

if ($model != 'all'){
    $parts = explode('/',$model);
    $modelfile = array_pop($parts);
    $modellang = array_pop($parts);

    $model1 = implode('/',[$package, $model]);

    if ($modellang == $langpair){
        echo("<li><b>Language pair:</b> $langpair [$best_link]</li>");
    }
    else{
        if ($showlang != 'all'){
            $url_param = make_query(['scoreslang' => 'all']);
            $all_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">all languages</a>";
            echo("<li><b>Language pair:</b> $langpair [$all_link][$best_link]</li>");
        }
        else{
            $url_param = make_query(['scoreslang' => $langpair]);
            $lang_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$langpair</a>";
            echo("<li><b>Language pair:</b> [$lang_link] all languages [$best_link]</li>");
        }
    }

    $url_param = make_query(['model1' => $model1, 'model2' => 'unknown']);
    $comparelink = "[<a rel=\"nofollow\" href=\"compare.php?". SID . '&'.$url_param."\">compare</a>]";
    $modelhome = $storage_url.$package;
    $downloadlink = "<a rel=\"nofollow\" href=\"$modelhome/$model.zip\">$modellang/$modelfile</a>";
    echo("<li><b>Model:</b> $downloadlink $comparelink</li>");
    
    if ($benchmark != 'all'){
        $url_param = make_query(['test' => 'all']);
        $test_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">all benchmarks</a>";
        echo("<li><b>Benchmark:</b> $benchmark [$test_link]</li>");
    }
    else{
        echo("<li><b>Benchmark:</b> all benchmarks</li>");
    }
}
elseif ($benchmark != 'all'){
    echo("<li><b>Language pair:</b> $langpair [$best_link]</li>");
    echo("<li><b>Model:</b> all models</li>");
    echo("<li><b>Benchmark:</b> ");
    if ($benchmark != 'avg'){
        $testset_srclink = "<a rel=\"nofollow\" href=\"$testset_src\">$srclang</a>";
        $testset_trglink = "<a rel=\"nofollow\" href=\"$testset_trg\">$trglang</a>";
        echo($benchmark." [".$testset_srclink.'-'.$testset_trglink."]");
    }
    else{
        echo('average ');
    }
    $url_param = make_query(['test' => 'all']);
    echo(" [<a rel=\"nofollow\" href=\"index.php?$url_param\">all benchmarks</a>]</li>");
}
else{
    echo("<li><b>Language pair:</b> $langpair</li>");
    echo("<li><b>Model:</b> top models</li>");
    echo("<li><b>Benchmark:</b> $benchmark");
    $url_param = make_query(['test' => 'avg']);
    echo(" [<a rel=\"nofollow\" href=\"index.php?$url_param\">average</a>]</li>");
}
echo("<li><b>Metrics:</b> ");
print_metric_options($metric);
echo("</li></ul>");

if ( isset( $_COOKIE['PHPSESSID'] ) ) {
    echo("<img src=\"barchart.php?". SID ."\" alt=\"barchart\" />");
}
else{
    $url_param = make_query([]);
    echo("<img src=\"barchart.php?$url_param\" alt=\"barchart\" />");
}
echo('<ul><li>orange = OPUS-MT models, blue = Tatoeba-MT models, green = compact models</li>');
if ($benchmark == 'all' || (strpos($benchmark,'tatoeba') !== false)){
    echo('<li>Note: Tatoeba test sets are not reliable for OPUS-MT models!</li>');
}


/////////////////////////////////////////////////////
// score table
/////////////////////////////////////////////////////

echo '</ul></div><div id="scores" class="query">';
if ($model != 'all'){
    print_model_scores($model,$showlang,$benchmark,$package,$metric);
}
else{
    print_scores($langpair,$benchmark,$package,$metric);
}

echo('</div>');




function print_model_scores($model,$langpair='all',$benchmark='all', $pkg='Tatoeba-MT-models',$metric='all'){
    global $storage_url;

    // echo(get_score_filename($langpair, 'all', $metric, $model, $pkg));
    $lines = read_scores($langpair, 'all', $metric, $model, $pkg);

    echo("<h3>Model Scores ($model)</h3>");
    echo('<table>');
    echo("<tr><th>ID</th><th>Language</th><th>Benchmark</th><th>Output</th><th>$metric</th></tr>");
    $id = 0;
    $langlinks = array();
    $additional_languages = 0;
    $additional_benchmarks = 0;
    $avg1 = 0;
    $avg2 = 0;
    
    foreach ($lines as $line){
        $parts = explode("\t",rtrim($line));
        if ($langpair != 'all'){
            if ($parts[0] != $langpair){
                $additional_languages++;
                continue;
            }
        }
        if ($benchmark != 'all'){
            if ($parts[1] != $benchmark){
                $additional_benchmarks++;
                continue;
            }
        }
        if (array_key_exists($parts[0],$langlinks)){
            $langlink = $langlinks[$parts[0]];
        }
        else{
            $query = make_query(['scoreslang' => $parts[0]]);
            $langlink = "<a rel=\"nofollow\" href=\"index.php?$query\">$parts[0]</a>";
            $langlinks[$parts[0]] = $langlink;
        }

        $modelhome = $storage_url.$pkg;
        $evallink = "<a rel=\"nofollow\" href=\"$modelhome/$model.eval.zip\">download</a>";
        
        $url_param = make_query(['test' => $parts[1],'langpair' => $parts[0], 'start' => 0, 'end' => 9]);
        $translink = "<a rel=\"nofollow\" href=\"translations.php?".SID.'&'.$url_param."\">show</a>";

        $url_param = make_query(['test' => $parts[1]]);
        $testlink = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$parts[1]</a>";

        echo("<tr><td>$id</td><td>$langlink</td><td>$testlink</td><td>$translink, $evallink</td><td>$parts[2]</td></td></tr>");
        $avg1 += $parts[2];
        $id++;
    }

    if ($id > 0){
        $avg1 /= $id;
        $avg1 = sprintf('%5.3f',$avg1);
    }
        
    $langlink = '';
    $testlink = '';
    if ($additional_languages > 0){
        $url_param = make_query(['scoreslang' => 'all']);
        $langlink = "<a rel=\"nofollow\" href=\"index.php?".$url_param."\">show all</a>";
    }
    if ($additional_benchmarks > 0){
        $url_param = make_query(['test' => 'all']);
        $testlink = "<a rel=\"nofollow\" href=\"index.php?".$url_param."\">show all</a>";
    }
    echo("<tr><th></th><th>$langlink</th><th>$testlink</th><th>average</th><th>$avg1</th></th></tr>");    
    echo('</table>');
}


function print_scores($langpair='all', $benchmark='all', $pkg='Tatoeba-MT-models', $metric='bleu'){

    $lines = read_scores($langpair, $benchmark, $metric);
    if ($lines == false){
        $lines = array();
    }
    if ($benchmark == 'avg'){
        $averaged_benchmarks = implode(', ',explode(' ',rtrim(array_shift($lines))));
        echo("<h3>Model Scores (averaged over $averaged_benchmarks testsets)</h3>");
    }
    elseif ($benchmark == 'all'){
        echo("<h3>Model Scores (top scores on all available benchmarks)</h3>");
    }
    else{
        echo("<h3>Model Scores ($metric scores on the \"$benchmark\" testset)</h3>");
    }
    $id    = sizeof($lines);



    echo('<table><tr><th>ID</th>');
    if ( $benchmark == 'all'){
        echo("<th>Benchmark</th>");
    }
    echo("<th>$metric</th><th>Other</th><th>Output</th><th>Model Download</th></tr>");
    
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
        $evallink = "<a rel=\"nofollow\" href=\"$baselink.eval.zip\">download</a>";
        
        $url_param = make_query(['model' => implode('/',[$modellang,$modelbase]), 'pkg' => $modelpkg, 'scoreslang' => $langpair, 'test' => 'all' ]);
        $scoreslink = "<a rel=\"nofollow\" href=\"index.php?$url_param\">scores</a>";

        if ( $benchmark != 'avg'){
            $url_param = make_query(['model' => implode('/',[$modellang,$modelbase]),
                                     'pkg' => $modelpkg,'test' => $test,'langpair' => $langpair,
                                     'start' => 0, 'end' => 9 ]);
            $translink = "<a rel=\"nofollow\" href=\"translations.php?".SID.'&'.$url_param."\">show</a>";
            $evallink = $translink.', '.$evallink;
        }

        if ( $benchmark == 'all'){
            $url_param = make_query(['test' => $test, 'scoreslang' => $langpair ]);
            echo("<tr><td>$count</td><td><a rel=\"nofollow\" href=\"index.php?$url_param\">$test</a></td>");
        }
        else{
            echo("<tr><td>$id</td>");
        }
        $pretty_score = $metric == 'bleu' ? sprintf('%4.1f',$parts[0]) : sprintf('%5.3f',$parts[0]);
        echo("<td>$pretty_score</td><td>$scoreslink</td><td>$evallink</td><td>$link</td></tr>");
        $count++;
    }
    echo('</table>');
}

?>
</body>
</html>
