<?php

class pemlConfig extends _pemlConfig {
  
  protected static $libraries = "../Libraries";
  
  public static $settings = array(
    "strict" => true,
    "implicit_tags" => true,
    "indentation" => "  ",
    "cacheDir" => "Cache",
    "showSource" => true,
    "alwaysParse" => true
  );
  
}

?>