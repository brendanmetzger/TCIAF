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
    
    private $references = [
      'has' => [
        'track'   => ['feature', 'broadcast'],
        'curator' => ['person', 'organization'],
      ],
      'acts' => []
    ];
    
  }