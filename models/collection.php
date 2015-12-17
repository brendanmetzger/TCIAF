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

    public function getCurators(\DOMElement $context)
    {
      return $context->find("edge[@type='curator']")->map(function($edge) {
        return ['person' => new Person($edge['@vertex'])];
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
        $out['duration']+= $feature['feature']->duration;
      }

      $out['duration'] = round($out['duration'] / 60, 1);
      return new \bloc\types\Dictionary($out);
    }
  }
