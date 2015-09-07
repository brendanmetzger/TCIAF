<?php
namespace models;

/**
  * Ensemble
  *
  */

  class Collection extends Model
  {
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
    
  }