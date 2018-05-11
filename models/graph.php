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
    const DB = 'data/tciaf2';

    public $storage = null;

    static public function instance($id = null)
    {
      static $instance = null;

      if ($instance === null) {
        $instance = new static();
      }

      return $id ? $instance->storage->getElementById($id) : $instance;
    }

    static public function group($type)
    {
      return self::instance()->query("graph/group[@type='{$type}']/");
    }

    static public function ID($id = null)
    {
      if (is_null($id)) return null;
      if ($id instanceof \bloc\model) return $id->context;
      if (! $element = Graph::instance($id) ) return null;

      return $element;
    }
    
    static public function ALPHAID(int $n) {
      $out = '';
      $alphabet = array_merge(range('A', 'Z'), range('a', 'z'));
      $b = count($alphabet);
      
      do  {
        $d = floor($n / $b);
        $r = $n % $b;
        $n = $d;
        $out = $alphabet[$r] . $out;
      } while ($n > 0);

      return $out;
    }
    
    static public function INTID(string $s) {
      $out = 0;
      $alphabet = array_flip(array_merge(range('A', 'Z'), range('a', 'z')));
      $base     = count($alphabet);

      foreach (array_reverse(str_split($s)) as $exp => $val) {
        $out += ($base ** $exp) * $alphabet[$val];
      }
      return $out;
    }

    static public function FACTORY($model, $data = [])
    {
      if ($model instanceof \DOMElement) {
        $element = $model;
        $model = $element->parentNode->getAttribute('type');
        if ($model === 'archive') {
          $model = $element->getAttribute('mark');
        }
      } else if (is_string($model)){
        $element = null;
      } else {
        throw new \RunTimeException("Not Found", 404);

      }

      $classname = NS . __NAMESPACE__ . NS . $model;

      return  new $classname($element, $data);
    }

    static public function SORT($type, $key = null)
    {
      if ($mark = strpos($type, ':')) {
        $key  = strtoupper(substr($type, $mark + 1));
        $type = substr($type, 0, $mark);
      }

      return [
        'newest' => function($a, $b) {
          return $a->getAttribute('created') < $b->getAttribute('created');
        },
        'updated' => function($a, $b) {
          return $a->getAttribute('updated') < $b->getAttribute('updated');
        },
        'alpha-numeric' => function($a, $b) {
          return $a->getAttribute('id') > $b->getAttribute('id');
        },
        'date' => function($a, $b) {
          return strtotime($a->getFirst('premier', 0, false)->getAttribute('date')) < strtotime($b->getFirst('premier', 0, false)->getAttribute('date'));
        },
        'recommended' => function($a, $b) use($key){
          return $a->getFirst('spectra', 0, false)->getAttribute($key) < $b->getFirst('spectra', 0, false)->getAttribute($key);
        },
        'duration' => function($a, $b) {
          return $a->getAttribute('mark') < $b->getAttribute('mark');
        }
      ][$type];
    }

    static public function EDGE($id, $type, $caption = null) {
      $edge = self::instance()->storage->createElement('edge');
      $edge->setAttribute('vertex', $id);
      $edge->setAttribute('type', $type);
      $edge->setNodeValue($caption);
      return $edge;
    }

    static public function GROUPS($model)
    {
      $dtd = Graph::instance()->getDTD('groups');
      preg_match('/ATTLIST group type \(([a-z\s|]+)\)/i', $dtd, $result);
      return (new \bloc\types\Dictionary(preg_split('/\s?\|\s?/i', $result[1])))->map(function($item) use($model){
        return ['name' => $item];
      });
    }

    static public function RELATIONSHIPS()
    {
      $dtd = Graph::instance()->getDTD('groups');
      preg_match('/ATTLIST edge type \(([a-z\s|]+)\)/i', $dtd, $result);
      return (new \bloc\types\Dictionary(preg_split('/\s?\|\s?/i', $result[1])))->map(function($item) {
        return ['name' => $item];
      });
    }

    private function __construct()
    {
      $this->storage = new Document(self::DB, ['validateOnParse' => true]);
    }

    public function getDTD()
    {
      return file_get_contents(PATH . 'models/graph.dtd');
    }

    public function query($expression)
    {
      return (new Query($this->storage))->path($expression);
    }

  }
