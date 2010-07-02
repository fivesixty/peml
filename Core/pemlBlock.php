<?php

class pemlLine {
  
  public $indent, $text, $_prev, $_next, $empty, $_offset;
  public $linenum = 0;
  
  private function calcIndent() {
    $inc = strlen(pemlConfig::$settings['indentation']);
    for ($count = 0;
         substr($this->text, $inc*$count, $inc) == pemlConfig::$settings['indentation'];
         $count++);
	  $this->indent = $count;
  }
  
  // Returns the next non-blank line.
  private function next() {
    $possnext = $this->_next;
    while ($possnext != null && $possnext->empty)
      $possnext = $possnext->_next;
    return $possnext;
  }
  
  // Returns the previous non-blank link.
  private function prev() {
    $possprev = $this->_prev;
    while ($possprev != null && $possprev->empty)
      $possprev = $possprev->_prev;
    return $possprev;
  }
  
  // Returns the indent of the previous non-blank line.
  private function prevIndent() {
    if ($this->prev == null)
      return $this->_offset;
    return $this->prev->indent;
  }
  
  // Returns the indent of the next non-blank line.
  private function nextIndent() {
    if ($this->next == null)
      return $this->_offset;
    return $this->next->indent;
  }
  
  // Constructs a ParseLine 
  public function __construct($line, $indent_offset = 0) {
    $this->text = $line;
    $this->empty = $this->isempty($line);
    $this->calcIndent();
    $this->_next = null;
    $this->_offset = $indent_offset;
  }
  
  public function __get($attr) {
    return call_user_func(array($this, $attr));
  }
  
  public function __toString() {
    return (string) $this->text;
  }
  
  private function isempty($text) {
    $b = trim($text);
    return empty($b);
  }
  
}

class pemlBlock implements Iterator {
 
  private $_firstNode;
  private $_currentNode;
  private $_count;
  private $_key;
  
  public function pemlBlock($contents, $indent_offset=0) {
    $this->_firstNode = NULL;
    $this->_currentNode = NULL;
    $this->_count = 0;
    $this->_key = 0;
    
    if (!is_array($contents))
      $lines = preg_split('/\r?\n/', $contents);
    else
      $lines = $contents;
    foreach($lines as $line) {
      $cleanline = str_replace("	", pemlConfig::$settings['indentation'], $line);
      
      $nl = new pemlLine((string) $line, pemlConfig::$settings['indentation'], $indent_offset);
      
      if ($this->_currentNode == null) {
        if (is_object($line)) {
          $nl->linenum = $line->linenum;
        } else {
          $nl->linenum = 1;
        }
          $this->_currentNode = $this->_firstNode = $nl;
      } else {
        $this->_currentNode->_next = $nl;
        $nl->_prev = $this->_currentNode;
        $nl->linenum = $this->_currentNode->linenum+1;
        $this->_currentNode=$nl;
      }
      
    }
    $this->rewind();
  }
  
  protected function lastIndentDelta() {
    return ($this->current()->indent - $this->peekPrev()->indent);
  }
  
  protected function nextIndentDelta() {
    return ($this->peekNext()->indent - $this->current()->indent);
  }
  
  public function first() {
    return $this->_firstNode;
  }
  
  public function peekNext() {
    return $this->current()->next;
  }
  
  public function peekPrev() {
    return $this->current()->prev;
  }
  
  public function current() {
    return $this->_currentNode;
  }
  
  public function next() {
    if ($this->_currentNode != null)
      $this->_currentNode = $this->_currentNode->next;
  }
  
  public function prev() {
    $this->_currentNode = $this->_currentNode->prev;
  }
  
  public function valid() {
    return $this->_currentNode != null;
  }
  
  public function rewind() {
    $this->_currentNode = $this->_firstNode;
  }
  
  public function key() {
    return $this->current()->linenum;
  }
}
 
?>