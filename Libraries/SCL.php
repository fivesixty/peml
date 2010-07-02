<?php

// Standard PHP wrapper.
class SCL_php extends pemlCommand {
  protected $block = true;
  
  function start($arg)  { return "<?php"; }
  function line($line) { return trim($line);   }
  function finish()    { return "?>";    }
}

// Filters

function SCL_tag_namedLink($content) {
  if (preg_match_all("/\[(.+?)\]\((.+?)\)/i", $content, $link, PREG_SET_ORDER) > 0) {
    foreach ($link as $url) {
      if (preg_match("/^https?:\/\/.*/i", $url[1]))
        $content = str_replace($url[0], "<a href=\"{$url[1]}\">{$url[2]}</a>", $content);
      else
        $content = str_replace($url[0], "<a href=\"http://{$url[1]}\">{$url[2]}</a>", $content);
    }
  }
  return $content;
}

function SCL_tag_unnamedLink($content) {
  return preg_replace("/\[(.+?)\]\((.+?)\)/i", "<a href=\"$1\">$2</a>", $content);
}

function SCL_tag_phpinline($content) {
  return preg_replace("/\{=(.+?)=\}/", "<?php echo $1; ?>", $content);
}

// Flow control.

// Consolidate tests.
abstract class _SCL_ifelse extends pemlCommand {
  protected $container = true;
  
  function finish() {
    $nextCommand = $this->peekCommand();
    if ($nextCommand != "elseif" && $nextCommand != "else") {
      return "<?php endif; ?>";
    }
  }
}

// :if
class SCL_if extends _SCL_ifelse {
  function start($arg) { return "<?php if($arg): ?>"; }
}

// :elseif
class SCL_elseif extends _SCL_ifelse {
  function start($arg) { return "<?php elseif($arg): ?>"; }
}

// :else
class SCL_else extends pemlCommand {
  protected $container = true;
  
  function start($arg) { return "<?php else: ?>";  }
  function finish()   { return "<?php endif; ?>"; }
}

// :foreach
class SCL_foreach extends pemlCommand {
  protected $container = true;
  
  function start($arg)   { return "<?php foreach($arg): ?>"; }
  function finish()     { return "<?php endforeach; ?>";     }
}

// :while
class SCL_while extends pemlCommand {
  protected $container = true;
  
  function start($arg)   { return "<?php while($arg): ?>"; }
  function finish()     { return "<?php endwhile; ?>";     }
}

// :for -- todo, make this smarter.
class SCL_for extends pemlCommand {
  protected $container = true;
  
  function start($arg)   { return "<?php for($arg): ?>"; }
  function finish()     { return "<?php endfor; ?>";     }
}


// HTML Helpers

// Clearing div.
class SCL_clear extends pemlCommand {
  protected $inline = true;
  
  function inline($arg) { return '<div class="clear">&nbsp;</div>'; }
}

// Script tag.
class SCL_js extends pemlCommand {
  protected $inline = true;
  
  function inline($arg) { return '<script type="text/javascript" src="'.$arg.'.js"></script>'; }
}

class SCL_css extends pemlCommand {
  protected $inline = true;
  
  function inline($arg) {
    $link = trim(substr($arg, 3));
    $suffix = "<![endif]-->";
    switch (substr($arg, 0, 3)) {
      case "IE6":
        $prefix = "<!--[if IE 6]>";
        break;
      case "IE7":
        $prefix = "<!--[if IE 7]>";
        break;
      case "IE ":
        $prefix = "<!--[if IE]>";
        break;
      default:
        $prefix = $suffix = "";
        $link = $arg;
    }
    return $prefix . '<link rel="stylesheet" type="text/css" href="' . $link . '.css">' . $suffix;
  }
}

// doctype shorthand, defaults to HTML5.
class SCL_doctype extends pemlCommand {
  protected $inline = true;
  
  function inline($arg) {
    switch ($arg) {
      case "html4 strict":
        return '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
      case "html4 transitional":
        return '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
      case "html4 frameset":
        return '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">';
      case "xhtml 1.0 strict":
        return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
      case "xhtml 1.0 transitional":
        return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
      case "xhtml 1.0 frameset":
        return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">';
      case "xhtml 1.1":
        return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';
      default:
        return "<!DOCTYPE html>";
    }
  }
}

// Code block, preserves whitespace.
class SCL_code extends pemlCommand {
  protected $block = true;
  private $first = true;
  
  function start($arg) { }
  
  function line($line) {
    $this->setIndent = $line->indent - $this->startIndent - 1;
    $newline = htmlentities(trim($line));
    if ($this->first) {
      $this->first = false;
      return "<pre>$newline";
    } else {
      return $newline;
    }
  }
    
  function finish() {
    $this->setIndent = 0;
    return "</pre>";
  }
}

// include.
class SCL_include extends pemlCommand {
  protected $inline = true;
  
  function inline($arg) { return "<?php include('$arg'); ?>"; }
}

// include inside a php tag.
pemlCore::registerHook("php", "include", "phpinclude");
class SCL_phpinclude extends pemlCommand {
  protected $inline = true;
  
  function inline($arg) { return "include('$line');"; }
}

// Google analytics.
class SCL_ganalytics extends pemlCommand {
  protected $inline = true;
  
  function inline($arg) {
    return "<script type=\"text/javascript\">
var gaJsHost = ((\"https:\" == document.location.protocol) ? \"https://ssl.\" : \"http://www.\");
document.write(unescape(\"%3Cscript src='\" + gaJsHost + \"google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E\"));
</script>
<script type=\"text/javascript\">
try {
var pageTracker = _gat._getTracker(\"$arg\");
pageTracker._trackPageview();
} catch(err) {}</script>";
  }
}

?>