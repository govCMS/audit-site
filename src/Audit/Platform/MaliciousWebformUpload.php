<?php

namespace Drutiny\GovCMS\Audit\Platform;

use Drutiny\Audit;
use Drutiny\Audit\Drupal\ModuleEnabled;
use Drutiny\Sandbox\Sandbox;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Malicious webform uploads",
 *  description = "Spammers are known to want to uplaod files to webforms that allow anonymous user users access.",
 *  remediation = "Restrict upload types, enforce a max upload size, use a random folder underneath <code>/webform/</code> to store the uploads.",
 *  not_available = "Webform is not enabled.",
 *  success = "There are no files uploaded that look malicious.",
 *  failure = "There :prefix <code>:number_of_silly_uploads</code> malicious webform upload:plural.:files",
 *  exception = "Could not determine the amount of malicious uploads.",
 * )
 */
class MaliciousWebformUpload extends Audit {

  /**
   * Ensure the webform module is enabled.
   */
  public function requiresWebformModule(Sandbox $sandbox) {
    $sandbox->setParameter('module', 'webform');
    $check = new ModuleEnabled($sandbox);
    return $check->check($sandbox);
  }

  /**
   *
   */
  public function audit(Sandbox $sandbox) {

    // Look for NFL uploads.
    $output = $sandbox->drush()->sqlQuery("SELECT filename FROM file_managed WHERE UPPER(filename) LIKE '%NFL%' AND status = 0;");
    $output = array_filter($output);

    $malicious_uploads = count($output);

    $sandbox->setParameter('malicious_uploads', count($output));
    $sandbox->setParameter('malicious_files', $output);

    // $this->setToken('plural', $number_of_silly_uploads > 1 ? 's' : '');
    // $this->setToken('prefix', $number_of_silly_uploads > 1 ? 'are' : 'is');

    return $malicious_uploads === 0;
  }

}
