<?php
namespace models\traits;

trait sponsor {
  public function getSponsors(\DOMElement $context)
  {
    $output = [];
    $sponsors = $context->find("edge[@type='sponsor']");
    if ($sponsors->count() < 1) return null;

    foreach ($sponsors as $edge) {
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
