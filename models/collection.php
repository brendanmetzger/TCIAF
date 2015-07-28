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
    
  }