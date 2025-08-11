<?php

namespace Drupal\novaposhta\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\novaposhta\API\NovaPoshtaAPI;

class NovaPoshtaCreateUserForm extends FormBase{

	protected $novaPoshta;
  protected $counterpartyProperty;
  protected $API;

	function __construct($CounterpartyProperty = NULL){
		$this->novaPoshta = \Drupal::service('NovaPoshta');
    $this->counterpartyProperty = $CounterpartyProperty;
    $this->API = new NovaPoshtaAPI(\Drupal::config('novaposhta.settings')->get('config.api_key'));
	}
 
	public function getFormId(){
    return 'novaposhta_create_user_form';
  }
  
  public function buildForm(array $form, FormStateInterface $form_state){
    $form += [
      '#prefix' => '<div id="novaposhta_create_user_form_ajax_wrap">',
      '#suffix' => '</div>',
      'status_messages' => [
        '#type' => 'status_messages'
      ]
    ];
    $form['CounterpartyProperty'] = [
      '#type' => 'hidden',
      '#value' => $this->counterpartyProperty
    ];
    $form['CounterpartyType'] = [
      '#type' => 'select',
      '#title' => $this->novaPoshta->t('Counterparty type'),
      '#options' => $this->API->getTypesOfCounterparties(),
      '#required' => TRUE,
      '#default_value' => 'PrivatePerson',
      '#ajax' => [
        'wrapper' => 'novaposhta_create_user_form_ajax_wrap',
        'callback' => '::ajaxReload'
      ]
    ];
    $CounterpartyType = $form_state->getValue('CounterpartyType');
    if($CounterpartyType == 'Organization'){
      $form['OwnershipForm'] = [
        '#type' => 'select',
        '#title' => $this->novaPoshta->t('Form of ownership'),
        '#options' => $this->API->getOwnershipFormsList(),
        '#required' => TRUE
      ];
      $form['FirstName'] = [
        '#type' => 'textfield',
        '#title' => $this->novaPoshta->t('Organization name'),
        '#required' => TRUE
      ];
    } else {
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
      $form['Email'] = [
        '#type' => 'email',
        '#title' => $this->novaPoshta->t('E-mail')
      ];
    }
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->novaPoshta->t('Create'),
        '#name' => 'save',
        '#ajax' => [
          'wrapper' => 'novaposhta_create_user_form_ajax_wrap',
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
        'CounterpartyType' => $values['CounterpartyType'],
        'CounterpartyProperty' => $values['CounterpartyProperty']
      ];
      switch($fields['CounterpartyType']){
        case'Organization':
          $fields['OwnershipForm'] = $values['OwnershipForm'];
          $fields['FirstName'] = trim($values['FirstName']);
          break;
        case'PrivatePerson':
          $fields['LastName'] = !empty($values['LastName']) ? trim($values['LastName']) : '';
          $fields['FirstName'] = !empty($values['FirstName']) ? trim($values['FirstName']) : '';
          $fields['MiddleName'] = !empty($values['MiddleName']) ? trim($values['MiddleName']) : '';
          $fields['Phone'] = !empty($values['Phone']) ? $this->novaPoshta->replacePhone(trim($values['Phone'])) : '';
          $fields['Email'] = !empty($values['Email']) ? trim($values['Email']) : '';
          break;
      }
      if(!empty($fields)){
        $result = $this->API->saveCounterparty($fields);
        if(!empty($result['errors'])){
          foreach ($result['errors'] as $error){
            \Drupal::messenger()->addMessage($error, 'error');
          }
        }
        if(!empty($result['success'])){
          $this->API->getCounterparties([
            'CounterpartyProperty' => $fields['CounterpartyProperty']
          ], TRUE);
          $response = new AjaxResponse();
          $form = [
            '#markup' => $this->novaPoshta->t('Counterparty successfully created')
          ];
          $response->addCommand(new ReplaceCommand('#novaposhta_create_user_form_ajax_wrap', $form));
          $setValues = [
            [
              'fieldName' => ['recipient','Recipient'],
              'value' => $result['data'][0]['Ref']
            ]
          ];
          if(!empty($result['data'][0]['ContactPerson']['data'][0]['Ref'])){
            $setValues[] = [
              'fieldName' => ['recipient','ContactRecipient'],
              'value' => $result['data'][0]['ContactPerson']['data'][0]['Ref']
            ];
            $this->API->getCounterpartyContactPersons([
              'Ref' => $result['data'][0]['Ref']
            ], TRUE);
          }
          $response->addCommand(new InvokeCommand('[name="ajaxSetValue"]', 'val', [json_encode($setValues)]));
          $response->addCommand(new InvokeCommand('[name="ajaxSetValue"]', 'change', []));
          return $response;
        }
      }
    }
    return $form;
  }
}
