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
  
  public function __construct($id = null, $data =[])
  {
    $this->template['form'] = 'vertex';
    parent::__construct($id, $data);
  }
  
}