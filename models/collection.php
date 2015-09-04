<?php
namespace models;

/**
  * Ensemble
  *
  */

  class Collection extends Model
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
      'item'   => ['feature', 'broadcast'],
      'curator' => ['person', 'organization'],
    ];
    
    public function getFeatures(\DOMElement $context)
    {

      return $context->find("edge[@type='item']")->map(function($edge) {
        return ['feature' => new Feature($edge['@vertex'])];
      });
      
    }
    
  }