<?php

namespace models;

/**
  * Search
  *
  */

  class Search
  {
    private $index = [],
            $query = null;
  
    public function __construct($query)
    {
      
      $this->query = $query;
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
      foreach (Token::storage()->find($this->query) as $result) {
        $this->addToIndex($result['@id'], $result['@title']);
      }
      return $this->index;
    }
  
  
    public function asJSON($subset = false, $cache = false)
    {
      $json = json_encode($this->getIndex($subset));
      if ($subset && $cache) {
        $subset = strtoupper($subset);
        $path = sprintf('%sdata/cache/search/%s', PATH, $cache);
        if (! file_exists($path)) {
          mkdir($path, 0777, true);
        }
        file_put_contents($path . "/{$subset}.json", $json);
      }
      return $json;
    }
  }