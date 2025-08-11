<?php

namespace Drupal\novaposhta;

use \Drupal\Core\Url;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\novaposhta\Form\NovaPoshtaENForm;
use Drupal\novaposhta\Form\NovaPoshtaCreateUserForm;
use Drupal\novaposhta\Form\NovaPoshtaCreateContactPersonForm;
use Drupal\novaposhta\API\NovaPoshtaAPI;
use Drupal\basket\Admin\BasketDeleteConfirm;
use Drupal\novaposhta\Form\NovaPoshtaSettingsForm;

class AdminPages{

	protected $novaPoshta;
	protected $basket;

	function __construct(){
		$this->novaPoshta = \Drupal::service('NovaPoshta');
		$this->basket = \Drupal::service('Basket');
	}
 
	public function alter(&$element, $params){
		switch($params['page_type']){
			case'novaposhta':
				switch($params['page_subtype']){
					default:
						if(!empty(\Drupal::request()->query->get('create'))){
							$element['#info']['content']['view'] = [
								'#prefix' => '<div class="basket_table_wrap">',
                '#suffix' => '</div>',
                [
                  '#prefix'	=> '<div class="b_title">',
                  '#suffix'	=> '</div>',
                  '#markup'	=> $this->novaPoshta->t('Create invoice')
                ],[
                  '#prefix'	=> '<div class="b_content">',
                  '#suffix'	=> '</div>',
                  'form' => \Drupal::formBuilder()->getForm(new NovaPoshtaENForm(
                    FALSE,
                    NULL,
                    \Drupal::request()->query->get('order')
                  ))
                ]
							];
						} else if(!empty($num = \Drupal::request()->query->get('viewEn'))){
							$NovaPoshtaView = new NovaPoshtaView(NULL, $num);
							$element['#info']['content']['view'] = [
								'build' => $NovaPoshtaView->build(),
								'#attached' => [
									'library'	=> ['novaposhta/js'],
									'drupalSettings' => [
										'npPostUpdate' => 1
									]
								]
							];
						} else if(!empty($editID = \Drupal::request()->query->get('edit'))){
							$EN = $this->novaPoshta->loadEnByID($editID);
							if(!empty($EN->en_num)){
								$element['#info']['content']['view'] = [
									'#prefix' => '<div class="basket_table_wrap">',
                  '#suffix' => '</div>',
                  [
                    '#prefix' => '<div class="b_title">',
                    '#suffix'	=> '</div>',
                    '#markup'	=> 'ЕН: '.$EN->en_num
                  ],[
                    '#prefix'	=> '<div class="b_content">',
                    '#suffix'	=> '</div>',
                    'form' => \Drupal::formBuilder()->getForm(new NovaPoshtaENForm(FALSE, $EN))
                  ]
								];
							} else {
								$element['#info']['content']['view'] = $this->basket->getError(404);
							}
						} else {
							$element['#info']['content']['view'] = [
								'CreateLink' => [
									'#type' => 'link',
									'#title' => $this->novaPoshta->t('Create invoice'),
									'#url' => new Url('basket.admin.pages', [
										'page_type' => 'novaposhta'
									],[
										'query' => [
											'create' => 'NEW'
										],
										'attributes' => [
											'id' => 'CreateLink'
										]
									])
								],[
									'#prefix' => '<div class="basket_table_wrap">',
									'#suffix'	=> '</div>',
									[
										'#prefix' => '<div class="b_title">'.$this->novaPoshta->t('Invoices'),
										'#suffix'	=> '</div>',
										'#type' => 'inline_template',
										'#template' => '<a href="javascript:void(0);" class="button--link" onclick="{{ onclick }}" data-post="{{ post }}" title="{{ title }}"><span class="ico">{{ ico|raw }}</span> {{ text }}</a>',
										'#context' => [
											'text' => $this->novaPoshta->t('Refresh list'),
											'ico' => $this->basket->getIco('refresh.svg', 'novaposhta'),
											'onclick' => 'basket_admin_ajax_link(this, \''.Url::fromRoute('basket.admin.pages', [
                        'page_type' => 'api-novaposhta-getDocumentList'
                        ])->toString().'\')',
                      'post' => json_encode([
                        'dateStart' => date('d.m.Y', strtotime('-'.NOVAPOSHTA_EN_DAYS.' days')),
                        'dateStop' => date('d.m.Y'),
                      ]),
										]
									],[
										'#prefix' => '<div class="b_content b_content_novaposhta">',
										'#suffix'	=> '</div>',
										'views' => $this->basket->getView(NOVAPOSHTA_VIEW_ID, NOVAPOSHTA_VIEW_DISPLAY_ID),
										'#attached' => [
											'library'	=> ['novaposhta/admin']
										]
									]
								]
							];

						}
					break;
				}
			break;
			case'api':
				if($params['page_subtype'] == 'novaposhta'){
					switch($params['page_subtype1']){
						case'createCounterparty':
							if(!empty($_POST['CounterpartyProperty'])){
								\Drupal::service('BasketPopup')->openModal(
                  $element,
                  $_POST['CounterpartyProperty'] == 'Recipient' ? $this->novaPoshta->t('Create recipient') : $this->novaPoshta->t('Create sender'),
                  \Drupal::formBuilder()->getForm(new NovaPoshtaCreateUserForm($_POST['CounterpartyProperty'])),
                  [
                    'width' => 400,
                    'class' => ['novaposhta_popup']
                  ]
                );
							}
						break;
						case'createContactRecipient':
							if(!empty($_POST['CounterpartyRef'])){
								\Drupal::service('BasketPopup')->openModal(
                  $element,
                  $this->novaPoshta->t('Create a contact person'),
                  \Drupal::formBuilder()->getForm(new NovaPoshtaCreateContactPersonForm($_POST['CounterpartyRef'])),
                  [
                    'width' => 400,
                    'class' => ['novaposhta_popup']
                  ]
                );
							}
						break;
						case'getDocumentList':
							if(!empty($_POST['dateStart']) && !empty($_POST['dateStop'])){

                // Update invoice list
                (new NovaPoshtaAPI())
                  ->updateDocumentList([
                    'GetFullList' => 1,
                    'DateTimeFrom' => $_POST['dateStart'],
                    'DateTimeTo' => $_POST['dateStop'],
                  ]);

								$element->addCommand(new InvokeCommand('body', 'append', ['<script>location.reload();</script>']));
							}
						break;
						case'viewInfo':
							if(!empty($_POST['viewID'])){
								$NovaPoshtaView = new NovaPoshtaView($_POST['viewID']);
								\Drupal::service('BasketPopup')->openModal(
                  $element,
                  '',
                  $NovaPoshtaView->build(),
                  [
                    'width' => 960,
                    'class' => ['novaposhta_popup']
                  ]
                );
							}
						break;
						case'getStatusDocuments':
							if(!empty($_POST['np_EN'])){
								$API = new NovaPoshtaAPI();
								\Drupal::service('BasketPopup')->openModal(
                  $element,
                  '',
                  [
                    '#theme' => 'novaposhta_en_tracking',
                    '#attached' => [
                      'library'	=> ['novaposhta/admin']
                    ],
                    '#info' => [
                      'en_num' => $_POST['np_EN'],
                      'request'	=> $API->getStatusDocuments([$_POST['np_EN']])
                    ]
                  ],
                  [
                    'width' => 400,
                    'class' => ['novaposhta_popup']
                  ]
                );
							}
						break;
						case'deleteEN':
							if(!empty($_POST['npID'])){
								$EN = $this->novaPoshta->loadEnByID($_POST['npID']);
								if(!empty($EN->en_num)){
									if(!empty($_POST['confirm'])){
                    $this->novaPoshta->deleteByID($_POST['npID']);
                    $element->addCommand(new InvokeCommand('body', 'append', ['<script>location.reload();</script>']));
                  } else {
                    \Drupal::service('BasketPopup')->openModal(
                      $element,
                      $this->novaPoshta->t('Delete').': '.$EN->en_num,
                      BasketDeleteConfirm::confirmContent([
                        'onclick' => 'basket_admin_ajax_link(this, \''.Url::fromRoute('basket.admin.pages', ['page_type' => 'api-novaposhta-deleteEN'])->toString().'\')',
                        'post' => json_encode([
                          'npID' => $_POST['npID'],
                          'confirm' => 1
                        ]),
                      ]),
                      [
                        'width' => 400,
                        'class' => ['novaposhta_popup']
                      ]
                    );
                  }
								}
							}
						break;
						case'updateInfo':
							if(!empty($_POST['enNum'])){
								$API = new NovaPoshtaAPI();
								$API->getStatusDocuments([$_POST['enNum']]);
								$NovaPoshtaView = new NovaPoshtaView(NULL, $_POST['enNum']);
								$element->addCommand(new ReplaceCommand('[data-en_num="'.$_POST['enNum'].'"]', $NovaPoshtaView->build()));
							}
						break;
					}
				}
			break;
			case'settings':
				switch($params['page_subtype']){
					case'novaposhta':
						$element['#info']['content']['view'] = [
							'#prefix' => '<div class="basket_table_wrap">',
              '#suffix' => '</div>',
              [
                '#prefix' => '<div class="b_title">',
                '#suffix'	=> '</div>',
                '#markup'	=> $this->novaPoshta->t('Settings')
              ],[
                '#prefix'	=> '<div class="b_content">',
                '#suffix'	=> '</div>',
                'form' => \Drupal::formBuilder()->getForm(new NovaPoshtaSettingsForm()),
                'template' => [
                  '#type'	=> 'details',
                  '#title' => $this->novaPoshta->t('Template EN'),
                  '#open'	=> FALSE,
                  'form' => \Drupal::formBuilder()->getForm(new NovaPoshtaENForm(TRUE))
                ]
              ]
						];
					break;
				}
			break;
		}
	}
 
	public function orderLinks(&$links, $order){
		if(\Drupal::currentUser()->hasPermission('access novaposhta en')){
			$EN = $this->loadOrderEN($order->id);
			if(empty($EN->en_edit_id)){
				$query = [
					'create' => 'NEW',
					'order' => $order->id
				];
			} else {
				$query = [
					'edit' => $EN->en_edit_id
				];
			}
			$links['np_en'] = [
				'#type'	=> 'inline_template',
				'#template' => '<a href="{{ url }}" class="button--link" target="_blank"><span class="ico">{{ ico|raw }}</span> {{ text }}</a>',
				'#context' => [
					'text' => $this->novaPoshta->t('EN'),
					'ico' => $this->basket->getIco('np.svg', 'novaposhta'),
					'target' => '_blank',
					'url' => Url::fromRoute('basket.admin.pages', [
						'page_type' => 'novaposhta'
					],[
						'query' => $query
					])->toString()
				]
			];
		}
	}
 
	public function loadOrderEN($orderId){
		$query = \Drupal::database()->select('novaposhta_en_orders', 'o');
		$query->condition('o.order_id', $orderId);
		$query->fields('o');
		$query->leftJoin('novaposhta_en', 'en', 'en.en_num = o.en_num');
		$query->addExpression('en.id', 'en_edit_id');
		return $query->execute()->fetchObject();
	}
}
