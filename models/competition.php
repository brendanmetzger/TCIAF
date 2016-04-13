<?php

namespace models;

/**
  * Competition
  *
  */

  class Competition extends Vertex
  {
    use traits\banner, traits\sponsor, traits\periodical;

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
      // TODO: templates can be set automatically with overriding the constructor
      $this->template['digest'] = $this->_template($this->context);
    }

    protected function _template(\DOMElement $context)
    {
      if ($context['premier']->count() > 0 && strtotime($context['premier']['@date']) > time()) {
        return 'competition/preview';
      }
      $edges = $context->find("edge[@type='edition']");
      if ($edges->count() > 1) {
        return 'competition/overview';
      } else {
        // TODO: Edge should be a model. new Edge($edges->pick())->vertex ... to get owner
        $this->type = $edges->pick()['@vertex'];

        return 'competition/edition';
      }
    }


    public function getPermalink(\DOMElement $context)
    {
      return "/overview/competition/{$context['@id']}";
    }

    public function getCompetitions()
    {
      return $this->editions->sort(function($a, $b) {
        return substr($a['edition']['title'], 0, 4) < substr($b['edition']['title'], 0, 4);
      });
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
