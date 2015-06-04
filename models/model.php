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
        // we have an entire element, that can have elements, attributes, etc, so merge that.
        // Be extremely careful here - this will create an element and add to the document. it's up to
        // you to ensure that if you are going to be inserting an array of elems (see is_int($node) above)
        // that you make sure your array index starts at zero. If you don't, you will probably have an 
        // empty node inserted into the document, and this will likely cause a validation error.
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
  
  
  
  // Here are some specific setter/getters that are universal
  
  public function setIdAttribute(\DOMElement $context, $id)
  {
      
    if (empty($id)) {
      $id = 'pending:' . uniqid();
    } else if (strpos(strtolower($id), 'pending') === 0) {

      $id = str_replace('pending', substr($this->get_model(), 0, 1), $id);
    }

    $context->setAttribute('id', $id);
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
  
  public function getGroupTypes()
  {
    $dtd = Graph::instance()->getDTD('groups');
    $name = $this->get_model();
    preg_match('/ATTLIST group type \(([a-z\s|]+)\)/i', $dtd, $result);
    return (new \bloc\types\Dictionary(preg_split('/\s\|\s/i', $result[1])))->map(function($item) use($name){
      $ret = ['name' => $item];
      if ($name == $item) {
        $ret['disabled'] = true;
      }
      return $ret;
    });
  }
  
  public function getEdgeTypes()
  {
    $dtd = Graph::instance()->getDTD('groups');
    preg_match('/ATTLIST edge type \(([a-z\s|]+)\)/i', $dtd, $result);
    return (new \bloc\types\Dictionary(preg_split('/\s\|\s/i', $result[1])))->map(function($item) {
      return ['name' => $item];
    });
  }
}