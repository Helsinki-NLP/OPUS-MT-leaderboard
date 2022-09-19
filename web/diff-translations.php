<?php
session_start();
include 'functions.php';
$style = get_param('style', 'light');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <title>OPUS-MT - Example Translations</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="index.css" type="text/css">
<?php
if ($style == 'dark'){
    echo '  <link rel="stylesheet" type="text/css" href="diff_dark.css">'."\n";
}
else{
    echo '  <link rel="stylesheet" type="text/css" href="diff_light.css">'."\n";
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
$styles    = array('light','dark');

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
        $query = make_query(['test' => 'all']);
        echo '<li><a rel="nofollow" href="compare.php?'.SID.'&'.$query.'">Return to model comparison</a></li>';
        echo '<li>Test Set: '.$benchmark.'</li>';
        echo '<li>Language Pair: '.$langpair.'</li>';
        $query = make_query(['test' => $benchmark]);
        echo '<li><a rel="nofollow" href="compare-translations.php?'.SID.'&'.$query.'">Show translation without highlighting difference</a></li>';
        echo '</ul>';
        echo '<div style="float: left;">';
        show_page_links($start, $end, count($trans1));
        echo '</div><div style="float: right;">';
        print_diffstyle_options($diffstyle);
        print_style_options($style);
        echo '</div><br/><hr/>';
        

        $evalfile1 = tempnam(sys_get_temp_dir(),'opusmtevalentry');
        $evalfile2 = tempnam(sys_get_temp_dir(),'opusmtevalentry');

        if ($fp1 = fopen($evalfile1, 'w')){
            if ($fp2 = fopen($evalfile2, 'w')){
                $id = 0;
                while ($id < count($trans1)){
                    fwrite($fp1, '   SOURCE: '.$trans1[$id]."\n");
                    fwrite($fp1, 'REFERENCE: '.$trans1[$id+1]."\n");
                    fwrite($fp1, '  MODEL 1: '.$trans1[$id+2]."\n");
                    fwrite($fp1, '  MODEL 2: '.$trans2[$id+2]."\n");
                    fwrite($fp1, '     DIFF: '.$trans1[$id+2]."\n\n");
                    fwrite($fp2, '   SOURCE: '.$trans1[$id]."\n");
                    fwrite($fp2, 'REFERENCE: '.$trans1[$id+1]."\n");
                    fwrite($fp2, '  MODEL 1: '.$trans1[$id+2]."\n");
                    fwrite($fp2, '  MODEL 2: '.$trans2[$id+2]."\n");
                    fwrite($fp2, '     DIFF: '.$trans2[$id+2]."\n\n");
                    $id+=4;
                }
                fclose($fp2);
            }
            fclose($fp1);
        }

        /*
        if ($fp = fopen($evalfile1, 'w')){
            fwrite($fp, implode("\n",$trans1));
            fclose($fp);
        }

        if ($fp = fopen($evalfile2, 'w')){
            fwrite($fp, implode("\n",$trans2));
            fclose($fp);
        }
        */

        print_file_diff($evalfile1, $evalfile2, $diffstyle);

        unlink($evalfile1);
        unlink($evalfile2);

    }
}

?>
