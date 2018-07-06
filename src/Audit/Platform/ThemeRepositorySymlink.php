<?php

namespace Drutiny\GovCMS\Audit\Platform;

use Drutiny\Audit;
use Drutiny\Audit\Drupal\ModuleEnabled;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Annotation\Token;

/**
 *  Ensure symlinks in themes do not reference locations outside the theme.
 *
 * @Token(
 *   name = "links",
 *   description = "An array of symlink locations found in the theme respository (Excluding bad links).",
 *   type = "array",
 *   default = {}
 * )
 * @Token(
 *   name = "bad_links",
 *   description = "An array of symlinks that reference files",
 *   type = "array",
 *   default = {}
 * )
 */
class ThemeRepositorySymlink extends Audit {

  /**
   *
   */
  public function audit(Sandbox $sandbox) {
    $stat = $sandbox->drush(['format' => 'json'])->status();

    $command = <<<EOT
  theme_dir=`realpath {$stat['root']}/{$stat['themes']}/site/`;
  echo \$theme_dir;
  find \$theme_dir -type l -ls -exec realpath {} \;
EOT;

    $response = $sandbox->exec($command);

    // Clean up output and turn into filepaths.
    $data = array_filter(array_map('trim', explode(PHP_EOL, $response)));

    // First link is actually the theme directory real path.
    $theme_directory = array_shift($data);

    $links = [];

    $links = array_map(function ($link_data) {
      list($metadata, $realpath) = $link_data;
      $metadata = array_values(array_filter(explode(' ', $metadata)));
      return [
        'symlink' => $metadata[9],
        'realpath' => $realpath
      ];
    }, array_chunk($data, 2));

    // Bad links are any link that does not reside inside the theme directory.
    $sandbox->setParameter('bad_links', array_filter($links, function ($link) use ($theme_directory) {
      return strpos($link['realpath'], $theme_directory) !== 0;
    }));
    $sandbox->setParameter('bad_links_count', count($sandbox->getParameter('bad_links')));

    // Good links (called just links) are links that are not bad links (duh!).
    $sandbox->setParameter('links', array_filter($links, function ($link) use ($theme_directory) {
      return strpos($link['realpath'], $theme_directory) === 0;
    }));
    $sandbox->setParameter('links_count', count($sandbox->getParameter('links')));

    return $sandbox->getParameter('bad_links_count') == 0;
  }

}
