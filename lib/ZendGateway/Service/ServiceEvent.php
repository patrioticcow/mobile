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
use Zend\Di\Di;
use Zend\Http\PhpEnvironment\Response;
use Zend\Http\PhpEnvironment\Request;
use Zend\EventManager\Event;

/**
 * A basic service events
 */
class ServiceEvent extends Event
{

    const REQUEST_NAME = 'request';

    const RESPONSE_NAME = 'response';

    const METADATA_NAME = 'metadata';

    private $locator;

    private $route;

    private $request;

    private $response;

    /**
     *
     * @var Parameters
     */
    private $reqParams;

    /**
     *
     * @param field_type $request            
     */
    public function setRequest ($request)
    {
        $this->request = $request;
    }

    /**
     *
     * @param field_type $response            
     */
    public function setResponse ($response)
    {
        $this->response = $response;
    }

    /**
     * constructs a new service event
     */
    public function __construct (array $metadata = array(), Di $locator = null, 
            Request $request = null, Response $response = null)
    {
        parent::__construct(__CLASS__);
        $this->request = $request == null ? new Request() : $request;
        $this->response = $response == null ? new Response() : $response;
        $this->setParam(ServiceEvent::METADATA_NAME, $metadata);
        $this->setLocator($locator);
    }

    /**
     *
     * @return the $locator
     */
    public function getLocator ()
    {
        if (! $this->locator instanceof Di) {
            $this->locator = new Di();
        }
        return $this->locator;
    }

    /**
     *
     * @param field_type $locator            
     */
    public function setLocator ($locator)
    {
        $this->locator = $locator;
    }

    public function route (Route $route = null)
    {
        if ($route != null)
            $this->route = $route;
        
        return $this->route;
    }

    /**
     *
     * @return Request
     */
    public function getRequest ()
    {
        return $this->request;
    }

    /**
     *
     * @return Response
     */
    public function getResponse ()
    {
        return $this->response;
    }

    /**
     *
     * @return array
     */
    public function getMetadata ($element = null)
    {
        $data = $this->getParam(ServiceEvent::METADATA_NAME);
        if ($element == null) {
            return $data;
        }
        $result = array();
        foreach ($data as $key => $value) {
            if ($key === $element) {
                array_push($result, $value);
            } else 
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        if ($k === $element) {
                            if (is_array($v)) {
                                foreach ($v as $k2 => $v2) {
                                    array_push($result, $v2);
                                }
                            } else {
                                array_push($result, $v);
                            }
                        }
                    }
                } else {
                    array_push($result, $value);
                }
        }
        return $result;
    }

    public function isError ()
    {
        return $this->getParam('error', false);
    }

    public function setError ($message, $code = 500, $details = null)
    {
        $this->setParam('error', 
                array(
                        $code,
                        $message,
                        $details
                ));
        $this->stopPropagation();
        return $this;
    }

    public function getError ()
    {
        return $this->getParam('error', null);
    }

    public function setRequestParams ($reqParams)
    {
        $this->reqParams = $reqParams;
    }

    /**
     *
     * @return \Zend\Stdlib\Parameters
     */
    public function requestParams ()
    {
        return $this->reqParams;
    }

    public function setRequestParam ($name, $value)
    {
        $this->reqParams[$name] = $value;
    }
}

