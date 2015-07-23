<?php

namespace models;

/**
  * Search
  *
  */

  class Search
  {
    private $index = [],
            $list = null;
            
    public $key = '@id',
           $tag = '@title';
  
  
    
    static public function clear($directory = '/')
    {
      $path = PATH . 'data/cache/search';
      $files = array_diff(scandir($path . $directory), array('.','..')); 
      foreach ($files as $file) {
        $filepath = $directory . $file;      
        if (is_dir($path . $filepath)) {
          self::clear($filepath . '/');
        } else {
          unlink($path.$filepath);
        }
      }
    }
    
    public function __construct($list)
    {
      $this->list = $list;
    }
  
    public function addToIndex($id, $value)
    {
      $parts = preg_split('/\s+/', $value);
      $level = 1;
      foreach ($parts as $part) {
        $idx = substr(strtolower($part), 0, 1);
        $key = strtolower(preg_replace('/[^a-z0-9]/i', '', $part));
        $this->index[$idx][$id] = [$level++, $value];
      }
    }
  
    public function getIndex($subset = false)
    {
      if (empty($this->index)) {
        $this->execute();
      }
      
      if ($subset) {
        $subset = $this->index[strtolower($subset)];

        uasort($subset, function($a, $b) {
          if ($a[0] == $b[0]) {
            return $a[1] > $b[1];
          }
          return $a[0] - $b[0];
        });
        
        
        return array_map(function($k, $v) {
          return [$k, $v[1]];
        }, array_keys($subset), $subset);
      }
      
      return $this->index;
    }
    
    private function execute()
    {
      $this->index = array_fill_keys(array_merge(range('a', 'z'), range(0, 9)), []);
      foreach ($this->list as $result) {
        $this->addToIndex($result[$this->key], $result[$this->tag]);
      }
      return $this->index;
    }
    
  
  
    public function asJSON($bucket, $subset = false, $cache = false)
    {
      $json = json_encode($this->getIndex($subset));
      if ($subset && $cache) {
        $subset = strtoupper($subset);
        $path = sprintf('%sdata/cache/search/%s/%s', PATH, $bucket, $cache);
        if (! file_exists($path)) {
          if (!mkdir($path, 0777, true)) {
            echo "NO";
          };
        }
        file_put_contents($path . "/{$subset}.json", $json);
      }
      return $json;
    }
  }