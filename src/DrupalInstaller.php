<?php

namespace zaporylie\DrupalInstaller;

use Composer\IO\IOInterface;
use Composer\Script\Event;

class DrupalInstaller {

  /**
   * Interactively install Drupal.
   *
   * @param \Composer\Script\Event $event
   */
  public static function installDrupal(Event $event) {
    $io = $event->getIO();

    // Check if composer works in interactive mode.
    if (!$io->isInteractive()) {
      $io->write('<debug>Unable to install Drupal interactively; --no-interaction flag was set.</debug>', true, IOInterface::DEBUG);
      return;
    }

    // Get Drupal Root.
    $drupalFinder = new \DrupalFinder\DrupalFinder();
    if (!$drupalFinder->locateRoot(getcwd())) {
      $io->writeError('<error>Unable to locate Drupal root</error>');
      return;
    }
    $drupalRoot = $drupalFinder->getDrupalRoot();
    $io->write("<info>Drupal root: $drupalRoot</info>", true, IOInterface::VERBOSE);

    // Get confirmation.
    $confirmation = $io->askConfirmation('<info>Do you want to install Drupal?</info> [<comment>Y,n</comment>]',TRUE);
    if (!$confirmation) {
      $io->write('<warning>Drupal installation has been skipped</warning>');
      return;
    }

    // @todo Find available profiles and add to option list.
    $profile = $io->select('<info>The install profile you wish to run.</info> [<comment>default</comment>]', ['default' => 'default'], 'default');
    if ($profile == 'default') {
      $profile = NULL;
    }

    // @todo: Find if settings.php exists and already has db credentials.
    // Ask if stored credentials should be used.
    // Useful when this plugin is used via composer scripts.

    // Get database credentials for new project.
    $db_driver = $io->ask('<info>Database drriver:</info> [<comment>mysql</comment>] ', 'mysql');
    $db_user = $io->askAndValidate('<info>Database user:</info> ', '\\zaporylie\\DrupalInstaller\\DrupalInstaller::isNotEmpty');
    $db_password = $io->askAndValidate('<info>Database password:</info> ', '\\zaporylie\\DrupalInstaller\\DrupalInstaller::isNotEmpty');
    $db_host = $io->ask('<info>Database host:</info> [<comment>localhost</comment>] ', 'localhost');
    $db_port = $io->ask('<info>Database port:</info> [<comment>3306</comment>] ',  3306);

    // Database name.
    $db_name = $io->askAndValidate('<info>Database name:</info> ', '\\zaporylie\\DrupalInstaller\\DrupalInstaller::isNotEmpty');

    // SQL root user.
    $db_su_user = $io->ask('<info>Database admin user:</info> [<comment>root</comment>] ', 'root');
    $db_su_password = $io->ask('<info>Database admin password:</info> ');

    // Run command and log to io.
    $output = [];
    exec("composer exec -v -- drush --root=$drupalRoot si $profile --db-url='$db_driver://$db_user:$db_password@$db_host:$db_port/$db_name' --db-su='$db_su_user' --db-su-pw='$db_su_password' -y", $output);
    foreach ($output as $line) {
      $io->write($line);
    }
  }

  /**
   * @param $value
   * @return mixed
   */
  public static function isNotEmpty($value) {
    if (!empty($value)) {
      return $value;
    }
    throw new \InvalidArgumentException('Input cannot be empty');
  }

}
