<?php
namespace models;
use \bloc\DOM\Document;

/**
 * Token
 */

class Token
{
  const DB = 'data/db6';
  
  private $storage = [];
  static public function storage($type = 'doc')
  {
    static $instance = null;
    
    if ($instance === null) {
      $instance = new static();
    }
    return $instance->storage[$type];
  }
  
  private function __construct()
  {
    $this->storage['doc']   = new Document(self::DB, ['validateOnParse' => true]);
    $this->storage['xpath'] = null ;
  }
}