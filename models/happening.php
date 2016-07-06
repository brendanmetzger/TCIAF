<?php
namespace models;

/**
  * Happening
  *
  */

  class Happening extends Vertex
  {
    use traits\navigation;
    use traits\banner, traits\sponsor, traits\periodical;

    public $_location = "Date Range (if applicable)";
    public $_premier = "Live Date";

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
      'host'        => ['person', 'organization'],
      'presenter'   => ['person'],
      'participant' => ['person'],
      'session'     => ['feature'],
      'edition'     => ['happening'],
      'page'        => ['article'],
      'playlist'    => ['collection'],
      'sponsor'     => ['organization'],
    ];

    static public function EVENTS($timeline = 'upcoming')
    {
      $now = time();
      $happenings = Graph::GROUP('happening')
           ->find("vertex[edge[@type='host' and @vertex='TCIAF']]")
           ->sort(Graph::sort('date'))
           ->map(function($vertex) {
             return ['item' => new self($vertex)];
           })
           ->filter(function($event) use($now, $timeline){
             $time = strtotime($event['item']['premier']['@date']);
             if ($timeline == 'past') {
               return $time < $now;
             } else {
               return $time > $now;
             }
           });

      return $happenings;
    }

    public function __construct($id = null, $data =[])
    {
      parent::__construct($id, $data);
      $this->template['form'] = 'vertex';
    }

    public function get_template(\DOMElement $context)
    {
      if ($context['premier']->count() > 0) {
        if (strtotime($context['premier']['@date']) > time()) {
          return 'preview';
        }
      }
      return 'edition';
    }

    public function getTeaser(\DOMElement $context)
    {
      return $context['location']['@ref'] ?: $this->date;
    }

    public function getPermalink(\DOMElement $context)
    {
      $path = $this->editions->count() > 0 ? "/overview/conference/" : "/explore/detail/";
      return $path . $context['@id'];
    }

    public function getSessions(\DOMElement $context)
    {
      return $context->find("edge[@type='session']")->map(function($edge) {
        return ['session' => new Feature($edge['@vertex']), 'edge' => $edge];
      });
    }

    public function getParticipants(\DOMElement $context)
    {
      return $context->find("edge[@type='participant']")->map(function($edge) {
        return ['person' => new Person($edge['@vertex']), 'edge' => $edge];
      });
    }

    public function getPresenters(\DOMElement $context)
    {
      $presenters = $context->find("edge[@type='presenter']");
      return $presenters->count() < 1 ? null : $presenters->map(function($edge) {
        return ['person' => new Person($edge['@vertex']), 'edge' => $edge];
      });
    }

    public function getConferences(\DOMElement $context)
    {
      return $this->editions->sort(function($a, $b) {
        return substr($a['edition']['title'], 0, 4) < substr($b['edition']['title'], 0, 4);
      });
    }

    public function getRecent()
    {
      return $this->conferences->limit(1, 2);
    }

    public function getParticipantsAlpha()
    {
      return $this->participants->sort(function($a, $b) {
        return ucfirst(ltrim($a['person']->title, "\x00..\x2F")) > ucfirst(ltrim($b['person']->title, "\x00..\x2F"));
      });
    }
  }
