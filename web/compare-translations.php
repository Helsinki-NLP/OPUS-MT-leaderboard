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
$benchmark = get_param('test', 'all');
$metric    = get_param('metric', 'bleu');
$showlang  = get_param('scoreslang', 'all');
$model1    = get_param('model1', 'all');
$model2    = get_param('model2', 'all');

$start     = get_param('start', 0);
$end       = get_param('end', 9);

list($srclang, $trglang, $langpair) = get_langpair();



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
        echo '<ul><li>Model 1: <a rel="nofollow" href="index.php?'.$query.'">'.$model1.'</a></li>';
        $query = make_query(array('model' => $model2, 'pkg' => $pkg2, 'test' => 'all'));
        echo '<li>Model 2: <a rel="nofollow" href="index.php?'.$query.'">'.$model2.'</a></li>';
        echo '<li>Test Set: '.$benchmark.'</li>';
        echo '<li>Language Pair: '.$langpair.'</li>';
        $query = make_query(['diff' => 'wdiff']);
        echo '<li><a rel="nofollow" href="diff-translations.php?'.$query.'">Highlight model translation difference</a></li>';
        $query = make_query(['test' => 'all']);
        echo '<li><a rel="nofollow" href="compare.php?'.$query.'">Return to score comparison</a></li>';
        echo '</ul>';
        show_page_links($start, $end, count($trans1));
        echo '<hr/>';


        
        echo '<pre>';
        $nr_examples = floor(count($trans1)/4);
        for ($i=0; $i<$nr_examples; $i++){
            echo '   SOURCE: '.$trans1[$i*4]."\n";
            echo 'REFERENCE: '.$trans1[$i*4+1]."\n";
            echo '  MODEL 1: '.$trans1[$i*4+2]."\n";
            echo '  MODEL 2: '.$trans2[$i*4+2]."\n\n";            
        }
        echo '</pre>';

    }
}

?>
