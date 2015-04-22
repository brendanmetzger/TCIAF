<?php

namespace models;

/**
  * Search
  *
  */

  class Search
  {
    private $index = [];
  
    public function __construct()
    {
      $this->index = array_fill_keys(array_merge(range('a', 'z'), range(0, 9)), []);
    }
  
    public function addToIndex($id, $value)
    {
      $key = preg_replace('/[^a-z0-9]/i', '', $value);
      $parts = preg_split('/\s+/', $value);
      foreach ($parts as $part) {
        $idx = substr(strtolower($part), 0, 1);
        $this->index[$idx][$key] = [$id, $value];
      }
    }
  
    public function getIndex()
    {
      return $this->index;
    }
  
  
    public function asJSON($subset = false)
    {
      return json_encode($subset ? $this->index[$subset] : $this->index);
    }
  }