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


$srclang   = isset($_GET['src'])    ? test_input($_GET['src'])    : 'deu';
$trglang   = isset($_GET['trg'])    ? test_input($_GET['trg'])    : 'eng';
$benchmark = isset($_GET['test'])   ? test_input($_GET['test'])   : 'flores101-devtest';
$metric    = isset($_GET['metric']) ? test_input($_GET['metric']) : 'bleu';
$langpair  = implode('-',[$srclang,$trglang]);


$leaderboard_url = 'https://raw.githubusercontent.com/Helsinki-NLP/OPUS-MT-leaderboard/master/scores';
$testsets = file(implode('/',[$leaderboard_url,'benchmarks.txt']));

// url-encoded parameters
$srclang_url = urlencode($srclang);
$trglang_url = urlencode($trglang);
$benchmark_url = urlencode($benchmark);
$metric_url = urlencode($metric);

echo '<div class="header">';
foreach ($testsets as $testset){
    $parts = explode("\t",$testset);
    if ($parts[0] == $benchmark){
        echo("[$parts[0]]");
        $testlangs = rtrim($parts[1]);
    }
    else{
        $test_url = urlencode($parts[0]);
        $link = "index.php?src=$srclang_url&trg=$trglang_url&test=$test_url&metric=$metric_url";
        echo("[<a href=\"$link\">$parts[0]</a>]");
    }    
}

echo '<hr/>';
if (isset($_GET['test'])){
$langpairs = explode(' ',$testlangs);
if (sizeof($langpairs) > 20){
    $srclangs = array();
    $trglangs = array();
    foreach ($langpairs as $l){
        $langs = explode('-',$l);
        array_push($srclangs,$langs[0]);
        array_push($trglangs,$langs[1]);
    }
    $srclangs = array_unique($srclangs);
    $trglangs = array_unique($trglangs);
    echo('<table><tr><td>source:</td><td>');
    foreach ($srclangs as $l){
        if ($l == $srclang){
            echo("[$l]");
        }
        else{
            $lang_url = urlencode($l);
            $link = "index.php?src=$lang_url&trg=$trglang_url&test=$benchmark_url&metric=$metric_url";
            echo("[<a href=\"$link\">$l</a>]");
        }
    }
    echo('</td></tr><tr><td>target:</td><td>');
    foreach ($trglangs as $l){
        if ($l == $trglang){
            echo("[$l]");
        }
        else{
            $link = "index.php?src=$srclang&trg=$l&test=$benchmark&metric=$metric";
            echo("[<a href=\"$link\">$l</a>]");
        }
    }
    echo('</td></tr></table>');
}
// elseif (sizeof($langpairs) > 1){
else{
    echo('<table><tr><td>language pair:</td><td>');
    $invalid = true;
    foreach ($langpairs as $l){
        if ($l == $langpair){
            $invalid = false;
        }
        $langs = explode('-',$l);
        if (sizeof($langs) == 2){
            if ($l == $langpair){
                echo("[$l]");
            }
            else{
                $s_url = urlencode($langs[0]);
                $t_url = urlencode($langs[1]);
                $link = "index.php?src=$langs[0]&trg=$langs[1]&test=$benchmark_url&metric=$metric_url";
                echo("[<a href=\"$link\">$l</a>]");
            }
        }
    }
    echo('</td></tr></table>');
    if ( $invalid ){
        $oldlang = $langpair;
        $langpair = $langpairs[0];
        $parts = explode('-',$langpair);
        $srclang = $parts[0];
        $trglang = $parts[1];
        $srclang_url = urlencode($srclang);
        $trglang_url = urlencode($trglang);
        echo("Invalid language pair $oldlang for this benchmark: change to $langpair!");
    }
}
}
echo '</div>';


echo '<br/><table><tr><td>';
echo('<div id="chart">');
echo("<h1>OPUS-MT leaderboard</h1>");

$metrics = array('bleu', 'chrf');
$metriclinks = array();
foreach ($metrics as $m){
    if ($m != $metric){
        $m_url = urlencode($m);
        $metriclinks[$m] = "index.php?src=$srclang_url&trg=$trglang_url&metric=$m_url";
        if (isset($_GET['test'])){
            $metriclinks[$m] .= "&test=$benchmark_url";
        }
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
if (isset($_GET['model'])){
    $parts = explode('/',$_GET['model']);
    $modelfile = array_pop($parts);
    $modellang = array_pop($parts);
    $model_url = urlencode($_GET['model']);
    echo("<li>model: $modellang/$modelfile</li>");
    if ($modellang == $langpair){
        echo("<li>language pair: $langpair</li>");
    }
    else{
        if (isset($_GET['scoreslang'])){
            $url_param = "metric=$metric_url&src=$srclang_url&trg=$trglang_url&model=$model_url";
            if (isset($_GET['test'])){
                $url_param .= "&test=$benchmark_url";
            }
            $lang_link = "<a href=\"index.php?$url_param\">all languages</a>";
            echo("<li>language pair: $langpair [$lang_link]</li>");
        }
        else{
            $langpair_url = urlencode($langpair);
            $url_param = "metric=$metric_url&src=$srclang_url&trg=$trglang_url&model=$model_url&scoreslang=$langpair_url";
            if (isset($_GET['test'])){
                $url_param .= "&test=$benchmark_url";
            }
            $lang_link = "<a href=\"index.php?$url_param\">$langpair</a>";
            echo("<li>language pair: [$lang_link] all languages</li>");
        }
    }
}
elseif (isset($_GET['test'])){
    $testset_srclink = "<a href=\"$testset_src\">$srclang</a>";
    $testset_trglink = "<a href=\"$testset_trg\">$trglang</a>";
    echo("<li>language pair: $testset_srclink - $testset_trglink</li>");
    echo("<li>benchmark: $benchmark");
    $url_param = "metric=$metric_url&src=$srclang_url&trg=$trglang_url";
    echo(" [<a href=\"index.php?$url_param\">all benchmarks</a>]</li>");
}
else{
    echo("<li>language pair: $langpair");
}
echo("<li>metrics: $metric");
foreach ($metriclinks as $m => $l){
    echo(" | <a href=\"$l\">$m</a>");
}
echo("</li></ul>");




if (isset($_GET['test'])){
    $file  = implode('/',[$leaderboard_url,$langpair,$benchmark,$metric.'-scores.txt']);
}
else{
    $file  = implode('/',[$leaderboard_url,$langpair,'top-'.$metric.'-scores.txt']);
}
$lines = file($file);
$id    = sizeof($lines);

if ($id>0 and $lines[0]){
    $url_param = "metric=$metric_url";    
    if (isset($_GET['model'])){
        $model_url = urlencode($_GET['model']);
        $url_param .= "&model=$model_url";
        if (isset($_GET['scoreslang'])){
            $langpair_url = $_GET['scoreslang'];
            $url_param .= "&scoreslang=$langpair_url";
        }
    }
    else{
        $url_param .= "&src=$srclang_url&trg=$trglang_url";
        if (isset($_GET['test'])){
            $url_param .= "&test=$benchmark_url";
        }
    }
    echo("<img src=\"barchart.php?$url_param\" alt=\"barchart\" />");
    echo '</div><div id="scores">';
    // echo '</td><td><div class="query">';
    echo '<div class="query">';

    if (isset($_GET['model'])){
        print_score_table($_GET['model'],$_GET['scoreslang']);
    }
    else{

    echo('<table><tr><th>ID</th>');
    if ( ! isset($_GET['test'])){
        echo("<th>Benchmark</th>");
    }
    echo("<th>$metric</th><th>other</th><th>output</th><th>model</th></tr>");
    $langpair_url = urlencode($langpair);
    $url_param = "src=$srclang_url&trg=$trglang_url&metric=$metric_url";
    $count=0;
    foreach ($lines as $line){
        $id--;
        $parts = explode("\t",rtrim($line));
        if ( ! isset($_GET['test'])){
            $benchmark = array_shift($parts);
            $benchmark_url = urlencode($benchmark);
        }
        $model = explode('/',$parts[1]);
        $modelzip = array_pop($model);
        $modellang = array_pop($model);
        $modelbase = substr($modelzip, 0, -4);
        $baselink = substr($parts[1], 0, -4);
        $link = "<a href=\"$parts[1]\">$modellang/$modelzip</a>";
        $evallink = "<a href=\"$baselink.eval.zip\">zipfile</a>";
        $model_url = urlencode("$modellang/$modelbase");
        $scoreslink = "<a href=\"index.php?$url_param&model=$model_url&scoreslang=$langpair_url\">scores</a>";
        if ( ! isset($_GET['test'])){
            echo("<tr><td>$count</td><td><a href=\"index.php?$url_param&test=$benchmark_url\">$benchmark</a></td>");
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


function print_score_table($model,$langpair){
    $modelhome = 'https://object.pouta.csc.fi/Tatoeba-MT-models';
    $score_file = implode('/',[$modelhome,$model]).'.scores.txt';
    $lines = file($score_file);
    echo('<table>');
    echo("<tr><th>ID</th><th>Language</th><th>Benchmark</th><th>ChrF</th><th>BLEU</th></tr>");
    $id = 0;
    // $url_param = "metric=$metric_url&src=$srclang_url&trg=$trglang_url";
    $langlinks = array();
    foreach ($lines as $line){
        $parts = explode("\t",$line);
        if (isset($langpair)){
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
            $langlink = "<a href=\"index.php?src=$srclang_url&trg=$trglang_url\">$parts[0]</a>";
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
