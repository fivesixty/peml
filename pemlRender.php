<?php

require_once("peml.php");

if ($filepath != null) {
  if (pemlConfig::$settings['showSource'] && isset($_GET['source'])) {
    echo "<pre>";
    echo htmlentities(file_get_contents($filepath));
    echo "</pre>";
  } else {
    $fileid = md5($filepath);
    $dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . pemlConfig::$settings['cacheDir'];
    if (!is_dir($dir))
      mkdir($dir);
    $storepath = $dir . DIRECTORY_SEPARATOR . $fileid . ".peml";
    
    if (pemlConfig::$settings['alwaysParse'] || !is_file($storepath) || filemtime($filepath) > filemtime($storepath)) {
      
      // Grab the file into an array and cut off the first line.
      $peml_data = array_slice(preg_split("/\r?\n/", file_get_contents($filepath)), 1);
      
      $peml = new pemlCore;
      $content = $peml->parse($peml_data);
      
      file_put_contents($storepath, $content);
    }
    require_once($storepath);
  }
  die();
}