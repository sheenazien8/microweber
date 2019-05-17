<?php
namespace Microweber\Utils\Backup;

use Microweber\Utils\Backup\Traits\DatabaseCustomFieldsWriter;
use Microweber\Utils\Backup\Traits\DatabaseContentFieldsWriter;
use Microweber\Utils\Backup\Traits\DatabaseContentDataWriter;

class DatabaseWriter
{
	use DatabaseCustomFieldsWriter;
	use DatabaseContentFieldsWriter;
	use DatabaseContentDataWriter;

	public $content;

	public function setContent($content)
	{
		$this->content = $content;
	}

	private function _getRelationship($item)
	{
		$relationship = array();
		foreach ($this->content[$item['rel_type']] as $dataItem) {
			if ($dataItem['id'] == $item['rel_id']) {
				$relationship = $dataItem;
			}
		}
		return $relationship;
	}
	
	private function _fixItemEncoding($item) {
		
		// Fix encoding
		array_walk_recursive($item, function (&$element) {
			if (is_string($element)) {
				$element = utf8_decode($element);
				$element = str_replace('Â ', ' ', $element);
				$element = str_replace("Â ", ' ', $element);
			}
		});
		
		return $item;
	}
	
	private function _unsetItemFields($item) {
		$unsetFields = array('id', 'rel_id', 'order_id', 'position');
		foreach($unsetFields as $field) {
			unset($item[$field]);
		}
		return $item;
	}
	
	private function _saveItem($table, $item) {
		
		$item = $this->_fixItemEncoding($item);
		
		$dbSelectParams = array();
		$dbSelectParams['no_cache'] = true;
		$dbSelectParams['limit'] = 1;
		$dbSelectParams['single'] = true;
		$dbSelectParams['do_not_replace_site_url'] = 1;
		
		if (isset($item['payment_verify_token'])) {
			$dbSelectParams['payment_verify_token'] = $item['payment_verify_token'];
		}
		
		if (isset($item['option_key'])) {
			$dbSelectParams['option_key'] = $item['option_key'];
		}
		
		if (isset($item['option_value'])) {
			$dbSelectParams['option_value'] = $item['option_value'];
		}
		
		if (isset($item['option_group'])) {
			$dbSelectParams['option_group'] = $item['option_group'];
		}
		
		if (isset($item['session_id'])) {
			$dbSelectParams['session_id'] = $item['session_id'];
		}
		
		if (isset($item['created_at'])) {
			$dbSelectParams['created_at'] = $item['created_at'];
		}
		
		if (isset($item['title'])) {
			$dbSelectParams['title'] = $item['title'];
		}
		
		if (isset($item['email'])) {
			$dbSelectParams['email'] = $item['email'];
		}
		
		if (isset($item['first_name'])) {
			$dbSelectParams['first_name'] = $item['first_name'];
		}
		
		if (isset($item['last_name'])) {
			$dbSelectParams['last_name'] = $item['last_name'];
		}
		
		if (isset($item['amount'])) {
			$dbSelectParams['amount'] = $item['amount'];
		}

		$checkItemIsExists = db_get($table, $dbSelectParams);
		/* 
		if (isset($item['email'])) {
			
			var_dump($checkItemIsExists);
			die();
		} */
		
		if ($checkItemIsExists) {
			$itemId = $checkItemIsExists['id'];
			echo $table . ': Item is allready saved.' . PHP_EOL;
		} else {
			echo $table . ': Item is saved.' . PHP_EOL;
			$item = $this->_unsetItemFields($item);
			$itemId = db_save($table, $item);
		}
		$this->_saveCustomFields($item, $itemId);
		$this->_saveContentData($itemId);
	}
	
	private function _saveItemWithRelationship($table, $item) {
		
		$relationship = $this->_getRelationship($item);

		$item = $this->_fixItemEncoding($item);
		$item = $this->_unsetItemFields($item);
		
		$item['allow_html'] = true;
		$item['allow_scripts'] = true;
		$item['is_deleted'] = false;
		
		
		$dbSelectParams = array();
		$dbSelectParams['no_cache'] = true;
		$dbSelectParams['limit'] = 1;
		$dbSelectParams['single'] = true;
		$dbSelectParams['do_not_replace_site_url'] = 1;
		$dbSelectParams['title'] = $item['title'];
		
		$checkItemIsExists = db_get($table, $dbSelectParams);
		
		if ($checkItemIsExists) {
			$itemId = $checkItemIsExists['id'];
			echo $table . ': Item is allready saved.' . PHP_EOL;
		} else {
			echo $table . ': Item is saved.' . PHP_EOL;
			$itemId = db_save($table, $item);
		}
		
		$this->_saveCustomFields($item, $itemId);
		$this->_saveContentData($itemId);
		
	}
	
	public function runWriter()
	{
		$importTables = array('categories', 'modules', 'comments', 'content', 'media', 'options', 'calendar', 'cart_orders');
		//$importTables = array('cart_orders');
		
		// All db tables
		foreach ($importTables as $table) {
			if (isset($this->content[$table])) {
				foreach ($this->content[$table] as $item) {
					
					/* if (isset($item['rel_type']) && isset($item['rel_id'])) {			
						$this->_saveItemWithRelationship($table, $item);
						continue;
					} */
					
					$this->_saveItem($table, $item);
				}
			}
		}
	}
}