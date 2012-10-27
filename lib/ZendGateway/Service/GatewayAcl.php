<?php
namespace ZendGateway\Service;
use Zend\Permissions\Acl\Resource\GenericResource;
use Zend\Permissions\Acl\Role\GenericRole;
use Zend\Permissions\Acl\Acl;

class GatewayAcl extends Acl
{

    const DEFAULT_ROLE = 'guest';

    public function __construct ($config)
    {
        if (! isset($config['roles']) || ! isset($config['resources'])) {
            throw new \Exception('Invalid ACL Config found');
        }
        
        $roles = $config['roles'];
        if (! isset($roles[self::DEFAULT_ROLE])) {
            $roles[self::DEFAULT_ROLE] = '';
        }
        
        $this->addRoles($roles)->addResources($config['resources']);
    }

    protected function addRoles ($roles)
    {
        foreach ($roles as $index => $value) {
            if (is_int($index)) {
                $name = $value;
                $parents = array();
            } else {
                $name = $index;
                if (empty($value)) {
                    $parents = array();
                } else {
                    $parents = explode(',', $value);
                }
            }
            if(!$this->hasRole($name))
                $this->addRole(new GenericRole($name), $parents);
        }
        
        return $this;
    }

    protected function addResources ($resources)
    {
        foreach ($resources as $route => $options) {
            
            if (! $this->hasResource($route)) {
                $this->addResource(new GenericResource($route));
            }
            
            if (! empty($options)) {
                if (is_array($options)) {
                    
                    foreach ($options as $index => $value) {
                        if (is_string($index)) {
                            $roles = explode(',', $index);
                            $methods = explode(',', strtolower($value));
                            $this->allow($roles, $route, $methods);
                        } else {
                            $roles = explode(',', $value);
                            $this->allow($roles, $route);
                        }
                    }
                } else {
                    $this->allow($options, $route);
                }
            }
        }
        
        return $this;
    }
}

?>