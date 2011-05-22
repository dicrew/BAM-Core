<?php

class BAMCore_Player extends BAMCore_Object {

	protected $arrayFields = array(
		'firstName', 'lastName'
	);

	public static function loadMultiple($ids = array()) {
		// TODO: Get from the database.
		$players = array();
		for ($i = 0; $i < 3; $i++) {
			$player = new self();
			$player->setFirstName("Foo $i");
			$player->setLastName("Bar $i");
			$players[] = $player;
		}
		if (empty($ids)) {
			return $players;			
		} else {
			$results = array();
			foreach ($ids as $id) {
				if (isset($players[$id])) {
					$results[$id] = $players[$id];
				}
			}
			return $results;
		}
	}
	
	public static function load($id) {
 		$players = self::loadMultiple(array($id));
		return $players ? reset($players) : false;
	}

}