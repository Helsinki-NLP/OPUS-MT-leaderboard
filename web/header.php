<?php 

$leaderboard_url = 'https://raw.githubusercontent.com/Helsinki-NLP/OPUS-MT-leaderboard/master/scores';

// $testsets = file(implode('/',[$leaderboard_url,'benchmarks.txt']));
// $all_langpairs = file(implode('/',[$leaderboard_url,'langpairs.txt']));


// form for selecting benchmarks and language pairs

echo '<div class="header">';
echo '<form action="'.$_SERVER['PHP_SELF'].'" method="get">';
// echo '<input type="hidden" id="session" name="session" value="clear">';
echo '<input type="hidden" id="model" name="model" value="all">';
echo '<input type="hidden" id="model1" name="model1" value="unknown">';
echo '<input type="hidden" id="model2" name="model2" value="unknown">';
echo '<input type="hidden" id="test" name="test" value="all">';
echo '<input type="hidden" id="scoreslang" name="scoreslang" value="all">';


/*
echo 'select benchmark: <select name="test" id="langpair" onchange="this.form.submit()">';
echo "<option value=\"avg\">average</option>";
echo "<option value=\"all\">all</option>";

foreach ($testsets as $testset){
    list($test,$langs) = explode("\t",$testset);
    $test_url = urlencode($test);
    if ($test == $benchmark){
        echo "<option value=\"$test_url\" selected>$test</option>";
        $testlangs = rtrim($langs);
    }
    else {
        echo "<option value=\"$test_url\">$test</option>";
    }
}
echo '</select>';

// get list of language pairs in this benchmark
// get all available language pairs if no specific benchmark is seslected

if (($benchmark == "all") || ($benchmark == "avg")){
    $langpairs = array_map('rtrim', file(implode('/',[$leaderboard_url,'langpairs.txt'])));
    unset($_GET['test']);
}
else{
    $langpairs = explode(' ',$testlangs);
}
*/


$langpairs = array_map('rtrim', file(implode('/',[$leaderboard_url,'langpairs.txt'])));
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
echo '  [<a href="index.php?session=clear">restart</a>]';
$query = make_query(['model' => 'all', 'test' => 'all', 'scoreslang' => 'all']);
echo '  [<a href="index.php?'.SID.'&'.$query.'">compare scores</a>]';
$query = make_query(['model1' => 'unknown', 'model2' => 'unknown', 'test' => 'all', 'scoreslang' => 'all']);
echo '  [<a href="compare.php?'.SID.'&'.$query.'">compare models</a>]';
echo '  [<a href="releases.php">show release history</a>]';
echo '</form>';

echo '<hr/>';

/*
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
                $link = $_SERVER['PHP_SELF']."?src=$lang_url&trg=$trglang_url&test=$benchmark_url&metric=$metric_url";
                echo("[<a rel=\"nofollow\" href=\"$link\">$l</a>]");
            }
        }
        echo('</td></tr><tr><td>target:</td><td>');
        foreach ($trglangs as $l){
            if ($l == $trglang){
                echo("[$l]");
            }
            else{
                $link = $_SERVER['PHP_SELF']."?src=$srclang&trg=$l&test=$benchmark&metric=$metric";
                echo("[<a rel=\"nofollow\" href=\"$link\">$l</a>]");
            }
        }
        echo('</td></tr></table>');
    }
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
                    $link = $_SERVER['PHP_SELF']."?src=$langs[0]&trg=$langs[1]&test=$benchmark_url&metric=$metric_url";
                    echo("[<a rel=\"nofollow\" href=\"$link\">$l</a>]");
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
*/
echo '</div>';


?>
