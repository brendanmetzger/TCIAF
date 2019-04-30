<?php
namespace models;

abstract class Vertex extends \bloc\Model
{
  use traits\resolver;

  // The Location and premier fields are capapable of storing slightly
  // different data based on context. These properties are just for templates
  // to show text for the user - can be overridden in child models.
  public $_location = 'Link';
  public $_premier  = 'Date';

  protected $_help = [];

  public $template = ['form' => null, 'digest' => null, 'card' => null, 'index' => null, 'upload' => 'image'];

  static public $fixture = [
    'vertex' => [
      '@' => ['id' => null, 'title' => '', 'created' => '', 'updated' => '', 'text' => 'description'],
      'media' => [],
      'img' => [],
      'audio' => [],
      'edge'  => [],
    ]
  ];

  public function getHelp(\DOMElement $context)
  {
    $markdown = new \vendor\Parsedown;
    $markdown->setBreaksEnabled(true);
    $help = array_map(function($text) use ($markdown){
      return ['markdown' => $markdown->text($text), 'plain' => $text] ;
    }, $this->_help);
    return new \bloc\types\Dictionary($help);
  }
  
  public function getCreated(\DOMElement $context)
  {
    return empty($context['@created']) ? time() : \models\graph::INTID($context['@created']);
  }

  public function setIdAttribute(\DOMElement $context, $id)
  {
    $flag = 'pending-';
    if (empty($id)) {
      $id = $flag . uniqid();
    } else if (substr($id, 0, strlen($flag)) === $flag) {
      $size = $context->ownerDocument->documentElement->getAttribute('serial') + 1;
      $context->ownerDocument->documentElement->setAttribute('serial', $size);
      $id = Graph::ALPHAID($size);
    }

    $context->setAttribute('id', $id);
  }

  public function getTitle(\DOMNode $context)
  {
    $parsedown = new \vendor\Parsedown;
    return strip_tags($parsedown->text(trim($context->getAttribute('title'))) , '<em><strong>');
  }

  public function setUpdatedAttribute(\DOMElement $context)
  {
    $context->setAttribute('updated',  Graph::ALPHAID(time()));
  }

  public function setTextAttribute(\DOMElement $context, $abstract)
  {
    if (! is_array($abstract)) {
      $abstract = array_fill_keys(explode(' ', $abstract), '');
    }
    
    foreach ($abstract as $type => $text) {
      if ($text == '') continue;
      if ($context['@mark'] != 'html') {
        $markdown = new \vendor\Parsedown;
        $markdown->setBreaksEnabled(true);
        $text = $markdown->text($text);
      }
      
      file_put_contents(PATH . "data/text/{$type}/{$context['@id']}.html", $text);      
    }
    
    $context->setAttribute('text', implode(' ', array_keys($abstract)));
    
  }

  public function getAbstract(\DOMElement $context, $parse = true)
  {
    $abstracts = new \bloc\types\dictionary(explode(' ', $context['@text']));
    if ($abstracts->count() < 1) {

      return [[
       'type' => strtolower(static::$fixture['vertex']['@']['text'] ?? 'description'),
       'index' => 0,
       'text' => '',
       'required' => 'required',
      ]];
    }
    
    

    return $abstracts->map(function($type, $idx) use($parse, $context){

			$path = trim(PATH . "data/text/{$type}/{$context['@id']}.html");

			$content = file_exists($path) ? file_get_contents($path) : null;

      $text = $parse && $context['@mark'] != 'html' ? (new \vendor\Parseup($content))->output() : ($parse ? htmlentities($content) : $content);
    
      return [
       'type'    => $type,
       'index'   => $idx,
       'text'    => $text,
      ];
    });
  }

  public function getSummary(\DOMElement $context)
  {
		$abstract = $this->getAbstract($context, false);
		if (!is_object($abstract)) return;
    if ($node = \bloc\DOM\Document::ELEM("<root>{$abstract->current()['text']}</root>")) {
      if ($node->childNodes->length > 0) {
        $len = strlen($node->firstChild->nodeName) + 2;
        return substr($node->firstChild->write(), $len, -($len + 1));
      }

    }
  }

  public function getLocked(\DOMElement $context)
  {
    return $context['@sticky'] ?: 'no';
  }

  public function getBody(\DOMElement $context)
  {
		$abstract = $this->getAbstract($context, false);
		if (!is_object($abstract)) return;
    /*
      TODO should return first paragraph, not first element
    */
    if ($node = \bloc\DOM\Document::ELEM("<root>{$abstract->current()['text']}</root>")) {
      if ($node->childNodes->length > 0) {
        \bloc\application::instance()->log($node->firstChild->write());
        $len = strlen($node->firstChild->nodeName) + 2;
        return substr($node->firstChild->write(), $len, -($len + 1));
      }

    }
  }

  public function setEdge(\DOMElement $context, $value)
  {
    $atts = $value['@'];
    $eid  = $context->parentNode['@id'];
    $ref  = Graph::ID($atts['vertex']);
    $type =  $atts['type'] ?: $context['@type'];
    
    // find connected edges
    $connected = $ref->find("edge[@vertex='{$eid}' and @type='{$type}']");

    if (empty($atts['type'])) {
      foreach ($connected as $connection) {
        $ref->removeChild($connection);
      }
      return false;
    }
    
    $context->setAttribute('type',  $atts['type']);
    $context->setAttribute('vertex', $atts['vertex']);
    
    if (array_key_exists('CDATA', $value)) {
      $context->nodeValue = $value['CDATA'];
    }
    

    
    
    if ($context->parentNode->parentNode['@type'] != 'archive') {
      if ($connected->count() < 1) {
        $ref->appendChild($context->cloneNode(true))->setAttribute('vertex', $eid);
      }
    } else if ($connected->count() > 0) {
      foreach ($connected as $connection) {
        $ref->removeChild($connection);
      }
    }

  }

  public function setMedia(\DOMElement $context, $media)
  {
    if (empty($media['@']['src'])) {
      return false;
    }

    // Check for a query string - due to latency involved with Elastic Transcoding, we
    // don't set transcoded url until after a save. It is tacked on as a query parameter until then.
    if ($pending = parse_url($media['@']['src'], PHP_URL_QUERY)) {
      $media['@']['src'] = $pending;
    }

    $context->setAttribute('src',  $media['@']['src']);
    $context->setAttribute('type', $media['@']['type']);
    $context->setAttribute('mark', $media['@']['mark']);
    if (array_key_exists('CDATA', $media)  && strtolower($media['CDATA'] != 'a caption')) {
      $context->nodeValue = $media['CDATA'];
    }
  }

  public function getMedia(\DomElement $context)
  {
    $media = [
      'audio' => [],
      'image' => [],
      'size'  => [
        'image' => 0,
        'audio' => 0,
       ]
    ];

    foreach ($context['media'] as $node) {
      $item = new Media($node);
      $item->attach($this);
      $media[$node['@type']][] = $item;
      $media['size'][$node['@type']]++;
    }

    return new \bloc\types\Dictionary($media);
  }

  public function getStatus($context)
  {
    $created  = strtotime(Graph::INTID($context['@created']));
    $updated  = strtotime(Graph::INTID($context['@updated']));
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

  public function template($name)
  {
    return $this->template[$name] ?: $this->get_model();
  }

  protected function getContent($context)
  {
		$dict = [];

		foreach ($this->getAbstract($context, false) as $abstract) {
			$dict[$abstract['type']] = $abstract['text'];
    }
		return new \bloc\types\Dictionary($dict);
  }

  public function getEdges($context)
  {
    return $context['edge']->map(function($edge) {
      return [ 'vertex' => Graph::FACTORY(Graph::ID($edge['@vertex'])), 'edge' => $edge, 'index' => $edge->getIndex(), 'process' => 'keep'];
    });
  }

  public function getStructure(\DOMElement $context)
  {
    $output = [];

    foreach ($this->edges as $type => $models) {
      $output[$type] = [
        'priority' => 'normal',
        'items'    => [],
      ];

      foreach ($models as $model) {
        $output[$type]['items'][$model] = [];
      }
    }

    foreach ($context['edge'] as $edge) {
      $type   = $edge['@type'];
      $vertex = Graph::FACTORY(Graph::ID($edge['@vertex']));

      if (! array_key_exists($type, $output)) {
        $output[$type] = [
          'priority' => 'low',
          'items'    => [],
        ];
      }

      $output[$type]['items'][$vertex->_model][] = ['vertex' => $vertex, 'edge' => $edge, 'index' => $edge->getIndex(), 'process' => 'keep'];


    }
    
    $out = [];

    foreach ($output as $type => $config) {
      $b = ['name' => $type, 'items' => [], 'priority' => $config['priority']];

      foreach ($config['items'] as $model => $items) {
        $b['items'][] = ['name' => $model, 'type' => $type, 'items' => $items];
      }

      $out[] = $b;
    }

    return $out;

  }

  public function getPermalink(\DOMElement $context)
  {
    return "/{$this->_model}/{$context['@key']}";
  }
  
  public function setKeyAttribute(\DOMElement $context, $value, $unique = '')
  {
    setlocale(LC_ALL, "en_US.utf8");
    $title = iconv('UTF-8', 'ASCII//TRANSLIT', $this->title);
    $find = [
      '/^[^a-z]*behind\W+the\W+scenes[^a-z]*with(.*)/i' => '$1-bts',
      '/(re:?sound\s+#\s*[0-9]{1,4}:?\s*|best\s+of\s+the\s+best:\s*)/i' => '',
      '/^the\s/i'    => '',
      '/^\W+|\W+$/'  => '',
      '/[^a-z\d\s]/i' => '',
      '/\s+/' => '-',
    ];
    $key =  strtolower(preg_replace(array_keys($find), array_values($find), $title));
    $context->setAttribute('key', $key);

  }
}
