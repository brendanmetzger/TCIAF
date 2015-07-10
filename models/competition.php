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
          [
            'CDATA' => '',
            '@' => [
              'content' => 'about'
            ]
          ]
        ]
      ]
    ];
    
    
    public function getIssues(\DOMElement $context)
    {
      return $context['edge']->map(function($edge) {
        return Graph::ID($edge['@vertex']);
      });
    }
    
    public function getAbout($context)
    {
      $this->parseText($context);
      return isset($this->about) ? $this->about : null;
    }
    
  }