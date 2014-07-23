<?

Router::connect('/kodyPocztowe/', array('plugin' => 'Dane', 'controller' => 'datasets', 'action' => 'search', 'alias' => 'kody_pocztowe'));
Router::connect('/kodyPocztowe/:id', array('plugin' => 'KodyPocztowe', 'controller' => 'KodyPocztowe', 'action' => 'view'), array('id' => '[0-9]{2}-?[0-9]{3}'));
Router::connect('/kodyPocztowe/:action', array('plugin' => 'Dane', 'controller' => 'datasets', 'alias' => 'kody_pocztowe'), array('action' => 'filters|switchers|sortings'));
Router::connect('/kodyPocztowe/:action', array('plugin' => 'KodyPocztowe', 'controller' => 'KodyPocztowe'));

