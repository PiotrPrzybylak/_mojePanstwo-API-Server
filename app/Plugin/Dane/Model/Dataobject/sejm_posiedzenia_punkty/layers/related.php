<?

	$object = $this->getObject($dataset, $id);
	
	$output = array(
	    'groups' => array(),
	);
	
	
	$punkt_id = $object['data']['id'];
	
	/*
    $output['groups'][] = array(
        'id' => 'debaty',
        'title' => 'Przebieg prac nad projektem ustawy',
        'objects' => array(
            array(
                'dataset' => 'legislacja_projekty_ustaw',
                'object_id' => $projekt_id,
            )
        ),
    );
    */
	
	
	
	
	// DEBATY    
        
    if( $debaty = $this->DB->selectValues("SELECT `subpunkt_id` FROM `stenogramy_subpunkty-punkty` WHERE `punkt_id`='$punkt_id' AND `deleted`='0' LIMIT 100") )
    {
    	
    	$group = array(
	        'id' => 'debaty',
	        'title' => 'Debaty',
	        'objects' => array(),
	    );
    	
        foreach ($debaty as $oid)
            $group['objects'][] = array(
                'dataset' => 'sejm_debaty',
                'object_id' => $oid,
            );

        $output['groups'][] = $group;

    }
	
	
	
	return $output;
	