<?php
namespace models;

/**
 * Feature
 */

class Feature
{
  public $storage;
  
  public function __construct()
  {
    $this->storage = new \bloc\DOM\Document('data/records', ['validateOnParse' => true]);    
  }
  
  static public function create($instance, $data)
  {
    $record = $instance->storage->createElement('record', null);
    foreach ($data['attributes'] as $key => $value) {
      $record->setAttribute($key, $value);
    }
    
    $instance->storage->documentElement->appendChild($record);
    
    if (!empty($data['abstract'])) {
      $abstract = $record->appendChild($instance->storage->createElement('abstract', $data['abstract']['CDATA']));
    }
    
    if (!empty($data['premier'])) {
      $premier = $record->appendChild($instance->storage->createElement('premier', $data['premier']['CDATA']));
      $premier->setAttribute('date', $data['premier']['date']);
    }
    
    if (!empty($data['location'])) {
      $record->appendChild($instance->storage->createElement('location', $data['location']['CDATA']));
    }
    
    
    return $instance->storage->validate() ? $instance : false;
  }
  
  public function save()
  {
    $file = PATH . 'data/records.xml';
    return $this->storage->save($file);
  }
}