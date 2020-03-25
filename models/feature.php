<?php namespace models;

function timecode($time) {
  return $time <= 60 ?   sprintf('%02d', floor($time)) : timecode($time / 60) .':'. sprintf('%02d', $time % 60);
}

/**
  * Feature
  *
  */
  class Feature extends Vertex
  {

    protected $_help = [
      'overview' => 'Features represent Stories, Re:sounds, and Conference Sessions.',
      'premier' => 'A full timestamp is used for accuracy, though in most instances site visitors will only see the year. This helps Re:sound keep track of order. If this field is empty, the feature will NOT show up in library—so leave empty if you would like to keep items hidden!.',
      'duration' => 'This is only necessary if the feature/session item will show up on a schedule, ie., the TCF Conference',
      'edges' => 'A story should have a producer associated, wheras a conference session should have a presenter associated as well as be attached to the appropriate happening. Re:sounds should have the TCF (organization) listed as a producer in addition to other folks.',
      'extras' => '(extras are legacy to accomodate the old site—they do not serve a particularly useful function on new features)'
    ];


    static public $fixture = [
      'vertex' => [
        'spectra' => [
          '@' => ['F'=>50,'S'=>50,'M'=>50,'R'=>50,'P'=>50,'T'=>50,'A'=>50]
        ]
      ]
    ];

    protected $edges = [
      'producer'    => ['person', 'organization'],
      'presenter'   => ['person'],
      'extra'       => ['article', 'feature'],
      'award'       => ['competition'],
      'item'        => ['collection'],
      'session'     => ['happening'],
      'participant' => ['competition'],
    ];

    public function __construct($id = null, $data = [])
    {
      parent::__construct($id, $data);

      $this->template['upload'] = 'audio-image';
      if ($this->happenings->count() > 0 && $this->presenters->count() > 0) {
        $this->template['digest'] = 'session';
      } else if ($this->context->find('edge[@vertex="A"]')->count() > 0) {
        $this->template['digest'] = 'broadcast';
      }
    }
    
    public function setDateAttribute(\DOMElement $context, $date)
    {
      if (! empty($date) && $date = (new \DateTime($date))->format('Y-m-d\TH:i')) {
        $context->setAttribute('date', $date);
      }
    }

    public function getYear(\DOMElement $context)
    {
      try {
        return (new \DateTime($context['premier']['@date']))->format('Y');
      } catch (\Exception $e) {
        return 'NA';
      }
    }

    public function getSpectra(\DOMElement $context)
    {
      $spectra = $this::$fixture['vertex']['spectra']['@'];

      if ($spectrum = $context->getFirst('spectra')) {
        foreach ($spectrum->attributes as $attr) {
          $spectra[$attr->name] = $attr->value;
        }
      }

      $sp = Graph::instance()->query('graph/config/')->find('spectra')->map(function($item) use($spectra) {
        $key = substr($item['@id'], 1);
        return ['key' => $key, 'item' => $item, 'title' => $item->nodeValue, 'value' => $spectra[$key]];
      });
      return $sp;
    }

    public function getGradient(\DOMElement $context)
    {
      $color = 'linear-gradient(90deg, %s)';
      $count = 0;

      foreach ($this->getSpectra($context) as $spectra) {
        $h = round($count++ * 255);
        $s = round((abs(50 - $spectra['value']) / 100) * 200) . '%';
        $l = round(((abs(100 - $spectra['value']) / 100) * 50) + 40) . '%';
        $colors[] = sprintf('hsla(%s, %s, %s, 0.35)', $h, $s, $l);
      }

      return sprintf($color, implode(',', $colors));
    }

    public function setAbstract(\DOMElement $context, array $abstract)
    {
      if ($abstract['@']['content'] == 'description' && empty($abstract['CDATA'])) {
        $context->setAttribute('content', 'description');
        // throw new \UnexpectedValueException("Please add a description", 400);
      }
      return parent::setAbstract($context, $abstract);
    }

    public function getTypes(\DOMElement $context)
    {
      $types = [];

      foreach ($context->find("edge[@type]") as $edge) {
        if ($edge->getAttribute('vertex') === 'A') return "show";
        $types[] = $edge->getAttribute('type');
      }

      return implode(' ', array_unique($types, SORT_STRING));
    }

    public function getAward(\DOMElement $context)
    {
      $award = $context->find("edge[@type='award']");

      if ($award->count() > 0) {
        $edge = $award->pick(0);
        $competition = new Competition($edge['@vertex']);
        $html = "<strong>{$edge->nodeValue}</strong><span>{$competition->title}</span>";
        return new \bloc\types\Dictionary(['title' => $edge->nodeValue, 'competition' => $competition, 'html' => $html]);
      }
    }

    public function getExtra(\DOMElement $context)
    {
      return isset($this->extra) ? $this->extra : null;
    }

    public function getDuration(\DOMElement $context)
    {
      return $this->media['size']['audio'] > 0 ? timecode($this->media['audio'][0]->mark) : 0;
    }

    public function getImage(\DOMElement $context)
    {
      if ($image = $this->media['image'][0]) {
        // \bloc\application::instance()->log($image['domain']);
        return $image;
      }
    }

    public function getProducers(\DOMElement $context)
    {
      return $context->find("edge[@type='producer' and @vertex!='A']")->map(function($edge) {
        return ['person' => Graph::FACTORY(Graph::ID($edge['@vertex'])), 'role' => 'Producer'];
      });
    }

    public function getProducer(\DOMElement $context)
    {
      return preg_replace('/(.*),/','$1 and', implode(', ', array_map(function($item) {
        return $item['person']->title;
      }, iterator_to_array($this->producers))));
    }

    public function getPresenter(\DOMElement $context)
    {
      return preg_replace('/(.*),/','$1 and', implode(', ', array_map(function($item) {
        return $item['person']->title;
      }, iterator_to_array($this->presenters))));
    }

    public function getPresenters(\DOMElement $context)
    {
      $presenters = $context->find("edge[@type='presenter']");
      $count = $presenters->count();
      return $presenters->map(function($edge) use ($count){
        return ['person' => new Person($edge['@vertex']), 'role' => 'Presenter', 'count' => $count];
      });
    }

    public function getPlaylists(\DOMElement $context)
    {
      return $context->find("edge[@type='item']")->map(function($collection) {
        return ['collection' => new Collection($collection['@vertex'])];
      });
    }

    public function getExtras(\DOMElement $context)
    {
      return $context->find("edge[@type='extra']")->map(function($extra) {
        return ['article' => new Article($extra['@vertex'])];
      });
    }

    public function getCompetitions(\DomElement $context)
    {
      return $context->find("edge[@type='participant']")->map(function($extra) {
        return ['competition' => new Competition($extra['@vertex'])];
      });
    }

    public function getHappenings(\DomElement $context)
    {
      return $context->find("edge[@type='session']")->map(function($extra) {
        return ['happening' => new Happening($extra['@vertex'])];
      });
    }

    public function getRecommended(\DOMElement $context)
    {
      $correlation = \controllers\Task::pearson($context['@id'])->best;
      arsort($correlation);
      
      return (new \bloc\types\Dictionary(array_keys(array_slice($correlation, 0, 3, true))))->map(function($id) {
       return ['item' => Graph::FACTORY(Graph::ID($id))];
      });
    }

  }
