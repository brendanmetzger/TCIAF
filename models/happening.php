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
        'extra'       => ['article', 'feature'],
      ],
      'acts' => [
        'edition'       => ['happening'],
      ]
    ];
  }