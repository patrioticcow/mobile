<?php

/*******************************************************************************
 * Copyright (c) 2012, 2012 Zend Technologies.
 * All rights reserved. This program and the accompanying materials
 * are the copyright of Zend Technologies and is protected under
 * copyright laws of the United States.
 * You must not copy, adapt or redistribute this document for
 * any use.
 *******************************************************************************/
namespace ZendGateway\Service\Instruction;
use ZendGateway\Service\GatewayUtils;
use Zend\Http\PhpEnvironment\Response;
use Zend\Http\PhpEnvironment\Request;
use ZendGateway\Service\ServiceInstruction;
use ZendGateway\Service\ServiceEvent;

/**
 * Dispatch request
 */
class Dispatch extends ServiceInstruction
{

    const ERROR_SERVICE_NOT_FOUND = "Service or callable not found";

    /**
     * constructs a new dispatch service instruction
     *
     * @param array $metadata            
     */
    public function __construct ()
    {
        parent::__construct('dispatch');
    }

    protected function internalRun (Request $req, Response $res, 
            ServiceEvent $event)
    {
        
        // prepare callable
        $callbackData = $this->prepareCallback($req, $res, $event);
        // dispatch
        $event->setParam('result', 
                call_user_func_array($callbackData['target'], 
                        $callbackData['params']));
    }

    private function prepareCallback ($req, $res, ServiceEvent $event)
    {
        $locator = $event->getLocator();
        $routeMatch = $event->route()->params();
        $callable = $event->route()->callback();
        $data = array();
        $initData = array(
                'req' => $req,
                'res' => $res,
                'event' => $event
        );
        $requestParameters = $this->getRequestParameters($event, $req);
        
        $params = $routeMatch->getParams();
        $methodParams = null;
        
        $callable = GatewayUtils::prepareCallable($event, $callable);
        
        if (is_string($callable) || $callable instanceof \Closure) {
            $methodParams = $this->getFunctionParams($callable);
        } else 
            if (is_array($callable) && count($callable) == 2) {
                $dispatcher = $callable[0];
                $dispatch = $callable[1];
                $methodParams = $this->getObjectMethodParams($dispatcher, 
                        $dispatch);
            }
        
        foreach ($methodParams as $param) {
            $name = $param->getName();
            if ($param->isOptional()) {
                $data[$name] = ! empty($requestParameters[$name]) ? $requestParameters[$name] : $param->getDefaultValue();
            } else 
                if (isset($params[$name])) {
                    $data[$name] = $params[$name];
                } else 
                    if (isset($requestParameters[$name])) {
                        $data[$name] = $requestParameters[$name];
                    } else 
                        if (isset($initData[$name])) {
                            $data[$name] = $initData[$name];
                        }
        }
        
        return array(
                'target' => $callable,
                'params' => $data
        );
    }

    private function getObjectMethodParams ($object, $method)
    {
        $classRef = new \ReflectionObject($object);
        $methodRef = $classRef->getMethod($method);
        $paramsRef = $methodRef->getParameters();
        return $paramsRef;
    }

    private function getFunctionParams ($method)
    {
        $methodRef = new \ReflectionFunction($method);
        $paramsRef = $methodRef->getParameters();
        return $paramsRef;
    }

    private function getMethodParams ($callback)
    {
        if (is_string($callback) || $callback instanceof \Closure) {
            return $this->getFunctionParams($callback);
        } else 
            if (is_array($callback) && count($callback) == 2) {
                $dispatcher = $callback[0];
                $dispatch = $callback[1];
                if (is_object($dispatcher)) {
                    return $this->getObjectMethodParams($dispatcher, $dispatch);
                } else 
                    if (is_string($dispatcher)) {
                        return $this->getServiceMethodParams($dispatcher, 
                                $dispatch);
                    }
            }
        return null;
    }
}
