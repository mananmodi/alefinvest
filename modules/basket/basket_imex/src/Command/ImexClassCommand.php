<?php

namespace Drupal\basket_imex\Command;

use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Generator\GeneratorInterface;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BasketImexCommand.
 *
 * Drupal\Console\Annotations\DrupalCommand (
 *     extension="basket_imex",
 *     extensionType="module"
 * )
 */
class ImexClassCommand extends Command {

  use ConfirmationTrait;
  use ModuleTrait;

  /**
   * Drupal\Console\Core\Generator\GeneratorInterface definition.
   *
   * @var \Drupal\Console\Core\Generator\GeneratorInterface
   */
  protected $generator;

  /**
   * Manager object.
   *
   * @var \Drupal\Console\Extension\Manager
   */
  protected $extensionManager;

  /**
   * Validator object.
   *
   * @var \Drupal\Console\Utils\Validator
   */
  protected $validator;

  /**
   * String converter.
   *
   * @var \Drupal\Console\Core\Utils\StringConverter
   */
  protected $stringConverter;

  /**
   * Constructs a new EventNotificationsCommand object.
   */
  public function __construct(Manager $extensionManager, Validator $validator, StringConverter $stringConverter, GeneratorInterface $generator) {
    $this->generator = $generator;
    $this->extensionManager = $extensionManager;
    $this->validator = $validator;
    $this->stringConverter = $stringConverter;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('basket_imex:generate:service')
      ->setDescription('Generate a new "Basket IMEX Service"')
      ->addOption(
        'module',
        NULL,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.common.options.module')
      )
      ->addOption(
        'id',
        NULL,
        InputOption::VALUE_REQUIRED,
        'Enter Service ID'
      )
      ->addOption(
        'name',
        NULL,
        InputOption::VALUE_REQUIRED,
        'Enter Service name'
      )
      ->addOption(
        'class',
        NULL,
        InputOption::VALUE_REQUIRED,
        'Enter class name'
      )
      ->setAliases(['imex-s']);
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {

    // --module option
    $module = $this->getModuleOption();

    // --id option
    $id = $input->getOption('id');
    if (!$id) {
      $id = $this->getIo()->ask(
        'Enter Service ID',
        $module,
        function ($id) {
          return $this->validator->validateMachineName($id);
        }
      );
      $input->setOption('id', $id);
    }

    // --name option
    $name = $input->getOption('name');
    if (!$name) {
      $name = $this->getIo()->ask(
        'Enter Service name',
        implode(' ', $this->getArrNames($id))
      );
      $input->setOption('name', $name);
    }

    // --class option
    $class = $input->getOption('class');
    if (!$class) {
      $class = $this->getIo()->ask(
        'Enter Plugin name',
        implode('', $this->getArrNames($id)) . 'Class',
        function ($class) {
          return $this->validator->validateClassName($class);
        }
      );
      $input->setOption('class', $class);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    if (!$this->confirmOperation()) {
      return 1;
    }
    $this->generator->generate([
      'g_type' => 'service',
      'module' => $input->getOption('module'),
      'id' => $input->getOption('id'),
      'name' => $input->getOption('name'),
      'class' => $input->getOption('class'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  private function getArrNames($string) {
    $items = explode('_', $string);
    foreach ($items as &$item) {
      $item = ucfirst($item);
    }
    return $items;
  }

}
