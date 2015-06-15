<?php
namespace models;

abstract class Model extends \bloc\Model
{
  public $context = null,
         $errors  = [],
         $form    = null;
         
    
  
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
    $element = key($input);
    $pending_removal = [];
    
    foreach ($input[$element] as $key => $value) {
      
      if (empty($value)) {
        /*
          TODO consider if this is a good case of when we should be deleting a node. Could work for vertex or children.
        */
        continue;
      } else if ($key === '@') {
        
        /*
          TODO consider what happens when value is empty
        */
        $this->setAttributes($value, $context);
        
      } else if ($key === 'CDATA') {
        
        $this->{"set{$element}"}($context, $value);
        
      } else if (is_int($key)) {
        // if the key is an integer, we have an array of elements to add/update. If the set(Element)
        // method returns false, we add add the found/created element to a list of nodes to remove at the
        // completion of this routine -- ie. return false to delete the context node.
        $subcontext = $context->parentNode->getFirst($element, $key);
        echo $context->write(true);
        
        if ($this->{"set{$element}"}($subcontext, $input[$element][$key]) === false) {
          $pending_removal[] = $subcontext;
       }
      } else {
        // we have an entire element, that can have elements, attributes, etc, so merge that.
        // Be extremely careful here - this will create an element and add to the document. it's up to
        // you to ensure that if you are going to be inserting an array of elems (see is_int($key) above)
        // that you make sure your array index starts at zero. If you don't, you will probably have an 
        // empty node inserted into the document, and this will likely cause a validation error.
        $this->mergeInput([$key => $value], $context->getFirst($key));
      }
    }
    
    if (! $context->hasAttributes() && ! $context->hasChildNodes()) {
      $pending_removal[] = $context;
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
    $context->setAttribute('updated',  (new \DateTime())->format('Y-m-d H:i:s'));
  }
  
  public function setAbstract(\DOMElement $context, $value)
  {
    $context->setNodeValue(str_replace('↩↩' , '¶', preg_replace("/\r\n/", '↩', $value)));
  }
  
  public function getAbstract(\DOMElement $context)
  {
    static $abstract = null;
    
    if ($abstract === null) {
      $abstract = $context->getFirst('abstract');
      $abstract->setNodeValue(str_replace(["↩", "¶"], ["\n", "\n\n"], $abstract->nodeValue));
    }
    return $abstract;
  }
  
  public function setEdge(\DOMElement $context, $value)
  {
    // return false if the edge has no id
  }
  
  public function getStatus($context)
  {
    static $status = null;
    /*
      TODO Errors shall go here.
    */
    
    if ($status === null) {
      $created = strtotime($context['@created']);
      $updated = strtotime($context['@updated']);
      if ($created != $updated) {
        $recent = (time() - $updated) < 5;
        $message =  $recent ? "Just Saved" : "Last Edited " . round((time() - $updated) / (24 * 60 * 60), 1) . " days ago.";
        $type = $recent ? 'success' : 'info';
      } else {
        $message = "Creating new {$this->get_model()}";
        $type = 'info';
      }
      
      $status = new \bloc\types\Dictionary(['text' => $message, 'type' => $type]);
      
    }
    return $status;
  }
  
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
  
  public function getForm()
  {
    return $this->form ?: $this->get_model();
  }
  
  public function setReferencedEdges($edges)
  {
    
    /*
    TODO
    
    ultimately this will be simplified a great deal. The form data, after being set, can just
    be the proxy for the actual vertex's setEdge method. If the edge is new, it is created/appended. If it is missing
    data (return false from the setEdge method) it will be deleted from the vertex.
    */
    foreach ($edges as $action => $group) {
      foreach ($group as $ref_id => $edge) {
        foreach ($edge as $parts) {
          echo "<pre>";
          print_r($parts);
          if (array_key_exists('type', $parts)) {
            $this->{"{$action}ReferenceEdge"}($ref_id, $this['@id'], $parts['type'], $parts['caption']);
          }
        }
      } 
    }
    $this->save();
  }
  
  private function removeReferenceEdge($vertex_id, $edge_id, $edge_type)
  {
    $elem = Graph::instance()->query("graph/group/vertex[@id='{$vertex_id}']")->pick("/edge[@vertex='{$edge_id}' and @type = '{$edge_type}']");
    $elem->parentNode->removeChild($elem);
  }
  
  private function addReferenceEdge($vertex_id, $edge_id, $edge_type, $caption)
  {
    Graph::ID($vertex_id)->appendChild(Graph::EDGE($edge_id, $edge_type, $caption));
  }
}