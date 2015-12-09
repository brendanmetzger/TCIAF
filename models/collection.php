<?php
namespace models;

/**
  * Ensemble
  *
  */

  class Collection extends Vertex
  {
    use traits\banner;

    static public $fixture = [
      'vertex' => [
        'abstract' => [
          [
            'CDATA' => '',
            '@' => [
              'content' => 'about'
            ]
          ]
        ]
      ]
    ];

    protected $edges = [
      'item'    => ['feature', 'broadcast'],
      'curator' => ['person', 'organization'],
      'page'    => ['article'],
      'playlist'=> ['competition', 'happening'],
    ];

    public function __construct($id = null, $data =[])
    {
      $this->template['form'] = 'vertex';
      parent::__construct($id, $data);
    }


    public function getFeatures(\DOMElement $context)
    {
      return $context->find("edge[@type='item']")->map(function($edge) {
        return ['feature' => new Feature($edge['@vertex'])];
      });
    }

    public function getArticles(\DOMElement $context)
    {
      return $context->find("edge[@type='page']")->map(function($edge) {
        return ['article' => new Article($edge['@vertex'])];
      });
    }

  }
