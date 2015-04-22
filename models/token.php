<?php

namespace models;
use \bloc\DOM\Document;

/**
  * Token
  *
  */

  class Token
  {
    const DB = 'data/db7';
  
    private $storage = null;

    static public function storage()
    {
      static $instance = null;
    
      if ($instance === null) {
        $instance = new static();
      }
      return $instance->storage;
    }
  
    static public function factory($model_name)
    {
      return  NS . __NAMESPACE__ . NS . $model_name;
    }
  
    private function __construct()
    {
      $this->storage = new Document(self::DB, ['validateOnParse' => true]);
    }
  }