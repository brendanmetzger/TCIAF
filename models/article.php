<?php

  namespace models;
/*
 * Broadcast
 */

class Article extends Feature
{
  static public $fixture = [
    'vertex' => [
      'abstract' => [
        [
          'CDATA' => '',
          '@' => [
            'content' => 'body'
          ]
        ]
      ]
    ]
  ];
  
  protected $references = [
    'has' => [],
    'acts'  => [
      'extra' => ['feature', 'broadcast'],
      'item' => ['collection', 'competition']
    ],
  ];
}