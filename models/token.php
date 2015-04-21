<?php
namespace models;
use \bloc\DOM\Document;

/**
 * Token
 */

class Token
{
  const DB = 'data/db6';
  
  private $storage = null;
  static public function storage()
  {
    static $instance = null;
    
    if ($instance === null) {
      $instance = new static();
    }
    return $instance->storage;
  }
  
  private function __construct()
  {
    $this->storage = new Document(self::DB, ['validateOnParse' => true]);
  }
}