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
    
    private $references = [
      'has' => [
        'host'        => ['person'],
        'participant' => ['person'],
        'extra'       => ['article'],
      ],
      'acts' => []
    ];
  }