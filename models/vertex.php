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

  public $template = ['form' => null, 'digest' => null, 'card' => null, 'index' => null, 'upload' => 'image'];

  static public $fixture = [
    'vertex' => [
      '@' => ['id' => null, 'title' => '', 'created' => '', 'updated' => '', 'mark' => 0],
      'abstract' => [
        [
          'CDATA'  => '',
          '@' => ['content' => 'description']
        ]
      ],
      'location' => [
        'CDATA' => ''
      ],
      'premier' => [
        'CDATA' => '',
        '@' => [
          'date' => null
        ]
      ],
      'media' => [],
      'edge'  => [],
    ]
  ];

  public function setIdAttribute(\DOMElement $context, $id)
  {
    if (empty($id)) {
      $id = substr($this->_model, 0, 1) . '-' . uniqid();
    }
    $context->setAttribute('id', $id);
  }

  public function getTitle(\DOMNode $context)
  {
    return strip_tags((new \Parsedown())->text(trim($context->getAttribute('title'))) , '<em><strong>');
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
    return true;
  }

  public function getAbstract(\DOMElement $context, $parse = true)
  {
    if ($context['abstract']->count() < 1) {
      return [[
       'type' => static::$fixture['vertex']['abstract'][0]['@']['content'],
       'index' => 0,
       'text' => '',
       'required' => 'required',
      ]];
    }

    return $context['abstract']->map(function($abstract) use($parse){

			$path = PATH . $abstract->getAttribute('src');
			$content = file_exists($path) ? file_get_contents($path) : null;

      return [
       'type' => $abstract->getAttribute('content'),
       'index' => $abstract->getIndex(),
       'text' => $parse ? (new \Parseup($content))->output() : $content,
      ];
    });
  }

  public function getSummary(\DOMElement $context)
  {
		$abstract = $this->getAbstract($context, false);
		if (!is_object($abstract)) return;
    if ($node = \bloc\DOM\Document::TAG("<root>{$abstract->current()['text']}</root>")) {
      if ($node->childNodes->length > 0) {
        $len = strlen($node->firstChild->nodeName) + 2;
        return substr($node->firstChild->write(), $len, -($len + 1));
      }

    }
  }

  public function setEdge(\DOMElement $context, $value)
  {
    $atts  = $value['@'];
    $eid   = $context->parentNode['@id'];
    $ref   = Graph::ID($atts['vertex']);

    $type =  $atts['type'] ?: $context['@type'];
    $edges = $ref->find("edge[@vertex='{$eid}' and @type='{$type}']");

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

    // Check for a query string - due to latency involved with Elastic Transcoding, we
    // don't set transcoded url until after a save. It is tacked on as a query parameter until then.
    if ($pending = parse_url($media['@']['src'], PHP_URL_QUERY)) {
      $media['@']['src'] = $pending;
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

  public function GETpermalink(\DOMNode $context)
  {
    return "/explore/detail/{$context['@id']}";
  }
}
