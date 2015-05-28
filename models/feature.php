<?php

namespace models;

/**
  * Feature
  *
  */
  class Feature extends Model
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
          '@' => ['F'=>50,'S'=>50,'M'=>50,'G'=>50,'R'=>50,'P'=>50,'T'=>50,'A'=>50]
        ]
      ]
    ];
    
    public function setSpectra(\DOMElement $context, $spectrum)
    {
      if (is_array($spectrum)) {
        foreach ($spectrum as $key => $value) {
          $context->setAttribute($key, $value);
        }
      }
    }
    
    public function getSpectra(\DOMElement $context)
    {
      
      $spectra = $this::$fixture['vertex']['spectra']['@'];
      
      if ($spectrum = $context->getFirst('spectra')) {
        foreach ($spectrum->attributes as $attr) {
          $spectra[$attr->name] = $attr->value;
        }
      }
      
      return Graph::instance()->query('graph/config')->find('/spectra')->map(function($item) use($spectra) {
        return ['item' => $item, 'title' => $item->nodeValue, 'value' => $spectra[$item['@id']]];
      });
    }
    
    public function getAudio(\DOMElement $context)
    {
      static $audio = null;

      if ($audio === null) {
        $audio = new \bloc\types\Dictionary([]);
        $media = $context['media'];
        foreach ($media as $item) {


          if ($item['@type'] === 'audio') {
            $audio = new \bloc\types\Dictionary([
              'src'     => $item['@src'],
              'type'    => 'audio',
              'index'   => $item->getIndex(),
            ]);
            break;
          }
        }
      }
      return $audio;
    }

    public function getThumbnails(\DOMElement $context)
    {
      static $images = null;
      if ($images === null) {
        $media = $context['media'];
        $images = [];
        foreach ($media as $item) {
          if ($item['@type'] === 'image') {
            
            $images[] = [
              'index' => $item->getIndex(),
              'url'   => preg_replace('/^(feature-photos\/photos\/[0-9]+\/)(.*)$/i', '$1small/$2', $item['@src']),
              'src'   => $item['@src'],
              'type'  => 'image',
              'mark'  => 0,
              'caption' => $item->nodeValue,
            ];
          }
        }
      }
      return new \bloc\types\Dictionary($images);
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
  }