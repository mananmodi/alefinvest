<?php

namespace Drupal\clean_files_entity;

use Drupal\Core\Database\Query\Condition;

class CleanFilesEntity{

	protected static $max;

	private static function getMax(){
		if(!self::$max){
			self::$max = !empty($GLOBALS['config']['clean_files_entity']['max']) ? $GLOBALS['config']['clean_files_entity']['max'] : 50;
		}
		return self::$max;
	}

	public static function run($entityType){
		$query = \Drupal::database()->select('file_usage', 'f');
		$query->fields('f', ['fid']);
		$query->condition('f.type', $entityType);
		// node_field_data
		$query->leftJoin('node_field_data', 'n', 'n.nid = f.id');
		$query->isNull('n.nid');
		// ---
		$fids = $query->range(0, self::getMax())->execute()->fetchCol();
		if(!empty($fids)){
			self::delete($fids);
		}
	}
	public static function runFolder($folders = []){
		$query = \Drupal::database()->select('file_managed', 'f');
		$query->fields('f', ['fid']);
		$db_or = new Condition('OR');
		foreach ($folders as $folder){
			$db_or->condition('f.uri', $query->escapeLike($folder) . '%', 'LIKE');
		}
		$query->condition($db_or);
		// node_field_data
		$query->leftJoin('file_usage', 'u', 'u.fid = f.fid');
		$query->isNull('u.fid');
		// ---
		$fids = $query->range(0, self::getMax())->execute()->fetchCol();
		if(!empty($fids)){
			self::delete($fids);
		}
	}
	public static function delete($fids){
		\Drupal::database()->delete('file_usage')
      ->condition('fid', $fids, 'in')
      ->execute();
		$names = [];
		foreach (\Drupal::entityTypeManager()->getStorage('file')->loadMultiple($fids) as $file){
			$names[] = $file->getFileUri();
			$file->delete();
		}
		\Drupal::logger('clean_files_entity')->notice(count($names).': <pre>'.print_r($names, TRUE).'</pre>');
	}
}