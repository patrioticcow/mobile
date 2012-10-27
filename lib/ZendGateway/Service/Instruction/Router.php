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
use ZendGateway\Service\Exception\DuplicateRouteException;
use ZendGateway\Service\Route;
use Zend\Http\PhpEnvironment\Response;
use Zend\Http\PhpEnvironment\Request;
use ZendGateway\Service\ServiceInstruction;
use ZendGateway\Service\ServiceEvent;

/**
 * Route request
 */
class Router extends ServiceInstruction
{

    const ERROR_SERVICE_NOT_FOUND = 'Service not found';

    /**
     *
     * @var array
     */
    protected $namedRoutes = array();

    /**
     * Routes
     *
     * @var Route[]
     */
    protected $routes;

    /**
     * Routes by method
     *
     * Array of method => Route[] pairs
     *
     * @var array
     */
    protected $routesByMethod = array();

    /**
     * parameters
     */
    const PARAM_URL = 'url';

    const PARAM_METHOD = 'method';

    const PARAM_HANDLER = 'handler';

    const PARAM_NAME = 'name';

    /**
     * Index of route that matched
     *
     * @var null int
     */
    protected $routeIndex = - 1;

    /**
     * constructs a new router service instruction
     *
     * @param array $metadata            
     */
    public function __construct ()
    {
        parent::__construct('route');
    }

    protected function internalRun (Request $req, Response $response, 
            ServiceEvent $event)
    {
        // TODO cache router!
        $this->routes = $this->data;
        if ($this->routes == null) {
            $event->setError(Router::ERROR_SERVICE_NOT_FOUND, 400, 
                    'No routes defined.');
            return;
        }
        
        // prepare routes
        $this->prepareRoutes();
        
        // match url
        $method = $req->getMethod();
        if (! isset($this->routesByMethod[$method])) {
            $event->setError(Router::ERROR_SERVICE_NOT_FOUND, 400, 
                    'No routes defined for HTTP ' . $method . '.');
            return;
        }
        
        $routes = $this->routesByMethod[$method];
        foreach ($routes as $index => $route) {
            /*if ($index <= $this->routeIndex) {
                // Skip over routes we've already looked at
                continue;
            }*/
            $result = $route->route()->match($req);
            if ($result) {
                $this->routeIndex = $index;
                $route->params($result);
                $event->route($route);
                return;
            }
        }
        $event->setError(Router::ERROR_SERVICE_NOT_FOUND, 400, 
                'No matching route found.');
    }

    protected function processData (ServiceEvent $event)
    {
        if (is_array($this->data)) {
            foreach ($this->data as &$d) {
                if ($d instanceof \SimpleXMLIterator) {
                    $attr = (array) $d->attributes();
                    $attr = $attr['@attributes'];
                    $method = $attr[Router::PARAM_METHOD];
                    $route = $attr[Router::PARAM_URL];
                    $handler = $attr[Router::PARAM_HANDLER];
                    $name = null;
                    if (isset($attr[Router::PARAM_NAME]))
                        $name = $attr[Router::PARAM_NAME];
                    $r = new Route($event->getRequest(), $method, $route, 
                            $handler, $name);
                    foreach ($d as $key => $val) {
                        $r->$key($val);
                    }
                    $d = $r;
                }
            }
        }
    }

    /**
     * Register a named route
     *
     * @param Route $route            
     * @throws Exception\DuplicateRouteException if route with the same name
     *         already registered
     */
    protected function registerNamedRoute (Route $route)
    {
        $name = $route->name();
        
        if (! $name) {
            return;
        }
        
        if (isset($this->namedRoutes[$name])) {
            if ($route === $this->namedRoutes[$name]) {
                return;
            }
            
            throw new DuplicateRouteException(
                    sprintf(
                            'Duplicate
             attempt to register route by name "%s" detected', $name));
        }
        
        $this->namedRoutes[$name] = $route;
    }

    /**
     * Prepare routes
     *
     * Ensure no duplicate routes, determine what named routes are available,
     * and determine which routes respond to which methods.
     */
    protected function prepareRoutes ()
    {
        foreach ($this->routes as $index => $route) {
            $this->registerNamedRoute($route);
            $this->registerRouteMethods($route, $index);
        }
    }

    /**
     * Determine what methods a route responds to
     *
     * @param Route $route            
     * @param int $index            
     */
    protected function registerRouteMethods (Route $route, $index)
    {
        foreach ($route->respondsTo() as $method) {
            if (! isset($this->routesByMethod[$method])) {
                $this->routesByMethod[$method] = array();
            }
            $this->routesByMethod[$method][$index] = $route;
        }
    }
}
