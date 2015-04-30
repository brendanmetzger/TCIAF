<?php
namespace models;

abstract class Model extends \bloc\Model
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
      ],
      'media'   => [],
      'pointer' => [],
    ]
  ];
  
  public function __construct($id = null)
  {
    if ($id !== null) {
      if ($id instanceof \DOMElement) {
        $this->context = $id;
      } else {
        $this->context = Token::ID($id);
      }
    }
  }
  
  public function __call($method, $arguments)
  {
    $accessor = substr($method, 0, 3); // will be get or set
    $context = $arguments[0];
   
    if ($accessor == 'get') {
      return $context[substr($method,3)];
    } else {
      $value   = $arguments[1];
    
      if (substr($method, -9) == 'attribute') {
        $key = substr($method, 3, -9);
        $context->setAttribute($key, $value);
      } else {
        $context->setNodeValue($value);
      }
    }
  }
  
  public function __get($property)
  {
    return $this->{"get{$property}"}($this->context);
  }
  
  static public function create($instance, $data)
  {
    if ($instance->context === null) {
      $instance->context = Token::storage()->createElement('token', null);
      Token::storage()->pick('//group[@type="'.static::NAME.'"]')->appendChild($instance->context);
    }

    $data = array_replace_recursive(self::$fixture, static::$fixture, $data);

    $instance->mergeInput($data);
    if (Token::storage()->validate()) {
      return $instance;
    } else {
      $instance->errors = libxml_get_errors();
      return false;
    }
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
      if (empty($value)) continue;
      if ($node === '@') {
        $this->setAttributes($value, $context);
      } else if ($node === 'CDATA') {
        $this->{"set{$key}"}($context, $value);
      } else if (is_int($node)) {
        echo "deal with array of {$key} elements";
      } else {
        $this->mergeInput([$node => $value], $context->getFirst($node));
      }
    }
  }
  
  public function setAttributes(array $attributes, \DOMElement $context)
  {
    foreach ($attributes as $property => $value) {
      $this->{"set{$property}attribute"}($context, $value);
    }
  }  
  
  public function setUpdatedAttribute(\DOMElement $context, $value)
  {
    $context->setAttribute('updated', (new \DateTime())->format('Y-m-d H:i:s'));
  }
  
  public function setAbstract(\DOMElement $context, $value)
  {
    $context->setNodeValue(str_replace('↩↩' , '¶', preg_replace("/\r\n/", '↩', $value)));
  }
  
  public function getAbstract(\DOMElement $context)
  {
    return str_replace(["↩", "¶"], ["\n", "\n\n"], $context->getFirst('abstract')->nodeValue);
  }
  
  public function getStatus($context)
  {
    $updated = strtotime($context['@updated']);
    $message = (time() - $updated) < 5 ? "Just Saved" : "Last Edited " . date('m/d/y g:ia', $updated);
    return new \bloc\types\Dictionary(['text' => $message, 'type' => 'success']);
  }
}