<?php namespace models;


class Schedule {
  private $dataset = [
    'events' => [],
    'range'  => [], // in hours
  ];
  
  public function __construct (\iterator $edges) {
    
    foreach ($edges as $edge) {
      $this->place($edge);
    }
  }
  
  public function render() {
    // \bloc\application::instance()->log($this->events);
    return $this->dataset['events'];
  }
  
  public function getRange($value='') {
    # code...
  }
  
  public function groupToDays($value='') {
    # code...
  }
  
  
  
  private function place(\DOMnode $edge) {
    $model = \models\Graph::FACTORY(\models\Graph::ID($edge['@vertex']));
    
    $event = [
      'id'    => $edge['@vertex'],
      'start' => strtotime($model['premier']['@date']),
      'end'   => strtotime($model['premier']['@duration']),
      'title' => $model->title,
      'location' => $model['location']['@ref'] ?? 'TBD',
    ];
    
    $event['duration'] = ($event['end'] - $event['start']) / 60;
    
    \bloc\application::instance()->log($event);
    $this->dataset['events'][] = ['item' => $event];
  }
}
