<?php

  namespace models;
/*
 * Broadcast
 */

class Broadcast extends Feature
{
  public $form = 'feature';
  
  protected $edges = [
    'producer' => ['person'],
    'extra'    => ['article', 'feature'],
    'item'     => ['collection'],
  ];
}