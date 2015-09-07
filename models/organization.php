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
          [
            'CDATA' => '',
            '@' => [
              'content' => 'about'
            ]
          ]
        ]
      ]
    ];
    
    protected $edges = [
      'staff'   => ['person'],
      'friend'  => ['person'],
      'board'   => ['person'],
      'sponsor' => ['organization', 'competition', 'happening'],
      'judge'   => ['competition'],
    ];
    
    public function __construct($id = null, $data =[])
    {
      $this->template['form'] = 'vertex';
      parent::__construct($id, $data);
    }
    
    public function getSummary(\DOMElement $context)
    {
      $this->parseText($context);
      return substr(strip_tags($this->about), 0, 100) . '...';
    }
    
    
    public function getAbout(\DOMElement $context)
    {
      $this->parseText($context);
      return isset($this->about) ? $this->about : null;
    }
    
    public function getStaff(\DOMElement $context)
    {
      return $context->find("edge[@type='staff']")->map(function($edge) {
        return ['person' => new Person($edge['@vertex']), 'position' => $edge->nodeValue];
      });
    }
    
    public function getBoard(\DOMElement $context)
    {
      return $context->find("edge[@type='board']")->map(function($edge) {
        return ['person' => new Person($edge['@vertex']), 'position' => $edge->nodeValue];
      });
    }
    
    public function getPhoto(\DOMElement $context)
    {
      if ($photo = $this->media['image']->current()) {
        return $photo;
      }
    }
    
    public function getSupporters(\DOMElement $context)
    {
      return $context->find("edge[@type='sponsor' and contains(., 'Support')]")->map(function($edge) {
        return ['organization' => new Organization($edge['@vertex']), 'type' => $edge->nodeValue];
      });
      
    }
    
  }