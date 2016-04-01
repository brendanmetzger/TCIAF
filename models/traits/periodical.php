<?php
namespace models\traits;


trait periodical {

  public function setDateAttribute(\DOMElement $context, $date)
  {
    if ($date = (new \DateTime($date))->format('Y-m-d H:i:s')) {
      $context->setAttribute('date', $date);
    }
  }

  public function getDate(\DOMElement $context)
  {
    return (new \DateTime($context['premier']['@date']))->format('l, F jS, Y');
  }


  public function getEditions(\DOMElement $context)
  {
    return $context->find("edge[@type='edition']")->map(function($edge) {
      return ['edition' => \models\Graph::FACTORY(\models\Graph::ID($edge['@vertex']))];
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
