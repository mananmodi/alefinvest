<?php
/**
 * @ViewsField("novaposhta_en_settings")
 */

namespace Drupal\novaposhta\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\novaposhta\NovaPoshtaView;
use Drupal\Core\Url;

class NovaPoshtaEnSettings extends FieldPluginBase{

	protected $basket;
	protected $novaPoshta;

	function __construct(){
    call_user_func_array(parent::__construct(...), func_get_args());
		$this->basket = \Drupal::service('Basket');
		$this->novaPoshta = \Drupal::service('NovaPoshta');
	}
  
  public function query() {
    $params = $this->options['group_type'] != 'group' ? ['function' => $this->options['group_type']] : [];
    $this->field_alias = $this->query->addField('novaposhta_en', 'id', 'np_id', $params);
    $this->field_alias = $this->query->addField('novaposhta_en', 'en_num', 'np_en_num', $params);
    $this->addAdditionalFields();
  }
  
  public function render(ResultRow $values){
    $NovaPoshtaView = new NovaPoshtaView($values->np_id);
    return [
      '#type' => 'inline_template',
      '#template' => '<a href="javascript:void(0);" class="settings_row tooltipster_init">{{ico|raw}}</a>
    	<div class="tooltipster_content">
    			{% for link in links %}
					{% if link %}
						<a href="{% if link.url %}{{ link.url }}{% else %}javascript:void(0);{% endif %}" class="button--link" target="{% if link.target %}{{ link.target }}{% else %}_self{% endif %}" onclick="{{ link.onclick }}" data-post="{{ link.post }}"><span class="ico">{{ link.ico|raw }}</span> {{ link.text }}</a><br/>
					{% endif %}
				{% endfor %}
    		</div>',
      '#context' => [
        'ico' => $this->basket->getIco('settings_row.svg', 'basket'),
        'links' => [
          'view' => [
            'text' => $this->novaPoshta->t('View'),
            'ico'	=> $this->basket->getIco('eye.svg', 'novaposhta'),
            'onclick' => 'basket_admin_ajax_link(this, \''.Url::fromRoute('basket.admin.pages', [
              'page_type' => 'api-novaposhta-viewInfo'
              ])->toString().'\')',
            'post' => json_encode([
              'viewID' => $values->np_id
            ])
          ],
          'print' => $NovaPoshtaView->getPrintLink(),
          'tracking' => [
            'text' => $this->novaPoshta->t('Track down'),
            'ico' => $this->basket->getIco('tracking.svg', 'novaposhta'),
            'onclick' => 'basket_admin_ajax_link(this, \''.Url::fromRoute('basket.admin.pages', [
              'page_type' => 'api-novaposhta-getStatusDocuments'
              ])->toString().'\')',
            'post' => json_encode([
              'np_EN' => $values->np_en_num
            ])
          ],
          'edit' => [
            'text' => $this->novaPoshta->t('Edit'),
            'ico'	=> $this->basket->getIco('edit.svg', 'novaposhta'),
            'url' => Url::fromRoute('basket.admin.pages', [
              'page_type' => 'novaposhta'
            ],[
              'query' => ['edit' => $values->np_id]
            ])->toString()
          ],
          'delete' => [
            'text' => $this->novaPoshta->t('Delete'),
            'ico' => $this->basket->getIco('trash.svg', 'novaposhta'),
            'onclick' => 'basket_admin_ajax_link(this, \''.Url::fromRoute('basket.admin.pages', [
              'page_type' => 'api-novaposhta-deleteEN'
              ])->toString().'\')',
            'post' => json_encode([
              'npID' => $values->np_id
            ])
          ]
        ]
      ]
    ];
  }
}