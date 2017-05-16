<?php

  namespace models;
/*
 * Broadcast
 */

class Article extends Vertex
{
  use traits\banner, traits\periodical;
  public $_premier = "Publication Date";
  static public $fixture = [
    'vertex' => [
      'abstract' => [
        [
          'CDATA' => '',
          '@' => [
            'content' => 'description'
          ]
        ]
      ]
    ]
  ];

  protected $edges = [
    'producer' => ['person'],
    'extra'    => ['feature', 'broadcast'],
    'item'     => ['competition'],
    'page'     => ['collection', 'competition', 'happening'],
  ];

  public function __construct($id = null, $data =[])
  {
    $this->template['form'] = 'vertex';
    $this->template['upload'] = 'audio-image';
    parent::__construct($id, $data);

    if ($this->context['premier']->count() < 1) {
      $this->context->getFirst('premier')->setAttribute('date', $this->context["@created"]);
    }
  }

  public function getFeatures(\DOMElement $context)
  {
    return $context->find("edge[@type='extra']")->map(function($extra) {
      return ['feature' => new Feature($extra['@vertex'])];
    });
  }

  public function getSuffix(\DOMElement $context)
  {
    \bloc\application::instance()->log();
    return substr(strip_tags($this->title), 18);
  }
  
  public function getSections(\DOMElement $context) 
  {
    // 'sectionize' text on h2 elements.
    $object    = \bloc\dom\document::ELEM("<div>{$this->content->description}</div>");
    $current   = $object->firstChild;
    $out = [];
    while ($current) {
      if ($current->nodeName == 'h2') {
        $out[] = ['title' => $current->nodeValue, 'content' => ''];
      } else {

        $out[count($out)-1]['content'] .= $current->write();
      }
      
      $current = $current->nextSibling;
    }
    
    
    return $out;
  }

}
