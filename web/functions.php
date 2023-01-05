<?php

if (isset($_GET['session'])){
    if ($_GET['session'] == 'clear'){
        clear_session();
    }
}



$scores_url  = 'https://raw.githubusercontent.com/Helsinki-NLP/OPUS-MT-leaderboard/master/scores';
$storage_url = 'https://object.pouta.csc.fi/';

// $evaluation_metrics = array('bleu', 'chrf');
// $evaluation_metrics = array('bleu', 'chrf', 'comet');
$evaluation_metrics = array('bleu', 'spbleu', 'chrf', 'chrf++', 'comet');



function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

function get_param($key, $default){

    // check the query string first and overwrite session variable
    if (isset($_GET[$key])){
        $_SESSION['params'][$key] = test_input($_GET[$key]);
        return $_SESSION['params'][$key];
    }
    
    if (array_key_exists('params', $_SESSION)){
        if (isset($_SESSION['params'][$key])){
            return $_SESSION['params'][$key];
        }
    }
    
    return $default;
}


function set_param($key, $value){
    $_SESSION['params'][$key] = $value;
}

function get_langpair(){
    if (isset($_GET['langpair'])){
        list($srclang,$trglang) = explode('-',$_GET['langpair']);
        $_SESSION['params']['src'] = $srclang;
        $_SESSION['params']['trg'] = $trglang;
    }
    else{
        $srclang   = get_param('src', 'deu');
        $trglang   = get_param('trg', 'eng');
    }
    $langpair  = implode('-',[$srclang,$trglang]);
    return [$srclang, $trglang, $langpair];
}

function set_langpair($langpair){
    list($srclang,$trglang) = explode('-',$langpair);
    $_SESSION['params']['src'] = $srclang;
    $_SESSION['params']['trg'] = $trglang;
    $_GET['src'] = $srclang;
    $_GET['trg'] = $trglang;
    $_GET['langpair'] = $langpair;
}

function make_query($data){
    if ( isset( $_COOKIE['PHPSESSID'] ) ) {
        return http_build_query($data);
    }
    else{
        if (array_key_exists('params', $_SESSION)){
            $params = $_SESSION['params'];
        }
        else{
            $params = array();
        }
        foreach ($data as $key => $value){
            $params[$key] = $value;
        }
        return http_build_query($params);
    }
}



function get_score_filename($langpair, $benchmark, $metric='bleu', $model='all', $pkg='Tatoeba-MT-models'){
    global $scores_url, $storage_url;
    $modelhome = $storage_url.$pkg;

    if ($model != 'all'){
        if ($metric != 'all'){
            $file = implode('/',[$modelhome,$model]).'.'.$metric.'-scores.txt';
        }
        else{
            $file = implode('/',[$modelhome,$model]).'.scores.txt';
        }
    }
    elseif ($benchmark == 'avg'){
        $file  = implode('/',[$scores_url,$langpair,'avg-'.$metric.'-scores.txt']);
    }
    elseif ($benchmark != 'all'){
        $file  = implode('/',[$scores_url,$langpair,$benchmark,$metric.'-scores.txt']);
    }
    else{
        $file  = implode('/',[$scores_url,$langpair,'top-'.$metric.'-scores.txt']);
    }

    return $file;
}

// read scores from session cache or from file

function read_scores($langpair, $benchmark, $metric='bleu', $model='all', $pkg='Tatoeba-MT-models', $cache_size=10){
    global $scores_url, $storage_url;
    $modelhome = $storage_url.$pkg;

    if ($model != 'all'){
        if ($metric != 'all'){
            $file = implode('/',[$modelhome,$model]).'.'.$metric.'-scores.txt';
        }
        else{
            $file = implode('/',[$modelhome,$model]).'.scores.txt';
        }
    }
    elseif ($benchmark == 'avg'){
        $file  = implode('/',[$scores_url,$langpair,'avg-'.$metric.'-scores.txt']);
    }
    elseif ($benchmark != 'all'){
        $file  = implode('/',[$scores_url,$langpair,$benchmark,$metric.'-scores.txt']);
    }
    else{
        $file  = implode('/',[$scores_url,$langpair,'top-'.$metric.'-scores.txt']);
    }

    // echo $file;

    if (! array_key_exists('cached-scores', $_SESSION)){
        $_SESSION['cached-scores'] = array();
        $_SESSION['next-cache-key'] = 0;
    }
    
    $key = array_search($file, $_SESSION['cached-scores']);
    if ($key !== false){
        if (array_key_exists('scores', $_SESSION)){
            if (array_key_exists($key, $_SESSION['scores'])){
                // echo "read scores from cached file with key $key";
                if (is_array($_SESSION['scores'][$key])){
                    return $_SESSION['scores'][$key];
                }
            }
        }
    }

    if ($_SESSION['next-cache-key'] >= $cache_size){
        $_SESSION['next-cache-key'] = 0;
    }

    $key = $_SESSION['next-cache-key'];
    // echo "save scores for $file in cache with key $key";
    $_SESSION['cached-scores'][$key] = $file;
    $_SESSION['scores'][$key] = @file($file);
    $_SESSION['next-cache-key']++;
    if (is_array($_SESSION['scores'][$key])){
        return $_SESSION['scores'][$key];
    }
    return array();
}


// fetch the file with all benchmark translations for a specific model

function get_translation_file($model, $pkg='Tatoeba-MT-models'){
    global $storage_url;
    $modelhome = $storage_url.$pkg;
    $file = implode('/',[$modelhome,$model]).'.eval.zip';

    $tmpfile = tempnam(sys_get_temp_dir(),'opusmteval');
    if (copy($file, $tmpfile)) {
        return $tmpfile;
    }
}


// fetch benchmark translation file and use a cache to keep a certain number
// of them without reloading them

function get_translation_file_with_cache($model, $pkg='Tatoeba-MT-models', $cache_size=10){
    global $storage_url;
    $modelhome = $storage_url.$pkg;
    $file = implode('/',[$modelhome,$model]).'.eval.zip';
    
    if (! array_key_exists('cached-files', $_SESSION)){
        $_SESSION['cached-files'] = array();
        $_SESSION['next-filecache-key'] = 0;
    }
    $key = array_search($file, $_SESSION['cached-files']);
    if ($key !== false){
        if (array_key_exists('files', $_SESSION)){
            if (array_key_exists($key, $_SESSION['files'])){
                if (file_exists($_SESSION['files'][$key])){
                    // echo "read translations from cache with key $key in file ".$_SESSION['files'][$key];
                    return $_SESSION['files'][$key];
                }
            }
        }
    }

    if ($_SESSION['next-filecache-key'] >= $cache_size){
        $_SESSION['next-filecache-key'] = 0;
    }

    $key = $_SESSION['next-filecache-key'];
    // echo "save scores for $file in cache with key $key";
    $_SESSION['cached-files'][$key] = $file;
    
    $tmpfile = tempnam(sys_get_temp_dir(),'opusmteval');
    if (copy($file, $tmpfile)) {
        $_SESSION['files'][$key] = $tmpfile;
        $_SESSION['next-filecache-key']++;
        return $_SESSION['files'][$key];
    }
}

function clear_session(){
    if (isset($_SESSION['files'])){
        foreach ($_SESSION['files'] as $key => $file){
            if (file_exists($file)) {
                // echo(".... $file ...");
                unlink($file);
            }
        }
    }
    $_SESSION = array();
}

function get_translations ($benchmark, $langpair, $model, $pkg='Tatoeba-MT-models'){
    
    $evalfile = implode('.',[$benchmark, $langpair, 'compare']);
    $tmpfile = get_translation_file_with_cache($model, $pkg);

    $zip = new ZipArchive;
    if ($zip->open($tmpfile) === TRUE) {
        $content = $zip->getFromName($evalfile);
        $zip->close();
    }
    
    if ( ! isset( $_COOKIE['PHPSESSID'] ) ) {
        unlink($tmpfile);
    }    
    return $content;
}


// read only a certain slice of examples
// (assumes that the data is 4 lines per example)

function get_selected_translations ($benchmark, $langpair, $model, $pkg='Tatoeba-MT-models', $start=0, $end=99){
    
    $evalfile = implode('.',[$benchmark, $langpair, 'compare']);
    $tmpfile = get_translation_file_with_cache($model, $pkg);

    $examples = array();
    $count = 0;
    
    $zip = new ZipArchive;
    if ($zip->open($tmpfile) === TRUE) {
        if ($fp = $zip->getStream($evalfile)){
            $buffer = '';
            while (!feof($fp)) {
                $contents = fread($fp, 8192);
                $lines = explode("\n",$buffer.$contents);
                $buffer = array_pop($lines);
                foreach ($lines as $line){
                    array_push($examples, $line);
                }
                $count = floor(count($examples)/4);
                if ($count >= $end){
                    break;
                }
            }
            array_push($examples, $buffer);
            fclose($fp);
        }
        $zip->close();
    }
    if ( ! isset( $_COOKIE['PHPSESSID'] ) ) {
        unlink($tmpfile);
    }
    return array_slice($examples, $start*4, ($end-$start+1)*4);
}

function show_page_links($start=0, $end=9, $nr_shown=10){

    $nr_examples = $end-$start+1;
    if ($start > 0){
        $newstart = $start-$nr_examples;
        if ($newstart < 0){
            $newstart = 0;
        }
        $newend = $newstart+$end-$start;
        $query = make_query(['start' => 0, 'end' => $nr_examples-1]);
        echo '[<a href="'.$_SERVER['PHP_SELF'].'?'.$query.'">start</a>] ';
        $query = make_query(['start' => $newstart, 'end' => $newend]);
        echo '[<a href="'.$_SERVER['PHP_SELF'].'?'.$query.'">show previous</a>] ';
    }
    echo 'show examples '.$start.' - '.$end;
    if ($nr_shown>$nr_examples){
        $newstart = $end+1;
        $newend = $end+$nr_examples;
        $query = make_query(['start' => $newstart, 'end' => $newend]);
        echo ' [<a href="'.$_SERVER['PHP_SELF'].'?'.$query.'">show next</a>]';
    }
}


function print_file_diff($file1, $file2, $diffstyle = 'wdiff'){
    
    // TODO: how safe is this?
    // TODO: ansi2html.sh should not be in this dir, should it?

    echo '<div class="f9 b9"><pre>';
    if ($diffstyle == 'gitdiff'){
        system("git diff --color-words --no-index  $file1 $file2 | tail -n +6 | sed 's/\@\@.*\@\@//' | ./ansi2html.sh --body-only");
    }
    elseif ($diffstyle == 'diff'){
        system("diff -u $file1 $file2 | colordiff | perl /usr/share/doc/git/contrib/diff-highlight/diff-highlight | tail -n +4 | grep -v '\@\@.*\@\@' | ./ansi2html.sh --body-only");
    }
    else{
        system("wdiff $file1 $file2 | colordiff | perl /usr/share/doc/git/contrib/diff-highlight/diff-highlight | ./ansi2html.sh --body-only");
    }
    echo '</pre></div>';
}

function print_metric_options($selected_metric='bleu'){
    global $evaluation_metrics;
    foreach ($evaluation_metrics as $m){
        if ($m == $selected_metric){
            echo(" $m ");
        }
        else{
            $query = make_query(array('metric' => $m));
            $link = $_SERVER['PHP_SELF'].'?'.$query;
            echo("[<a rel=\"nofollow\" href=\"$link\">$m</a>]");
        }
    }
}

function print_diffstyle_options($diffstyle='wdiff'){
    $diffstyles = array('diff','wdiff','gitdiff');    
    foreach ($diffstyles as $style){
        if ($style == $diffstyle){
            echo '['.$style.']';
        }
        else{
            $query = make_query(['diff' => $style]);
            echo '[<a rel="nofollow" href="'.$_SERVER['PHP_SELF'].'?'.$query.'">'.$style.'</a>]';
        }
    }
}

function print_style_options($style='light'){
    $styles    = array('light','dark');    
    foreach ($styles as $s){
        if ($s == $style){
            echo '['.$s.']';
        }
        else{
            $query = make_query(['style' => $s]);
            echo '[<a rel="nofollow" href="'.$_SERVER['PHP_SELF'].'?'.SID.'&'.$query.'">'.$s.'</a>]';
        }
    }
}

?>
