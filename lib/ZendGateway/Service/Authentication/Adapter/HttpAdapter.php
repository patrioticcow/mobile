<?php
/*******************************************************************************
 * Copyright (c) 2012, 2012 Zend Technologies.
* All rights reserved. This program and the accompanying materials
* are the copyright of Zend Technologies and is protected under
* copyright laws of the United States.
* You must not copy, adapt or redistribute this document for
* any use.
*******************************************************************************/
namespace ZendGateway\Service\Authentication\Adapter;
use ZendGateway\Service\GatewayUtils;
use ZendGateway\Service\XMLUtils;
use ZendGateway\Service\Authentication\Resolver\DbResolver;
use ZendGateway\Service\Authentication\Resolver\InMemoryResolver;
use Zend\Authentication\Result;
use Zend\Authentication\Adapter\Http;
use Zend\Http\PhpEnvironment\Response;
use Zend\Http\PhpEnvironment\Request;
use ZendGateway\Service\ServiceEvent;

class HttpAdapter
{

    const PARAM_REALM = 'realm';

    const PARAM_NAME = 'name';

    /**
     *
     * @param Request $req            
     * @param Response $res            
     * @param ServiceEvent $event            
     * @param unknown $data            
     * @return Result
     */
    public static function authenticate (Request $req, Response $res, 
            ServiceEvent $event, $data)
    {
        $data = HttpAdapter::processData($data);
        
        $result = null;
        $config = array(
                'accept_schemes' => 'basic'
        );
        
        $pattern = null;
        $excludePattern = null;
        
        $realm = $data['realm'];
        
        if (isset($data['pattern']))
            $pattern = $data['pattern'];
        if (isset($data['exclude-pattern']))
            $excludePattern = $data['exclude-pattern'];
        
        $url = $req->getUri()->getPath();
        
        if (($pattern && preg_match('~' . $pattern . '~u', $url)) && ! ($excludePattern &&
                 preg_match('~' . $excludePattern . '~u', $url))) {
            
            $config['realm'] = $realm;
            $allowAnonymous = isset($data['anonymous']);
            $provider = $data['provider'];
            $options = $provider['options'];
            
            $type = $provider['type'];
            
            if (is_string($options)) {
                $options = GatewayUtils::prepareCallable($event, $options);
                $options = call_user_func($options);
            }
            
            switch ($type) {
                case 'in-memory':
                    if (isset($options['method'])) {
                        $options = GatewayUtils::prepareCallable($event, 
                                $options['method']);
                        $options = call_user_func($options);
                    }
                    $resolver = new InMemoryResolver($options, $allowAnonymous);
                    break;
                case 'db':
                    $resolver = new DbResolver($options);
                    break;
                case 'ldap':
                    // TODO
                    break;
            }
            
            $adapter = new Http($config);
            $adapter->setBasicResolver($resolver);
            $adapter->setRequest($req);
            $adapter->setResponse($res);
            $result = $adapter->authenticate();
            return $result;
        }
        
        return $result;
    }

    public static function processData ($data)
    {
        if ($data instanceof \SimpleXMLIterator) {
            $result = array();
            return XMLUtils::elementToArray($data, true);
        }
        return $data;
    }
}
