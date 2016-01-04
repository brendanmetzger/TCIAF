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
      $caption = $media->nodeValue ?: str_replace('_', ' ', substr($media['@src'], strrpos($media['@src'], '/') + 1, -4));
      $domain = 'http://s3.amazonaws.com';
      $this->slug = [
        'domain'  => $domain,
        'index'   => $index === null ? $media->getIndex() : $index,
        'url'     => preg_replace('/^(feature-photos\/photos\/[0-9]+\/)(.*)$/i', '$1large/$2', $media['@src']),
        'src'     => $media['@src'],
        'type'    => $media['@type'],
        'mark'    => $media['@mark'] ?: 0,
        'caption' => (new \Parsedown())->text($caption),
        'title'   => $media->parentNode['@title'],
        'plain'   => $caption,
        'xid'     => $index ?: $media['@type'] . '/' . $media->parentNode['@id'] . '/' . $media->getIndex(),
      ];
    }

    protected function initialize() {}
    protected function identify($identity) {}
    public function save() {}

    static public function COLLECT(\bloc\dom\Iterator $media, $filter = null)
    {
      $collect = [];
      foreach ($media as $item) {
        if ($filter === null || $item['@type'] === $filter) {
          $collect[] = new self($item);
        }
      }
      return new \bloc\types\Dictionary($collect);
    }


    public function __get($key)
    {

      if (!array_key_exists($key, $this->slug)) {
        throw new \RuntimeException("No {$key}");
      }

      return $this->slug[$key];
    }
  }
