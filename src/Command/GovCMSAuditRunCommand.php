<?php

namespace GovCMS\Audit\Command;

use Drutiny\Assessment;
use Drutiny\Command\AbstractReportingCommand;
use Drutiny\Container;
use Drutiny\Driver\Exec;
use Drutiny\Profile\ProfileSource;
use Drutiny\ProgressBar;
use Drutiny\Target\Registry as TargetRegistry;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * GovCMS Audit Run command.
 */
class GovCMSAuditRunCommand extends AbstractReportingCommand
{
    use \Drutiny\Policy\ContentSeverityTrait;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('govcms:audit:run')
            ->setDescription('Run GovCMS site audit.')
            ->addArgument(
                'profile',
                InputArgument::REQUIRED,
                'The name of the profile to run.'
            )
            ->addArgument(
                'target',
                InputArgument::REQUIRED,
                'The target to run the policy collection against.'
            )
            ->addOption(
                'remediate',
                'r',
                InputOption::VALUE_NONE,
                'Allow failed policy aduits to remediate themselves if available.'
            )
            ->addOption(
                'exit-on-severity',
                'x',
                InputOption::VALUE_OPTIONAL,
                'Send an exit code to the console if a policy of a given severity fails. Defaults to none (exit code 0). (Options: none, low, normal, high, critical)',
                'none'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Ensure Container logger uses the same verbosity.
        Container::setVerbosity($output->getVerbosity());
        $console = new SymfonyStyle($input, $output);

        // Setup the check.
        $profile = ProfileSource::loadProfileByName($input->getArgument('profile'));

        $profile->setReportPerSite($input->getOption('report-per-site'));

        // Override the title of the profile with the specified value.
        if ($title = $input->getOption('title')) {
            $profile->setTitle($title);
        }

        $filepath = $input->getOption('report-filename');
        $format = $input->getOption('format');

        // If format is not out to console and the filepath isn't set, automate
        // what the filepath should be.
        if (!in_array($format, ['console', 'terminal']) && !$filepath) {
            $filepath = strtr('target-profile-date.format', [
                'target' => preg_replace('/[^a-z0-9]/', '',
                    strtolower($input->getArgument('target'))),
                'profile' => $input->getArgument('profile'),
                'date' => date('Ymd-His'),
                'format' => $input->getOption('format'),
            ]);
        } // If the filepath is not set for console formats, then force to stdout.
        elseif (in_array($format, ['console', 'terminal']) && !$filepath) {
            $filepath = 'stdout';
        }

        // Setup the reporting format.
        $format = $profile->getFormatOption($input->getOption('format'));

        // Setup the policy definitions.
        $policyDefinitions = $profile->getAllPolicyDefinitions();

        // Get some information from the local site-alias.
        $proc = new Exec();
        $data = $proc->exec('drush sa @alias --format=json', [
            '@alias' => $input->getArgument('target'),
        ]);
        $sites = json_decode($data, true);

        // Setup the progress bar to log updates.
        $steps = count($policyDefinitions) * count($sites);

        $progress = new ProgressBar($output, $steps);

        // We don't want to run the progress bar if the output is to stdout.
        // Unless the format is console/terminal as then the output doesn't matter.
        // E.g. turn of progress bar in json, html and markdown formats.
        if ($filepath == 'stdout' && !in_array($format->getFormat(), [
                'console',
                'terminal',
            ])) {
            $progress->disable();
        } // Do not use the progress bar when using a high verbosity logging output.
        elseif ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $progress->disable();
        }

        $results = [];

        $start = new \DateTime(date('Y-m-d H:00:00', strtotime('-24 hours')));
        $end = new \DateTime(date('Y-m-d H:00:00'));
        $profile->setReportingPeriod($start, $end);

        $policies = [];
        foreach ($policyDefinitions as $policyDefinition) {
            $policies[] = $policyDefinition->getPolicy();
        }

        $progress->start();

        foreach ($sites as $project => $site) {
            // Setup the target.
            $target = TargetRegistry::loadTarget('@' . $project);
            // Setup the site uri.
            $uri = $site['uri'];

            try {
                $target->setUri($uri);
            } catch (\Drutiny\Target\InvalidTargetException $e) {
                Container::getLogger()
                    ->warning("Target cannot be evaluated: " . $e->getMessage());
                $progress->advance(count($policyDefinitions));
                continue;
            }

            $assessment = new Assessment($uri);
            $assessment->assessTarget($target, $policies, $start, $end,
                $input->getOption('remediate'));
            $results[$uri] = $assessment;
        }

        $progress->finish();

        if (!count($results)) {
            Container::getLogger()->error("No results were generated.");
            return;
        }

        // Export to the terminal.
        $target = TargetRegistry::loadTarget($input->getArgument('target'));
        $this->report($profile, $input, $output, $target, $results);
        $report = $format->render($profile, $target, $results)->fetch();

        $this->setSeverity($input->getOption('exit-on-severity'));

        // Do not use a non-zero exit code when no severity is set (Default).
        if (!$this->getSeverity()) {
            return;
        }

        $bad_results = 0;

        foreach ($results as $assessment) {
            if ($assessment->isSuccessful()) {
                continue;
            }

            // Increase the exit severity to match the highest assessment severity.
            if ($assessment->getSeverity() >= $this->getSeverity()) {
                $this->setSeverity($assessment->getSeverity());
                $bad_results++;
            }
        }

        // Zero exit code when no bad results.
        if (!$bad_results) {
            return;
        }

        // Return the highest found severity as exit code.
        return $this->getSeverity();
    }
}
