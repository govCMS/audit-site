<?php

namespace Drutiny\GovCMS\Audit;

use Drutiny\Audit;
use Drutiny\Audit\Drupal\ModuleEnabled;
use Drutiny\Sandbox\Sandbox;

/**
 *
 */
class SearchDB extends Audit {

  /**
   * Ensure the search module is enabled.
   */
  public function requiresSearchModule(Sandbox $sandbox) {
    $sandbox->setParameter('module', 'search');
    $check = new ModuleEnabled($sandbox);
    return $check->check($sandbox);
  }

  /**
   *
   */
  public function audit(Sandbox $sandbox) {

    $sandbox->setParameter('module', 'search_api_db');
    $check = new ModuleEnabled($sandbox);
    if ($check->check($sandbox)) {
      return TRUE;
    }

    // Find out if there are active indexes using the db service class.
    $indexes = $sandbox->drush()->sqlQuery("SELECT i.machine_name FROM {search_api_index} i LEFT JOIN {search_api_server} s ON i.server = s.machine_name WHERE i.status > 0 AND s.class = 'search_api_db_service';");
    if (empty($indexes)) {
      return TRUE;
    }

    $sandbox->setParameter('indexes', count($indexes) > 1);

    // If the database is in use, find out how many nodes are in it.
    $output = $this->context->drush->sqlQuery('SELECT COUNT(item_id) FROM {search_api_db_default_node_index};');

    // There are some differences in running the command on site factory then
    // locally.
    if (count($output) == 1) {
      $index_size = (int) $output[0];
    }
    else {
      $index_size = (int) $output[1];
    }
    $sandbox->setParameter('index_size', $index_size);

    $max_size = $sandbox->getParameter('max_size', 50);
    if ($index_size < $max_size) {
      return AuditResponse::AUDIT_WARNING;
    }

    return FALSE;
  }

}
