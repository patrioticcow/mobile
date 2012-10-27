<?php
namespace ZendGateway\Service;

class GatewayUtils
{
    
    public static function prepareCallable (ServiceEvent $event, $callable)
    {
        if (is_string($callable) && preg_match('/::/', $callable)) {
            $callable = explode('::', $callable);
            $callable[0] = $event->getLocator()->get($callable[0]);
        } else
            if (is_array($callable) && ! is_object($callable[0])) {
            $callable[0] = $event->getLocator()->get($callable[0]);
        }
        return $callable;
    }
}

?>