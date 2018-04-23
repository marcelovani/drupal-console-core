<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\Command.
 */

namespace Drupal\Console\Core\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Utils\DrupalFinder;

/**
 * Class Command
 *
 * @package Drupal\Console\Core\Command
 */
abstract class Command extends BaseCommand
{
    use CommandTrait;

    /**
     * @var DrupalFinder;
     */
    protected $drupalFinder;

    /**
     * @var DrupalStyle
     */
    private $io;

    /**
     * @var $learning
     */
    private $learning;

    /**
     * @var bool
     */
    private $maintenance = false;

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new DrupalStyle($input, $output);
        $this->learning = $input->getOption('learning');
    }

    /**
     * @return \Drupal\Console\Core\Style\DrupalStyle
     */
    public function getIo()
    {
        return $this->io;
    }

    /**
     * Check maintenance mode.
     *
     * @return bool
     */
    public function isMaintenance()
    {
        return $this->maintenance;
    }

    /**
     * Enable maintenance mode.
     *
     * @return $this
     *   Command.
     */
    public function enableMaintenance()
    {
        $this->maintenance = true;
        return $this;
    }

    /**
     * Create Exception
     *
     * @return void
     *
     */
    public function createException($message) {
        $this->getIo()->error($message);
        exit(1);
    }

    /**
     * @param \Drupal\Console\Core\Utils\DrupalFinder $drupalFinder
     */
    public function setDrupalFinder($drupalFinder) {
        $this->drupalFinder = $drupalFinder;
    }

    /**
     * Runs a list of commands.
     *
     * @param $list The list of commands to run.
     *
     * @todo Check if we can reuse the code found in ChainCommand.
     */
    public function runCommands(&$commands) {
      foreach ($commands as $key => $item) {
        $parameters = array();
        $command = $this->getApplication()->find($item['command']);

        // Command arguments.
        if (!empty($item['arguments'])) {
          foreach ($item['arguments'] as $name => $value) {
            $parameters[$name] = $value;
          }
        }

        // Command options.
        if (isset($item['options'])) {
          $options = array_filter($item['options']);
          foreach ($options as $name => $value) {
            $parameters['--' . $name] = $value;
          }
        }

        $commandInput = new ArrayInput(array_filter($parameters));
        if ($this->learning && !empty(trim($commandInput))) {
            $this->io->comment(
               $this->translator->trans('commands.exec.messages.executing-command') .': ',
               false
            );
            $this->getIo()->info($item['command']);
        }

        if ($command->run($commandInput, $this->getIo()) !== 0) {
          return 1;
        }
        // Remove from queue.
        unset($commands[$key]);
      }
    }

  }
