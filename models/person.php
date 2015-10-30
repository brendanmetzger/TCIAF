<?php
namespace models;

/**
 * Person
 */

class Person extends Model
{
  use traits\banner;
  
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

  protected $edges = [
    'producer'    => ['feature', 'broadcast', 'article'],
    'presenter'   => ['feature', 'happening'],
    'extra'       => ['article'],
    'judge'       => ['competition'],
    'curator'     => ['collection'],
    'participant' => ['feature'],
    'host'        => ['happening', 'competition', 'feature'],
    'staff'       => ['organization'],
    'sponsor'     => ['happening', 'competition'],
  ];
  
  public function __construct($id = null, $data =[])
  {
    $this->template['form'] = 'vertex';
    parent::__construct($id, $data);
  }
    
  public function authenticate($password)
  {
    if (! password_verify($password, $this->context->getAttribute('hash'))) {
      throw new \InvalidArgumentException("Might I ask you to try once more?", 1);
    }
    return $this->context;
  }
  
  public function authorize()
  {
    /*
      TODO need a system to check for role of staff or contributor.
    */
  }
  
  public function setIdAttribute(\DOMElement $context, $value)
  {
    
    if (empty($value)) {
      $value = 'p-' . preg_replace('/[^a-z0-9]/i', '', static::$fixture['vertex']['@']['title']);
    }
    
    if (empty($value)) {
      $this->errors[] = "Name Invalid, either doesn't exist, or is not unique enough.";
      throw new \RuntimeException($message, 1);
    }

    $context->setAttribute('id', $value);
    
  }
  
  public function getHash($string)
  {
    return password_hash($string, PASSWORD_DEFAULT);
  }
  
  public function getBio(\DOMElement $context)
  {
    $this->parseText($context);
    return isset($this->bio) ? $this->bio : null;
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
  
  public function getContributions(\DOMElement $context)
  {
    $out = [];
    foreach ($this->edges as $key => $groups) {
      $items = $context->find("edge[@type='{$key}']");
      if ($items->count() > 0) {
        $out[] = [
          'name' => $key,
          'items' => $items->map(function($collection) {
            $item = Graph::factory(Graph::ID($collection['@vertex']));
            if ($collection['@vertex'] === "TCIAF") {
              $overview = "/overview/tciaf";
            } else if ($item->_model == 'competition') {
              $overview = '/overview/competition/' . $collection['@vertex'];
            } else if ($item->_model == 'happening') {
              $overview = "/overview/conference/{$collection['@vertex']}";
            } else {
              $overview = "/explore/detail/{$collection['@vertex']}";
            }
            return ['item' => $item, 'overview' => $overview];
          }),
        ];
      }
    }
    return $out;
  }
}