<?php

namespace models;

/**
  * Organization
  *
  */

  class Organization extends Vertex
  {
    use traits\banner, traits\sponsor;
    public $_location = "Website";
    public $_premier = "Expires";

    protected $_help = [
      'overview' => 'Organizations are typically sponsors, but can also be used to collect groups of individuals—such as TCF itself.',
      'edges' => 'If the organization is a sponsor, labeling the edge will be utilized. Review other organizations to see current implementations.',
      'extras' => '(not implemented)'
    ];

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

    public function getPermalink(\DOMElement $context)
    {
      $id = $context['@id'];
      return $id == 'TCIAF' ? '/overview/tciaf' : '/explore/index/'. $id;
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

    public function getFriends(\DOMElement $context)
    {
      // see traits\sponsor
      return $this->groupByTitle($context, 'friend');
    }

    public function getFunders(\DOMElement $context)
    {
      // see traits\sponsor
      return $this->groupByTitle($context, 'sponsor');
    }
  }
