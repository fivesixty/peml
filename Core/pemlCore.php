<?php

class pemlCore {
  
  private $output;
  private $errors;
  private $log;
  private $current_command;
  
  static private $command_shortcuts = array();
  static private $command_hooks = array();
  static private $commands = array("tag" => "pemlTag");
  static private $filters = array();
  
  public function __construct() {
    pemlConfig::loadLibraries();
  }
  
  public function processHooks($command, $block) {
    if (!isset(pemlCore::$command_hooks[$command]))
      return false;
    else {
      $subcommand = $this->readCommand($block->current(), $argument);
      if (isset(pemlCore::$command_hooks[$command][$subcommand])) {
        $subblock = $this->blockLines($block);
        $this->renderBlock(pemlCore::$command_hooks[$command][$subcommand], $argument, $subblock, $block, false);
        return true;
      } else {
        return false;
      }
    }
  }
  
  static public function registerHook($command, $subcommand, $mapping) {
    if (!isset(pemlCore::$command_hooks[$command]))
      pemlCore::$command_hooks[$command] = array();
    if (!isset(pemlCore::$command_hooks[$command][$subcommand]))
      pemlCore::$command_hooks[$command][$subcommand] = $mapping;
    else
      throw new Exception("Hook conflict: $command.$subcommand\n");
  }
  
  static public function registerShortcut($shortcut, $command, $override=false) {
    if (!isset(pemlCore::$command_shortcuts[$shortcut]) || $override)
      pemlCore::$command_shortcuts[$shortcut] = $command;
    else
      throw new Exception("Overriding of shortcut '$shortcut' for :$command is not allowed.\n");
  }
  
  static public function registerCommand($command, $class) {
    if (!isset(pemlCore::$commands[$command]))
      pemlCore::$commands[$command] = $class;
    else
      throw new Exception("Command name conflict: $command\n");
  }
  
  static public function registerFilter($command, $callback) {
    if (!isset(pemlCore::$filters[$command]))
      pemlCore::$filters[$command] = array();
    pemlCore::$filters[$command][] = $callback;
  }
  
  // 
  public function parse($filecontents) {
    $this->output = array();
    $this->errors = array();
    $this->log = array();
    
    $file = new pemlBlock($filecontents);
    $this->parseBlock($file);
    
    if (pemlConfig::$settings['strict'] && count($this->errors) > 0) {
      $this->showErrors($file);
      //throw new Exception("Uncaught errors in peml parse [strict mode].");
    }
    
    return implode("\n", $this->output);
  }
  
  private function showErrors($file) {
    echo "<pre><code>";
    foreach ($this->errors as $linenum => $error)
      echo str_pad($linenum, 3, " ", STR_PAD_LEFT) . ":  $error\n";
    
    echo "\n\n\n";
    foreach($file as $linenum => $line) {
      $errline = htmlentities(str_pad($linenum, 3, " ", STR_PAD_LEFT) . ":  " . $line . "\n");
      if (isset($this->errors[$linenum]))
        echo "<span style=\"color: red;\">$errline</span>";
      else
        echo $errline;
    }
      
    echo "\n\n\n";
    foreach($this->output as $linenum => $line)
      echo htmlentities(str_pad($linenum+1, 3, " ", STR_PAD_LEFT) . ":  " . $line . "\n");
    echo "</code></pre>";
  }
  
  public function errorLog() {
    $return = "";
    foreach ($this->errors as $key => $line) {
      $return .= str_pad($key, 3, " ", STR_PAD_RIGHT) . ":  " . htmlentities($line) . "\n";
    }
    return $return;
  }
  
  public function pushOutput($content, $indent) {
    $this->output[] = $this->indent($indent) . $content;
  }
  
  public function runFilters(&$content) {
    if (isset(pemlCore::$filters[$this->current_command]))
      foreach (pemlCore::$filters[$this->current_command] as $filter) {
        $content = $filter($content);
      }
  }
  
  protected function logError($message, $line) {
    $this->errors[$line] = $message;
  }
  
  public function assertDeeper($block, $pblock) {
    if ($block->current() == null)
      $this->logError("Expected this indent to be deeper. ". $pblock->current()->text, $pblock->key()+2);
  }
  
  public function assertLevel($block, $pblock) {
    if ($block->current() != null) {
      $this->logError("Expected this indent not to be deeper.", $block->key()+1);
    }
  }
  
  protected function indent($num) {
    return str_repeat(pemlConfig::$settings['indentation'], $num);
  }
  
  protected function renderBlock($command, $linetext, $block, $parentblock, $traverse=true) {
    if ($block->current() != null)
      $indent = $block->current()->indent-1;
    else
      $indent = $parentblock->current()->indent;
    
    if (isset(pemlCore::$commands[$command])) {
      // track our current command
      $this->current_command=$command;
      new pemlCore::$commands[$command]($command, $linetext, $block, $parentblock, $indent, $this);
      // Restore the current_command.
      $this->current_command=$command;
    } else {
      $this->pushOutput(":" . $command, $indent);
    }
    if ($traverse) {
      $parentblock->next();
      if ($parentblock->current() != null)
        $this->parseBlock($parentblock);
    }
  }
  
  public function parseBlock($parentblock) {
    // Grab the line.
    $line = $parentblock->current();
    
    // Read the command.
    $command = $this->readCommand($line, $command_text);
    
    // Fetch the block.
    $block = $this->blockLines($parentblock);
    
    // Render
    $this->renderBlock($command, $command_text, $block, $parentblock);
  }
  
  public function readCommand($line, &$rest) {
    $trimmed = trim($line);
    
    // Get the first 2 characters to test with.
    $shortcut = trim(substr($trimmed, 0, 2));
    // Get the rest of the string
    $shortcut_text = trim(substr($trimmed, 2));
    
    if (isset(pemlCore::$command_shortcuts[$shortcut])) {  // Is a command shortcut
      $rest = $shortcut_text;
      return pemlCore::$command_shortcuts[$shortcut];
    } elseif (substr($trimmed, 0, 1) == ':') {  // Is a command.
      if (preg_match("/^(?<command>[a-z]++)(?<rest>.*)$/i", substr($trimmed, 1), $match) > 0) {
        $rest = trim($match['rest']);
        return $match['command'];
      } else {
        $this->logError("Invalid command format.");
        return false;
      }
    } else {  // Is a tag
      $rest = $trimmed;
      return "tag";
    }
    
  }
  
  // Check a string for attributes. Returns an associative array, or false.
  // Input string is modified to the remainder of the string after matching.
  public function parseAttributes(&$string) {
    $trimmed = trim($string);
    $attributes = array();
    
    if (substr($trimmed, 0, 1) != '(')
      return false;
    
    $workingString = $trimmed;
    $this->log[] = "Parsing attributes.";
    while(strlen($workingString) > 0) {
      
      // Disallowing colons in attribute names.
      // Match attribute name
      if (preg_match('/^\s*+(?<name>[a-zA-Z_][-a-zA-Z0-9_.]*+)\s*+(?<rest>.*)$/', substr($workingString, 1), $attr) == 0) {
        return false;
      }
      
      $attrName = $attr['name'];
      $workingString = $attr['rest'];
      
      $this->log[] = "Found attribute $attrName, working: '$workingString'";
      
      // Find what kind of assignment we're doing.
      if (preg_match('/^(?<assign>:=?+)\s*+(?<rest>.*)$/', $workingString, $assign) == 0) {
        return false;
      }
      
      $phpAssign = ($assign['assign'] == ":=") ? true : false;
      $workingString = $assign['rest'];
      
      $this->log[] = "Found assign $workingString";
      
      $offset = 0;
      $inquotes = false;
      $openBrackets = 1;
      $prevChar = ' ';
      $foundMore = false;
      $foundEnd = false;
      // Looking for a ` or an ending bracket.
      for (;$offset <= strlen($workingString) && $openBrackets > 0;$offset++) {
        $char = substr($workingString, $offset, 1);
        
        if (!$inquotes && $char == '(')
          $openBrackets++;
        elseif (!$inquotes && $char == ')') {
          $openBrackets--;
          if ($openBrackets == 0) {
            $foundEnd = true;
            break;
          }
        } elseif ($prevChar != '\\' && $char == '"')
          $inquotes = !$inquotes;
        
        if ($openBrackets == 1)
          if (!$inquotes && $char == '`') {
            $foundMore = true;
            break;
          }
        $prevChar = $char;
      }
      
      $attrValue = trim(substr($workingString, 0, $offset));
      // skip the ) or `
      $workingString = substr($workingString, $offset+1);
      
      $this->log[] = "Found value $openBrackets";
      
      if (($foundEnd || $foundMore) && !empty($attrValue)) {
        if ($phpAssign)
          $attrValue = "<?php echo $attrValue; ?>";
        $attributes[$attrName] = $attrValue;
        if ($foundEnd)
          break;
      } else {
        return false;
      }
    }
    $string = $workingString;
    return $attributes;
  }
  
  // Collect all lines until the indent falls to this same level.
  protected function blockLines($block) {
    $lines = array();
    if ($block->current() == null)
      return;
    $block_indent = $block->current()->indent;
    while ($block->current()->nextIndent > $block_indent) {
      $block->next();
      $lines[] = $block->current();
    }
    $blocklines = new pemlBlock($lines, $block_indent);
    return $blocklines;
  }
  
}


?>