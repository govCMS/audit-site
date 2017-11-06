<?php

namespace Drutiny\GovCMS\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\ArrayInput;
use Drutiny\Command\ProfileRunCommand;

class LocalCommand extends Command
{
    protected function configure()
    {
      $this
         // the name of the command (the part after "bin/console")
         ->setName('pre-forklift')
         ->addArgument(
           'drush_alias',
           InputArgument::REQUIRED,
           'The drush alias of the local site. E.g. @local.dev'
         )
         // the short description shown while running "php bin/console list"
         ->setDescription('Validates a govCMS site is ready for forklifting');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
      $command = new ProfileRunCommand();
      $command->setApplication($this->getApplication());

      $report_filename = str_replace('@', '', $input->getArgument('drush_alias'));
      $report_filename .= '-govcms-audit' . date('Ymd-His') . '.html';

      $arguments = array(
          // 'command' => 'profile:run',
          '--format' => 'html',
          '--report-filename' => $report_filename,
          'profile' => 'preflight',
          'target'  => $input->getArgument('drush_alias')
      );

      $input = new ArrayInput($arguments);

      return $command->run($input, $output);
    }
}
 ?>
