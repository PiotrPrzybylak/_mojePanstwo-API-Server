<?

$data = $this->DB->query("SELECT `krs_nadzorcy`.nazwa, `krs_nadzorcy`.imiona, `krs_osoby`.`id`, `krs_osoby`.`data_urodzenia`, `krs_osoby`.`privacy_level` 
		FROM `krs_nadzorcy` 
		LEFT JOIN `krs_osoby` 
		ON `krs_nadzorcy`.`osoba_id` = `krs_osoby`.`id` 
		WHERE `krs_nadzorcy`.`pozycja_id` = '" . addslashes($id) . "' AND `krs_nadzorcy`.`deleted`='0' AND `krs_nadzorcy`.`removed`='0' 
		ORDER BY `krs_nadzorcy`.`ord` ASC LIMIT 100");

$output = array();
foreach ($data as $d) {
    $osoba = array(
        'nazwa' => _ucfirst($d['krs_nadzorcy']['nazwa'] . ' ' . $d['krs_nadzorcy']['imiona']),
    );

    if (isset($d['krs_osoby']['id'])) {
        $osoba['data_urodzenia'] = $d['krs_osoby']['data_urodzenia'];
        $osoba['privacy_level'] = $d['krs_osoby']['privacy_level'];
        $osoba['osoba_id'] = $d['krs_osoby']['id'];
        $osoba['krs_osoby.url'] = Dataobject::apiUrl('krs_osoby', $d['krs_osoby']['id']);
    }

    $output[] = $osoba;
}


return $output;