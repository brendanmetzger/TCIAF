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

    public $_location = "Location";
    public $_premier = "Start time";
    public $_mark    = "Do not link to full page";

    protected $_help = [
      'overview' => 'Events can be one off in nature, or repeating like the conference. In the case of the conference, it is divided into years individually, and these are attached to the main conference via an edge labeled *edition* (see associations below). Happenings (as well as competitions) keep track of time, and can organize themselves in that regard. Because of this, they can appear in a preview state, which shows a template that is very informational in nature, and once their *archive date* has been surpassed, the template switches again to something resembling playlist.',
      'premier' => 'Even if a start time is not applicable, use this field to automatically set the page into archive mode—otherwise if the date is in the future, the page will be in preview mode.',
      'edges' => 'When creating a conference, be sure to include it as an edition so it shows on the conference landing page. Make sure to specify the host, most likely TCF is the organization. Articles attached as a page will show up as a jump navigation menu in preview mode.'
    ];

    static public $fixture = [
      'vertex' => [
        '@' => ['text' => 'about', 'mark' => null]
      ]
    ];

    protected $edges = [
      'host'        => ['person', 'organization'],
      'presenter'   => ['person'],
      'participant' => ['person'],
      'session'     => ['feature', 'happening'],
      'edition'     => ['happening'],
      'page'        => ['article'],
      'playlist'    => ['collection'],
      'sponsor'     => ['organization'],
    ];

    static public function EVENTS($timeline = 'upcoming')
    {
      $now = time();
      $happenings = Graph::GROUP('happening')
           ->find("vertex[edge[@type='host' and @vertex='A']]")
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
      $finish = $context['premier']['@duration'] ?? $context['premier']['@date'];
      if (strtotime($finish) > time()) {
        return 'preview';
      }      
      return 'edition';
    }

    public function getTeaser(\DOMElement $context)
    {
      return $context['location']['@ref'] ?: $this->date;
    }

    public function getPermalink(\DOMElement $context)
    {
      $path = $this->editions->count() > 0 ? "/conference/" : "/explore/lookup/happening/";
      return $path . $context['@key'];
    }

    public function getSessions(\DOMElement $context)
    {
      // TODO must be features, not happenings
      return $context->find("edge[@type='session']")->map(function($edge) {
        return ['item' => new Feature($edge['@vertex']), 'edge' => $edge];
      });
    }
    
    public function getSchedule(\DOMElement $context)
    {
      $schedule = new \models\schedule($context->find("edge[@type='session']"));

      
      return $schedule->render();
      
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
    
    public function setMarkAttribute(\DOMElement $context, $value)
    {
      if (empty($value)) {
        $context->removeAttribute('mark');
      } else {
        $context->setAttribute('mark', $value);
      }
    }
    
    public function getMark(\DOMElement $context)
    {
      
      if (! $context['@mark']) throw new \RuntimeException('no mark value');
      
      return $context['@mark'];
    }
  }
