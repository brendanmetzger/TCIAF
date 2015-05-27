<?php
namespace models;

abstract class Model extends \bloc\Model
{
  public $context = null,
         $errors  = [];
  
  static public $fixture = [
    'vertex' => [
      '@' => [
        'id'      => null,
        'title'   => '',
        'created' => '',
        'updated' => '',
        'mark'  => 0,
      ],
      'abstract' => [
        'CDATA'  => '',
        '@' => [
          'content' => 'description'
        ]
      ],
      'media'   => [],
      'edge' => [],
    ]
  ];
  
  public function __construct($id = null)
  {
    if ($id !== null) {
      if ($id instanceof \DOMElement) {
        $this->context = $id;
      } else {
        $this->context = Graph::ID($id);
      }
    } else {
      self::create($this);
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

      if (strtolower(substr($method, -9)) == 'attribute') {
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
  
  static public function create($instance, $data = [])
  {
    if ($instance->context === null) {
      $instance->context = Graph::instance()->storage->createElement('vertex', null);
      $data['vertex']['@']['created'] = (new \DateTime())->format('Y-m-d H:i:s');
      Graph::group($instance->get_model())->pick('.')->appendChild($instance->context);
    }

    static::$fixture = array_replace_recursive(self::$fixture, static::$fixture, $data);
    
    
    $instance->mergeInput(static::$fixture, $instance->context);

    return $instance;
  }
  
  public function save()
  {
    if (Graph::instance()->storage->validate()) {
      return Graph::instance()->storage->save(PATH . Graph::DB . '.xml');
    } else {
      $this->errors = Graph::instance()->storage->errors();

      return false;
    }
  }
  
  public function mergeInput($input, \DOMElement $context)
  {
    $key = key($input);
    $pending_removal = [];
    
    foreach ($input[$key] as $node => $value) {
      
      if (empty($value)) {
        /*
          TODO consider if this is a good case of when we should be deleting a node. Could work for vertex or children.
        */
        continue;
      } else if ($node === '@') {
        
        $this->setAttributes($value, $context);
        
      } else if ($node === 'CDATA') {
        
        $this->{"set{$key}"}($context, $value);
        
      } else if (is_int($node)) {
        
        $subcontext = $context->parentNode->getFirst($key, $node);

        if ($this->{"set{$key}"}($subcontext, $input[$key][$node]) === false) {
          $pending_removal[] = $subcontext;
       }
      } else {
        // we have an entire element, that can have elements, attributes, etc, so merge that
        $this->mergeInput([$node => $value], $context->getFirst($node));
      }
    }
    foreach ($pending_removal as $element) {
      $element->parentNode->removeChild($element);
    }
  }
  
  public function setAttributes(array $attributes, \DOMElement $context)
  {
    foreach ($attributes as $property => $value) {
      $this->{"set{$property}Attribute"}($context, $value);
    }
  }
  
  public function setIdAttribute(\DOMElement $context, $value)
  {
      
    if (empty($value)) {
      $value = 'pending';
    } else if (strtolower($value) === 'pending') {

      $value = substr($this->get_model(), 0, 1) . ':' . uniqid();
    }

    $context->setAttribute('id', $value);
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
    static $status = null;
    /*
      TODO Errors shall go here.
    */
        
    $updated = strtotime($context['@updated']);
    $message = (time() - $updated) < 5 ? "Just Saved" : "Last Edited " . date('m/d/y g:ia', $updated);
    return new \bloc\types\Dictionary(['text' => $message, 'type' => 'success']);
  }
}