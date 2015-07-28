<?php

namespace models;

/**
  * Organization
  *
  */

  class Organization extends Model
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
        'staff' => ['person'],
        'friend' => ['person'],
      ],
      'acts'    => [
        'sponsor'    => ['organization', 'competition', 'happening'],
      ]
    ];
    
    public function getSummary(\DOMElement $context)
    {
      $this->parseText($context);
      return substr(strip_tags($this->about), 0, 100) . '...';
    }
  }