<?php
namespace models\traits;
use \models\Graph;

trait resolver {
  protected function identify($identity) {
    $node = Graph::ID($identity);
    return $node;
  }

  protected function initialize() {
    static::$fixture = array_replace_recursive(self::$fixture, static::$fixture);
    static::$fixture['vertex']['@']['created'] = Graph::ALPHAID(time());
    $node = Graph::instance()->storage->createElement('vertex', null);
    $this->input(static::$fixture, $node);
    return Graph::group($this->get_model())->pick('.')->appendChild($node);
  }

  public function save()
  {
    $filepath = PATH . Graph::DB . '.xml';
    $this->setUpdatedAttribute($this->context);
    if (empty($this->errors) && Graph::instance()->storage->validate() && is_writable($filepath)) {
      return Graph::instance()->storage->save($filepath);
    } else {

      $this->errors = array_merge(["Did not save"], $this->errors, array_map(function($error) {
        return $error->message;
      }, Graph::instance()->storage->errors()));

      return false;
    }
  }
}
