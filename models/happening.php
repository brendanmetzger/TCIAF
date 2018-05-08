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
    public $_premier = "Archive Date";

    protected $_help = [
      'overview' => 'Events can be one off in nature, or repeating like the conference. In the case of the conference, it is divided into years individually, and these are attached to the main conference via an edge labeled *edition* (see associations below). Happenings (as well as competitions) keep track of time, and can organize themselves in that regard. Because of this, they can appear in a preview state, which shows a template that is very informational in nature, and once their *archive date* has been surpassed, the template switches again to something resembling playlist.',
      'premier' => 'Use this field to automatically set the page into archive mode. When the date is in the future, the page will be in preview mode.',
      'edges' => 'When creating a conference, be sure to include it as an edition so it shows on the conference landing page. Make sure to specify the host, most likely TCF is the organization. Articles attached as a page will show up as a jump navigation menu in preview mode.'
    ];

    static public $fixture = [
      'vertex' => [
        '@' => ['text' => 'about']
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
      if ($this->editions->count() == 1) {
        $this->origin = $this->editions->current()['edition'];
      }

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
        return ['item' => new Feature($edge['@vertex']), 'edge' => $edge];
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
