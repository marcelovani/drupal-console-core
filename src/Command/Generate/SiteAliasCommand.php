<?php

namespace Drupal\Console\Core\Command\Generate;

use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Core\Utils\DrupalFinder;
use Drupal\Console\Core\Generator\SiteAliasGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class SiteAliasCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class SiteAliasCommand extends Command
{
    /**
     * @var SiteAliasGenerator
     */
    protected $generator;

    /**
     * @var DrupalFinder
     */
    protected $drupalFinder;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var DrupalStyle
     */
    private $io;

    /**
     * @var array
     */
    private $types = [
        'local',
        'ssh',
        'container'
    ];

    /**
     * @var array
     */
    private $extraOptions = [
        'local' => [],
        'ssh' => [
            'none' => 'none',
            'vagrant' => '-o PasswordAuthentication=no -i ~/.vagrant.d/insecure_private_key',
        ],
        'container' => [
            'none' => 'none',
            'drupal4docker' => 'docker-compose exec --user=82 php'
        ]
    ];

    public function __construct(
        SiteAliasGenerator $generator,
        ConfigurationManager $configurationManager,
        DrupalFinder $drupalFinder
    ) {
        $this->generator = $generator;
        $this->configurationManager = $configurationManager;
        $this->drupalFinder = $drupalFinder;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:site:alias')
            ->setDescription(
                $this->trans('commands.generate.site.alias.description')
            )
            ->setHelp($this->trans('commands.generate.site.alias.help'))
            ->addOption(
                'site',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.generate.site.alias.options.site')
            )
            ->addOption(
                'name',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.site.alias.options.name')
            )
            ->addOption(
                'environment',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.site.alias.options.environment')
            )
            ->addOption(
                'type',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.site.alias.options.type')
            )
            ->addOption(
                'composer-root',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.generate.site.alias.options.composer-root')
            )
            ->addOption(
                'drupal-root',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.drupal-root')
            )
            ->addOption(
                'server-root',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.server-root')
            )
            ->addOption(
                'site-uri',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.site-uri')
            )
            ->addOption(
                'host',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.host')
            )
            ->addOption(
                'user',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.user')
            )
            ->addOption(
                'port',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.port')
            )
            ->addOption(
                'extra-options',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.extra-options')
            )
            ->addOption(
                'directory',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.site.alias.options.directory')
            )
            ->addOption(
                'account-name',
                'admin',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.account-name')
            )
            ->addOption(
                'account-pass',
                '?????',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.account-pass')
            )
            ->addOption(
                'account-mail',
                'email@example.com',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.account-mail')
            )
            ->addOption(
                'repo-type',
                'git',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.repo-type')
            )
            ->addOption(
                'repo-url',
                'git@github.com:user/repo.git',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.repo-url')
            )
            ->addOption(
                'repo-branch',
                'master',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.repo-branch')
            )
            ->setAliases(['gsa']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->io = new DrupalStyle($input, $output);
        $this->io->comment($this->trans('application.options.tips.autocomplete-arrows'));

        // Site name.
        $site = $input->getOption('site');
        $name = $input->getOption('name');
        if (!$name) {
            $sites = $this->configurationManager->getSites();
            if (!empty($sites)) {
                $sites = array_keys($this->configurationManager->getSites());
                $name = $this->getIo()->choiceNoList(
                    $this->trans('commands.generate.site.alias.questions.name'),
                    $sites,
                    current($sites),
                    TRUE
                );

                if (is_numeric($name)) {
                    $name = $sites[$name];
                }
            } else {
                $name = $this->getIo()->ask(
                    $this->trans('commands.generate.site.alias.questions.name')
                );
            }

            $input->setOption('name', $name);
        }

        // Environment.
        $environment = $input->getOption('environment');
        if (!$environment) {
            $environment = $this->getIo()->ask(
                $this->trans('commands.generate.site.alias.questions.environment'),
                $this->configurationManager->getConfiguration()->get('application.environment')
            );

            $input->setOption('environment', $environment);
        }

        // Type.
        $type = $input->getOption('type');
        if (!$type) {
            $type = $this->getIo()->choice(
                $this->trans('commands.generate.site.alias.questions.type'),
                $this->types,
                reset($this->types)
            );

            $input->setOption('type', $type);
        }

        // Backwards compatibility after renaming option to drupal-root.
        $composerRoot = $input->getOption('composer-root');
        // Drupal root.
        $drupalRoot = $input->getOption('drupal-root');
        if (empty($drupalRoot) && !empty($composerRoot)) {
            $drupalRoot = $composerRoot;
        }
        if (!$drupalRoot) {
            $root = $this->drupalFinder->getComposerRoot();
            $drupalRoot = $this->getIo()->ask(
                $this->trans('commands.generate.site.alias.questions.drupal-root'),
                '/var/www/' . $name
            );

            $input->setOption('drupal-root', trim($drupalRoot, '/'));
        }

        // Server root.
        $serverRoot = $input->getOption('server-root');
        if (!$serverRoot) {
            $serverRoot = $this->getIo()->askEmpty(
                $this->trans('commands.generate.site.alias.questions.server-root'),
                $drupalRoot . '/' . 'web'
            );

            $input->setOption('server-root', $serverRoot);
        }

        // Site URI.
        $siteUri = $input->getOption('site-uri');
        if (!$siteUri) {
            $uri = explode('.', $environment);
            if (count($uri)>1) {
                $uri = $uri[1];
            } else {
                $uri = 'default';
            }
            $siteUri = $this->getIo()->askEmpty(
                $this->trans('commands.generate.site.alias.questions.site-uri'),
                $uri
            );

            $input->setOption('site-uri', $siteUri);
        }

        // Host name.
        $host = $input->getOption('host');
        if (!$host) {
            $host = $this->getIo()->askEmpty(
                $this->trans('commands.generate.site.alias.questions.host'),
                'example.com'
            );

            $input->setOption('host', $host);
        }

        // Extra options.
        $extraOptions = $input->getOption('extra-options');
        if (!$extraOptions) {
            $options = array_values($this->extraOptions[$type]);
            if (!empty($options)) {
                $extraOptions = $this->getIo()->choice(
                    $this->trans(
                        'commands.generate.site.alias.questions.extra-options'
                    ),
                    $options,
                    current($options)
                );
            }
            $extraOptions = ($extraOptions == 'none') ? '' : $extraOptions;
            $input->setOption('extra-options', $extraOptions);
        }

        // Remote user/port.
        switch ($type) {
            case 'ssh':
            case 'container':
                $user = $input->getOption('user');
                if (!$user) {
                    $user = $this->getIo()->askEmpty(
                        $this->trans('commands.generate.site.alias.questions.user')
                    );

                    $input->setOption('user', $user);
                }

                $port = $input->getOption('port');
                if (!$port) {
                    $port = $this->getIo()->askEmpty(
                        $this->trans('commands.generate.site.alias.questions.port')
                    );

                    $input->setOption('port', $port);
                }
                break;
        }

        // Site installation arguments.
        $account_name = $input->getOption('account-name');
        if (!$account_name) {
            $account_name = $this->getIo()->ask(
                $this->trans('commands.generate.site.alias.questions.account-name'),
                'admin'
            );

            $input->setOption('account-name', $account_name);
        }
        $account_pass = $input->getOption('account-pass');
        if (!$account_pass) {
            $account_pass = $this->getIo()->ask(
                $this->trans('commands.generate.site.alias.questions.account-pass'),
                ''
            );

            $input->setOption('account-pass', $account_pass);
        }
        $account_mail = $input->getOption('account-mail');
        if (!$account_mail) {
            $account_mail = $this->getIo()->ask(
                $this->trans('commands.generate.site.alias.questions.account-mail'),
                'email@example.com'
            );

            $input->setOption('account-mail', $account_mail);
        }

        // Repository arguments.
        $repo_type = $input->getOption('repo-type');
        if (!$repo_type) {
            $repo_type = $this->getIo()->ask(
                $this->trans('commands.generate.site.alias.questions.repo-type'),
                'git'
            );

            $input->setOption('repo-type', $repo_type);
        }
        $repo_url = $input->getOption('repo-url');
        if (!$repo_url) {
            $repo_url = $this->getIo()->ask(
                $this->trans('commands.generate.site.alias.questions.repo-url'),
                ''
            );

            $input->setOption('repo-url', $repo_url);
        }
        $repo_branch = $input->getOption('repo-branch');
        if (!$repo_branch) {
            $repo_branch = $this->getIo()->ask(
                $this->trans('commands.generate.site.alias.questions.repo-branch'),
                'master'
            );

            $input->setOption('repo-branch', $repo_branch);
        }

        // Directory.
        $directory = $input->getOption('directory');
        if ($site && $this->drupalFinder->getComposerRoot()) {
            $directory = $this->drupalFinder->getComposerRoot() . '/console/';
        }

        if (!$directory) {
            $directories = $this->configurationManager->getConfigurationDirectories();
            $directory = $this->getIo()->choice(
                $this->trans('commands.generate.site.alias.questions.directory'),
                $directories,
                reset($directories)
            );

            $input->setOption('directory', $directory);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $site = $input->getOption('site');
        $directory = $input->getOption('directory');
        if ($site && $this->drupalFinder->isValidDrupal()) {
            $directory = $this->drupalFinder->getComposerRoot() . '/console/';
        }
        $this->generator->generate(
            [
                'name' => $input->getOption('name'),
                'environment' => $input->getOption('environment'),
                'type' => $input->getOption('type'),
                'extra_options' => $input->getOption('extra-options'),
                'root' => $input->getOption('drupal-root'),
                'server_root' => $input->getOption('server-root'),
                'host' => $input->getOption('host'),
                'account_name' => $input->getOption('account-name'),
                'account_pass' => $input->getOption('account-pass'),
                'account_mail' => $input->getOption('account-mail'),
                'repo_type' => $input->getOption('repo-type'),
                'repo_url' => $input->getOption('repo-url'),
                'repo_branch' => $input->getOption('repo-branch'),
                'uri' => $input->getOption('site-uri'),
                'port' => $input->getOption('port'),
                'user' => $input->getOption('user'),
                'directory' => $directory,
            ]
        );
    }
}
