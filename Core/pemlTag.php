<?php

class pemlTag extends pemlCommand {
  protected $container = true;
  protected $inline = true;
  protected $filter = false;
  
  private $echo = false;
  private $tagName = "";
  private $argTrailing = "";
  
  function inline($arg) {
    $tag = $this->getOpeningTag($arg);
    if (empty($tag)) {
      if ($this->echo)
        $tag .= "<?php echo {$this->argTrailing}; ?>";
      else {
        $this->filter($arg);
        $tag = $arg;
      }
    } else {
      if (!empty($this->argTrailing)) {
        if ($this->echo)
          $tag .= "><?php echo {$this->argTrailing}; ?>" . $this->finish();
        else {
          $tag .= ">{$this->argTrailing}" . $this->finish();
        }
      } else {
        $tag .= " />";
      }
    }
    return $tag;
  }
  
  function start($arg) {
    return $this->getOpeningTag($arg) . ">{$this->argTrailing}";
  }
  
  function finish() {
    return "</{$this->tagName}>";
  }
  
  function getOpeningTag($string) {
    
    if (preg_match("/^(?<name>[a-z][a-z0-9]*+)(?<rest>.*)$/i", $string, $tag) > 0) {
      $this->tagName = $tag['name'];
      $implicitTag = false;
      $trimmed = $tag['rest'];
    } else {
      $this->tagName = "div";
      $implicitTag = true;
      $trimmed = $string;
    }
    
    // Start building attributes.
    $attributes = array();
    
    // Check for classes.
    if (preg_match("/^(?<classes>(?:\.-?[_a-zA-Z]+[_a-zA-Z0-9-]*+)*+)(?<rest>.*)$/", $trimmed, $classes) > 0 && strlen($classes['classes']) > 0) {
      $classnames = implode(" ", array_slice(explode(".", $classes['classes']), 1));
      $attributes['class'] = $classnames;
      $trimmed = $classes['rest'];
      $hasClass = true;
    } else {
      $hasClass = false;
    }
    
    // Check for id.
    if (preg_match("/^#(?<id>[a-z][a-z0-9-_:\.]*+)(?<rest>.*)$/", $trimmed, $id) > 0) {
      $attributes['id'] = $id['id'];
      $trimmed = $id['rest'];
      $hasId = true;
    } else {
      $hasId = false;
    }
    
    // Check for attributes.
    if (($attrs=$this->parseAttributes($trimmed)) !== false) {
      $attributes = array_merge($attributes, $attrs);
    }
      
    if (substr($trimmed, 0, 1) == '=') {
      $trimmed = substr($trimmed, 1);
      $this->echo = true;
    } else {
      $this->echo = false;
    }
    
    $this->argTrailing = trim($trimmed);
    $this->filter($this->argTrailing);
    
    if (pemlConfig::$settings['strict'] && !in_array($this->tagName, $this->validTags)) {
      $this->tagName = "";
      $this->echo = false;
      return "";
    }
    
    if (!$implicitTag || !empty($attributes)) {
      $tagText = "<" . $this->tagName;
      if (count($attributes) > 0) {
        foreach($attributes as $key => $value)
          $tagText .= " $key=\"$value\"";
      }
    } else {
      $tagText = "";
    }
    return $tagText;
  }
  
  private $validTags = array("a", "abbr", "acronym", "address", "applet", "area", "article", "aside", "audio", "b", "base", "basefont", "bdo", "big", "blockquote", "body", "br", "button", "canvas", "caption", "center", "cite", "code", "col", "colgroup", "command", "datalist", "dd", "del", "details", "dialog", "dfn", "dir", "div", "dl", "dt", "em", "embed", "fieldset", "figure", "font", "footer", "form", "frame", "frameset", "h1", "h2", "h3", "h4", "h5", "h6", "head", "header", "hgroup", "hr", "html", "i", "iframe", "img", "input", "ins", "keygen", "kbd", "label", "legend", "li", "link", "map", "mark", "menu", "meta", "meter", "nav", "noframes", "noscript", "object", "ol", "optgroup", "option", "output", "p", "param", "pre", "progress", "q", "rp", "rt", "ruby", "s", "samp", "script", "section", "select", "small", "source", "span", "strike", "strong", "style", "sub", "sup", "table", "tbody", "td", "textarea", "tfoot", "th", "thead", "time", "title", "tr", "tt", "u", "ul", "var", "video", "xmp");
}
  
?>