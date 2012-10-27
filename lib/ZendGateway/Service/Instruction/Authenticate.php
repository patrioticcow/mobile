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
use ZendGateway\Service\Authentication\Adapter\HttpAdapter;
use Zend\Http\PhpEnvironment\Response;
use Zend\Http\PhpEnvironment\Request;
use ZendGateway\Service\ServiceInstruction;
use ZendGateway\Service\ServiceEvent;

/**
 * Authenticate request
 */
class Authenticate extends ServiceInstruction
{

    const PARAM_REALM = 'realm';

    const PARAM_PROVIDER = 'provider';

    const PARAM_TYPE = 'type';

    const PARAM_OPTIONS = 'options';

    protected $type;

    /**
     * constructs a new authentication service instruction
     *
     * @param array $metadata            
     */
    public function __construct ()
    {
        parent::__construct('authenticate');
    }

    protected function internalRun (Request $req, Response $res, 
            ServiceEvent $event)
    {
        if ($this->data == null)
            return;
        
        switch ($this->type) {
            case 'HTTPBasic':
                $result = HttpAdapter::authenticate($req, $res, $event, 
                        $this->options);
                break;
        }
        if ($result != null) {
            if (! $result->isValid()) {
                $event->stopPropagation(true);
            } else {
                $event->setParam('user', $result->getIdentity());
            }
        } else {
            $anonymousIdentity = new \stdClass();
            $anonymousIdentity->role = 'guest';
            $event->setParam('user', $anonymousIdentity);
        }
        return;
    }

    protected function processData (ServiceEvent $event)
    {
        if (sizeof($this->data) >= 1)
            $this->data = $this->data[0];
        
        if ($this->data instanceof \SimpleXMLIterator) {
            $attr = (array) $this->data->attributes();
            $attr = $attr['@attributes'];
            $x = $attr[Authenticate::PARAM_TYPE];
            $this->type = $attr[Authenticate::PARAM_TYPE];
            $this->options = $this->data;
        } else {
            $this->type = $this->data[Authenticate::PARAM_TYPE];
            $this->options = $this->data[Authenticate::PARAM_OPTIONS];
        }
    }
}
