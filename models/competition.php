<?php

namespace models;

/**
  * Competition
  *
  */

  class Competition extends Vertex
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
      'judge'       => ['person', 'organization'],
      'award'       => ['feature'],
      'sponsor'     => ['organization', 'person'],
      'page'        => ['article'],
      'edition'     => ['competition'],
      'playlist'    => ['collection'],
    ];

    public function __construct($id = null, $data =[])
    {
      parent::__construct($id, $data);
      $this->template['form'] = 'vertex';
      $this->template['digest'] = $this->awards->count() > 0 ? 'competition/edition' : 'competition/overview';
    }



    public function getEditions(\DOMElement $context)
    {
      return $context->find("edge[@type='edition']")->map(function($edge) {
        $competition = new Competition($edge['@vertex']);
        preg_match('/([0-9]{4})\s*(.*)/i', $competition['title'], $result);
        return ['competition' => $competition, 'year' => $result[1]];
      });
    }


    public function getCompetitions()
    {
      return $this->editions->sort(function($a, $b) {
        return substr($a['competition']['title'], 0, 4) < substr($b['competition']['title'], 0, 4);
      });
    }

    public function getAbout(\DOMElement $context)
    {
      $this->parseText($context);
      return isset($this->about) ? $this->about : null;
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
        $feature = new Feature($edge['@vertex']);
        return ['feature' => $feature, 'award' => $edge->nodeValue ?: $feature['title'], 'id' => $edge['@vertex']];
      });
    }

    public function getSponsors(\DOMElement $context)
    {
      return $context->find("edge[@type='sponsor']")->map(function($edge) {
        return ['organization' => new Organization($edge['@vertex'])];
      });
    }
  }
