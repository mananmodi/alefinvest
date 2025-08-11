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
class ImexFieldCommand extends Command {

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
      ->setName('basket_imex:generate:field')
      ->setDescription('Generate a new "Basket IMEX Field"')
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
        'Enter Field ID'
      )
      ->addOption(
        'name',
        NULL,
        InputOption::VALUE_REQUIRED,
        'Enter Field name'
      )
      ->addOption(
        'type',
        NULL,
        InputOption::VALUE_REQUIRED,
        'Enter Field Type'
      )
      ->addOption(
        'class',
        NULL,
        InputOption::VALUE_REQUIRED,
        'Enter class name'
      )
      ->setAliases(['imex-f']);
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
        'Enter Field ID',
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
        'Enter Field name',
        implode(' ', $this->getArrNames($id))
      );
      $input->setOption('name', $name);
    }

    // --f_type option
    $type = $input->getOption('type');
    if (!$type) {
      $type = $this->getIo()->ask(
        'Enter Field Type',
        'string'
      );
      $input->setOption('type', $type);
    }

    // --class option
    $class = $input->getOption('class');
    if (!$class) {
      $class = $this->getIo()->ask(
        'Enter Field name',
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
      'g_type' => 'field',
      'module' => $input->getOption('module'),
      'id' => $input->getOption('id'),
      'name' => $input->getOption('name'),
      'type' => $input->getOption('type'),
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
