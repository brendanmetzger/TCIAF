<?php
namespace models;

/**
  * Festival
  *
  */

  class Festival extends Model
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
  }