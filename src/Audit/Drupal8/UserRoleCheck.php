<?php

namespace Drutiny\GovCMS\Audit\Drupal8;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Annotation\Param;

/**
 * Audit to check ig user role has users associated to it.
 * @Param(
 *  name = "roles",
 *  description = "The machine name of the user role",
 * )
 * @Param(
 *   name = "allowed",
 *   description = "An array of user ID's to exclude from reporting",
 * )
 */
class UserRoleCheck extends Audit {

  /**
   * Return a generated user role object from a user config object.
   *
   * @param array $users
   *   The user configuration object.
   *
   * @return array
   *   An array of roles and users.
   */
  private function userObject($users = []) {
    $results = array();
    foreach ($users as $userKey => $user) {
      foreach($user['roles'] as $role) {
        $results[$role] = $user;
      }
    }
    return $results;
  }

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {

    // Parameters.
    $rolesToFind = $sandbox->getParameter('roles', array("administrator"));
    $allowedUserIDs = $sandbox->getParameter('allowed', array());

    // Create an empty array for users and results.
    $users = array();
    $results = array();

    // Get all user IDs.
    $uids = $sandbox->drush()->sqlQuery('SELECT (uid) FROM users;');
    foreach ($uids as $uid) {
      $users[$uid] = $uid;
    }

    // Get all user information.
    foreach ($users as $key => $user) {
      $userData = $sandbox->drush(['format' => 'json'])->userInformation("--uid={$user}");
      $users[$key] = $userData[count($userData)];
    }

    // Generate results
    $users = $this->userObject($users);
    foreach ($users as $user) {
      foreach ($rolesToFind as $roleToFind) {
        if (in_array($roleToFind, $user['roles'])) {
          $results[] = "User {$user['name']} ({$user['uid']}) is in the {$roleToFind} group.";
        }
      }
    }

    // Return results.
    if (!empty($results)) {
      $sandbox->setParameter('results', $results);
      return FALSE;
    }

    return TRUE;

  }

}
