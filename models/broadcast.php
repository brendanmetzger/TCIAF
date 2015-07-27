<?php

  namespace models;
/*
 * Broadcast
 */

class Broadcast extends Feature
{
  public $form = 'feature';
  
  private $references = [
    'has' => [
      'producer' => ['person'],
      'extra'    => ['article', 'feature'],
    ],
    'acts'    => [
      'track'    => ['collection'],
    ]
  ];
}