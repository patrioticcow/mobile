<?php
set_include_path ( '.' . PATH_SEPARATOR . '../lib' . PATH_SEPARATOR . '../services' );

require_once 'ZendGateway/Main.php';

$gateway->configure ( dirname ( __DIR__ ) . DIRECTORY_SEPARATOR . 'gateway.xml' );

$gateway->send ();
