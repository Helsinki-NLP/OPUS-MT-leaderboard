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
$metric    = get_param('metric', 'bleu');
$showlang  = get_param('scoreslang', 'all');
$model     = get_param('model', 'all');

list($srclang, $trglang, $langpair) = get_langpair();


if ($model != 'all'){
    if ($benchmark != 'all'){

        $query = make_query(array('model' => $model, 'test' => 'all'));
        echo '<ul><li>Model: <a rel="nofollow" href="index.php?'.$query.'">'.$model.'</a></li>';
        echo '<li>Test Set: '.$benchmark.'</li>';
        echo '<li>Language Pair: '.$langpair.'</li></ul>';
        echo 'The following shows blocks of three lines with <ol><li>INPUT</li><li>REFERENCE TRANSLATION</li><li>SYSTEM OUTPUT</li></ol><hr/>';

        $tmpfile = get_translation_file_with_cache($model, $package);
        $zip = new ZipArchive;
        if ($zip->open($tmpfile) === TRUE) {
            $evalfile = implode('.',[$benchmark, $langpair, 'compare']);
            echo '<pre>';
            echo $zip->getFromName($evalfile);
            echo '</pre>';
            $zip->close();
        } else {
            echo 'failed';
        }

        // TODO: when do we remove tempfiles if we use session cache?
        if ( ! isset( $_COOKIE['PHPSESSID'] ) ) {
            unlink($tmpfile);
        }
    }
}

?>
