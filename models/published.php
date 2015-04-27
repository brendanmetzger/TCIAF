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
        'media' => [
          '@' => [
            'src'  => '',
            'type' => 'audio'
          ]
        ],
        'spectrum' => [
          'CDATA' => 'F=0&S=50&M=50&G=50&R=50&P=50&T=50&A=50'
        ]
      ]
    ];
    
    public function setSpectrum(\DOMElement $context, array $spectrum)
    {
      // input comes in as an array from all the slider elements
      $context->setNodeValue(http_build_query($spectrum));      
    }
    
    public function getSpectrum(\DOMElement $context)
    {
      parse_str($context->nodeValue, static::$fixture['token']['spectrum']['CDATA']);
    }
  }