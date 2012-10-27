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
use Zend\Stdlib\PriorityQueue;
use Zend\Stdlib\CallbackHandler;
use Zend\Http\PhpEnvironment\Response;
use Zend\Http\PhpEnvironment\Request;
use ZendGateway\Service\ServiceInstruction;
use ZendGateway\Service\ServiceEvent;

/**
 * Initialize request
 */
class Initialize extends ServiceInstruction
{

    /**
     * constructs a new intialization service instruction
     *
     * @param array $metadata            
     */
    public function __construct ()
    {
        parent::__construct('initialize');
    }

    public function internalRun (Request $req, Response $response, 
            ServiceEvent $event)
    {
        $req = $event->getRequest();
        $response = $event->getResponse();
        $callable = $this->getInstructionMetadata($event);
        $pq = new PriorityQueue();
        if (is_array($callable)) {
            foreach ($callable as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $key => $value) {
                        if (! is_int($key)) {
                            $key = 1;
                        }
                        if (is_callable($value)) {
                            $handler = new CallbackHandler($value);
                            $pq->insert($handler, $key);
                        }
                    }
                } else 
                    if (is_callable($value)) {
                        $handler = new CallbackHandler($value);
                        $pq->insert($handler, 1);
                    }
            }
        } else 
            if (is_callable($callable)) {
                $handler = new CallbackHandler($callable);
                $pq->insert($handler, 1);
            }
        while ($pq->count() > 0) {
            $handler = $pq->top();
            $pq->remove($handler);
            $handler($req, $response, $event);
        }
    }
}
