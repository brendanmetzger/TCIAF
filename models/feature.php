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
          '@' => ['F'=>50,'S'=>50,'M'=>50,'R'=>50,'P'=>50,'T'=>50,'A'=>50]
        ]
      ]
    ];
    
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
    
    public function getGradient(\DOMElement $context)
    {
      $color = '-webkit-linear-gradient(left, %s);';
      $count = 0;
      
      foreach ($this->getSpectra($context) as $spectra) {
        $h = round($count++ * 255);
        $s = round((abs(50 - $spectra['value']) / 100) * 200) . '%';
        $l = round(((abs(100 - $spectra['value']) / 100) * 50) + 40) . '%';
        $colors[] = sprintf('hsla(%s, %s, %s, 0.35)', $h, $s, $l);
      }

      return sprintf($color, implode(',', $colors));
    }
    
    public function getAudio(\DOMElement $context)
    {
      static $audio = null;

      if ($audio === null) {
        $media = $context['media'];
        foreach ($media as $item) {
          if ($item['@type'] === 'audio') {
            $audio = new Media($item);
            break;
          }
        }
        
        if ($audio === null) {
          $audio = new \bloc\types\Dictionary(['message' => "No Track Added"]);
        }
      }
      return $audio;
    }
    
    public function getDescription(\DOMElement $context)
    {
      $this->parseText($context);
      return $this->description;
    }
    
    public function getExtra()
    {
      $this->parseText($context);
      return $this->extra;
    }
    
    public function getSummary(\DOMElement $context)
    {
      return substr(strip_tags($this->description), 0, 100) . '...';
    }
    
  }