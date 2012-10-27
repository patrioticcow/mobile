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
use ZendGateway\Service\XMLUtils;
use ZendGateway\Service\GatewayAcl;
use Zend\Http\PhpEnvironment\Response;
use Zend\Http\PhpEnvironment\Request;
use ZendGateway\Service\ServiceInstruction;
use ZendGateway\Service\ServiceEvent;

/**
 * Authenticate request
 */
class Authorize extends ServiceInstruction
{

    const PARAM_ROLE_NAME = 'name';

    const PARAM_ROLE_EXTENDS = 'extends';

    const PARAM_RESOURCE_PATTERN = 'pattern';

    const PARAM_RESOURCE_ROLE = 'role';

    const PARAM_RESOURCE_METHOD = 'method';

    /**
     * constructs a new authentication service instruction
     *
     * @param array $metadata            
     */
    public function __construct ()
    {
        parent::__construct('authorize');
    }

    protected function internalRun (Request $req, Response $res, 
            ServiceEvent $event)
    {
        if ($this->data == null)
            return;
            
            /* TODO many authorize defined? */
        if (sizeof($this->data) == 1)
            $this->data = $this->data[0];
        $acl = new GatewayAcl($this->data);
        
        $url = $req->getUri()->getPath();
        $resources = $acl->getResources();
        
        foreach ($resources as $resource) {
            if (preg_match('~' . $resource . '~u', $url)) {
                if (! $acl->isAllowed($event->getParam('user')->role, $resource, 
                        strtolower($req->getMethod()))) {
                    $event->setError('Access denied', 403);
                }
                break;
            }
        }
    }

    protected function processData (ServiceEvent $event)
    {
        if (sizeof($this->data) == 1)
            $this->data = $this->data[0];
        
        $array = array(
                'roles' => array(),
                'resources' => array()
        );
        if ($this->data instanceof \SimpleXMLIterator) {
            $result = XMLUtils::elementToArray($this->data);
            foreach ($result['role'] as $role) {
                $extends = $role['extends'];
                $rolename = $role['name'];
                if (isset($extends))
                    $array['roles'][$rolename] = $extends;
                else
                    array_push($array['roles'], $rolename);
            }
            foreach ($result['resource'] as $res) {
                $pattern = $res['pattern'];
                $array['resources'][$pattern] = array();
                if (! isset($res['allow']['role'])) {
                    foreach ($res['allow'] as $allow) {
                       $this->add($array, $pattern, $allow);
                    }
                } else {
                    $this->add($array, $pattern, $res);
                }
            }
           $this->data = $array;
        }
    }

    private function add (&$array, $pattern, $res)
    {
        $role = $res['allow']['role'];
        $methods = $res['allow']['method'];
        if (isset($methods))
            $array['resources'][$pattern][$role] = strtolower($methods);
        else
            array_push($array['resources'][$pattern], $role);
    }
}
