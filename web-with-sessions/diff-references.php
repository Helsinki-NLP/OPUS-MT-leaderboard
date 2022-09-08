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
<body>
<?php     


echo("<h1>OPUS-MT Example Translations</h1>");

// get query parameters
$package   = get_param('pkg', 'Tatoeba-MT-models');
$benchmark = get_param('test', 'all');
$model     = get_param('model', 'all');
$diffstyle = get_param('diff', 'wdiff'); // can be diff, wdiff or gitdiff
$start     = get_param('start', 0);
$end       = get_param('end', 9);

$diffstyles = array('diff','wdiff','gitdiff');


list($srclang, $trglang, $langpair) = get_langpair();

if ($model != 'all'){
    if ($benchmark != 'all'){

        $trans = get_selected_translations($benchmark, $langpair, $model, $package, $start, $end);

        $query = make_query(array('model' => $model, 'pkg' => $package, 'test' => 'all'));
        echo '<ul><li>Model: <a rel="nofollow" href="index.php?'.$query.'">'.$model.'</a></li>';
        echo '<li>Test Set: '.$benchmark.'</li>';
        echo '<li>Language Pair: '.$langpair.'</li>';
        echo '<li>Diff Style: ';
        print_diffstyle_options($diffstyle);
        echo ' (Model 1 = red, Model 2 = green)</li>';
        echo '<li>Background: ';
        print_diffbg_options($diffbg);
        echo '</li>';
        $query = make_query(['test' => $benchmark]);
        echo '<li><a rel="nofollow" href="translations.php?'.$query.'">Show translation without highlighting difference</a></li>';
        $query = make_query(['test' => 'all']);
        echo '<li><a rel="nofollow" href="index.php?'.$query.'">Return to score comparison</a></li>';
        echo '</li>';
        echo '</ul>';
        // echo 'The following shows the <b>input sentence</b> and the differences between <b>reference translations</b> (in red) and <b>system translations</b> (green)<br/><br/>';
        show_page_links($start, $end, count($trans));
        echo '<hr/>';
        
        $reffile = tempnam(sys_get_temp_dir(),'opusmtevalentry');
        $sysfile = tempnam(sys_get_temp_dir(),'opusmtevalentry');

        if ($fp1 = fopen($reffile, 'w')){
            if ($fp2 = fopen($sysfile, 'w')){
                $id = 0;
                while ($id < count($trans)){
                    fwrite($fp1, '   SOURCE: '.$trans[$id]."\n");
                    fwrite($fp1, 'REFERENCE: '.$trans[$id+1]."\n");
                    fwrite($fp1, '    MODEL: '.$trans[$id+2]."\n");
                    fwrite($fp1, '     DIFF: '.$trans[$id+1]."\n\n");
                    fwrite($fp2, '   SOURCE: '.$trans[$id]."\n");
                    fwrite($fp2, 'REFERENCE: '.$trans[$id+1]."\n");
                    fwrite($fp2, '    MODEL: '.$trans[$id+2]."\n");
                    fwrite($fp2, '     DIFF: '.$trans[$id+2]."\n\n");
                    $id+=4;
                }
                fclose($fp2);
            }
            fclose($fp1);
        }

        print_file_diff($reffile, $sysfile, $diffstyle);

        unlink($reffile);
        unlink($sysfile);

    }
}

?>
