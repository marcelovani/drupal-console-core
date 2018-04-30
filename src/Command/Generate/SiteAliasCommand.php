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
            // Site.
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
            // Environment.
            ->addOption(
                'environment',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.site.alias.options.environment')
            )
            // Type.
            ->addOption(
                'type',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.site.alias.options.type')
            )
            ->addOption(
                'extra-options',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.extra-options')
            )
            // Remote.
            ->addOption(
                'host',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.host')
            )
            ->addOption(
                'port',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.port')
            )
            ->addOption(
                'user',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.user')
            )
            // Repository.
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
            // Database.
            ->addOption(
                'db-dump',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.db-dump')
            )
            ->addOption(
                'db-driver',
                'mysql',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.db-driver')
            )
            ->addOption(
                'db-host',
                'mariadb',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.db-host')
            )
            ->addOption(
                'db-port',
                '3306',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.db-port')
            )
            ->addOption(
                'db-name',
                'drupal',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.db-name')
            )
            ->addOption(
                'db-user',
                'root',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.db-user')
            )
            ->addOption(
                'db-pass',
                '????',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.db-pass')
            )
            // Web host.
            ->addOption(
                'host-name',
                'example.com',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.host-name')
            )
            ->addOption(
                'host-port',
                '80',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.host-port')
            )
            // Server root.
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
            // Multisite.
            ->addOption(
                'site-uri',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.site-uri')
            )
            // Site installation.
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
            // Output.
            ->addOption(
                'directory',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.site.alias.options.directory')
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

        // Type i.e. ssh, local, container.
        $type = $input->getOption('type');
        if (!$type) {
            $type = $this->getIo()->choice(
                $this->trans('commands.generate.site.alias.questions.type'),
                $this->types,
                reset($this->types)
            );

            $input->setOption('type', $type);
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
                $this->io->comment($this->trans('commands.generate.site.alias.stage.remote'));
                $remote_host = $input->getOption('host');
                if (!$remote_host) {
                    $remote_host = $this->getIo()->askEmpty(
                        $this->trans('commands.generate.site.alias.questions.host')
                    );

                    $input->setOption('host', $remote_host);
                }

                $remote_port = $input->getOption('port');
                if (!$remote_port) {
                    $remote_port = $this->getIo()->askEmpty(
                        $this->trans('commands.generate.site.alias.questions.port')
                    );

                    $input->setOption('port', $remote_port);
                }

                $remote_user = $input->getOption('user');
                if (!$remote_user) {
                    $remote_user = $this->getIo()->askEmpty(
                        $this->trans('commands.generate.site.alias.questions.user')
                    );

                    $input->setOption('user', $remote_user);
                }
                break;
        }

        // Repository arguments.
        $this->io->comment($this->trans('commands.generate.site.alias.stage.repository'));
        $repo_type = $input->getOption('repo-type');
        if (!$repo_type) {
            $repo_type = $this->getIo()->askEmpty(
                $this->trans('commands.generate.site.alias.options.repo-type'),
                'git'
            );

            $input->setOption('repo-type', $repo_type);
        }
        $repo_url = $input->getOption('repo-url');
        if (!$repo_url) {
            $repo_url = $this->getIo()->askEmpty(
                $this->trans('commands.generate.site.alias.options.repo-url'),
                ''
            );

            $input->setOption('repo-url', $repo_url);
        }
        $repo_branch = $input->getOption('repo-branch');
        if (!$repo_branch) {
            $repo_branch = $this->getIo()->askEmpty(
                $this->trans('commands.generate.site.alias.options.repo-branch'),
                'master'
            );

            $input->setOption('repo-branch', $repo_branch);
        }

        // Database arguments.
        $this->io->comment($this->trans('commands.generate.site.alias.stage.database'));
        $db_driver = $input->getOption('db-driver');
        if (!$db_driver) {
            $db_driver = $this->getIo()->askEmpty(
                $this->trans('commands.generate.site.alias.options.db-driver'),
                'mysql'
            );

            $input->setOption('db-driver', $db_driver);
        }
        $db_host = $input->getOption('db-host');
        if (!$db_host) {
            $db_host = $this->getIo()->askEmpty(
                $this->trans('commands.generate.site.alias.options.db-host'),
                'mariadb'
            );

            $input->setOption('db-host', $db_host);
        }
        $db_port = $input->getOption('db-port');
        if (!$db_port) {
            $db_port = $this->getIo()->askEmpty(
                $this->trans('commands.generate.site.alias.options.db-port'),
                '3306'
            );

            $input->setOption('db-port', $db_port);
        }
        $db_name = $input->getOption('db-name');
        if (!$db_name) {
            $db_name = $this->getIo()->askEmpty(
                $this->trans('commands.generate.site.alias.options.db-name'),
                'drupal'
            );

            $input->setOption('db-name', $db_name);
        }
        $db_user = $input->getOption('db-user');
        if (!$db_user) {
            $db_user = $this->getIo()->askEmpty(
                $this->trans('commands.generate.site.alias.options.db-user'),
                'root'
            );

            $input->setOption('db-user', $db_user);
        }
        $db_pass = $input->getOption('db-pass');
        if (!$db_pass) {
            $db_pass = $this->getIo()->askEmpty(
                $this->trans('commands.generate.site.alias.options.db-pass'),
                ''
            );

            $input->setOption('db-pass', $db_pass);
        }
        $db_dump = $input->getOption('db-dump');
        if (!$db_dump) {
            $db_dump = $this->getIo()->askEmpty(
                $this->trans('commands.generate.site.alias.options.db-dump'),
                ''
            );

            $input->setOption('db-dump', $db_dump);
        }

        // Host.
        $this->io->comment($this->trans('commands.generate.site.alias.stage.host-name'));
        $host_name = $input->getOption('host-name');
        if (!$host_name) {
            $host_name = $this->getIo()->askEmpty(
                $this->trans('commands.generate.site.alias.questions.host-name'),
                'example.com'
            );

            $input->setOption('host-name', $host_name);
        }
        $host_port = $input->getOption('host-port');
        if (!$host_port) {
            $host_port = $this->getIo()->askEmpty(
                $this->trans('commands.generate.site.alias.questions.host-port'),
                '80'
            );

            $input->setOption('host-port', $host_port);
        }

        // Drupal root.
        $this->io->comment($this->trans('commands.generate.site.alias.stage.server'));
        $drupalRoot = $input->getOption('drupal-root');
        if (empty($drupalRoot)) {
            // Backwards compatibility after renaming option to drupal-root.
            $composerRoot = $input->getOption('composer-root');
            if (!empty($composerRoot)) {
                $drupalRoot = $composerRoot;
            }
        }
        if (!$drupalRoot) {
            $root = $this->drupalFinder->getComposerRoot();
            $drupalRoot = $this->getIo()->ask(
                $this->trans('commands.generate.site.alias.questions.drupal-root'),
                $root
            );

            $input->setOption('drupal-root', '/' . trim($drupalRoot, '/'));
        }

        // Server root.
        $serverRoot = $input->getOption('server-root');
        if (!$serverRoot) {
            $serverRoot = $this->getIo()->askEmpty(
                $this->trans('commands.generate.site.alias.options.server-root'),
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

        // Site installation arguments.
        $this->io->comment($this->trans('commands.generate.site.alias.stage.installation'));
        $account_name = $input->getOption('account-name');
        if (!$account_name) {
            $account_name = $this->getIo()->ask(
                $this->trans('commands.generate.site.alias.options.account-name'),
                'admin'
            );

            $input->setOption('account-name', $account_name);
        }
        $account_pass = $input->getOption('account-pass');
        if (!$account_pass) {
            $account_pass = $this->getIo()->askEmpty(
                $this->trans('commands.generate.site.alias.options.account-pass'),
                ''
            );

            $input->setOption('account-pass', $account_pass);
        }
        $account_mail = $input->getOption('account-mail');
        if (!$account_mail) {
            $account_mail = $this->getIo()->ask(
                $this->trans('commands.generate.site.alias.options.account-mail'),
                'email@example.com'
            );

            $input->setOption('account-mail', $account_mail);
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
                // Site.
                'name' => $input->getOption('name'),
                'environment' => $input->getOption('environment'),
                'type' => $input->getOption('type'),
                'extra_options' => $input->getOption('extra-options'),
                // Remote.
                'host' => $input->getOption('host'),
                'port' => $input->getOption('port'),
                'user' => $input->getOption('user'),
                // Repository.
                'repo_type' => $input->getOption('repo-type'),
                'repo_url' => $input->getOption('repo-url'),
                'repo_branch' => $input->getOption('repo-branch'),
                // Database.
                'db_driver' => $input->getOption('db-driver'),
                'db_host' => $input->getOption('db-host'),
                'db_port' => $input->getOption('db-port'),
                'db_name' => $input->getOption('db-name'),
                'db_user' => $input->getOption('db-user'),
                'db_pass' => $input->getOption('db-pass'),
                'db_dump' => $input->getOption('db-dump'),
                // Web host.
                'host_name' => $input->getOption('host-name'),
                'host_port' => $input->getOption('host-port'),
                // Server root.
                'root' => $input->getOption('drupal-root'),
                'server_root' => $input->getOption('server-root'),
                // Multisite.
                'uri' => $input->getOption('site-uri'),
                // Installation.
                'account_name' => $input->getOption('account-name'),
                'account_pass' => $input->getOption('account-pass'),
                'account_mail' => $input->getOption('account-mail'),
                // Output.
                'directory' => $directory,
            ]
        );
    }
}
