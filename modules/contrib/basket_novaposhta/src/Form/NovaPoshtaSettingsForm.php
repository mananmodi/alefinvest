<?php

namespace Drupal\novaposhta\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class NovaPoshtaSettingsForm extends FormBase{

  protected $novaPoshta;
  protected $config;
  protected $configEn;
  protected $module_handler;
  
  function __construct(){
    $this->novaPoshta = \Drupal::service('NovaPoshta');
    $this->config = \Drupal::config('novaposhta.settings');
    $this->configEn = \Drupal::config('novaposhta.en.settings');
    $this->module_handler = \Drupal::service('module_handler');
  }
  
  public function getFormId(){
    return 'novaposhta_settings_form';
  }
  
  public function buildForm(array $form, FormStateInterface $form_state){
    $form['api'] = [
      '#type'         => 'details',
      '#title'        => $this->novaPoshta->t('API'),
      '#open'         => FALSE,
      '#tree'         => TRUE,
      'api_key'       => [
        '#type'         => 'textfield',
        '#title'        => $this->novaPoshta->t('API KEY'),
        '#default_value'=> $this->config->get('config.api_key')
      ],
      'hide_labels'   => [
        '#type'         => 'checkbox',
        '#title'        => $this->novaPoshta->t('Hide labels of the fields of the basket'),
        '#default_value'=> $this->config->get('config.hide_labels')
      ],
      'jquery_style'  => [
        '#type'         => 'select',
        '#title'        => 'jQuery style',
        '#required'     => TRUE,
        '#options'      => $this->getJqueryStyleOptions(),
        '#default_value'=> $this->config->get('config.jquery_style')
      ],
      'actions'       => [
        '#type'         => 'actions',
        'submit'        => [
          '#type'         => 'submit',
          '#value'        => $this->novaPoshta->t('Save API KEY'),
          '#attributes'   => [
            'class'         => ['button--primary']
          ],
          '#name'         => 'saveAPI'
        ]
      ]
    ];
    $HOME = !empty($_SERVER['HOME']) ? $_SERVER['HOME'] : '';
    if(empty($HOME) && !empty($_SERVER['DOCUMENT_ROOT'])){
      $HOME = dirname($_SERVER['DOCUMENT_ROOT'],1);
    }
    $form['en'] = [
      '#type'         => 'details',
      '#title'        => $this->novaPoshta->t('Invoices'),
      '#open'         => FALSE,
      '#tree'         => TRUE,
      'scheduler'     => [
        '#title'        => $this->novaPoshta->t('Scheduler'),
        '#type'         => 'textarea',
        '#value'        => 'export HOME="'.$HOME.'"; /usr/bin/php '.DRUPAL_ROOT.'/vendor/drush/drush/drush.php -r '.DRUPAL_ROOT.' novaposhta:status_update',
        '#parents'      => ['scheduler'],
        '#disabled'     => TRUE,
        '#rows'         => 2,
        '#description'  => $this->novaPoshta->t('Automatically launch status updates through server settings')
      ],
      'recipient'     => [
        'auto'          => [
          '#type'         => 'checkbox',
          '#title'        => $this->novaPoshta->t('Automatically create a recipient from an order'),
          '#default_value'=> $this->configEn->get('config.recipient.auto')
        ],
        'fields'        => [
          '#type'         => 'container',
          '#states'       => ['visible' => ['input[name="en[recipient][auto]"]' => ['checked' => TRUE]]],
          'LastName'      => [
            '#type'         => 'textfield',
            '#title'        => $this->novaPoshta->t('Surname'),
            '#default_value'=> $this->configEn->get('config.recipient.fields.LastName'),
            '#description'  => '[token] / {{ node.*** }}'
          ],
          'FirstName'     => [
            '#type'         => 'textfield',
            '#title'        => $this->novaPoshta->t('Name'),
            '#default_value'=> $this->configEn->get('config.recipient.fields.FirstName'),
            '#description'  => '[token] / {{ node.*** }}'
          ],
          'MiddleName'    => [
            '#type'         => 'textfield',
            '#title'        => $this->novaPoshta->t('Middle name'),
            '#default_value'=> $this->configEn->get('config.recipient.fields.MiddleName'),
            '#description'  => '[token] / {{ node.*** }}'
          ],
          'Phone'         => [
            '#type'         => 'textfield',
            '#title'        => $this->novaPoshta->t('Phone'),
            '#default_value'=> $this->configEn->get('config.recipient.fields.Phone'),
            '#description'  => '[token] / {{ node.*** }}'
          ],
          'Email'         => [
            '#type'         => 'textfield',
            '#title'        => $this->novaPoshta->t('E-mail'),
            '#default_value'=> $this->configEn->get('config.recipient.fields.Email'),
            '#description'  => '[token] / {{ node.*** }}'
          ],
          'token'         => [
            '#theme'        => 'token_tree_link',
            '#token_types'  => ['node'],
            '#text'         => $this->novaPoshta->t('[available tokens]'),
          ]
        ]
      ],
      'print'         => [
        '#type'         => 'select',
        '#title'        => $this->novaPoshta->t('Printing forms'),
        '#options'      => [
          'printDocumentPdf'      => $this->novaPoshta->t('Print by Number - PDF'),
          'printMarking100x100'   => $this->novaPoshta->t('Print 100x100 - PDF'),
          'printMarking85x85'     => $this->novaPoshta->t('Print 85x85 - PDF'),
        ],
        '#default_value'=> $this->configEn->get('config.print'),
      ],
      'actions'       => [
        '#type'         => 'actions',
        'submit'        => [
          '#type'         => 'submit',
          '#name'         => 'saveEN',
          '#value'        => $this->novaPoshta->t('Save configuration'),
          '#attributes'   => [
            'class'         => ['button--primary']
          ]
        ]
      ]
    ];
    $form['OptionsSeat'] = [
      '#type'       => 'details',
      '#title'      => $this->novaPoshta->t('The size of the parcel for the post office'),
      '#open'       => FALSE,
      'sizes'       => [
        '#type'       => 'table',
        '#header'     => [
          $this->novaPoshta->t('Height'),
          $this->novaPoshta->t('Width'),
          $this->novaPoshta->t('Length')
        ]
      ],
      'actions'       => [
        '#type'         => 'actions',
        'submit'        => [
          '#type'         => 'submit',
          '#name'         => 'saveOptionsSeat',
          '#value'        => $this->novaPoshta->t('Save configuration'),
          '#attributes'   => [
            'class'         => ['button--primary']
          ]
        ]
      ]
    ];
    
    foreach ($this->novaPoshta->defOptionsSeat() as $line) {
      $form['OptionsSeat']['sizes'][] = [
        'height'      => [
          '#markup'       => $line[0]
        ],
        'width'       => [
          '#markup'       => $line[1]
        ],
        'length'       => [
          '#markup'       => $line[2]
        ]
      ];
    }
    $sConfig = \Drupal::config('novaposhta.OptionsSeat');
    foreach (range(0, 5) as $k) {
      $form['OptionsSeat']['sizes'][] = [
        'height'      => [
          '#type'       => 'number',
          '#parents'    => ['OptionsSeat', 'sizes', $k, 'height'],
          '#default_value' => $sConfig->get('config.sizes.'.$k.'.height')
        ],
        'width'       => [
          '#type'       => 'number',
          '#parents'    => ['OptionsSeat', 'sizes', $k, 'width'],
          '#default_value' => $sConfig->get('config.sizes.'.$k.'.width')
        ],
        'length'       => [
          '#type'       => 'number',
          '#parents'    => ['OptionsSeat', 'sizes', $k, 'length'],
          '#default_value' => $sConfig->get('config.sizes.'.$k.'.length')
        ]
      ];
    }
    return $form;
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state){
    $triggerdElement = $form_state->getTriggeringElement();
    if(!empty($triggerdElement['#name'])){
      switch($triggerdElement['#name']){
        
        case'saveAPI':
          // Clear cache settings
          $this->novaPoshta->clearTMP();
          
          // Clear templates
          $configTemplate = \Drupal::config('novaposhta.en.template')->get('config');
          $configTemplate['sender'] = [];
          \Drupal::configFactory()->getEditable('novaposhta.en.template')
            ->set('config', $configTemplate)
            ->save();
          
          // Save configuration
          \Drupal::configFactory()->getEditable('novaposhta.settings')
            ->set('config', $form_state->getValue('api'))
            ->save();
          
          break;
          
        case'saveEN':
          // Save configuration
          \Drupal::configFactory()->getEditable('novaposhta.en.settings')
            ->set('config', $form_state->getValue('en'))
            ->save();
          break;
          
        case'saveOptionsSeat':
          // Save configuration
          \Drupal::configFactory()->getEditable('novaposhta.OptionsSeat')
            ->set('config', $form_state->getValue('OptionsSeat'))
            ->save();
          break;
      }
      \Drupal::messenger()->addMessage($this->novaPoshta->t('The configuration options have been saved.'));
    }
  }
  
  private function getJqueryStyleOptions(){
    $options = [
      'chosen'        => 'Chosen'
    ];
    if($this->module_handler->moduleExists('webform')){
      if(\Drupal::service('webform.libraries_manager')->getLibrary('jquery.select2')){
        $options['select2'] = 'select2';
      }
    }
    return $options;
  }
}
