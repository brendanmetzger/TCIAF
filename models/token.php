<?php
namespace models;

use \bloc\dom\document;

/**
  * Token
  *
  */

  class Token
  {
    const DB = 'data/db8';
  
    private $storage = null;

    static public function storage()
    {
      static $instance = null;
    
      if ($instance === null) {
        $instance = new static();
      }
      return $instance->storage;
    }
    
    static public function ID($id)
    {
      if ($id === null || strtolower($id) === 'pending') return null;
      if (! $element = Token::storage()->getElementById($id)) {
        throw new \InvalidArgumentException("%s... Doesn't ring a bell.", 1);
      }
      return $element;
    }
  
    static public function factory($model, $element = null)
    {
      $classname = NS . __NAMESPACE__ . NS . $model;
      return  new $classname($element);
    }
  
    private function __construct()
    {
      $this->storage = new Document(self::DB, ['validateOnParse' => true]);
    }    
  }