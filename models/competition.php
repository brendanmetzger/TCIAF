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
          '@' => [
            'content' => 'about'
          ]
        ]
      ]
    ];
  }