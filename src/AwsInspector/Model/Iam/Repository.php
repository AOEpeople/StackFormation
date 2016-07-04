<?php

namespace AwsInspector\Model\Iam;

use AwsInspector\Model\Collection;

class Repository
{

    /**
     * @var \Aws\Iam\IamClient
     */
    protected $iamClient;

    public function __construct()
    {
        $this->iamClient = \AwsInspector\SdkFactory::getClient('Iam');
    }

    /**
     * @return User
     */
    public function findCurrentUser()
    {
        $result = $this->iamClient->getUser();
        return new User($result->get('User'));
    }

}