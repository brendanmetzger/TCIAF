<?php

namespace models;

/**
  * Competition
  *
  */

  class Competition extends Model
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
    
    public function getAbout($context)
    {
      $this->parseText($context);
      return $this->about;
    }
    
  }