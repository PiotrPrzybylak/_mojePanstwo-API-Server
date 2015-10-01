<?php

App::uses('GeoJsonMP', 'Geo.Model');

class Mapa extends AppModel
{

    public $useTable = false;
	
	public function geocode($q) {
		
		$point = explode(' ', $q);
		
		$ES = ConnectionManager::getDataSource('MPSearch');	    
	   	   
	    $params = array();
		$params['index'] = 'mojepanstwo_v1';
		$params['type']  = 'objects';
		$params['body'] = array(
			'_source' => 'data.*',
			'size' => 1,
			'query' => array(
				'bool' => array(
					'must' => array(
						array(
							'term' => array(
								'dataset' => 'miejsca',
							),
						),
						array(
							'nested' => array(
								'path' => 'miejsca-numery',
								'query' => array(
									'match_all' => new \stdClass(),
								),
								'inner_hits' => array(
									'size' => 1,
									'sort' => array(
										array(
											'_geo_distance' => array(
												'location' => array(
													'lat' => $point[0],
													'lon' => $point[1],
												),
												'order' => 'asc',
												'distance_type' => 'plane',
											),
										),
									),
								),
							),
						),
					),
				),
			),
			'sort' => array(
				array(
					'_geo_distance' => array(
						'miejsca-numery.location' => array(
							'lat' => $point[0],
							'lon' => $point[1],
						),
						'order' => 'asc',
						'distance_type' => 'plane',
					),
				),
			),
		);
		
		$ret = $ES->API->search($params);
		
		$places = array();
		foreach( $ret['hits']['hits'] as $h ) {
						
			$locations = array();
			foreach( $h['inner_hits']['miejsca-numery']['hits']['hits'] as $l )
				$locations[] = $l['_source'];
			
			$places[] = array(
				'data' => $h['_source']['data'],
				'locations' => $locations,
			);
			
		}
		
		if( $places ) {

            $types = array(
                'gmina_id' => 'gminy',
                'wojewodztwo_id' => 'wojewodztwa',
                'powiat_id' => 'powiaty'
            );

            $geo = new GeoJsonMP();

            foreach($places as $p => $place) {

                foreach($types as $t => $type) {

                    if(isset($place['data']['miejsca.' . $t]) &&
                    is_numeric($place['data']['miejsca.' . $t]) &&
                    $place['data']['miejsca.' . $t] > 0) {

                        $places[$p]['polygons'][$t] = $geo->getMapData(4, array($type), array(
                            $type => array($place['data']['miejsca.' . $t])
                        ));

                    }

                }

            }

		}
		
		
		return array(
			'places' => $places,
		);		
				
	}

} 