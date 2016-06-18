<?php

namespace AwsInspector\Model\SecurityGroup;

/**
 * Class SecurityGroup
 *
 * @method getGroupId()
 * @method getGroupName()
 * @method getOwnerId()
 * @method getDescription()
 * @method getVpcId()
 * @method getTags()
 * @method getIpPermissions()
 * @method getIpPermissionsEgress()
 */
class SecurityGroup extends \AwsInspector\Model\AbstractResource
{

    /**
     * @param $origin SecurityGroup|string security group, ip range or single ip address
     * @param $port
     * @param string $protocol
     * @return bool
     */
    public function hasAccess($origin, $port, $protocol='tcp') {
        foreach ($this->getIpPermissions() as $permission) {
            if ($permission['IpProtocol'] != $protocol || $permission['FromPort'] != $port) {
                continue;
            }
            if ($origin instanceof SecurityGroup) {
                foreach ($permission['UserIdGroupPairs'] as $idGroupPair) {
                    if ($idGroupPair['GroupId'] == $origin->getGroupId()) {
                        return true;
                    }
                }
            } else {
                $isRange = (strpos($origin, '/') !== false);
                foreach ($permission['IpRanges'] as $ipRange) {
                    if ($isRange) {
                        if ($origin == $ipRange['CidrIp']) {
                            return true;
                        }
                    } else {
                        if ($this->ipMatchesCidr($origin, $ipRange['CidrIp'])) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    protected function ipMatchesCidr($ip, $range)
    {
        list ($subnet, $bits) = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask; # nb: in case the supplied subnet wasn't correctly aligned
        return ($ip & $mask) == $subnet;
    }

}
