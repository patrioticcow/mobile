<?php

namespace Company;

/**
 * Stores customers information as JSON file located in temp dir.
 *
 */
class CustomersData
{
	static function add ($name, $location, $state = "", $phone = "")
	{
		$newUser = array();
		if ($name) {
			$newUser["name"] = $name;
		}
		if ($location) {
			$newUser["location"] = $location;
		}
		if ($state) {
			$newUser["state"] = $state;
		}
		if ($phone) {
			$newUser["phone"] = $phone;
		}
			
		$users = CustomersData::getAll();
		$users[] = $newUser;
		$filename = sys_get_temp_dir() . "/customers.json";
		file_put_contents($filename, json_encode($users));

		return array("result" => $name." added");
	}

	static function getAll ()
	{
		$filename = sys_get_temp_dir() . "/customers.json";
		$users = array(array("name"=> "Jane", "location"=>"London", "state"=>"walking", "phone"=>"123"),
				array("name"=> "Mark", "location"=>"San Francisco", "state"=>"coding", "phone"=>"456"),
				array("name"=> "Frank", "location"=>"Zakopane", "state"=>"skiing", "phone"=>"789"));
		if (file_exists($filename)) {
			$users = json_decode(file_get_contents($filename));
		}
			
		return $users;
	}
	
	static public function deleteAll() {
		$filename = sys_get_temp_dir() . "/customers.json";
    	file_put_contents($filename, json_encode(array()));
    	return array("result" => "users deleted");
	}

}
