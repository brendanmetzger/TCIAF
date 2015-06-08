<?php

namespace models;

/**
  * Organization
  *
  */

  class Organization extends Model
  {
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