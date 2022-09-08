<?php session_start(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
  <title>OPUS-MT - Example Translations</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"> 
  <link rel="stylesheet" href="index.css" type="text/css">
</head>
<body>

<?php     

include 'functions.php';

echo("<h1>OPUS-MT Example Translations</h1>");

// get query parameters
$package   = get_param('pkg', 'Tatoeba-MT-models');
$benchmark = get_param('test', 'all');
$model     = get_param('model', 'all');
$start     = get_param('start', 0);
$end       = get_param('end', 9);

list($srclang, $trglang, $langpair) = get_langpair();


if ($model != 'all'){
    if ($benchmark != 'all'){

        $trans = get_selected_translations($benchmark, $langpair, $model, $package, $start, $end);

        $query = make_query(array('model' => $model, 'test' => 'all'));
        echo '<ul><li>Model: <a rel="nofollow" href="index.php?'.$query.'">'.$model.'</a></li>';
        echo '<li>Test Set: '.$benchmark.'</li>';
        echo '<li>Language Pair: '.$langpair.'</li>';
        $query = make_query(['diff' => 'wdiff']);
        echo '<li><a rel="nofollow" href="diff-references.php?'.$query.'">Highlight difference between reference and model translation</a></li>';
        $query = make_query(['test' => 'all']);
        echo '<li><a rel="nofollow" href="index.php?'.$query.'">Return to score comparison</a></li>';
        echo '</ul>';
        // echo 'The following shows blocks of three lines with <ol><li>INPUT</li><li>REFERENCE TRANSLATION</li><li>SYSTEM OUTPUT</li></ol>';
        show_page_links($start, $end, count($trans));
        echo '<hr/>';

        echo '<pre>';
        $nr_examples = floor(count($trans)/4);
        for ($i=0; $i<$nr_examples; $i++){
            echo '   SOURCE: '.$trans[$i*4]."\n";
            echo 'REFERENCE: '.$trans[$i*4+1]."\n";
            echo '    MODEL: '.$trans[$i*4+2]."\n\n";
        }
        echo '</pre>';
    }
}

?>
