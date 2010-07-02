<?php

abstract class pemlCommand {
  
  private $blockChild;
  private $blockParent;
  private $peml;
  
  // Starting indent of the block.
  protected $startIndent;
  
  // Used to override indentation.
  protected $setIndent = false;
  
  // Whether to automatically filter content.
  protected $filter = true;
  
  // Select which we're going to process.
  protected $inline = false;
  protected $block = false;
  // Means our content block is a generic block.
  protected $container = false;
  
  public final function __construct($command, $argument, $childblock, $parentblock, $indent, $peml) {
    $this->startIndent = $indent;
    $this->blockChild = $childblock;
    $this->blockParent = $parentblock;
    $this->peml = $peml;
    
    $hasBlock = $this->hasBlock();
    
    if ($this->filter)
      $this->filter($argument);
    
    // Init
    if ($this->inline && !$hasBlock) {
      $this->addContent($this->inline($argument), $indent);
      return;
    }
    
    if (!$this->block && !$this->container) {
      $peml->assertLevel($this->blockChild, $this->blockParent);
      return;
    }
    
    $peml->assertDeeper($this->blockChild, $this->blockParent);
    
    if (($this->block || $this->container) && $hasBlock) {
      
      // Start
      $this->addContent($this->start($argument), $indent);
      
      // If we're a tag block, parse the contents with the normal parser.
      if ($this->container)
        $peml->parseBlock($this->blockChild);
      else {
        // Process lines.
        foreach ($this->blockChild as $line) {
          if (!$this->peml->processHooks($command, $this->blockChild)) {
            if ($this->filter)
              $this->filter($line);
            $this->addContent($this->line($line), $line->indent);
          }
        }
      }
      // Finish
      $this->addContent($this->finish(), $indent);
    }
  }
  
  // General processing methods.
  // inline($arg) - called when no block is associated.
  // start($arg) - called with argument of command line for containers/blocks.
  // line($line) - called for each line inside a block
  // finish() - called after container/block finished.
  
  
  // API Methods
  
  protected final function filter(&$string) {
    $this->peml->runFilters($string);
  }
  
  // Parse a set of attributes into an array.
  protected final function parseAttributes(&$string) {
    return $this->peml->parseAttributes($string);
  }
  
  // See whether this has a child block.
  private final function hasBlock() {
    return $this->blockChild->current() != null;
  }
  
  // Allow a peek outside of the block.
  protected final function peekCommand() {
    if ($this->blockParent->valid() && $this->blockParent->peekNext() != null)
      return $this->peml->readCommand($this->blockParent->peekNext(), $arg);
  }
  
  // Internal Methods
  
  // Add content to the output.
  private final function addContent($line, $indent) {
    if ($line == null)
      return;
    
    if ($this->setIndent !== false)
      $this->peml->pushOutput($line, $this->setIndent);
    else
      $this->peml->pushOutput($line, $indent);
  }
  
}


?>