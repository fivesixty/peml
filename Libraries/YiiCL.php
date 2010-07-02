<?php

// Form Methods

class YiiCL_chtml extends pemlCommand {
  protected $inline = true;
  
  function inline($arg) {
    return "<?php echo CHtml::$arg; ?>";
  }
}

class YiiCL_form extends pemlCommand {
  protected $container = true;
  
  function start($arg) {
    return "<?php echo CHtml::beginForm($arg); ?>";
  }
  function finish() {
    return "<?php echo CHtml::endForm(); ?>";
  }
}

// Wrap some generic functionality so it can be used for widgets and partials.
abstract class _YiiCL_component extends pemlCommand {
  protected $inline = true;
  protected $block = true;
  
  protected $methodName;
  protected $prefix = "";
  protected $suffix = "";
  
  function inline($arg) {
    return "{$this->prefix} \$this->{$this->methodName}('$arg'); {$this->suffix}";
  }
  
  function start($arg) {
    return "{$this->prefix} \$this->{$this->methodName}('$arg', array(";
  }
  
  function line($line) {
    preg_match('/^\s*+(?<var>[a-z0-9-_]++)\s*+:\s*+(?<value>.*)\s*$/i', $line, $arg);
    return "'{$arg['var']}' => {$arg['value']},";
  }
  
  function finish() {
    return ")); {$this->suffix}";
  }
}

class YiiCL_widget extends _YiiCL_component {
  protected $methodName = "widget";
  protected $prefix = "<?php";
  protected $suffix = "?>";
}

pemlCore::registerHook("php", "widget", "phpwidget");
class YiiCL_phpwidget extends _YiiCL_component {
  protected $methodName = "widget";
}

class YiiCL_partial extends _YiiCL_component {
  protected $methodName = "renderPartial";
  protected $prefix = "<?php";
  protected $suffix = "?>";
}

pemlCore::registerHook("php", "partial", "phppartial");
class YiiCL_phppartial extends _YiiCL_component {
  protected $methodName = "renderPartial";
}

class YiiCL_clip extends pemlCommand {
  protected $container = true;
  
  private $clipname;
  
  function start($arg) {
    $this->clipname = $arg;
    return "<?php \$this->beginClip('$arg'); ?>";
  }
  function finish() {
    return "<?php \$this->endClip('{$this->clipname}'); ?>";
  }
}

class YiiCL_clips extends pemlCommand {
  protected $inline = true;
  
  function inline($arg) {
    return "<?php echo \$this->clips['$arg']; ?>";
  }
}
  
?>