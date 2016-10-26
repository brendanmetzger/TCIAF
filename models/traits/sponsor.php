<?php
namespace models\traits;

trait sponsor {
  public function getSponsors(\DOMElement $context)
  {
    return $this->groupByTitle($context, 'sponsor');
  }

  protected function groupByTitle(\DOMElement $context, string $type)
  {
    $output = [];
    $sponsors = $context->find("edge[@type='{$type}']");
    if ($sponsors->count() < 1) return null;

    foreach ($sponsors as $edge) {
      $key = trim((string)$edge ?: $type);
      if (! array_key_exists($key, $output)) {
        $output[$key] = ['group' => ['name' => $key, 'items' => []]];
      }

      $output[$key]['group']['items'][] = ['item' => \models\Graph::FACTORY(\models\Graph::ID($edge['@vertex']))];
    }
    $output = array_values($output);
    return new \bloc\types\Dictionary($output);

  }
}
