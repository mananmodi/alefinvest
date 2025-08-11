<?php

namespace Drupal\novaposhta\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\novaposhta\API\NovaPoshtaAPI;

class NovaPoshtaCreateContactPersonForm extends FormBase{

	protected $novaPoshta;
  protected $counterpartyRef;
  protected $API;

	function __construct($CounterpartyRef = NULL){
		$this->novaPoshta = \Drupal::service('NovaPoshta');
    $this->counterpartyRef = $CounterpartyRef;
    $this->API = new NovaPoshtaAPI(\Drupal::config('novaposhta.settings')->get('config.api_key'));
	}
 
	public function getFormId(){
    return 'novaposhta_create_contact_person_form';
  }
  
  public function buildForm(array $form, FormStateInterface $form_state){
    $form += [
      '#prefix' => '<div id="novaposhta_create_contact_person_form_ajax_wrap">',
      '#suffix' => '</div>',
      'status_messages' => [
        '#type' => 'status_messages'
      ]
    ];
    $form['CounterpartyRef'] = [
      '#type' => 'hidden',
      '#value' => $this->counterpartyRef
    ];
    $form['LastName'] = [
      '#type' => 'textfield',
      '#title' => $this->novaPoshta->t('Surname'),
      '#required' => TRUE
    ];
    $form['FirstName'] = [
      '#type' => 'textfield',
      '#title' => $this->novaPoshta->t('Name'),
      '#required' => TRUE
    ];
    $form['MiddleName'] = [
      '#type' => 'textfield',
      '#title' => $this->novaPoshta->t('Middle name')
    ];
    $form['Phone'] = [
      '#type' => 'textfield',
      '#title' => $this->novaPoshta->t('Phone'),
      '#required' => TRUE,
      '#attributes' => [
        'data-mask' => $this->novaPoshta::PHONE_MASK,
        'placeholder' => str_replace(9, '_', $this->novaPoshta::PHONE_MASK)
      ]
    ];
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->novaPoshta->t('Create'),
        '#name' => 'save',
        '#ajax' => [
          'wrapper' => 'novaposhta_create_contact_person_form_ajax_wrap',
          'callback' => '::ajaxReload'
        ]
      ]
    ];
    return $form;
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state){}
  
  public function ajaxReload(array &$form, FormStateInterface $form_state){
    $triggerdElement = $form_state->getTriggeringElement();
    if(!empty($triggerdElement['#name']) && $triggerdElement['#name'] == 'save' && !$form_state->getErrors()){
      $values = $form_state->getValues();
      $fields = [
        'CounterpartyRef' => !empty($values['CounterpartyRef']) ? trim($values['CounterpartyRef']) : '',
        'FirstName' => !empty($values['FirstName']) ? trim($values['FirstName']) : '',
        'LastName' => !empty($values['LastName']) ? trim($values['LastName']) : '',
        'MiddleName' => !empty($values['MiddleName']) ? trim($values['MiddleName']) : '',
        'Phone' => !empty($values['Phone']) ? $this->novaPoshta->replacePhone(trim($values['Phone'])) : '',
      ];
      $result = $this->API->saveContactPerson($fields);
      if(!empty($result['errors'])){
        foreach ($result['errors'] as $error){
          \Drupal::messenger()->addMessage($error, 'error');
        }
      }
      if(!empty($result['success'])){
        $response = new AjaxResponse();
        \Drupal::service('BasketPopup')->openModal(
          $response,
          $this->novaPoshta->t('Create a contact person'),
          [
            '#markup' => $this->novaPoshta->t('Contact person created successfully')
          ],
          [
            'width' => 400,
            'class' => ['novaposhta_popup']
          ]
        );
        $this->API->getCounterpartyContactPersons([
          'Ref' => $values['CounterpartyRef']
        ], TRUE);
        $setValues = [
          [
            'fieldName' => ['recipient','ContactRecipient'],
            'value' => $result['data'][0]['Ref']
          ]
        ];
        $response->addCommand(new InvokeCommand('[name="ajaxSetValue"]', 'val', [json_encode($setValues)]));
        $response->addCommand(new InvokeCommand('[name="ajaxSetValue"]', 'change', []));
        return $response;
      }
    }
    return $form;
  }
}
