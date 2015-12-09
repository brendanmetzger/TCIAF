<?php

namespace models;

/**
  * Organization
  *
  */

  class Organization extends Vertex
  {
    use traits\banner;

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
      'host'    => ['happening'],
      'sponsor' => ['organization', 'competition', 'happening'],
      'judge'   => ['competition'],
    ];

    public function __construct($id = null, $data =[])
    {
      $this->template['form'] = 'vertex';
      parent::__construct($id, $data);
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

    public function getSupporters(\DOMElement $context)
    {
      return $context->find("edge[@type='sponsor' and contains(., 'Support')]")->map(function($edge) {
        return ['organization' => new Organization($edge['@vertex']), 'type' => $edge->nodeValue];
      });

    }

  }
