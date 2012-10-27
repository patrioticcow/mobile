<?php
require_once 'Zend/Loader/AutoloaderFactory.php';
use Zend\Loader\AutoloaderFactory;
AutoloaderFactory::factory(
        array(
                'Zend\Loader\StandardAutoloader' => array(
                        'namespaces' => array(
                                'ZendGateway' => dirname(dirname(__FILE__))
                        ),
                        'autoregister_zf' => true,
                        'fallback_autoloader' => true
                )
        ));

$gateway = new ZendGateway\Service\ServiceRuntime();
