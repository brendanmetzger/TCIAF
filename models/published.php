<?php

namespace models;

/**
  * Published
  *
  */
  class Published extends Model
  {
    const NAME = 'published';
    
    static public $fixture = [
      'token' => [
        'location' => [
          'CDATA' => ''
        ],
        'premier' => [
          'CDATA' => '',
          '@' => [
            'date' => 0
          ]
        ],
        'spectra' => [
          'CDATA' => 'F=50&S=50&M=50&G=50&R=50&P=50&T=50&A=50'
        ]
      ]
    ];
    
    public function setSpectra(\DOMElement $context, array $spectrum)
    {
      // input comes in as an array from all the slider elements
      $context->setNodeValue(http_build_query($spectrum));      
    }
    
    public function getSpectra(\DOMElement $context)
    {
      parse_str($context->getFirst('spectra')->nodeValue ?: $this::$fixture['token']['spectra']['CDATA'], $spectra);
     return Token::storage()->find('/tciaf/config/spectra')->map(function($item) use($spectra) {
        return ['item' => $item, 'title' => $item->nodeValue, 'value' => $spectra[$item['@id']]];
      });
    }
    
    public function getAudio(\DOMElement $context)
    {
      static $audio = null;
      if ($audio === null) {
        $media = $context['media'];
        foreach ($media as $item) {
          if ($item['@type'] === 'audio') {
            $audio = new \bloc\types\Dictionary(['src' => $item['@src'], 'type' => 'audio', 'index' => \bloc\registry::index()]);
            break;
          }
        }
      }
      return $audio;
    }
  }