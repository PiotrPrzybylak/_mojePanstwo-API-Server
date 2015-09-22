<?php

class Collection extends AppModel {

    public $useTable = 'collections';

    public $validate = array(
        'name' => array(
            'rule' => array('minLength', '3'),
            'required' => true,
        ),
        'user_id' => array(
            'required' => true
        )
    );

}