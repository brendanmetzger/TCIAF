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
    
    protected $references = [
      'has'  => [
        'judge'       => ['person', 'organization'],
        'award'       => ['feature'],
        'sponsor'     => ['organization', 'person'],
        'participant' => ['feature'],
        'extra'       => ['article'],
      ],
      'acts' => [
        'edition'       => ['competition'],
      ]
    ];
    
    
    public function getEditions(\DOMElement $context)
    {
      return $context->find("edge[@type='edition']")->map(function($edge) {
        return ['competition' => new Competition($edge['@vertex'])];
      });
    }
    
    public function getAbout(\DOMElement $context)
    {
      $this->parseText($context);
      return isset($this->about) ? $this->about : null;
    }
    
    public function getBanner(\DOMElement $context)
    {
      if ($photo = $this->media['image']->current()) {
        return $photo;
      }
    }
    
    public function getView()
    {
      return $this->awards->count() > 0 ? 'competition/edition' : 'competition/brief';
    }
    
    public function getParticipants(\DOMElement $context)
    {
      return $context->find("edge[@type='participant']")->map(function($edge) {
        return ['feature' => new Feature($edge['@vertex'])];
      });
    }
      
    
    public function getJudges(\DOMElement $context)
    {
      return $context->find("edge[@type='judge']")->map(function($edge) {
        return ['person' => new Person($edge['@vertex'])];
      });
    }
    
    public function getAwards(\DOMElement $context)
    {
      return $context->find("edge[@type='award']")->map(function($edge) {
        return ['feature' => new Feature($edge['@vertex']), 'award' => $edge->nodeValue];
      });
    }
    
    public function getSponsors(\DOMElement $context)
    {
      return $context->find("edge[@type='sponsor']")->map(function($edge) {
        return ['organization' => new Organization($edge['@vertex'])];
      });
    }
  }