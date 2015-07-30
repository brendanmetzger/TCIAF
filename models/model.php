<?php
namespace models;

abstract class Model extends \bloc\Model
{
  public $context = null,
         $errors  = [],
         $form    = null;
         
    
  
  static public $fixture = [
    'vertex' => [
      '@' => ['id' => null, 'title' => '', 'created' => '', 'updated' => '', 'mark' => 0],
      'abstract' => [
        [
          'CDATA'  => '',
          '@' => ['content' => 'description']
        ]
      ],
      'media' => [],
      'edge'  => [],
    ]
  ];
  
  public function save()
  {
    $filepath = PATH . Graph::DB . '.xml';
    $this->setUpdatedAttribute($this->context);
    if (empty($this->errors) && Graph::instance()->storage->validate() && is_writable($filepath)) {
      return Graph::instance()->storage->save($filepath);
    } else {
      
      $this->errors = array_merge(["Did not save"], $this->errors, array_map(function($error) {
        return $error->message;
      }, Graph::instance()->storage->errors()));

      return false;
    }
  }
  
  public function mergeInput($input, \DOMElement $context)
  {
    $element = key($input);
    $pending_removal = $pending_reorder = [];
    foreach ($input[$element] as $key => $value) {
      if (empty($value)) {
        continue;
      } else if ($key === '@') {
        /*
          TODO consider about empty values
        */
        $this->setAttributes($value, $context);
        
      } else if ($key === 'CDATA') {
        
        $this->{"set{$element}"}($context, $value);
        
      } else if (is_int($key)) {
        // if the key is an integer, we have an array of elements to add/update. If the set(Element)
        // method returns false, we add add the found/created element to a list of nodes to remove at the
        // completion of this routine -- ie. return false to delete the context node.
        $subcontext = $context->parentNode->getFirst($element, $key);

        if ($this->{"set{$element}"}($subcontext, $input[$element][$key]) === false) {
          $pending_removal[] = $subcontext;
       } else {
         // Appending the $subcontext ensures that the order remains the order provided by the input mechanism.
         $pending_reorder[] = $subcontext;
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
      if ($element->parentNode) {
        $element->parentNode->removeChild($element);
      }
    }
    
    foreach ($pending_reorder as $element) {
      $element->parentNode->appendChild($element);
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
      $id = substr($this->_model, 0, 1) . '-' . uniqid();
    }
    
    $context->setAttribute('id', $id);
  }
  
  public function getTitle(\DOMNode $context)
  {
    return strip_tags((new \Parsedown())->text($context->getAttribute('title')) , '<em><strong>');
  }
    
  
  public function setUpdatedAttribute(\DOMElement $context)
  {
    $context->setAttribute('updated',  (new \DateTime())->format('Y-m-d H:i:s'));
  }
  
  public function setAbstract(\DOMElement $context, array $abstract)
  {
    if (empty($abstract['CDATA'])) return false;
    
    $src = 'data/abstracts/' .$context->parentNode->getAttribute('id') . '-' . $context->getIndex() . '.html';
    $url = Graph::instance()->storage->createAttribute('src');
    $url->appendChild(Graph::instance()->storage->createTextNode($src));
    $context->setAttributeNode($url);
    $context->setAttribute('content', $abstract['@']['content']);

    $markdown = new \Parsedown();
    
    file_put_contents(PATH . $src, $markdown->text($abstract['CDATA']));
  }
  
  public function getAbstract(\DOMElement $context)
  {
    
    if ($context['abstract']->count() < 1) {
      return [[
       'type' => static::$fixture['vertex']['abstract'][0]['@']['content'],
       'index' => 0,
       'text' => '', 
      ]];
    }
    
    return $context['abstract']->map(function($abstract) {

      $content = file_get_contents(PATH . $abstract->getAttribute('src'));
      return [
       'type' => $abstract->getAttribute('content'),
       'index' => $abstract->getIndex(),
       'text' => (new \Parseup($content))->output(), 
      ];
    });
  }
  
  
  
  public function setEdge(\DOMElement $context, $value)
  {
    $atts  = $value['@'];
    $eid   = $context->parentNode['@id'];
    $ref   = Graph::ID($atts['vertex']);
    $edges = $ref->find("edge[@vertex='{$eid}' and @type='{$atts['type']}']");

    $connect = $edges->count() > 0 ? $edges->pick(0) : $ref->appendChild(Graph::instance()->storage->createElement('edge'));

    if (empty($atts['type'])) {
      $ref->removeChild($connect);
      return false;
    }
    
    
    

    
    $context->setAttribute('type',  $atts['type']);
    $connect->setAttribute('type', $atts['type']);
    
    $context->setAttribute('vertex', $atts['vertex']);
    $connect->setAttribute('vertex', $eid);
      
    if (array_key_exists('CDATA', $value)) {
      $context->nodeValue = $value['CDATA'];
      $connect->nodeValue = $value['CDATA'];
    }
  }
  
  public function setMedia(\DOMElement $context, $media)
  {
    if (empty($media['@']['src'])) {
      return false;
    }

    $context->setAttribute('src',  $media['@']['src']);
    $context->setAttribute('type', $media['@']['type']);
    $context->setAttribute('mark', $media['@']['mark']);
    if (array_key_exists('CDATA', $media)) {
      $context->nodeValue = $media['CDATA'];
    }
  }
  
  public function getMedia(\DomElement $context)
  {
    $media = [
      'audio' => [],
      'image' => [],
    ];
    
    foreach ($context['media'] as $item) {
      $media[$item['@type']][] = new Media($item);
    }
        
    return new \bloc\types\Dictionary($media);
  }
  
  
  public function getStatus($context)
  {
    $created  = strtotime($context['@created']);
    $updated  = strtotime($context['@updated']);
    $response = [];

    if (!empty($this->errors)) {
      $response['text']    = "Did not save";
      $response['type']    = 'alert';
      $response['errors']  = array_map(function($error) {
        return ['message' => $error];
      }, $this->errors);
    } else if ($created != $updated) {
      $recent  = (time() - $updated) < 5;
      $response['text'] =  $recent ? "Just Saved" : "Last Edited " . round((time() - $updated) / (24 * 60 * 60), 1) . " days ago.";
      $response['type']  = $recent ? 'success' : 'info';
    } else {
      $response['text'] = "Creating new {$this->get_model()}";
      $response['type'] = 'info';
    }
    return new \bloc\types\Dictionary($response);
  }
  
  
  
  public function __construct($id = null, $data = [])
  {
    $slugs = [];
    if ($id !== null) {
      if ($id instanceof \DOMElement) {
        $this->context = $id;
      } else {
        $this->context = Graph::ID($id);
      }
    } else {
      $this->context = Graph::instance()->storage->createElement('vertex', null);
      $slugs['vertex']['@']['created'] = (new \DateTime())->format('Y-m-d H:i:s');
      Graph::group($this->get_model())->pick('.')->appendChild($this->context);
    }
    
    static::$fixture = array_replace_recursive(self::$fixture, static::$fixture);

    if (!empty($data)) {
      try {
        static::$fixture = array_replace_recursive($data, $slugs);
        $this->mergeInput(static::$fixture, $this->context);
        
      } catch (\UnexpectedValueException $e) {
        $this->errors[] = $e->getMessage();
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
    $this->{$property} = $this->{"get{$property}"}($this->context);
    return $this->{$property};
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
          if (array_key_exists('type', $parts)) {
            $this->{"{$action}ReferenceEdge"}($ref_id, $this['@id'], $parts['type'], $parts['caption']);
          }
        }
      } 
    }
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
  
  protected function parseText($context)
  {
    foreach ($context->getElementsByTagName('abstract') as $abstract) {
      $this->{$abstract->getAttribute('content')} = $stuff = file_get_contents(PATH . $abstract->getAttribute('src')) ?: null;
    }
  }
  
  public function getEdges($context)
  {
    return $context['edge']->map(function($edge) {
      return [ 'vertex' => Graph::factory(Graph::ID($edge['@vertex'])), 'edge' => $edge, 'index' => $edge->getIndex(), 'process' => 'keep'];
    });
  }
  
  public function getStructure($context)
  {
    $has  = array_keys($this->references['has']);
    $acts = array_keys($this->references['acts']);
    // item / curator
    $output = [
      'has' => [],
      'acts' => []
    ];
    
  
    foreach ($this->references as $dir => $types) {
      foreach ($types as $type => $models) {
        if (!array_key_exists($type, $output[$dir])) {
          $output[$dir][$type] = [];
        }
        
        foreach ($models as $model) {
          if (! array_key_exists($model, $output[$dir][$type])) {
            $output[$dir][$type][$model] = [];
          }
        }
        
      }
    }
    
    foreach ($context['edge'] as $edge) {
      $type = $edge['@type'];
      $vertex = Graph::factory(Graph::ID($edge['@vertex']));
      $dir = in_array($type, $has) ? 'has' : 'acts';

      $output[$dir][$type][$vertex->_model][] = ['vertex' => $vertex, 'edge' => $edge, 'index' => $edge->getIndex(), 'process' => 'keep'];
      
    }
    
    
    $out = ['has' => [], 'acts' => []];
    foreach ($output as $dir => $types) {
      foreach ($types as $type => $models) {
        $b = ['name' => $type, 'items' => []];
        
        foreach ($models as $model => $items) {
          $b['items'][] = ['name' => $model, 'type' => $type, 'items' => $items];
        }
        
        $out[$dir][] = $b;
      }

    }
    
  
    return new \bloc\types\Dictionary($out);
  }
  
}