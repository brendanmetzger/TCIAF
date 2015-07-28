<?php

  namespace models;
/*
 * Broadcast
 */

class Broadcast extends Feature
{
  public $form = 'feature';
  
  protected $references = [
    'has' => [
      'producer' => ['person'],
      'extra'    => ['article', 'feature'],
    ],
    'acts'    => [
      'item' => ['collection'],
    ]
  ];
}