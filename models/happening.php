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
    
    protected $references = [
      'has' => [
        'host'        => ['person'],
        'participant' => ['person'],
        'extra'       => ['article'],
        'item'        => ['feature']
      ],
      'acts' => [
        'edition'       => ['happening'],
      ]
    ];
		
    public function getFeatures(\DOMElement $context)
    {
      return $context->find("edge[@type='item']")->map(function($edge) {
        return ['feature' => new Feature($edge['@vertex'])];
      });  
    }
		
    public function getParticipants(\DOMElement $context)
    {
      return $context->find("edge[@type='participant']")->map(function($edge) {
        return ['person' => new Person($edge['@vertex'])];
      });  
    }
		
  }