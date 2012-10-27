<?php

/*******************************************************************************
 * Copyright (c) 2012, 2012 Zend Technologies.
 * All rights reserved. This program and the accompanying materials
 * are the copyright of Zend Technologies and is protected under
 * copyright laws of the United States.
 * You must not copy, adapt or redistribute this document for
 * any use.
 *******************************************************************************/
namespace ZendGateway\Service;
use ZendGateway\Runtime\Procedure;
use Zend\Http\PhpEnvironment\Response;
use Zend\Http\PhpEnvironment\Request;
use ZendGateway\Runtime\Runtime;
use ZendGateway\Service\ServiceEvent;
use Zend\EventManager\Event;

/**
 * Service Runtime
 */
class ServiceRuntime extends Runtime
{

    /**
     * Definition array, holds all configs provided by @see #on($event, $config)
     * method
     *
     * @var array
     */
    public $metadata = array();

    /**
     *
     * @var Request
     */
    private $request;

    /**
     *
     * @var Response
     */
    private $response;

    function __construct (Procedure $procedure = null)
    {
        parent::__construct(
                $procedure == null ? new ServiceProcedure($this->getLocator()) : $procedure);
    }

    /**
     * Retrieve the request environment
     *
     * @return Request
     */
    public function request ()
    {
        if (! $this->request instanceof Request) {
            $this->setRequest(new Request());
        }
        return $this->request;
    }

    /**
     * Set the request object instance
     *
     * @param Request $request            
     * @return App
     */
    public function setRequest (Request $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Retrieve the response environment
     *
     * @return Response
     */
    public function response ()
    {
        if (! $this->response instanceof Response) {
            $this->setResponse(new Response());
        }
        return $this->response;
    }

    /**
     * Set the response object instance
     *
     * @param Response $response            
     * @return App
     */
    public function setResponse (Response $response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     *
     * @see Runtime#init
     */
    protected function createEvent ()
    {
        $event = new ServiceEvent($this->metadata, $this->getLocator(), 
                $this->request(), $this->response());
        return $event;
    }

    /**
     * Decorates the events with metadata
     *
     * @param string $event
     *            name of the metadata event
     * @param array $config            
     */
    public function on ($event, $config)
    {
        // TODO: validate input
        array_push($this->metadata, 
                array(
                        $event => $config
                ));
        
        return $this;
    }

    /**
     *
     * @param string $name            
     * @param array $arguments            
     */
    public function __call ($name, $arguments)
    {
        $this->on($name, $arguments);
        return $this;
    }

    /**
     *
     * @see Runtime#processException
     */
    protected function processException (\Exception $e)
    {}

    /**
     *
     * @return ZendGateway\Service\ServiceEvent
     * @see Runtime#result
     */
    protected function result (Event $event)
    {
        return $event;
    }

    public function configure ($configFile)
    {
        $iter = new \SimpleXMLIterator($configFile, 0, true);
        
        foreach ($iter as $key => $val) {
            $method = $key;
            $this->on($method, $val);
        }
    }

    private function addRoute ($method, $route, $handler, $name = null)
    {
        $route = new Route($this->request, $method, $route, $handler, $name);
        $this->route($route);
        return $route;
    }

    public function get ($route, $handler, $name = null)
    {
        return $this->addRoute('get', $route, $handler, $name);
    }

    public function post ($route, $handler, $name = null)
    {
        return $this->addRoute('post', $route, $handler, $name);
    }

    public function put ($route, $route, $handler, $name = null)
    {
        return $this->addRoute('put', $route, $handler, $name);
    }

    public function delete ($route, $handler, $name = null)
    {
        return $this->addRoute('delete', $route, $handler, $name);
    }

    public function head ($route, $handler, $name = null)
    {
        return $this->addRoute('head', $route, $handler, $name);
    }

    public function options ($route, $handler, $name = null)
    {
        return $this->addRoute('options', $route, $handler, $name);
    }

    public function trace ($route, $handler, $name = null)
    {
        return $this->addRoute('trace', $route, $handler, $name);
    }

    public function connect ($route, $handler, $name = null)
    {
        return $this->addRoute('connect', $route, $handler, $name);
    }

    public function authenticate ($config)
    {
        return $this->on('authenticate', func_get_args());
    }

    public function authorize ($config)
    {
        return $this->on('authorize', func_get_args());
    }
}
