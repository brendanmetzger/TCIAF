<?php
namespace models\traits;


trait periodical {
  private $date_format  = 'Y-m-d\TH:i';

  public function setDateAttribute(\DOMElement $context, $date)
  {
    if ($date = (new \DateTime($date))->format($this->date_format)) {
      $context->setAttribute('date', $date);
    }
  }
  
  public function setDurationAttribute(\DOMElement $context, $date)
  {
    if ($date = (new \DateTime($date))->format($this->date_format)) {
      $context->setAttribute('duration', $date);
    }
  }

  public function getDate(\DOMElement $context)
  {
    return (new \DateTime($context['premier']['@date']))->format('l, F jS, Y');
  }

  public function getYear(\DOMElement $context)
  {
    preg_match('/^(?:the\s)?([0-9]{4})\s*(.*)$/i', $context['@title'], $result);
    return $result[1] ?? 0;
  }


  public function getEditions(\DOMElement $context)
  {
    return $context->find("edge[@type='edition']")->map(function($edge) use ($context) {
      return ['edition' => \models\Graph::FACTORY(\models\Graph::ID($edge['@vertex']))];
    });
  }
  
  public function getOccurances(\DOMElement $context)
  {
    return $context->find("edge[@type='edition']")->map(function($edge) use ($context) {
      return ['edition' => \models\Graph::FACTORY(\models\Graph::ID($edge['@vertex']))];
    })->sort(function($a, $b) {
      return $a['edition']['year'] < $b['edition']['year'];
    });
  }
  
  public function getArticles(\DOMElement $context)
  {
    return $context->find("edge[@type='page']")->map(function($edge) {
      return ['item' => new \models\Article($edge['@vertex'])];
    });
  }

  public function getUpcoming(\DOMElement $context)
  {
    $now   = (new \DateTime())->format('YmdHis');
    $query = "vertex[edge[@vertex='{$context['@id']}'] and premier[number(translate(@date,'-: ','')) > {$now}]]";
    return \models\Graph::group($this->_model)
           ->find($query)
           ->map(function($item) {
             return ['item' => \models\Graph::FACTORY($item)];
            });
  }
}
