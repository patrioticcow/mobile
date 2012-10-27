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
use Zend\Http\Header\ContentType;
use Zend\Http\Header\GenericHeader;
use Zend\Http\Headers;
use Zend\Http\PhpEnvironment\Response;
use Zend\Http\PhpEnvironment\Request;
use ZendGateway\Service\ServiceInstruction;
use ZendGateway\Service\ServiceEvent;

class Render extends ServiceInstruction
{

    /**
     * constructs a new render service instruction
     *
     * @param array $metadata            
     */
    public function __construct ()
    {
        parent::__construct('render');
    }

    protected function internalRun (Request $req, Response $res, 
            ServiceEvent $event)
    {
        $result = $event->getParam('result');
        if ($result != null) {
            $res->setStatusCode(Response::STATUS_CODE_200);
            if (! $res->headersSent()) {
                $headers = $res->getHeaders();
                if (empty($headers)) {
                    $headers = new Headers();
                }
                if (! $headers->has('Content-Type')) {
                    $headers->addHeader(
                            ContentType::fromString('Content-Type: application/json'));
                    $result = array(
                            "result" => $result
                    );
                    $result = json_encode($result);
                }
                $headers->addHeader(
                        GenericHeader::fromString(
                                'Access-Control-Allow-Origin: *'));
            }
            $res->setContent($result);
        }
    }
}
