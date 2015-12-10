<?php
namespace models\traits;

trait sponsor {
  public function getSponsors(\DOMElement $context)
  {
    $output = [];
    foreach ($context->find("edge[@type='sponsor']") as $edge) {
      $key = (string)$edge ?: 'Sponsor';
      if (! array_key_exists($key, $output)) {
        $output[$key] = ['group' => ['name' => $key, 'items' => []]];
      }

      $output[$key]['group']['items'][] = ['organization' => new \models\Organization($edge['@vertex'])];
    }
    $output = array_values($output);
    return new \bloc\types\Dictionary($output);
  }
}
