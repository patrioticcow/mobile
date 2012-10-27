<?php
namespace ZendGateway\Service\Authentication\Resolver;
use Zend\Authentication\Result;
use Zend\Authentication\Adapter\DbTable;
use Zend\Db\Adapter\Adapter;
use Zend\Authentication\Adapter\Http\ResolverInterface;

class DbResolver implements ResolverInterface
{

    /**
     *
     * @var Adapter
     */
    private $db;

    private $tableName;

    private $identityColumn;

    private $credentialColumn;

    private $credentialTreatment;

    private $roleColumn;

    public function __construct ($options)
    {
        $this->db = new Adapter($options);
        
        $this->tableName = $options['tableName'];
        $this->identityColumn = $options['identityColumn'];
        $this->credentialColumn = $options['credentialColumn'];
        $this->roleColumn = $options['roleColumn'];
    }

    public function resolve ($username, $realm, $password = null)
    {
        $authAdapter = new DbTable($this->db, $this->tableName, 
                $this->identityColumn, $this->credentialColumn, 
                $this->credentialTreatment);
        $authAdapter->setIdentity($username);
        $authAdapter->setCredential($password);
        
        $result = $authAdapter->authenticate();
        if ($result->isValid()) {
            $res = $authAdapter->getResultRowObject(
                    array(
                            $this->identityColumn,
                            $this->roleColumn
                    ));
            $returnObject = new \stdClass();
            $returnObject->{'username'} = $res->{$this->identityColumn};
           if (isset($this->roleColumn) && isset($res->{$this->roleColumn}))
                $returnObject->{'role'} = $res->{$this->roleColumn};
            return new Result(Result::SUCCESS, $returnObject);
        }
        return false;
    }
}

?>