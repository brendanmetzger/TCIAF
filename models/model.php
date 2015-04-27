<?php
namespace models;

abstract class Model
{
  public $context = null,
         $errors  = [];
  
  static public $fixture = [
    'token' => [
      '@' => [
        'id'      => null,
        'title'    => '',
        'created' => '',
        'updated' => '',
      ],
      'abstract' => [
        'CDATA'   => '',
        '@' => [
          'content' => 'description'
        ]
      ]
    ]
  ];
  
  public function __construct($id = null)
  {
    if ($id !== null) {
      $this->context = Token::ID($id);
    }
  }
  
  static public function create($instance, $data)
  {
    
    if ($instance->context === null) {
      $instance->context = Token::storage()->createElement('token', null);
      Token::storage()->pick('//group[@type="'.static::NAME.'"]')->appendChild($instance->context);
    }

    $data = array_replace_recursive(self::$fixture, static::$fixture, $data);

    $instance->mergeInput($data);
    
    return Token::storage()->validate() ? $instance : false;
  }
  
  public function save()
  {
    if (Token::storage()->validate()) {
      return Token::storage()->save(PATH . Token::DB . '.xml');
    } else {
      $this->errors = libxml_get_errors();
      return false;
    }
  }
  
  public function mergeInput($input, \DOMElement $decendant = null)
  {
    $key = key($input);

    $context = $decendant ?: $this->context;
    foreach ($input[$key] as $node => $value) {
      if ($node === '@') {
        $this->setAttributes($value, $context);
      } else if ($node === 'CDATA') {
        $this->{"set{$key}"}($context, $value);
      } else {
        $elem = $context->getFirst($node) ?: $context->appendChild(Token::storage()->createElement($node));
        $this->mergeInput([$node => $value], $elem);        
      }
    }
  }
  
  public function setAttributes(array $attributes, \DOMElement $context)
  {
    foreach ($attributes as $property => $value) {
      $this->{"set{$property}attribute"}($context, $value);
    }
  }
  
  public function __call($method, $arguments)
  {
    if ($method === 'save') {
      print_r($arguments);
      exit();
    }
    $context = $arguments[0];
    $value   = $arguments[1];
    
    if (substr($method, -9) == 'attribute') {
      $key = substr($method, 3, -9);
      $context->setAttribute($key, $value);
    } else {
      $context->setNodeValue($value);
    }
  }
  
  
  public function setUpdatedAttribute(\DOMElement $context, $value)
  {
    $context->setAttribute('updated', (new \DateTime())->format('Y-m-d H:i:s'));
  }
  
  public function setAbstract(\DOMElement $context, $value)
  {
    $context->setNodeValue(preg_replace("/\s?\n\s*/", "¶", $value));
  }
  
  public function getAbstract(\DOMElement $context)
  {
    return str_replace("¶", "\n\n", $context->nodeValue);
  }
}