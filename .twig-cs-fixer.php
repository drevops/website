<?php

declare(strict_types = 1);

$ruleset = new TwigCsFixer\Ruleset\Ruleset();
$ruleset->addStandard(new TwigCsFixer\Standard\Twig());

$finder = new TwigCsFixer\File\Finder();
$finder->in(__DIR__ . '/web/modules/custom');
$finder->in(__DIR__ . '/web/themes/custom')->exclude([
  '.components-civictheme',
  'components_combined',
]);

$config = new TwigCsFixer\Config\Config();
$config->setRuleset($ruleset);
$config->setFinder($finder);
$config->addTokenParser(new Drupal\Core\Template\TwigTransTokenParser());

return $config;
