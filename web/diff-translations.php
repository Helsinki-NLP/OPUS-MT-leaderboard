<?php
session_start();
include 'functions.php';
$diffbg = get_param('diffbg', 'light');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <title>OPUS-MT - Example Translations</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"> 
  <link rel="stylesheet" href="index.css" type="text/css">
<?php
if ($diffbg == 'dark'){
    echo '  <link rel="stylesheet" type="text/css" href="diffdark.css">'."\n";
}
else{
    echo '  <link rel="stylesheet" type="text/css" href="diff.css">'."\n";
}
?>
</head>
<body class="f9 b9">
<?php     


echo("<h1>OPUS-MT Example Translations</h1>");

// get query parameters
$benchmark = get_param('test', 'all');
$metric    = get_param('metric', 'bleu');
$showlang  = get_param('scoreslang', 'all');
$model1    = get_param('model1', 'all');
$model2    = get_param('model2', 'all');
$diffstyle = get_param('diff', 'wdiff'); // can be diff, wdiff or gitdiff
$start     = get_param('start', 0);
$end       = get_param('end', 9);

list($srclang, $trglang, $langpair) = get_langpair();


$diffstyles = array('diff','wdiff','gitdiff');
$diffbgs    = array('light','dark');

if ($model1 != 'all' && $model2 != 'all'){

    list($pkg1,$lang,$name) = explode('/',$model1);
    $model1 = implode('/',[$lang,$name]);
    list($pkg2,$lang,$name) = explode('/',$model2);
    $model2 = implode('/',[$lang,$name]);
    
    if ($benchmark != 'all'){

        // $trans1 = explode("\n", get_translations ($benchmark, $langpair, $model1, $pkg1));
        // $trans2 = explode("\n", get_translations ($benchmark, $langpair, $model2, $pkg2));

        $trans1 = get_selected_translations ($benchmark, $langpair, $model1, $pkg1, $start, $end);
        $trans2 = get_selected_translations ($benchmark, $langpair, $model2, $pkg2, $start, $end);

        $query = make_query(array('model' => $model1, 'pkg' => $pkg1, 'test' => 'all'));
        echo '<ul><li>Model 1 (diff = red): <a rel="nofollow" href="index.php?'.$query.'">'.$model1.'</a></li>';
        $query = make_query(array('model' => $model2, 'pkg' => $pkg2, 'test' => 'all'));
        echo '<li>Model 2 (diff = green): <a rel="nofollow" href="index.php?'.$query.'">'.$model2.'</a></li>';
        echo '<li>Test Set: '.$benchmark.'</li>';
        echo '<li>Language Pair: '.$langpair.'</li>';
        echo '<li>Diff Style: ';
        print_diffstyle_options($diffstyle);
        print_diffbg_options($diffbg);
        // echo ' (Model 1 = red, Model 2 = green)</li>';
        echo '</li>';
        // echo '<li>Background: ';
        // print_diffbg_options($diffbg);
        // echo '</li>';
        $query = make_query(['test' => $benchmark]);
        echo '<li><a rel="nofollow" href="compare-translations.php?'.$query.'">Show translation without highlighting difference</a></li>';
        $query = make_query(['test' => 'all']);
        echo '<li><a rel="nofollow" href="compare.php?'.$query.'">Return to score comparison</a></li>';
        echo '</ul>';
        show_page_links($start, $end, count($trans1));
        echo '<hr/>';
        

        $evalfile1 = tempnam(sys_get_temp_dir(),'opusmtevalentry');
        $evalfile2 = tempnam(sys_get_temp_dir(),'opusmtevalentry');

        if ($fp = fopen($evalfile1, 'w')){
            fwrite($fp, implode("\n",$trans1));
            fclose($fp);
        }

        if ($fp = fopen($evalfile2, 'w')){
            fwrite($fp, implode("\n",$trans2));
            fclose($fp);
        }

        print_file_diff($evalfile1, $evalfile2, $diffstyle);

        unlink($evalfile1);
        unlink($evalfile2);

    }
}

?>
