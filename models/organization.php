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
    
    public function getSummary(\DOMElement $context)
    {
      $this->parseText($context);
      return substr(strip_tags($this->about), 0, 100) . '...';
    }
  }