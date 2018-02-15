<?php

namespace Drutiny\GovCMS\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Drutiny\Command\ProfileRunCommand;

class SaasCommand extends Command
{
    protected function configure()
    {
      $this
         // the name of the command (the part after "bin/console")
         ->setName('saas')
         ->addArgument(
           'uri',
           InputArgument::REQUIRED,
           'The uri used to reference the site on the saas platform.'
         )
         ->addOption(
            'stack',
            's',
            InputOption::VALUE_OPTIONAL,
            'The stack to connect to. Defaults to @govcms.01live.',
            '@govcms.01live'
          )
         // the short description shown while running "php bin/console list"
         ->setDescription('Validates a govCMS site is ready for lauch');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
      $command = new ProfileRunCommand();
      $command->setApplication($this->getApplication());

      $report_filename = $input->getArgument('uri');
      $report_filename .= '-govcms-audit' . date('Ymd-His') . '.html';

      $arguments = array(
          '--format' => 'html',
          '--report-filename' => $report_filename,
          '--uri' => [$input->getArgument('uri')],
          'profile' => 'review',
          'target'  => $input->getOption('stack')
      );

      $input = new ArrayInput($arguments);

      return $command->run($input, $output);
    }
}
 ?>
