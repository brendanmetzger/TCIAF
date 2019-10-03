<?php namespace models;


class Schedule {
  private $size;
  private $dataset = [
    'events' => [],
    'range'  => [], // in hours
  ];
  
  public function __construct (\iterator $edges) {
    $this->size = $edges->count();
    foreach ($edges as $edge) {
      $this->place($edge);
    }
    usort($this->dataset['events'], function ($a, $b) {
      return $a['item']['start'] > $b['item']['start'];
    });
    
    \bloc\application::instance()->log($this->draw());
  }
  
  public function render() {
    // \bloc\application::instance()->log($this->events);
    return $this->dataset['events'];
  }
  
  // divisor converts the length in seconds to something else (default is days 60 * 60 * 24)
  public function draw() {
    $first = $this->dataset['events'][0]['item']['start'];
    $last = $this->dataset['events'][$this->size - 1]['item']['end'];
    $start_offset = mktime(0, 0, 0, ...explode(',', date('m,d,y', $first)));
    $total_duration = ($last - $first) / 60;
    foreach ($this->dataset['events'] as &$event) {
      $event['item']['offset'] = (($event['item']['start'] - $start_offset) / 60) / $total_duration;
      $event['item']['size'] = $event['item']['duration'] / $total_duration;
    }
    
    
    return $this->dataset['events'];
  }
  
  public function groupToDays() {
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
    
    $this->dataset['events'][] = ['item' => $event];
  }
}
