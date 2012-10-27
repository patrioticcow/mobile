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
use ZendGateway\Runtime\Procedure;

/**
 * The Gateway Service procedure
 */
class ServiceProcedure extends Procedure
{

    public $instructions = array(
            'ZendGateway\Service\Instruction\Initialize',
            'ZendGateway\Service\Instruction\Router',
            'ZendGateway\Service\Instruction\Validate',
            'ZendGateway\Service\Instruction\Authenticate',
            'ZendGateway\Service\Instruction\Authorize',
            'ZendGateway\Service\Instruction\Dispatch',
            'ZendGateway\Service\Instruction\Render',
            'ZendGateway\Service\Instruction\HandleError'
    );

    public $steps = array(
            'initialize',
            'authenticate',
            'authorize',
            'route',
            'validate',
            'dispatch',
            'render'
    );

    function __construct (Di $locator = null)
    {
        parent::__construct();
        if ($locator == null) {
            $locator = new Di();
        }
        foreach ($this->instructions as $class) {
            $instruction = $locator->get($class);
            $this->next($instruction);
        }
    }
}
