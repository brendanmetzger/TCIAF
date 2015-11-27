<?php
namespace models;

/**
  * Happening
  *
  */

  class Happening extends Model
  {
    use traits\navigation;
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
      'host'        => ['person', 'organization'],
      'presenter'   => ['person'],
      'participant' => ['person'],
      'session'     => ['feature'],
      'edition'     => ['happening'],
      'page'        => ['article'],
      'playlist'    => ['collection'],
    ];

    public function __construct($id = null, $data =[])
    {
      $this->template['form'] = 'vertex';
      parent::__construct($id, $data);
    }

    public function getSessions(\DOMElement $context)
    {
      return $context->find("edge[@type='session']")->map(function($edge) {
        return ['session' => new Feature($edge['@vertex'])];
      });
    }

    public function getParticipants(\DOMElement $context)
    {
      return $context->find("edge[@type='participant']")->map(function($edge) {
        return ['person' => new Person($edge['@vertex'])];
      });
    }

    public function getEditions(\DOMElement $context)
    {
      return $context->find("edge[@type='edition']")->map(function($edge) {
        $happening = new Happening($edge['@vertex']);
        preg_match('/^([0-9]{4})\s*(.*)$/i', $happening['title'], $result);
        return ['edition' => $happening, 'year' => $result[1]];
      });
    }

    public function getConferences()
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
