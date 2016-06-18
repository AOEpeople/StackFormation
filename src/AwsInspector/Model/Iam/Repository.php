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
        $this->iamClient = \AwsInspector\SdkFactory::getClient('Iam', 'default', ['region' => 'us-east-1']);
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