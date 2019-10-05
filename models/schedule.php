<?php namespace models;


class Schedule {
  private $size;
  private $dataset = [
    'events' => [],
    'timeline'  => [], // in hours
  ];
  
  public function __construct (\iterator $edges) {
    $this->size = $edges->count();
    foreach ($edges as $edge) {
      $this->place($edge);
    }
    usort($this->dataset['events'], function ($a, $b) {
      return $a['item']['start'] > $b['item']['start'];
    });
    
    // \bloc\application::instance()->log($this->draw());
    $this->draw();
  }
  
  public function render() {
    // \bloc\application::instance()->log($this->events);
    return new \bloc\types\dictionary($this->dataset);
  }
  
  // divisor converts the length in seconds to something else (default is days 60 * 60 * 24)
  public function draw() {
    $first = $this->dataset['events'][0]['item']['start'];
    $last = $this->dataset['events'][$this->size - 1]['item']['end'];
    
    $start_offset = mktime(0, 0, 0, ...explode(',', date('m,d,y', $first)));
    $end_offset   = mktime(23, 59, 59, ...explode(',', date('m,d,y', $last)));
        
    $total_duration = round(($end_offset - $start_offset) / 60);
    
    for ($day_of=$start_offset, $one_day = 86400; $day_of < $end_offset; $day_of+=$one_day) { 
      $dataset['timeline'][] = [
        'date' => date('l, F jS', $day_of),
        'range' => [$day_of, $day_of + $one_day],
        'events' => [],
      ];
    }
    

    
    foreach ($this->dataset['events'] as $idx => &$event) {
      $event['item']['offset'] = ((($event['item']['start'] - $start_offset) / 60) / $total_duration * 100) . '%';
      $event['item']['size'] = ($event['item']['duration'] / $total_duration * 100) . '%';
      $slot = floor(($event['item']['start'] - $start_offset) / 3600);
      $length = $event['item']['duration'] / 60;
      $event['item']['range'] = [$slot, $slot + $length];
      $event['item']['layout'] = ['width' => 1, 'position' => 0];
      
      $prev = $idx - 1;
      
      while($prev >= 0 && $this->dataset['events'][$prev]['item']['range'][1] > $event['item']['range'][0]) {
        $this->dataset['events'][$prev]['item']['layout']['width']++;
        $event['item']['layout']['width']++;
        $event['item']['layout']['position']++;
        $prev--;
      }
    }
    
    \bloc\application::instance()->log($this->dataset['events']);
    
    return $this->dataset['events'];
  }
  
  
  
  private function place(\DOMnode $edge) {
    $model = \models\Graph::FACTORY(\models\Graph::ID($edge['@vertex']));
    $event = [
      'id'    => $edge['@vertex'],
      'start' => strtotime($model['premier']['@date']),
      'end'   => strtotime($model['premier']['@duration']),
      'title' => $model->title,
      'location' => $model['location']['@ref'] ?: 'TBD',
    ];
    
    $event['human_time'] = date('g:ia', $event['start']) . '–' . date('g:ia', $event['end']);
    $event['duration'] = ($event['end'] - $event['start']) / 60; 
    
    $this->dataset['events'][] = ['item' => $event];
  }
}
