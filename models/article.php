<?php

  namespace models;
/*
 * Broadcast
 */

class Article extends Vertex
{
  use traits\banner;

  static public $fixture = [
    'vertex' => [
      'abstract' => [
        [
          'CDATA' => '',
          '@' => [
            'content' => 'description'
          ]
        ]
      ]
    ]
  ];

  protected $edges = [
    'producer' => ['person'],
    'extra'    => ['feature', 'broadcast'],
    'item'     => ['competition'],
    'page'     => ['collection', 'competition', 'happening'],
  ];

  public function __construct($id = null, $data =[])
  {
    $this->template['form'] = 'vertex';
    $this->template['upload'] = 'audio-image';
    parent::__construct($id, $data);
  }

  public function getFeatures(\DOMElement $context)
  {
    return $context->find("edge[@type='extra']")->map(function($extra) {
      return ['feature' => new Feature($extra['@vertex'])];
    });
  }


}
