<?php
namespace models;

use \bloc\dom\document;
use \bloc\dom\query;

/**
  * Graph
  *
  */

  class Graph
  {
    const DB = 'data/db13';
  
    public $storage = null;
    
    static public function instance()
    {
      static $instance = null;
    
      if ($instance === null) {
        $instance = new static();
      }

      return $instance;
    }
    
    static public function group($type)
    {
      return self::instance()->query("graph/group[@type='{$type}']/");
    }
    
    static public function ID($id)
    {
      if ($id === null || strpos(strtolower($id), 'pending') === 0) return null;
      if (! $element = Graph::instance()->storage->getElementById($id)) {
        throw new \InvalidArgumentException("%s... Doesn't ring a bell.", 1);
      }
      return $element;
    }
    
    static public function EDGE($id, $type) {
      $edge = self::instance()->storage->createElement('edge');
      $edge->setAttribute('vertex', $id);
      $edge->setAttribute('type', $type);
      return $edge;
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
    
    public function getDTD()
    {
      return file_get_contents(PATH . 'data/graph.dtd');
    }
    
    public function query($expression)
    {
      return (new Query($this->storage))->path($expression);
    }
    
  }