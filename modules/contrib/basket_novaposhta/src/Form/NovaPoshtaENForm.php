<?php
namespace Drupal\novaposhta\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\novaposhta\API\NovaPoshtaAPI;
use Drupal\Core\Url;

class NovaPoshtaENForm extends FormBase{

	protected $novaPoshta;
	protected $API;
  protected $isTemplate;
  protected $editEN;
  protected $orderId;
	protected $senderAjax;
	protected $recipientAjax;
  protected $cargoAjax;
  protected $BackwardDelivery;
  protected $AfterpaymentOnGoodsCost;
  protected $jQueryStyleID;

	function __construct($isTemplate = NULL, $editEN = NULL, $orderId = NULL){
		$this->novaPoshta = \Drupal::service('NovaPoshta');
		$this->API = new NovaPoshtaAPI();
    $this->isTemplate = $isTemplate;
    $this->editEN = $editEN;
    $this->orderId = $orderId;
    $this->jQueryStyleID = $this->novaPoshta->getJqueryStyleID();

    $this->senderAjax = [
      'wrapper' => 'novaposhta_en_sender_ajax_wrap',
      'callback' => [$this, 'ajaxSenderReload']
    ];

    $this->recipientAjax = [
      'wrapper' => 'novaposhta_en_recipient_ajax_wrap',
      'callback' => [$this, 'ajaxRecipientReload']
    ];

    $this->cargoAjax = [
      'wrapper' => 'novaposhta_en_Cargo_ajax_wrap',
      'callback' => [$this, 'ajaxCargoReload']
    ];

    $this->BackwardDelivery = [
      'wrapper' => 'novaposhta_en_BackwardDelivery_ajax_wrap',
      'callback' => [$this, 'ajaxBackwardDeliveryReload']
    ];
    $this->AfterpaymentOnGoodsCost = [
      'wrapper' => 'novaposhta_en_AfterpaymentOnGoodsCost_ajax_wrap',
      'callback' => [$this, 'ajaxAfterpaymentOnGoodsCostReload']
    ];
	}

	public function getFormId(){
    return 'novaposhta_en_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state){

    // Set default value
	  if(empty($form_state->getValues())) {
      $this->novaPoshta->EN()->defValues($form_state, $this->editEN, $this->orderId);
	  }
    if(!empty($this->editEN) && empty($this->editEN->ref)){
      return \Drupal::service('Basket')->getError(404);
    }

    $triggerdElement = $form_state->getTriggeringElement();
    if(!empty($triggerdElement['#name']) && $triggerdElement['#name'] == 'ajaxSetValue'){
      $this->changeFormState($form_state);
    }

    $form += [
      '#prefix' => '<div id="novaposhta_en_form_ajax_wrap">',
      '#suffix'	=> '</div>',
      '#attached' => [
        'library'	=> [
          'novaposhta/admin'
        ]
      ]
    ];
    $this->novaPoshta->JquerySelectAttached($form);

    if(!empty($this->editEN) && $form_state->getValue('ServiceType') == 'WarehouseDoors'){
      return \Drupal::service('Basket')->getError(403, $this->novaPoshta->t('Editing is only available in the business office'));
    }

    // ServiceType
    $form['ServiceType'] = [
      '#type' => 'select',
      '#title' => $this->novaPoshta->t('Delivery type'),
      '#options' => [
        'WarehouseWarehouse' => $this->novaPoshta->t('Warehouse - Warehouse'),
        'WarehouseDoors' => $this->novaPoshta->t('Warehouse - Address')
      ],
      '#required' => TRUE,
      '#weight' => -100,
      '#default_value' => $form_state->getValue('ServiceType'),
      '#ajax' => [
        'wrapper' => 'novaposhta_en_form_ajax_wrap',
        'callback' => '::ajaxReload',
      ]
    ];
    if(empty($form_state->getValue('ServiceType'))){
      return $form;
    }
    if(!empty($this->editEN)){
      $form['ServiceType']['#disabled'] = TRUE;
    }
    $form['users']	= [
      '#type' => 'table',
      '#header' => [
        $this->novaPoshta->t('Sender data'),
        $this->novaPoshta->t('Recipient data')
      ]
    ];
    $form['ajaxSetValue'] = [
      '#type' => 'textarea',
      '#ajax' => [
        'wrapper' => 'novaposhta_en_form_ajax_wrap',
        'callback' => '::ajaxReload',
        'event' => 'change'
      ],
      '#attributes' => [
        'style' => 'display:none;'
      ]
    ];

    // Sender data
    $form['users']['']['sender'] = [
      '#parents' => ['sender'],
			'#prefix'	=> '<div id="'.$this->senderAjax['wrapper'].'">',
			'#suffix'	=> '</div>',
			'#wrapper_attributes' => [
				'class' => ['not_hover'],
				'width'	=> '50%'
			]
    ];
    $options = [
      '_none' => $this->novaPoshta->t('No template'),
    ];
    $templates = \Drupal::config('novaposhta.en.template')->get('config.sender.templates') ?? [];
    $form_state->setValue(['sender', 'templates'], $templates);
    $merge_templates = [];
    foreach ($templates as $key => $template) {
      $merge_templates[$key] = $key;
    }
    $options = array_replace_recursive($options, $merge_templates);
    $options['_new'] = $this->novaPoshta->t('Create a new template');
    $form['users']['']['sender']['template'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->novaPoshta->t('Sender template'),
      '#default_value' => $form_state->getValue(['sender', 'template']) ?? '_none',
      '#template_change' => TRUE,
      '#ajax' => $this->senderAjax,
    ];
    $triggerdElement = $form_state->getTriggeringElement();
    $template = $form_state->getValue(['sender', 'template']);
    if (isset($templates[$template]) && !empty($triggerdElement['#template_change'])) {
      $sender_data = $form_state->getValue('sender');
      $sender_data = array_replace_recursive($sender_data, $templates[$template]);
      $form_state->setValue('sender', $sender_data);

      $user_input = $form_state->getUserInput();
      $user_input['sender'] = array_replace_recursive($user_input['sender'], $templates[$template]);
      $form_state->setUserInput($user_input);
    }

    if ($template === '_new' && !empty($triggerdElement['#template_change'])) {
      $user_input = $form_state->getUserInput();
      if (isset($user_input['sender']['template_name'])) {
        unset($user_input['sender']['template_name']);
        $form_state->setUserInput($user_input);
      }
    }
    if ($template && $template !== '_none' && $template !== '_new') {
      $form['users']['']['sender']['template_remove'] = [
        '#type' => 'button',
        '#ajax' => [
          'wrapper' => 'novaposhta_en_sender_ajax_wrap',
          'callback' => [$this, 'removeTemplate']
        ],
        '#template_name' => $form_state->getValue(['sender', 'template_name']),
        '#value' => $this->novaPoshta->t('Delete template'),
        '#attributes' => [
          'style' => 'margin: 0; background: red !important;',
        ],
      ];
    }
    if ($template && $template !== '_none') {
      $form['users']['']['sender']['template_name'] = [
        '#type' => 'textfield',
        '#title' => $this->novaPoshta->t('Template name'),
        '#default_value' => $template === '_new' ? NULL : $form_state->getValue(['sender', 'template_name']),
        '#required' => TRUE,
      ];
    }

    $this->setAddress($form['users']['']['sender'], $form_state, $this->senderAjax);
    $form['users']['']['sender']['Sender'] = [
      '#type' => 'select',
      '#title' => $this->novaPoshta->t('Sender'),
      '#options' => $this->API->getCounterparties([
        'CounterpartyProperty' => 'Sender'
      ]),
      '#required' => TRUE,
      '#ajax' => $this->senderAjax,
      '#default_value' => $form_state->getValue(['sender', 'Sender'])
    ];
    $form['users']['']['sender']['ContactSender'] = [
      '#type' => 'select',
      '#title' => $this->novaPoshta->t('Contact person'),
      '#options' => $this->API->getCounterpartyContactPersons([
        'Ref' => !empty($SenderRef = $form_state->getValue(['sender', 'Sender'])) ? $SenderRef : NULL
      ]),
      '#required' => TRUE,
      '#empty_option' => t('- Select -'),
      '#ajax' => $this->senderAjax,
      '#default_value' => $form_state->getValue(['sender', 'ContactSender'])
    ];

    $SendersPhones = [];
    if(!empty($SenderRef = $form_state->getValue(['sender', 'Sender'])) && !empty($ContactSender = $form_state->getValue(['sender', 'ContactSender']))){
      $SendersPhones = $this->API->getCounterpartyContactPersons([
        'Ref' => $SenderRef,
        'Contact' => $ContactSender,
      ]);
    }
    $form['users']['']['sender']['SendersPhone'] = [
      '#type' => 'select',
      '#title' => $this->novaPoshta->t('Sender\'s phone'),
      '#options' => $SendersPhones,
      '#required' => TRUE,
      '#empty_option' => t('- Select -'),
      '#ajax' => $this->senderAjax,
      '#default_value' => $form_state->getValue(['sender', 'SendersPhone'])
    ];
    if(!empty($SendersPhone = $form_state->getValue(['sender', 'SendersPhone'])) && !empty($SenderRef)){
      if($SendersPhone == 'PhoneCustom'){
        $form['users']['']['sender']['SendersPhoneCustom'] = [
          '#type' => 'textfield',
          '#title' => $this->novaPoshta->t('Sender\'s phone'),
          '#required' => TRUE,
          '#attributes' => [
            'data-mask' => $this->novaPoshta::PHONE_MASK,
            'placeholder' => str_replace(9, '_', $this->novaPoshta::PHONE_MASK)
          ],
          '#default_value' => $form_state->getValue(['sender', 'SendersPhoneCustom'])
        ];
      }
    }

    // Recipient data
    $form['users']['']['recipient'] = [
      '#parents' => ['recipient'],
			'#prefix'	=> '<div id="'.$this->recipientAjax['wrapper'].'">',
			'#suffix'	=> '</div>',
			'#wrapper_attributes' => [
				'class' => ['not_hover'],
				'width'	=> '50%'
			]
    ];
    if($this->isTemplate){
      $form['users']['']['recipient']['text'] = [
        '#markup' => $this->novaPoshta->t('The data is filled individually')
      ];
    } else {
      $this->setAddress($form['users']['']['recipient'], $form_state, $this->recipientAjax);
      switch($form_state->getValue('ServiceType')){
        case'WarehouseDoors':
          $form['users']['']['recipient']['RecipientType'] = [
            '#type' => 'select',
            '#title' => $this->novaPoshta->t('Recipient type'),
            '#options' => $this->API->getTypesOfCounterparties(),
            '#required' => TRUE,
            '#default_value' => $form_state->getValue(['recipient', 'RecipientType'])
          ];
          $form['users']['']['recipient']['RecipientLastName'] = [
            '#type' => 'textfield',
            '#title' => $this->novaPoshta->t('Last name'),
            '#required' => TRUE,
            '#default_value' => $form_state->getValue(['recipient', 'RecipientLastName'])
          ];
          $form['users']['']['recipient']['RecipientFirstName'] = [
            '#type' => 'textfield',
            '#title' => $this->novaPoshta->t('Name'),
            '#required' => TRUE,
            '#default_value' => $form_state->getValue(['recipient', 'RecipientFirstName'])
          ];
          $form['users']['']['recipient']['RecipientMiddleName'] = [
            '#type' => 'textfield',
            '#title' => $this->novaPoshta->t('Middle name'),
            '#required' => TRUE,
            '#default_value' => $form_state->getValue(['recipient', 'RecipientMiddleName'])
          ];
          $form['users']['']['recipient']['RecipientsPhone'] = [
            '#type' => 'textfield',
            '#title' => $this->novaPoshta->t('Recipient\'s phone'),
            '#required' => TRUE,
            '#attributes' => [
              'data-mask' => $this->novaPoshta::PHONE_MASK,
              'placeholder' => str_replace(9, '_', $this->novaPoshta::PHONE_MASK)
            ],
            '#default_value' => $form_state->getValue(['recipient', 'RecipientsPhone'])
          ];
          break;
        default:
          $form['users']['']['recipient']['Recipient'] = [
            '#type' => 'select',
            '#title' => $this->novaPoshta->t('Recipient'),
            '#options' => $this->API->getCounterparties([
              'CounterpartyProperty' => 'Recipient'
            ]),
            '#required' => TRUE,
            '#ajax' => $this->recipientAjax,
            '#field_suffix' => [
              '#type' => 'inline_template',
              '#template' => '<a href="javascript:void(0);" class="button--link target" onclick="{{ onclick }}" data-post="{{ post }}">{{ text }}</a>',
              '#context' => [
                'text' => $this->novaPoshta->t('Create'),
                'onclick' => 'basket_admin_ajax_link(this, \''.Url::fromRoute('basket.admin.pages', [
                  'page_type' => 'api-novaposhta-createCounterparty'
                  ])->toString().'\')',
                'post' => json_encode(['CounterpartyProperty' => 'Recipient'])
              ]
            ],
            '#default_value' => $form_state->getValue(['recipient', 'Recipient'])
          ];
          $RecipientRef = $form_state->getValue(['recipient', 'Recipient']);
          if(!empty($RecipientRef) && empty($form['users']['']['recipient']['Recipient']['#options'][$RecipientRef])){
            $RecipientRef = NULL;
          } 
          $options = [];
          if ($form_state->getValue(['recipient', 'RecipientsPhone'])) {
            $contact = $this->API->getCounterpartyContactPersons([
              'Ref' => $RecipientRef,
            ], FALSE, $form_state->getValue(['recipient', 'RecipientsPhone']));
            foreach ($contact['data'] as $key => $item) {
              if (!empty($contact['data'][0]['Ref'])) {
                $options[$item['Ref']] = $item['LastName'] . ' ' . $item['FirstName'];
                $phone = $item['Phones'];
              }
            }
          }
          if (empty($options)) {
            $options = $this->API->getCounterpartyContactPersons([
              'Ref'	=> $RecipientRef
            ]);
          }
          $form['users']['']['recipient']['ContactRecipient'] = [
            '#type'	=> 'select',
            '#title' => $this->novaPoshta->t('Contact person'),
            '#options' => $options,
            '#empty_option' => t('- Select -'),
            '#required'	=> TRUE,
            '#ajax' => $this->recipientAjax,
            '#default_value' => $form_state->getValue(['recipient', 'ContactRecipient'])
          ];
          if(!empty($RecipientRef)){
            $form['users']['']['recipient']['ContactRecipient']['#field_suffix'] = [
              '#type' => 'inline_template',
              '#template' => '<a href="javascript:void(0);" class="button--link target" onclick="{{ onclick }}" data-post="{{ post }}">{{ text }}</a>',
              '#context' => [
                'text' => $this->novaPoshta->t('Create'),
                'onclick' => 'basket_admin_ajax_link(this, \''.Url::fromRoute('basket.admin.pages', [
                  'page_type' => 'api-novaposhta-createContactRecipient'
                  ])->toString().'\')',
                'post' => json_encode(['CounterpartyRef' => $RecipientRef])
              ]
            ];
          }
          $RecipientsPhones = [];
          if(!empty($RecipientRef = $form_state->getValue(['recipient', 'Recipient'])) && !empty($ContactRecipient = $form_state->getValue(['recipient', 'ContactRecipient']))){
            $RecipientsPhones = $this->API->getCounterpartyContactPersons([
              'Ref' => $RecipientRef,
              'Contact' => $ContactRecipient,
            ]);
          }
          if (!empty($phone)) {
            $RecipientsPhones[$phone] = $phone;
          }
          $form['users']['']['recipient']['RecipientsPhone'] = [
            '#type' => 'select',
            '#title' => $this->novaPoshta->t('Recipient\'s phone'),
            '#options' => $RecipientsPhones,
            '#required' => TRUE,
            '#empty_option' => t('- Select -'),
            '#ajax'	=> $this->recipientAjax,
            '#default_value' => $form_state->getValue(['recipient', 'RecipientsPhone'])
          ];
          if(!empty($RecipientsPhone = $form_state->getValue(['recipient', 'RecipientsPhone'])) && !empty($RecipientRef)){
            if($RecipientsPhone == 'PhoneCustom'){
              $form['users']['']['recipient']['RecipientsPhoneCustom'] = [
                '#type' => 'textfield',
                '#title' => $this->novaPoshta->t('Recipient\'s phone'),
                '#required' => TRUE,
                '#attributes'	=> [
                  'data-mask' => $this->novaPoshta::PHONE_MASK,
                  'placeholder' => str_replace(9, '_', $this->novaPoshta::PHONE_MASK)
                ],
                '#default_value' => $form_state->getValue(['recipient', 'RecipientsPhoneCustom'])
              ];
            }
          }
          break;
      }
    }

    $form['all'] = [
      '#type' => 'table',
      '#header' => [
        $this->novaPoshta->t('Parameters of departure'),
        $this->novaPoshta->t('Payment Information'),
      ],
      '' => []
    ];
    $form['all']['']['parameters'] = [
      '#wrapper_attributes'=> [
        'class' => ['not_hover'],
        'width' => '50%'
      ],
      'Cargo' => [
        '#parents' => [],
        '#prefix' => '<div id="'.$this->cargoAjax['wrapper'].'">',
        '#suffix' => '</div>',
        'CargoType' => [
          '#type' => 'select',
          '#title' => $this->novaPoshta->t('Type of cargo'),
          '#options' => $this->API->getCargoTypes(),
          '#required' => TRUE,
          '#ajax' => $this->cargoAjax,
          '#default_value' => $form_state->getValue('CargoType')
        ],
      ]
    ];

    switch($form_state->getValue('CargoType')){
      case'Parcel':
      case'Cargo':
      $form['all']['']['parameters']['Cargo']['Weight'] = [
        '#type' => 'number',
        '#title' => $this->novaPoshta->t('Total weight'),
        '#min' => 0.1,
        '#step' => 0.1,
        '#field_suffix' => $this->novaPoshta->t('kg'),
        '#required' => TRUE,
        '#default_value' => $form_state->getValue('Weight')
      ];
      $form['all']['']['parameters']['Cargo']['SeatsAmount'] = [
        '#type' => 'number',
        '#title' => $this->novaPoshta->t('Number of seats'),
        '#min' => 1,
        '#step' => 1,
        '#required' => TRUE,
        '#default_value' => $form_state->getValue('SeatsAmount')
      ];
      break;
      case'Documents':
        $form['all']['']['parameters']['Cargo']['Weight'] = [
          '#type' => 'select',
          '#title' => $this->novaPoshta->t('Total weight'),
          '#options' => [
            '0.1' => '0.1',
            '0.5' => '0.5',
            '1.0' => '1.0'
          ],
          '#field_suffix' => $this->novaPoshta->t('kg'),
          '#required' => TRUE,
          '#default_value' => $form_state->getValue('Weight')
        ];
        $form['all']['']['parameters']['Cargo']['SeatsAmount'] = [
          '#type' => 'number',
          '#title' => $this->novaPoshta->t('Number of seats'),
          '#min' => 1,
          '#step' => 1,
          '#required' => TRUE,
          '#default_value' => $form_state->getValue('SeatsAmount')
        ];
        break;
    }
    $form['all']['']['payment'] = [
      '#wrapper_attributes'=> [
        'class' => ['not_hover'],
        'width' => '50%'
      ],
      '#parents' => [],
      'Cost' => [
        '#type' => 'number',
        '#title' => $this->novaPoshta->t('Announced price'),
        '#required' => TRUE,
        '#min' => 1,
        '#step' => 1,
        '#field_suffix' => $this->novaPoshta->t('UAH'),
        '#default_value' => $form_state->getValue('Cost')
      ],
      'BackwardDelivery' => [
        '#prefix' => '<div id="'.$this->BackwardDelivery['wrapper'].'">',
        '#suffix' => '</div>',
        'on' => [
          '#type'  => 'checkbox',
          '#title' => $this->novaPoshta->t('Cash on delivery'),
          '#ajax' => $this->BackwardDelivery,
          '#default_value' => $form_state->getValue(['BackwardDelivery', 'on'])
        ]
      ],
      'AfterpaymentOnGoodsCost' => [
        '#prefix' => '<div id="'.$this->AfterpaymentOnGoodsCost['wrapper'].'">',
        '#suffix' => '</div>',
        'on' => [
          '#type' => 'checkbox',
          '#title' => $this->novaPoshta->t('Payment control'),
          '#ajax' => $this->AfterpaymentOnGoodsCost,
          '#default_value' => $form_state->getValue(['AfterpaymentOnGoodsCost', 'on'])
        ]
      ],
      'Description' => [
        '#type' => 'textfield',
        '#title' => $this->novaPoshta->t('Description of departure'),
        '#required' => TRUE,
        '#default_value' => $form_state->getValue('Description')
      ],
      'DateTime' => [
        '#type' => 'date',
        '#title' => $this->novaPoshta->t('Date of dispatch'),
        '#required' => TRUE,
        '#default_value' => $form_state->getValue('DateTime')
      ],
      'PayerType' => [
        '#type' => 'select',
        '#title' => $this->novaPoshta->t('Payer'),
        '#options' => $this->API->getTypesOfPayers([]),
        '#required' => TRUE,
        '#default_value' => $form_state->getValue('PayerType')
      ],
      'PaymentMethod' => [
        '#type' => 'select',
        '#title' => $this->novaPoshta->t('Form of payment'),
        '#options' => $this->API->getPaymentForms([]),
        '#required' => TRUE,
        '#default_value' => $form_state->getValue('PaymentMethod')
      ],
      'InfoRegClientBarcodes' => [
        '#type' => 'textfield',
        '#title' => $this->novaPoshta->t('Internal number'),
        '#default_value' => $form_state->getValue('InfoRegClientBarcodes'),
      ]
    ];
    if(!empty($this->orderId)){
      $form['all']['']['payment']['InfoRegClientBarcodes']['#value'] = $this->orderId;
      $form['all']['']['payment']['InfoRegClientBarcodes']['#disabled'] = TRUE;
    }
    if(!empty($form_state->getValue(['BackwardDelivery', 'on']))){
      $form['all']['']['payment']['BackwardDelivery']['amount'] = [
        '#type' => 'number',
        '#title' => $this->novaPoshta->t('Cash on delivery amount'),
        '#required' => TRUE,
        '#min' => 0,
        '#step' => 0.01,
        '#field_suffix' => $this->novaPoshta->t('UAH'),
        '#default_value' => $form_state->getValue(['BackwardDelivery', 'amount']),
      ];
      if(!empty($form_state->getValue(['BackwardDelivery', 'disabled']))){
        $form['all']['']['payment']['BackwardDelivery']['amount']['#disabled'] = TRUE;
        $form['all']['']['payment']['BackwardDelivery']['on']['#disabled'] = TRUE;
      }
    }
    if(!empty($form_state->getValue(['AfterpaymentOnGoodsCost', 'on']))){
      $form['all']['']['payment']['AfterpaymentOnGoodsCost']['amount'] = [
          '#type' => 'number',
          '#title' => $this->novaPoshta->t('Summ'),
          '#required' => TRUE,
          '#min' => 0,
          '#step' => 0.01,
          '#field_suffix' => $this->novaPoshta->t('UAH'),
          '#default_value' => $form_state->getValue(['AfterpaymentOnGoodsCost', 'amount']),
        ];
      if(!empty($form_state->getValue(['AfterpaymentOnGoodsCost', 'disabled']))){
          $form['all']['']['payment']['AfterpaymentOnGoodsCost']['amount']['#disabled'] = TRUE;
          $form['all']['']['payment']['AfterpaymentOnGoodsCost']['on']['#disabled'] = TRUE;
        }
    }
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#name' => 'insert',
        '#value' => $this->novaPoshta->t('Create invoice'),
        '#validate' => ['::insertValid']
      ]
    ];
    if($this->isTemplate){
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#name' => 'saveTemplate',
        '#value' => $this->novaPoshta->t('Save template')
      ];
    }
    if(!empty($this->editEN)){
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#name' => 'update',
        '#value' => $this->novaPoshta->t('Update invoice'),
        '#validate' => ['::validatePaymentOptions', '::insertValid']
      ];
    }

    $recipientPoint = $form_state->getValue(['recipient', 'point']);
    if($form_state->getValue('CargoType') != 'Documents') {
      if(!empty($recipientPoint) && !empty($form['users']['']['recipient']['point']['#options'])) {
        if(strpos($form['users']['']['recipient']['point']['#options'][$recipientPoint], 'Поштомат') !== FALSE) {
          $form['all']['']['parameters']['Cargo']['OptionsSeat'] = [
            '#type' => 'select',
            '#title' => $this->novaPoshta->t('Cell'),
            '#required' => TRUE,
            '#options' => $this->novaPoshta->getOptionsSeat()
          ];
        }
      }
    }

    return $form;
  }

  public function validatePaymentOptions(array &$form, FormStateInterface $form_state){
    if ( !empty($form_state->getValue(['BackwardDelivery', 'on'])) && !empty($form_state->getValue(['AfterpaymentOnGoodsCost', 'on'])) ){
      $form_state->setErrorByName('AfterpaymentOnGoodsCost][on', $this->novaPoshta->t("'Cash on delivery' and 'Payment control' are not allowed at the same time"));
    }
  }

  public function removeTemplate($form, $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (!empty($triggering_element['#template_name'])) {
      $templateName = $triggering_element['#template_name'];
      $config = \Drupal::configFactory()->getEditable('novaposhta.en.template');
      $templates = $config->get('config.sender.templates');
      if (!empty($templates[$templateName])) {
        unset($templates[$templateName]);
      }
      $config->set('config.sender.templates', $templates);
      $config->save();
      unset($form['users']['']['sender']['template']['#options'][$templateName]);
      unset($form['users']['']['sender']['template_name']);
      unset($form['users']['']['sender']['template_remove']);
      $form_state->setRebuild();
    }
    return $form['users']['']['sender'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state){
    $triggerdElement = $form_state->getTriggeringElement();
    if(!empty($triggerdElement['#name'])){
      switch($triggerdElement['#name']){
        case'saveTemplate':
          $values = $form_state->getValues();
          foreach ($values as $key => $value){
            if(in_array($key, ['submit', 'form_build_id', 'form_token', 'form_id', 'saveTemplate', 'ajaxSetValue'])){
              unset($values[$key]);
            }
          }
          $templates = \Drupal::config('novaposhta.en.template')->get('config.sender.templates') ?? [];
          if (!empty($values['sender']['template']) && $values['sender']['template'] !== '_none' && !empty($values['sender']['template_name'])) {
            unset($values['sender']['template']);
            $templates[$values['sender']['template_name']] = $values['sender'];
            $values['sender'] = \Drupal::config('novaposhta.en.template')->get('config.sender');
          }
          $values['sender']['templates'] = $templates;
          if (isset($values['sender']['template'])) {
            unset($values['sender']['template']);
          }
          // Save configuration
          $config = \Drupal::configFactory()->getEditable('novaposhta.en.template');
          $config->set('config', $values);
          $config->save();
          \Drupal::messenger()->addMessage($this->novaPoshta->t('The configuration options have been saved.'), 'status');
          break;
        case'insert':
          if(!empty($IntDocNumber = $form_state->get('IntDocNumber'))){
            $form_state->setRedirect('basket.admin.pages', [
              'page_type' => 'novaposhta'
            ],[
              'query' => [
                'viewEn' => $IntDocNumber
              ]
            ]);
          }
          break;
      }
    }
  }

  public function insertValid($form, $form_state){
    if ( $form_state->hasAnyErrors() ){
      return;
    }
    $triggerdElement = $form_state->getTriggeringElement();
    if(!empty($triggerdElement['#name'])){
      switch($triggerdElement['#name']){
        case'update':
        case'insert':
          \Drupal::service('NovaPoshta')->EN()->setEn($form_state, $this->editEN);
          break;
      }
    }
  }

  public function setAddress(&$form, $form_state, $ajax){
    $key = $form['#parents'][0];
    $ServiceTypeKey = $form_state->getValue('ServiceType').'_'.$key;
    switch($ServiceTypeKey){
      case'WarehouseDoors_recipient':
        $form['city'] = [
          '#type' => 'textfield',
          '#title' => $this->novaPoshta->t('City'),
          '#required' => TRUE,
          '#default_value' => $form_state->getValue([$key, 'city']),
        ];
        $form['street'] = [
          '#type' => 'textfield',
          '#title' => $this->novaPoshta->t('Street'),
          '#required' => TRUE,
          '#default_value' => $form_state->getValue([$key, 'street'])
        ];
        $form['house'] = [
          '#type' => 'textfield',
          '#title' => $this->novaPoshta->t('House number'),
          '#required' => TRUE,
          '#default_value' => $form_state->getValue([$key, 'house'])
        ];
        break;
      default:
        $form['region'] = [
          '#type' => 'select',
          '#title' => $this->novaPoshta->t('Region'),
          '#options' => $this->API->getRegions([]),
          '#ajax' => $ajax,
          '#required' => TRUE,
          '#attributes' => [
            'class' => [$this->jQueryStyleID.'_np_select'],
            'data-min' => 0
          ]
        ];
        if(!empty($region = $form_state->getValue([$key, 'region']))){
          if(!empty($form['region']['#options'][$region])) $form['region']['#default_value'] = $region;
          else $form_state->setValue([$key, 'region'], NULL);
        }
        $form['city'] = [
          '#type' => 'select',
          '#title' => $this->novaPoshta->t('City'),
          '#options' => $this->API->getCitis([
            'region' => $form_state->getValue([$key, 'region'])
          ]),
          '#ajax' => $ajax,
          '#required' => TRUE,
          '#attributes' => [
            'class' => [$this->jQueryStyleID.'_np_select'],
            'data-min' => 0
          ]
        ];
        if(!empty($city = $form_state->getValue([$key, 'city']))){
          if(!empty($form['city']['#options'][$city])) $form['city']['#default_value'] = $city;
          else $form_state->setValue([$key, 'city'], NULL);
        }
        $form['point'] = [
          '#type' => 'select',
          '#title' => $this->novaPoshta->t('Point'),
          '#options' => $this->API->getPoints([
            'city' => $form_state->getValue([$key, 'city'])
          ]),
          '#ajax' => [
            'wrapper' => 'novaposhta_en_form_ajax_wrap',
            'callback' => '::ajaxReload',
          ],
          '#required' => TRUE,
          '#attributes' => [
            'class' => [$this->jQueryStyleID.'_np_select'],
            'data-min' => 0
          ]
        ];
        if(!empty($point = $form_state->getValue([$key, 'point']))){
          if(!empty($form['point']['#options'][$point])) $form['point']['#default_value'] = $point;
          else $form_state->setValue([$key, 'point'], NULL);
        }
        break;
    }
  }

  public static function ajaxReload($form, $form_state){
    return $form;
  }

  public function ajaxRecipientReload($form, $form_state){
    return $form['users']['']['recipient'];
  }

  public function ajaxSenderReload($form, $form_state){
    return $form['users']['']['sender'];
  }

  public function ajaxCargoReload($form, $form_state){
    return $form['all']['']['parameters']['Cargo'];
  }

  public function ajaxBackwardDeliveryReload($form, $form_state){
    return $form['all']['']['payment']['BackwardDelivery'];
  }
  public function ajaxAfterpaymentOnGoodsCostReload($form, $form_state){
    return $form['all']['']['payment']['AfterpaymentOnGoodsCost'];
  }

  public static function changeFormState($form_state){
    if(!empty($setValue = $form_state->getValue('ajaxSetValue'))){
      $setValues = json_decode(trim($setValue), TRUE);
      if(!empty($setValues)){
        foreach ($setValues as $setValue){
          $form_state->setValue($setValue['fieldName'], $setValue['value']);
        }
      }
      $form_state->setValue('ajaxSetValue', '');
      $values  = &$form_state->getValues();
      $userInput = &$form_state->getUserInput();
      $userInput = $values;
    }
  }
}
