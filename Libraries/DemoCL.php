<?php

pemlCore::registerShortcut("#", "include");

class DemoCL_highlight extends pemlCommand {
  protected $inline = true;
  
  function inline($arg) {
    return '<span>' . highlight_string($arg, true) . '</span>';
  }
}

pemlCore::registerHook("php", "highlight", "phphighlight");
class DemoCL_phphighlight extends pemlCommand {
  protected $inline = true;
  function inline($arg) {
    return 'echo \'' . str_replace("'", "\\'", (highlight_string($arg, true))) . '\';';
  }
}

class DemoCL_prepend extends pemlCommand {
  protected $block = true;
  private $prepend_text;
  
  function start($arg) {
    $this->prepend_text = $arg;
  }
  
  function line($line) {
    return "{$this->prepend_text} $line";
  }
  
  function finish() {}
}

?>