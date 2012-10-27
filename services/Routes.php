<?php

use Company\CustomersData;

class Routes {

	public function getCustomers($req, $res) {
		return CustomersData::getAll();
	}


}
