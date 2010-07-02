<?php

abstract class _pemlConfig {
  
  protected static $libraries = "../Libraries";
  private static $loaded = false;
  
  public static function loadLibraries() {
    if (_pemlConfig::$loaded)
      return;
    _pemlConfig::$loaded = true;
    pemlConfig::$libraries = dirname(__FILE__) . DIRECTORY_SEPARATOR . pemlConfig::$libraries . DIRECTORY_SEPARATOR;
    if (is_dir(pemlConfig::$libraries)) {
      if ($dh = opendir(pemlConfig::$libraries)) {
        while (($file = readdir($dh)) !== false) {
          if (preg_match("/^(?<name>.*)\.php$/", $file, $lib) > 0)
            pemlConfig::loadLibrary($lib['name']);
        }
        closedir($dh);
        return;
      }
    }
    die("Invalid libraries directory." . pemlConfig::$libraries);
  }
  
  // Include a library, and autoregister its commands.
  private static function loadLibrary($name) {
    
    // Include library configuration if available.
    if (is_file("$name.php")) {
      include_once("$name.php");
    }
    
    // Find which classes have been declared.
    $pre_classes = get_declared_classes();
    $pre_functions = get_defined_functions();
    require_once(pemlConfig::$libraries . "$name.php");
    $classes = array_diff(get_declared_classes(), $pre_classes);
    $post_functions = get_defined_functions();
    $functions = array_diff($post_functions['user'], $pre_functions['user']);
    
    // Go through added classes. [Commands]
    foreach ($classes as $class) {
      // Make sure the class is prefixed with the library name.
      if (substr($class, 0, strlen($name)) == $name) {
        
        $command = substr($class, strlen($name));
        // Chop off leading _ if it's being used to seperate library & command.
        if (substr($command, 0, 1) == "_")
          $command = substr($command, 1);
          
        // Register the command.
        pemlCore::registerCommand($command, $class);
      }
    }
    
    // Go through added functions
    foreach($functions as $function) {
      // Make sure the function is prefixed with the library name.
      if (substr($function, 0, strlen($name)) == strtolower($name)) {
        
        $filter = substr($function, strlen($name));
        // Chop off leading _ if it's being used to seperate library & filter.
        if (substr($filter, 0, 1) == "_")
          $filter = substr($filter, 1);
          
        // Register the filter.
        // so SCL_tag_linkify -> registerFilter("tag", "SCL_tag_linkify")
        pemlCore::registerFilter(substr($filter, 0, strpos($filter, "_")), $function);
      }
    }
  }
  
}

?>