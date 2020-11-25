<?php

namespace Drutiny\GovCMS\Audit\Drupal8;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Annotation\Param;

/**
 * Anonymous Publishing Access
 *
 * @Param(
 *  name = "roles",
 *  description = "The name of the user to check.",
 *  type = "array"
 * )
 * @Param(
 *  name = "patterns",
 *  description = "The patterns to check for.",
 *  type = "array"
 * )
 */
class AnonymousPublishingAccess extends Audit {

  /**
   * A simple function to determine if a string contains a substring.
   *
   * @param string $source
   *   The source material to check against.
   * @param string $substring
   *   The substring to search $source for.
   *
   * @return bool
   *   The result, which will be true when the substring was found.
   */
  function contains($source, $substring) : bool {
    if (strpos($source, $substring) !== false) {
      return 'true';
    }
    return false;
  }

  /**
   *
   */
  public function audit(Sandbox $sandbox) {

    $warnings = [];
    $default_patterns = array(
      "administer nodes",
      "create .* content",
      "delete any .*",
      "delete own .*",
      "edit any .*",
      "edit own .*",
      "revert .* revisions",
      "view .* revisions",
      "view .* submissions",
      "view unpublished",
    );

    $patterns = (array) $sandbox->getParameter('patterns', $default_patterns);
    $roles = (array) $sandbox->getParameter('roles', array('anonymous'));

    if (empty($roles) || empty($patterns)) {
      return Audit::FAIL;
    }

    foreach ($roles as $role) {
      try {
        $config = $sandbox->drush(['format' => 'json'])->configGet("user.role.{$role}");
      } catch (\Exception $e) {
        // If the configuration object could not be found, return an Error state.
        return Audit::ERROR;
      } finally {
        if (isset($config)) {
          foreach ($config['permissions'] as $permission) {
            foreach ($patterns as $num => $pattern) {
              if (preg_match('/('.$patterns[$num].')/', $permission)) {
                $warnings[] = "{$role}: {$permission}";
              }
              elseif ($this->contains($permission, $patterns[$num])) {
                $warnings[] = "{$role}: {$permission}";
              }
            }
          }
        }
      }
      // Cleanup to prevent memory leaks.
      unset($config);
    }

    if (empty($warnings)) {
      return Audit::SUCCESS;
    }

    $sandbox->setParameter('roles', $roles);
    $sandbox->setParameter('warnings', $warnings);

    Audit::FAIL;
  }

}
