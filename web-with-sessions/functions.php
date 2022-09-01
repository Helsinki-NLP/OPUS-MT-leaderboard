<?php

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

function make_query($data){
    if ( isset( $_COOKIE['PHPSESSID'] ) ) {
        return http_build_query($data);
    }
    else{
        $params = $_SESSION['params'];
        foreach ($data as $key => $value){
            $params[$key] = $value;
        }
        return http_build_query($params);
    }
}



// read scores from session cache or from file

function read_scores($langpair, $benchmark, $metric, $model='all', $pkg='Tatoeba-MT-models', $cache_size=10){
    
    $leaderboard_url = 'https://raw.githubusercontent.com/Helsinki-NLP/OPUS-MT-leaderboard/master/scores';
    $modelhome = 'https://object.pouta.csc.fi/'.$pkg;

    if ($model != 'all'){
        $file = implode('/',[$modelhome,$model]).'.scores.txt';
    }
    elseif ($benchmark != 'all'){
        $file  = implode('/',[$leaderboard_url,$langpair,$benchmark,$metric.'-scores.txt']);
    }
    else{
        $file  = implode('/',[$leaderboard_url,$langpair,'top-'.$metric.'-scores.txt']);
    }

    if (! array_key_exists('cached-scores', $_SESSION)){
        $_SESSION['cached-scores'] = array();
        $_SESSION['next-cache-key'] = 0;
    }
    
    $key = array_search($file, $_SESSION['cached-scores']);
    if ($key !== false){
        if (array_key_exists('scores', $_SESSION)){
            if (array_key_exists($key, $_SESSION['scores'])){
                // echo "read scores from cache with key $key";
                return $_SESSION['scores'][$key];
            }
        }
    }

    if ($_SESSION['next-cache-key'] >= $cache_size){
        $_SESSION['next-cache-key'] = 0;
    }

    $key = $_SESSION['next-cache-key'];
    // echo "save scores for $file in cache with key $key";
    $_SESSION['cached-scores'][$key] = $file;
    $_SESSION['scores'][$key] = file($file);
    $_SESSION['next-cache-key']++;
    return $_SESSION['scores'][$key];
    
}



?>
