<?php

namespace models;

/**
  * Competition
  *
  */

  class Competition extends Vertex
  {
    use traits\banner, traits\sponsor, traits\periodical;

    public $_premier = "Archive Date";


    protected $_help = [
      'overview' => 'There are two competitions: **RHD/TCF** and the **ShortDocs Challenge**. Competitions are divided into years individually, and these are attached to one of the main two competitions via an edge labeled *edition* (see associations below). Competitions (as well as happenings) keep track of time, and can organize themselves in that regard. Because of this, they can appear in a preview state, which shows a template that is very informational in nature, and once their *archive date* has been surpassed, the template switches again to something resembling playlist. Templates will automatically organize winners and participants as necessary when switching modes. Watch this video to see some in-depth examples.',
      'premier' => 'Use this field to automatically set the page into archive mode. When the date is in the future, the page will be in preview mode.',
      'edges' => 'All competitions MUST have at least one edition. Main competitions (those on the landing page) will have many editions. The annual representation of each competition needs only ONE parent edition, which is the competition it is a part of. Articles attached as a page will show up as a jump navigation menu in preview mode. The winning features will show up as a playlist when the page goes into archive mode.',
      'extras' => 'When creating an extra textbox, the information provided will show in the sidebar as long as the page is in preview mode, and will go away in archive mode. Thus, it can be a good place to highlight important dates or provide a registration link (using markdown).'
    ];

    static public $fixture = [
      'vertex' => [
        '@' => ['text' => 'about']
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
      if ($this->editions->count() < 1) return;
      if ($this->editions->count() > 1) {
        return 'competition/overview';
      } else {
        $this->origin = $this->editions->current()['edition'];

        if ($context['premier']->count() > 0 && strtotime($context['premier']['@date']) > time()) {
          return 'competition/preview';
        }

        return 'competition/edition';
      }
    }

    public function getPermalink(\DOMElement $context)
    {
      return "/competition/{$context['@key']}";
    }
    
    public function getCompetitions()
    {
      return $this->editions->sort(function($a, $b) {
        return substr($b['edition']['title'], 0, 4) - substr($a['edition']['title'], 0, 4);
      });
    }

    public function getParticipants(\DOMElement $context)
    {
      return $context->find("edge[@type='participant']")->map(function($edge) {
        return ['item' => new Feature($edge['@vertex'])];
      });
    }

    public function getJudges(\DOMElement $context)
    {
      $judges = $context->find("edge[@type='judge']");

      return $judges->count() < 1 ? null : $judges->map(function($edge) {
        return ['person' => new Person($edge['@vertex'])];
      });
    }

    public function getAwards(\DOMElement $context)
    {
      $markdown = new \vendor\Parsedown;
      return $context->find("edge[@type='award']")->map(function($edge)  {
        $feature = new Feature($edge['@vertex']);
        $out = ['item' => $feature, 'award' => $edge->nodeValue ?: $feature['title'], 'id' => $edge['@vertex']];
        if ($edge->nodeValue) {
          $out['caption'] = "Winner of the {$this->title} {$edge->nodeValue} Award" ;
        }
        return $out;
      });
    }

  }
