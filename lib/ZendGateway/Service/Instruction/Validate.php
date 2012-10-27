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

use ZendGateway\Service\XMLUtils;

use Zend\InputFilter\InputFilter;
use Zend\Stdlib\Parameters;
use ZendGateway\Service\ServiceEvent;
use Zend\Http\PhpEnvironment\Response;
use Zend\Http\PhpEnvironment\Request;
use Zend\InputFilter\Factory;
use ZendGateway\Service\ServiceInstruction;

class Validate extends ServiceInstruction
{

    const PARAM_ATTR_NAME = 'name';

    const PARAM_ATTR_REQUIRED = 'required';

    const PARAM_ATTR_ALLOW_EMPTY = 'allow_empty';

    const VALIDATOR = 'validator';

    const FILTER = 'filter';

    private $routesOptions = array();

    public function __construct ()
    {
        parent::__construct('validate');
    }

    public function internalRun (Request $req, Response $res, 
            ServiceEvent $event)
    {
        $this->data = $event->route();
        $this->processRouteData($event);
        if (empty($this->options))
            return;
        
        $params = $this->getRequestParameters($event, $req);
        
        $factory = new Factory();
        $inputFilter = null;
        $isValid = true;
        $msg = array();
        foreach ($this->options as $filter) {
            if ($filter instanceof InputFilter) {
                $inputFilter = $filter;
            } else {
                $inputFilter = $factory->createInputFilter($filter);
            }
            $inputFilter->setData($params);
            if (! $inputFilter->isValid()) {
                $isValid = false;
                foreach ($inputFilter->getMessages() as $name => $error) {
                    $m = $name . ': ' . implode(';', array_values($error));
                    $msg[$name] = $m;
                }
            }
            
            foreach ($params as $name => $value) {
                $input = $inputFilter->getValidInput();
                if (isset($input[$name]))
                    $params[$name] = $input[$name]->getValue();
            }
        }
        
        if (! $isValid) {
            $event->setError('Invalid parameter(s) in request', 400, 
                    implode('; ', $msg));
            return;
        }
        $event->setRequestParams(new Parameters($params));
    }

    protected function processRouteData (ServiceEvent $event)
    {
        $this->options = $this->data->getMetadata($this->name);
        if (empty($this->options))
            return;
        
        foreach ($this->options as $key => &$options) {
            if (is_string($options)) {
                $callable = GatewayUtils::prepareCallable($event, $options);
                $options = call_user_func($callable);
            } else 
                if ($options instanceof \SimpleXMLIterator) {
                    $params = array();
                    foreach ($options as $key => $param) {
                        $name = (string) $param[Validate::PARAM_ATTR_NAME];
                        $required = (string) $param[Validate::PARAM_ATTR_REQUIRED];
                        if (! empty($required) &&
                                 strtolower($required) == "false") {
                            $required = false;
                        }
                        
                        $allowEmpty = (string) $param[Validate::PARAM_ATTR_ALLOW_EMPTY];
                        if (! empty($allowEmpty) &&
                                 strtolower($allowEmpty) == "false") {
                            $allowEmpty = false;
                        }
                        $validators = XMLUtils::getOptions(Validate::VALIDATOR, 
                                $param);
                        $filters = XMLUtils::getOptions(Validate::FILTER, $param);
                        $params[$name] = array(
                                Validate::PARAM_ATTR_REQUIRED => $required,
                                Validate::PARAM_ATTR_ALLOW_EMPTY => $allowEmpty,
                                'validators' => $validators,
                                'filters' => $filters
                        );
                    }
                    $options = $params;
                }
        }
    }
}
