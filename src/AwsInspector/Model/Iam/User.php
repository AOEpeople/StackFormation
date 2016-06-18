<?php

namespace AwsInspector\Model\Iam;

/**
 * Class User
 *
 * @method getPath()
 * @method getUserName()
 * @method getUserId()
 * @method getArn()
 * @method getCreateDate()
 */
class User extends \AwsInspector\Model\AbstractResource
{

    public function getAccountId() {
        $parts = explode(':', $this->getArn());
        return $parts[4];
    }

}


