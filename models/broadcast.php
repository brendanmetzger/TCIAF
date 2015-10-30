<?php

  namespace models;
/*
 * Broadcast
 */

class Broadcast extends Feature
{
  protected $edges = [
    'producer' => ['person'],
    'extra'    => ['article', 'feature'],
    'item'     => ['collection'],
  ];
  
}