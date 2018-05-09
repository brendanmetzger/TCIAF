<?php

namespace models;

/**
  * Search
  *
  */

  class Search
  {
    private $index = [],
            $list = null,
            $type = null;

    public $key = '@id',
           $tag = '@title';

    
    static public function CLEAR($directory = '/')
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

    static public function FACTORY($group)
    {
      $instance = new self($group->find('vertex'));
      $instance->type = $group->getAttribute('type');
      return $instance;
    }

    public function __construct($list)
    {
      $this->list = $list;
    }

    public function createIndex($category = 'group')
    {
      $this->index = array_fill_keys(array_merge(range('a', 'z'), range(0, 9)), []);

      foreach ($this->list as $result) {
        $this->addToIndex($result[$this->key], $result[$this->tag]);
      }


      foreach ($this->index as $alpha => $subset) {
        $output = json_encode($this->format($subset));

        $path = sprintf('%sdata/cache/search/%s/%s', PATH, $category, $this->type);
        if (! file_exists($path)) {
          if (!mkdir($path, 0777, true)) {
            echo "NO";
          };
        }
        $alpha = strtoupper($alpha);
        file_put_contents($path . "/{$alpha}.json", $output);
      }
      return true;
    }

    public function addToIndex($id, $value)
    {
      $value = preg_replace('/[^a-z0-9\s\'\!\.\?\:]+/i', '', $value);
      $parts = preg_split('/\swith\s|\sthe\s|\s/i', $value);
      $level = 1;
      foreach ($parts as $part) {
        if ($level > 1 && strlen($part) < 3) continue;
        $idx = substr(strtolower($part), 0, 1);
        $key = strtolower($part);
        $this->index[$idx][$id] = [$level++, $value];
      }
    }

    public function getIndex($subset = false)
    {
      if (empty($this->index)) {
        $this->execute();
      }

      if ($subset) {
        return $this->format($this->index[strtolower($subset)]);
      }



      return $this->index;
    }

    private function format($subset)
    {
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
