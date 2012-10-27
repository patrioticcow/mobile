<?php
namespace ZendGateway\Service;
use Zend\Mvc\Router\Http\RouteInterface;
use ZendGateway\Service\Exception\InvalidRouteException;
use Zend\Mvc\Router\Http\Segment;
use ZendGateway\Service\Exception\InvalidMethodException;
use Zend\Mvc\Router;
use Zend\Http\PhpEnvironment\Request;

/**
 * Route
 *
 * Encapsulates the route, the callback to execute, and the methods it will
 * respond to.
 */
class Route
{

    /**
     * Allowed methods
     *
     * @var array
     */
    protected static $allowedMethods = array(
            Request::METHOD_OPTIONS,
            Request::METHOD_GET,
            Request::METHOD_HEAD,
            Request::METHOD_POST,
            Request::METHOD_PUT,
            Request::METHOD_DELETE,
            Request::METHOD_TRACE,
            Request::METHOD_CONNECT
    );

    private $metadata = array();

    /**
     * Callable assigned to this route
     *
     * @var callable
     */
    protected $handler;

    /**
     * Methods this route responds to
     *
     * @var array
     */
    protected $methods = array();

    /**
     * Name of this route (if any)
     *
     * @var null string
     */
    protected $name;

    /**
     * Route object
     *
     * @var RouteInterface
     */
    protected $route;

    /**
     * Parameters returned as the result of a route match
     *
     * @var null Router\RouteMatch
     */
    protected $params;

    /**
     * Add an HTTP method you will allow
     *
     * @param string $method            
     * @return void
     */
    public static function allowMethod ($method)
    {
        $method = strtoupper($method);
        if (in_array($method, static::$allowedMethods)) {
            return;
        }
        static::$allowedMethods[] = $method;
    }

    /**
     * Constructor
     *
     * Accepts the router and controller.
     *
     * @param RouteInterface $route            
     * @param callable $handler            
     */
    public function __construct ($req, $method, $route, $handler, $name = null)
    {
        $baseUrl = $req->getBaseUrl();
        if (strlen($baseUrl) > 0 && ! preg_match('/^\\' . $baseUrl . '/', 
                $route)) {
            $route = $baseUrl . $route;
        }
        
        if (is_string($route)) {
            $route = Segment::factory(
                    array(
                            'route' => $route
                    ));
        }
        if (! $route instanceof RouteInterface) {
            throw new InvalidRouteException(
                    'Routes are expected
              to be either strings or instances of
              Zend\Mvc\Router\RouteInterface');
        }
        
        $this->route = $route;
        $this->name = $name;
        $this->handler = $handler;
        $this->via($method);
    }

    /**
     * Get the actual route interface
     *
     * @return RouteInterface
     */
    public function route ()
    {
        return $this->route;
    }

    public function callback ()
    {
        return $this->handler;
    }

    /**
     * Assign one or more methods this route will respond to
     *
     * Additional arguments will be used as additional methods.
     *
     * @param string|array $method            
     * @return Route
     * @throws Exception\InvalidMethodException
     */
    public function via ($method)
    {
        if (is_string($method) && 1 < func_num_args()) {
            $method = func_get_args();
        }
        
        if (is_string($method)) {
            $method = (array) $method;
        }
        
        $method = array_map('strtoupper', $method);
        
        foreach ($method as $test) {
            if (! in_array($test, self::$allowedMethods)) {
                throw new InvalidMethodException(
                        sprintf(
                                'Invalid method "%s" specified; must be one of %s;' .
                                         ' if the method is valid, add it using Phlyty\Route::allowMethod()', 
                                        $test, 
                                        implode(', ', static::$allowedMethods)));
            }
            if (! isset($this->methods[$test])) {
                $this->methods[$test] = true;
            }
        }
        
        return $this;
    }

    /**
     * Does this route respond to the given method?
     *
     * If no method is provided, returns array of all methods to which this
     * route will respond.
     *
     * @param null|string $method            
     * @return array bool
     */
    public function respondsTo ($method = null)
    {
        if (null === $method) {
            return array_keys($this->methods);
        }
        
        $method = strtoupper($method);
        return isset($this->methods[$method]);
    }

    /**
     * Retrieve and/or set the route name
     *
     * Sets the route name if a non-empty string is provided, and then returns
     * the Route instance to allow a fluent interface.
     *
     * Otherwise, returns the route name.
     *
     * @param null|string $name            
     * @return Route string
     */
    public function name ($name = null)
    {
        if (is_string($name) && ! empty($name)) {
            $this->name = $name;
            return $this;
        }
        return $this->name;
    }

    /**
     * Return the route match parameters
     *
     * If none has been set yet, lazy instantiates an empty
     * Router\RouteMatch container.
     *
     * @return Router\RouteMatch
     */
    public function params ($params = null)
    {
        if (! empty($params))
            $this->params = $params;
        if (null === $this->params) {
            $this->params = new Router\RouteMatch(array());
        }
        return $this->params;
    }

    private function on ($event, $config)
    {
        $this->metadata[$event] = $config;
        
        return $this;
    }

    public function __call ($name, $arguments)
    {
        $this->on($name, $arguments);
        return $this;
    }

    public function metadata ()
    {
        return $this->metadata;
    }

    public function validate ($options)
    {
        $this->on('validate', $options);
        return $this;
    }

    public function getMetadata ($element = null)
    {
        $result = array();
        foreach ($this->metadata as $key => $value) {
            if ($key === $element) {
                array_push($result, $value);
            }
        }
        return $result;
    }
}
