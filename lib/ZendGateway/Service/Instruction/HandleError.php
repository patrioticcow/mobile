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
use Zend\Http\Header\GenericHeader;
use Zend\Http\Header\ContentType;
use Zend\Http\Headers;
use Zend\Http\PhpEnvironment\Response;
use Zend\Http\PhpEnvironment\Request;
use ZendGateway\Service\ServiceInstruction;
use ZendGateway\Service\ServiceEvent;

class HandleError extends ServiceInstruction
{

    /**
     * constructs a new render service instruction
     *
     * @param array $metadata            
     */
    public function __construct ()
    {
        parent::__construct('error');
    }

    protected function internalRun (Request $req, Response $res, 
            ServiceEvent $event)
    {
        $err = $event->getError();
        if ($err == null)
            return;
        if (is_int($err[0]))
            $res->setStatusCode($err[0]);
        else
            $res->setStatusCode(500);
        if (is_string($err[1]))
            $res->setReasonPhrase($err[1]);
        if (isset($err[2])) {
            $err[2] = trim($err[2]);
            if (empty($err[2])) {
                $err[2] = $err[1];
            }
            $content = $res->getContent();
            if (! empty($content)) {
                $err = $content . '; ' . $err[2];
            } else {
                $err = $err[2];
            }
            $err = array(
                    "error" => $err
            );
            $err = json_encode($err);
            $res->setContent($err);
        }
        $headers = $res->getHeaders();
        if (empty($headers)) {
            $headers = new Headers();
        }
        if (! $headers->has('Content-Type')) {
            $headers->addHeader(
                    ContentType::fromString('Content-Type: application/json'));
        }
        $headers->addHeader(
                GenericHeader::fromString('Access-Control-Allow-Origin: *'));
    }
}
