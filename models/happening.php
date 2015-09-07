<?php
namespace models;

/**
  * Happening
  *
  */

  class Happening extends Model
  {
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
      'host'        => ['person'],
      'presenter'   => ['person'],
      'participant' => ['person'],
      'extra'       => ['article'],
      'session'     => ['feature'],
      'edition'     => ['happening'],
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
    
    public function getParticipantsAlpha()
    {
      return $this->participants->sort(function($a, $b) {
        return ucfirst(ltrim($a['person']->title, "\x00..\x2F")) > ucfirst(ltrim($b['person']->title, "\x00..\x2F"));
      });
    }
  }