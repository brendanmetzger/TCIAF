<?php
namespace models;

/**
  * Ensemble
  *
  */

  class Collection extends Vertex
  {
    use traits\banner;

    public $_location = 'Feature (where)';
    public $_premier  = 'Date';

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
      'page'    => ['article', 'happening'],
      'playlist'=> ['competition', 'happening'],
    ];

    public function __construct($id = null, $data =[])
    {
      $this->template['form'] = 'vertex';
      parent::__construct($id, $data);
    }


    public function getFeatures(\DOMElement $context)
    {
      $markdown = new \vendor\Parsedown;
      return $context->find("edge[@type='item']")->map(function($edge) use ($markdown){
        return ['item' => new Feature($edge['@vertex']), 'caption' => strip_tags($markdown->text($edge), '<em><strong>')];
      });
    }

    public function getTriptych(\DOMElement $context)
    {
      return $this->features->limit(1, 3);
    }

    public function getArticles(\DOMElement $context)
    {
      return $context->find("edge[@type='page']")->map(function($edge) {
        return ['article' => new Article($edge['@vertex'])];
      });
    }

    public function getCurators(\DOMElement $context)
    {
      return $context->find("edge[@type='curator']")->map(function($edge) {
        return ['person' => Graph::FACTORY(Graph::ID($edge['@vertex']))];
      });
    }

    public function getSize(\DOMElement $context)
    {
      $out = [
        'length'   => 0,
        'duration' => 0,
      ];
      foreach ($this->features as $feature) {
        $out['length']+=1;
        $out['duration']+= $feature['item']->duration;
      }

      $out['duration'] = round($out['duration'] / 60, 1);
      return new \bloc\types\Dictionary($out);
    }
  }
