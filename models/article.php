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
  
  private $references = [
    'has' => [],
    'acts'  => [
      'extra'  => ['feature', 'collection', 'broadcast', 'competition']
    ],
  ];
}