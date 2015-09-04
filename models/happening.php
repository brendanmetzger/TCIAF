<?php
namespace models;

/**
  * Happening
  *
  */

  class Happening extends Model
  {
    public $form = 'vertex';
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
      'host'        => ['person'],
      'participant' => ['person'],
      'extra'       => ['article'],
      'item'        => ['feature'],
      'edition'     => ['happening'],
    ];
		
    public function getFeatures(\DOMElement $context)
    {
      return $context->find("edge[@type='item']")->map(function($edge) {
        return ['feature' => new Feature($edge['@vertex'])];
      });  
    }
		
    public function getParticipants(\DOMElement $context)
    {
      return $context->find("edge[@type='participant']")->sort(Graph::sort('alpha-numeric'))->map(function($edge) {
        return ['person' => new Person($edge['@vertex'])];
      });  
    }
		
  }