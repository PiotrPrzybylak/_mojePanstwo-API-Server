<?php
/**
 * Application model for CakePHP.
 *
 * This file is application-wide model file. You can put all
 * application-wide model-related methods here.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Model
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Model', 'Model');

/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package       app.Model
 */
class AppModel extends Model
{
    // TODO sprawdzoc gdzie wykorzystywany AppModel, lepiej zeby recursive był domyślnie ustawiony na niepobieranie zależnych modeli
    //public $recursive = -1;

	public function getCurrentUser($field = false) {
	 	
		App::uses('CakeSession', 'Model/Datasource');
		$Session = new CakeSession();	
		$user = $Session->read('Auth.User');
				
		if( $user && is_array($user) ) {
						
			if( $field===false )
				return $user;
			else
				return isset( $user[$field] ) ? $user[$field] : false;
 			
		} else return false;
	
	}

    /* public function syncObject($dataset, $dataset_id, $data) {
        App::import('model', 'DB');
        $this->DB = new DB();

        $row = $this->query('SELECT id FROM objects WHERE dataset_id = ? AND object_id = ?', array(
            $dataset_id,
            $data['id']
        ));

        $id = 0;

        if(!isset($row[0]['objects']['id'])) {

            $this->query("INSERT IGNORE INTO `objects` (`dataset`, `dataset_id`, `object_id`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `a`='1', `a_ts` = NOW()", array(
                $dataset,
                $dataset_id,
                $data['id']
            ));

            $id = $this->getLastInsertId();

        } else {
            $id = $row[0]['objects']['id'];
        }

        $ES = ConnectionManager::getDataSource('MPSearch');

        if(!$id)
            throw new Exception('Object not found');

        $params = array();
        $params['index'] = 'mojepanstwo_v1';
        $params['type']  = $dataset;
        $params['id']    = $id;
        $params['refresh'] = true;
        $params['parent'] = $data['id'];
        $params['body']  = $data;

        $ret = $ES->API->index($params);

        return $data['id'];
    } */
	
	public function objectIndex($data) {
		
		$results = false;
		
		if(
			isset( $data['dataset'] ) && 
			( $data['dataset'] ) && 
			isset( $data['object_id'] ) && 
			( $data['object_id'] ) 
		) {
		
			App::uses('HttpSocket', 'Network/Http');
			$HttpSocket = new HttpSocket();
			$results = $HttpSocket->post(objectIndexUrl, array(
				'_pass' => objectIndexPass,
				'dataset' => $data['dataset'],
				'object_id' => $data['object_id'],
			));
		
		}
				
		return $results;
		
	}
	
}
