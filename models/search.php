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
      foreach ($parts as $part) {
        $idx = substr(strtolower($part), 0, 1);
        $key = preg_replace('/[^a-z0-9]/i', '', $part);
        $this->index[$idx][$key] = [$id, $value];
      }
    }
  
    public function getIndex($subset = false)
    {
      if (empty($this->index)) {
        $this->execute();
      }
      return $subset ? $this->index[strtolower($subset)] : $this->index;
    }
    
    private function execute()
    {
      $this->index = array_fill_keys(array_merge(range('a', 'z'), range(0, 9)), []);
      foreach (Token::storage()->find($this->query) as $result) {
        $this->addToIndex($result['@id'], $result['@title']);
      }
      return $this->index;
    }
  
  
    public function asJSON($subset = false)
    {
      $json = json_encode($this->getIndex($subset));
      if ($subset) {
        $subset = strtoupper($subset);
        $path = sprintf('%sdata/cache/search/people', PATH);
        if (! file_exists($path)) {
          mkdir($path, 0777, true);
        }
        file_put_contents($path . "/{$subset}.json", $json);
      }
      return $json;
    }
  }