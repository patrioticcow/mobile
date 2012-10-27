<?php
namespace ZendGateway\Service\Authentication\Resolver;
use ZendGateway\Service\Exception\NoUsersDefinedException;
use Zend\Authentication\Result;
use Zend\Authentication\Adapter\Http\ResolverInterface;

class InMemoryResolver implements ResolverInterface
{

    private $users = array();

    private $allowAnonynous;

    public function __construct ($options, $allowAnonymous)
    {
        $users = $options;
        if (empty($users)) {
            throw new NoUsersDefinedException(
                    'No users defined for Authentication');
        }
        foreach ($users as $username => $data) {
            $this->users[$username]['role'] = $data[0];
            $this->users[$username]['password'] = $data[1];
            $this->users[$username]['username'] = $username;
        }
    }

    public function resolve ($username, $realm, $password = null)
    {
        if ($this->users[$username]['password'] == $password) {
            $returnObject = new \stdClass();
            $returnObject->{'username'} = $username;
            $returnObject->{'role'} = $this->users[$username]['role'];
            return new Result(Result::SUCCESS, $returnObject);
        } else 
            if ($password == null && $this->allowAnonynous) {
                return array();
            } else
                return false;
    }
}

?>