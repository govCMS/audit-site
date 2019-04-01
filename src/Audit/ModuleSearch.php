<?php

namespace Drutiny\GovCMS\Audit;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Annotation\Param;

/**
 * Scan for modules and themes inside the default theme folder (or a specified directory).
 * @Param(
 *  name = "directory",
 *  description = "Absolute filepath to directory to scan",
 *  type = "string",
 *  default = ""
 * )
 */
class ModuleSearch extends Audit {

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {

    $info = $sandbox->drush(['format' => 'json'])->status();
    $themeName = $info['theme'];
    $rootPath = $info['root'];
    $themePath = $sandbox->drush()->eval("'return drupal_get_path('theme', '{$themeName}');'");
    $results = array();
    $modulesFound = array();
    $themesFound = array();

    $directory = $sandbox->getParameter('directory', "");
    if ($directory === '') {
      $directory = "$rootPath/$themePath";
    }

    $types = array('info', 'module', 'theme', 'info.yml');
    $command = ['find', $directory, '-type f'];

    $conditions = [];
    foreach ($types as $type) {
      $conditions[] = '-iname "*.' . $type . '"';
    }

    $command[] = '\( ' . implode(' -or ', $conditions) . ' \)';
    $command[] = " || exit 0";

    $command = '\'' . implode(' ', $command) . '\'';
    $sandbox->logger()->info('[' . __CLASS__ . '] ' . $command);
    $output = $sandbox->drush()->ssh($command);

    if (empty($output)) {
      return Audit::NOT_APPLICABLE;
    }

    $matches = array_filter(explode(PHP_EOL, $output));
    $matches = array_map(function ($line) {
      list($filepath, $line_number, $code) = explode(':', $line, 3);
      return [
        'file' => basename($filepath),
        'directory' => implode('/', array_slice(explode('/', $filepath), 0, -1)),
        'machine_name' => implode('.', array_slice(explode('.', basename($filepath)), 0, 1)),

      ];
    }, $matches);

    if ($result = $sandbox->drush(['format' => 'json', 'fields' => 'type'])->pmList()) {
      foreach ($matches[0] as $module) {
        if ($result[$module]['type'] === 'module') {
          $modulesFound[] = $module;
        }
        if ($result[$module]['type'] === 'theme') {
          $themesFound[] = $module;
        }
      }
    }
    else {
      return Audit::ERROR;
    }

    $sandbox->setParameter('themesFound', $themesFound);
    $sandbox->setParameter('modulesFound', $modulesFound);

    return empty($modulesFound);
  }

}
