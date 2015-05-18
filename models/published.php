<?php

namespace models;

/**
  * Published
  *
  */
  class Published extends Model
  {    
    static public $fixture = [
      'vertex' => [
        'location' => [
          'CDATA' => ''
        ],
        'premier' => [
          'CDATA' => '',
          '@' => [
            'date' => null
          ]
        ],
        'spectra' => [
          'CDATA' => 'F=50&S=50&M=50&G=50&R=50&P=50&T=50&A=50'
        ]
      ]
    ];
    
    public function setSpectra(\DOMElement $context, $spectrum)
    {
      if (is_array($spectrum)) {
        $spectrum = http_build_query($spectrum);
      }
      // input comes in as an array from all the slider elements
      $context->setNodeValue($spectrum);      
    }
    
    public function getSpectra(\DOMElement $context)
    {
      parse_str($context->getFirst('spectra')->nodeValue ?: $this::$fixture['vertex']['spectra']['CDATA'], $spectra);
      return Token::storage()->find('/tciaf/config/spectra')->map(function($item) use($spectra) {
        return ['item' => $item, 'title' => $item->nodeValue, 'value' => $spectra[$item['@id']]];
      });
    }
    
    public function getAudio(\DOMElement $context)
    {
      static $audio = null;
      if ($audio === null) {
        $audio = new \bloc\types\Dictionary(['index' => \bloc\registry::index()]);
        $media = $context['media'];
        foreach ($media as $item) {
          if ($item['@type'] === 'audio') {
            $audio['src'] = $item['@src'];
            $audio['type'] = 'audio';
            break;
          }
        }
      }
      return $audio;
    }
    
    public function setMedia(\DOMElement $context, array $media)
    {
      $container = $context->parentNode; 
      $cloneable = $container->removeChild($context);
      
      foreach ($media as $item) {
        if (empty($item['@']['src'])) {
          continue;
        }
        
        $clone = $container->appendChild($cloneable->cloneNode());
        $clone->setAttribute('src', $item['@']['src']);
        $clone->setAttribute('type', $item['@']['type']);
        
        
      }
    }
    
  }