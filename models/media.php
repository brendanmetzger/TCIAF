<?php

namespace models;

/**
  * Media
  *
  */

  class Media extends \bloc\Model
  {
    use \bloc\types\Map;
    
    public $slug = [];
    
    public function __construct(\DOMNode $media, $index = null)
    {
      $this->slug = [
        'domain'  => 'http://tmp.s3.amazonaws.com',
        'index'   => $index === null ? $media->getIndex() : $index,
        'url'     => preg_replace('/^(feature-photos\/photos\/[0-9]+\/)(.*)$/i', '$1small/$2', $media['@src']),
        'src'     => $media['@src'],
        'type'    => $media['@type'],
        'mark'    => 0,
        'caption' => $media->nodeValue ?: str_replace('_', ' ', substr($media['@src'], strrpos($media['@src'], '/') + 1, -4)),
        'context' => $index ?: $media['@type'] . '/' . $media->parentNode['@id'] . '/' . $media->getIndex(),
      ];
    }
    
    static public function COLLECT(\bloc\dom\NodeIterator $media, $filter = null)
    {
      $collect = [];
      foreach ($media as $item) {
        if ($filter === null || $item['@type'] === $filter) {
          $collect[] = new self($item);
        }
      }
      return new \bloc\types\Dictionary($collect);
    }
    
    public function upload($file)
    {
      // do
    }
    
    public function __get($key)
    {
      if (!array_key_exists($key, $this->slug)) {
        throw new \RuntimeException("No {$key}");
        
      }
      return $this->slug[$key];
    }
  }