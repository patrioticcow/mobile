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
use Zend\Stdlib\Parameters;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Http\PhpEnvironment\Response;
use Zend\Http\PhpEnvironment\Request;
use ZendGateway\Runtime\RuntimeException;
use ZendGateway\Runtime\Instruction;
use ZendGateway\Service\ServiceEvent;

/**
 * Service instruction
 */
abstract class ServiceInstruction extends Instruction implements 
        ListenerAggregateInterface
{

    protected $name;

    protected $data;

    public function __construct ($name, array $metadata = array())
    {
        parent::__construct($name, 
                array(
                        $this,
                        'run'
                ), $metadata);
        $this->name = $name;
    }

    /**
     *
     * @var \Zend\Stdlib\CallbackHandler[]
     */
    protected $listeners = array();

    /**
     * Attach listeners to an event manager
     *
     * @param EventManagerInterface $events            
     * @return void
     */
    public function attach (EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach($this->name, 
                array(
                        $this,
                        'run'
                ) /*TODO attach with priority*/);
    }

    /**
     * Detach listeners from an event manager
     *
     * @param EventManagerInterface $events            
     * @return void
     */
    public function detach (EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * Executes a step in the dispatch cycle with the request, response and
     * event deatils
     *
     * @param Request $req            
     * @param Response $response            
     * @param ServiceEvent $event            
     * @return boolean true if next instructions should be processed
     */
    protected abstract function internalRun (Request $req, Response $response, 
            ServiceEvent $event);

    /**
     *
     * @param ServiceEvent $event            
     * @throws RuntimeException
     */
    public function run (ServiceEvent $event)
    {
        $message = $this->valid($event);
        $this->data = $this->getInstructionMetadata($event);
        if (! empty($this->data))
            $this->processData($event);
        if ($message == null) {
            return $this->internalRun($event->getRequest(), 
                    $event->getResponse(), $event);
        } else {
            throw new RuntimeException($message);
        }
    }

    /**
     * Validates and filters the event params
     *
     * @param ZendGateway\Service\ServiceEvent $event            
     * @return boolean true if valid and filtered correctly
     */
    public function valid (ServiceEvent $event)
    {}

    /**
     *
     * @param ServiceEvent $event            
     */
    protected function getInstructionMetadata (ServiceEvent $event)
    {
        $data = $event->getMetadata($this->name);
        return $data;
    }

    /**
     * Resolve the request parameters according to its content type
     *
     * @param
     *            $req
     * @return array
     */
    protected function getRequestParameters (ServiceEvent $event, Request $req)
    {
        if ($event->requestParams() != null) {
            return $event->requestParams();
        }
        
        $requestParameters = array();
        
        // GET and POST paraneters
        if ($req->isGet()) {
            $requestParameters = $req->getQuery()->toArray();
        } else 
            if ($req->isPost()) {
                $requestParameters = $req->getPost()->toArray();
            }
        
        // json parameters can be provided in the body as well
        $contentHeader = $req->getHeaders()->get("content-type");
        if (! empty($contentHeader) && strpos($contentHeader->getFieldValue(), 
                "application/json") !== false) {
            $content = $req->getContent();
            if (! empty($content)) {
                $decoded = json_decode($content);
                $requestParameters = array_merge($requestParameters, 
                        get_object_vars($decoded));
            }
        }
        $event->setRequestParams(new Parameters($requestParameters));
        return $requestParameters;
    }

    protected function processData (ServiceEvent $event)
    {
        return $this->data;
    }

   
}
