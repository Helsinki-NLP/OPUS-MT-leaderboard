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


// echo('<div class="diffheader">');
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

        $query = make_query(['test' => 'all']);
        echo '<ul><li>Model: <a rel="nofollow" href="index.php?'.$query.'">'.$model.'</a> (model translations in green)</li>';
        echo '<li>Test Set: '.$benchmark.' (reference translations in red)</li>';
        echo '<li>Language Pair: '.$langpair.'</li>';
        $query = make_query(['test' => $benchmark]);
        echo '<li><a rel="nofollow" href="translations.php?'.SID.'&'.$query.'">Show translation without highlighting difference</a></li>';
        echo '</ul>';
        echo '<div style="float: left;">';
        show_page_links($start, $end, count($trans));
        echo '</div><div style="float: right;">';
        print_diffstyle_options($diffstyle);
        print_style_options($style);
        echo '</div><br/><hr/>';

        
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
