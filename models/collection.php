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
    
    protected $references = [
      'has' => [
        'item'   => ['feature', 'broadcast'],
        'curator' => ['person', 'organization'],
      ],
      'acts' => []
    ];
    
    public function getFeatures(\DOMElement $context)
    {

      return $context->find("edge[@type='item']")->map(function($edge) {
        return ['feature' => new Feature($edge['@vertex'])];
      });
      
    }
    
  }