<?php
namespace models;

/**
 * Person
 */

class Person extends Vertex  implements \bloc\types\authentication
{
  use traits\banner;

  public $_location = "Contact";
  public $_premier = "Date Joined";

  static public $fixture = [
    'vertex' => [
      'abstract' => [
        [
          'CDATA'  => '',
          '@' => ['content' => 'bio']
        ]
      ]
    ]
  ];

  static public function N2ID($name)
  {
    return strtolower(preg_replace('/\W/', '-', $name));
  }

  protected $edges = [
    'producer'    => ['feature', 'broadcast', 'article'],
    'presenter'   => ['feature', 'happening'],
    'extra'       => ['article'],
    'judge'       => ['competition'],
    'curator'     => ['collection'],
    'participant' => ['feature'],
    'host'        => ['happening', 'competition', 'feature'],
    'staff'       => ['organization'],
    'board'       => ['organization'],
  ];

  public function __construct($id = null, $data =[])
  {
    $this->template['form'] = 'vertex';
    parent::__construct($id, $data);
  }

  public function authenticate($token)
  {
    if (! password_verify($token, $this->context['@hash'])) {
      throw new \InvalidArgumentException("Credentials do not match", 1);
    }
    return $this->context;
  }

  public function getHash($string)
  {
    return password_hash($string, PASSWORD_DEFAULT);
  }

  public function getFeatures(\DOMElement $context)
  {
    $features = $context->find("edge[@type='producer']");
    if ($features->count() > 0) {
      return $features->map(function($collection) {
        return ['feature' => new Feature($collection['@vertex'])];
      });
    }
  }
  
  public function getLast(\DOMElement $context)
  {
    return implode(' ', array_slice(explode(' ', $context['@title']), 1));
  }

  public function getContributions(\DOMElement $context)
  {
    $out = [];
    foreach ($this->edges as $key => $groups) {
      $items = $context->find("edge[@type='{$key}']");
      if ($items->count() > 0) {
        $out[] = [
          'name' => $key,
          'items' => $items->map(function($edge) {
            return ['item' => Graph::FACTORY(Graph::ID($edge['@vertex'])), 'edge' => $edge];
          }),
        ];
      }
    }
    return $out;
  }
}
