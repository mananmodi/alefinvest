<?php

namespace Drupal\novaposhta;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\novaposhta\API\NovaPoshtaAPI;
use Drupal\Core\File\FileSystemInterface;
use Drupal\novaposhta\API\NovaPoshtaApi2;

class NovaPoshta{

	const NOVAPOCHTA_DIR = 'public://settings/novaposhta';
	const PHONE_MASK = '+38(099)999-99-99';
	const JQUERY_STYLE_DEF = 'chosen';

	protected static $EN;
	protected static $NovaPoshtaEN;
  
  const DEF_OPTIONS_SEAT = [
    ['36', '40', '58'],
    ['23', '40', '58'],
    ['15', '40', '58'],
  ];

	public function t($string, $args = [], $context = 'novaposhta'){
		return new TranslatableMarkup($string, $args, ['context' => $context]);
	}
  
  public function defOptionsSeat() {
    return $this::DEF_OPTIONS_SEAT;
  }
	
  public function cronRun(){

    // Data cleansing
		$tmpDir = $this::NOVAPOCHTA_DIR.'/'.date('W_m_Y', strtotime('-1 month'));
		if(is_dir($tmpDir)){
			\Drupal::service('file_system')->deleteRecursive($tmpDir);
		}
		$this->clearTMP($tmpDir);
    
    if(empty($GLOBALS['config']['novaposhta']['disabled.cron.document.list'])) {

      // Update invoice list
      $API = new NovaPoshtaAPI();
      $API->updateDocumentList([
        'GetFullList' => 1,
        'DateTimeFrom' => date('d.m.Y', strtotime('-'.NOVAPOSHTA_EN_DAYS.' days')),
        'DateTimeTo' => date('d.m.Y'),
      ]);

      // Update status list
      $EnNums = \Drupal::database()->select('novaposhta_en', 'en')
        ->fields('en', ['en_num'])
        ->condition('en.delivery_day', time(), '>=')
        ->execute()->fetchCol();

      if(!empty($EnNums)){
        $API->getStatusDocuments($EnNums);
      }
    }

    // Update lists
    if(empty($GLOBALS['config']['novaposhta']['disabled.cron.area.list'])) {
      $this->runUpdate('area');
    }
    if(empty($GLOBALS['config']['novaposhta']['disabled.cron.city.list'])) {
      $this->runUpdate('city');
    }
	}
	
  public function clearTMP($DIR = NULL){
		if(empty($DIR)) $DIR = $this::NOVAPOCHTA_DIR;
		if(is_dir($DIR)){
			\Drupal::service('file_system')->deleteRecursive($DIR);
		}
	}
	
  public function variableSet($data = [], $fileName = '', $dir = NULL){
		if(empty($dir)) $dir = $this::NOVAPOCHTA_DIR;
		\Drupal::service('file_system')->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);
    $path = $dir.'/'.$fileName;
    file_put_contents($path, json_encode($data));
	}
	
  public function variableGet($fileName = '', $dir = NULL){
		if(empty($dir)) $dir = $this::NOVAPOCHTA_DIR;
		$path = $dir.'/'.$fileName;
    if(file_exists($path)){
      $file = file_get_contents($path);
    }
    return !empty($file) ? json_decode($file, TRUE) : [];
	}
	
  public function replacePhone($phone){
    return str_replace(['(',')','-','+', ' '], '', $phone);
  }
  
  public function loadEnByID($viewID){
    if(!isset(self::$EN[$viewID])){
      self::$EN[$viewID] = \Drupal::database()->select('novaposhta_en', 'n')
        ->fields('n')
        ->condition('n.id', $viewID)
        ->execute()->fetchObject();
    }
    return self::$EN[$viewID];
  }
  
  public function loadEnByNum($num){
		return \Drupal::database()->select('novaposhta_en', 'n')
      ->fields('n')
      ->condition('n.en_num', $num)
      ->execute()->fetchObject();
  }
  
  public function deleteByID($ID){
    $EN = $this->loadEnByID($ID);
    if(!empty($EN)){
      \Drupal::database()->delete('novaposhta_en')
        ->condition('id', $ID)
        ->execute();
      if(!empty($EN->ref)){
        $API = new NovaPoshtaAPI();
        $API->InternetDocumentDelete([
          'DocumentRefs' => $EN->ref
        ]);
      }
    }
  }
  
  public function EN($apiKEY = NULL){
    if(!isset(self::$NovaPoshtaEN[$apiKEY])){
      self::$NovaPoshtaEN[$apiKEY] = new NovaPoshtaEN($apiKEY);
    }
    return self::$NovaPoshtaEN[$apiKEY];
  }
  
  public function getJqueryStyleID(){
    $JqueryStyleID = \Drupal::config('novaposhta.settings')->get('config.jquery_style');
    if(empty($JqueryStyleID)){
      $JqueryStyleID = $this::JQUERY_STYLE_DEF;
    }
    return $JqueryStyleID;
  }
  
  public function JquerySelectAttached(&$form){
		$form['#attached']['drupalSettings']['no_results_text'] = $this->t('No matches');
		$form['#attached']['drupalSettings']['search_text'] = $this->t('Enter search data');
    switch($this->getJqueryStyleID()){
      case'chosen':
        $form['#attached']['library'][] = 'novaposhta/chosen';
        break;
    	case'select2':
        $form['#attached']['library'][] = 'webform/webform.element.select2';
        $form['#attached']['library'][] = 'novaposhta/np';
    		break;
    }
  }
  
  /**
   * @return void
   */
  public function runUpdate($type = '') {
    $API = new NovaPoshtaApi2(NULL);
    
    switch ($type) {
      case'area':
        $areas = $API->getAreas();
        if(!empty($areas['data'])) {
          foreach ($areas['data'] as $area) {
            \Drupal::database()->merge('novaposhta_lists')
              ->keys([
                'ref_id' => $area['Ref'],
                'type' => 'area'
              ])
              ->fields([
                'name' => trim($area['Description']),
                'last_update' => time()
              ])
              ->execute();
          }
        }
        break;
      case'city':
        $cities = $API->getCities(0, '', NULL);
        if(!empty($cities['data'])) {
          foreach ($cities['data'] as $city) {
            switch($city['SettlementTypeDescription']) {
              case'село':
                $city['SettlementTypeDescription'] = 'с';
                break;
              case'місто':
                $city['SettlementTypeDescription'] = 'м';
                break;
              case'селище міського типу':
                $city['SettlementTypeDescription'] = 'смт.';
                break;
              case'селище':
                $city['SettlementTypeDescription'] = 'сел';
                break;
            }
            $names = [
              $city['SettlementTypeDescription'] ?? NULL,
              $city['Description']
            ];
            
            $name = implode('. ', array_filter($names));
            \Drupal::database()->merge('novaposhta_lists')
              ->keys([
                'ref_id' => $city['Ref'],
                'type' => 'city'
              ])
              ->fields([
                'parent_id' => $city['Area'],
                'name' => trim($name),
                'last_update' => time()
              ])
              ->execute();
          }
        }
        break;
    }
  }
  
  public function listItem($id = '', $type = '') {
    return \Drupal::database()->select('novaposhta_lists', 'l')
      ->fields('l')
      ->condition('l.type', $type)
      ->condition('l.ref_id', $id)
      ->execute()->fetchObject();
  }
  
  public function getOptionsSeat() {
    $options = [];
    
    // Def
    foreach ($this::DEF_OPTIONS_SEAT as $item) {
      $item = implode('x', $item);
      $options[$item] = $item;
    }
    
    // All config
    foreach (\Drupal::config('novaposhta.OptionsSeat')->get('config.sizes') as $size) {
      $size = [
        $size['height'] ?? NULL,
        $size['width'] ?? NULL,
        $size['length'] ?? NULL,
      ];
      $size = array_filter($size);
      if(empty($size)) continue;
      $size = implode('x', $size);
      
      $options[$size] = $size;
    }
    
    return $options;
  }
}
