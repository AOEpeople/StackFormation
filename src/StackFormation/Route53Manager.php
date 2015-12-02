<?php

namespace StackFormation;

class Route53Manager
{

    /**
     * @return \Aws\Route53\Route53Client
     */
    protected function getRoute53Client() {
        return SdkFactory::getClient('Route53', ['region' => 'us-east-1']);
    }

    /**
     * @return \Aws\ElasticLoadBalancing\ElasticLoadBalancingClient
     */
    protected function getElbClient() {
        return SdkFactory::getClient('ElasticLoadBalancing');
    }

    public function getHostedZoneIdByHostedZoneName($hostedZoneName) {
        $res = $this->getRoute53Client()->listHostedZonesByName([
            'DNSName' => $hostedZoneName,
            'MaxItems' => 1
        ]);
        $id = $res->search('HostedZones[0].Id');
        $id = end(explode('/', $id));
        return $id;
    }

    public function getElbInfo($elb) {
        $res = $this->getElbClient()->describeLoadBalancers([
            'LoadBalancerNames' => [$elb]
        ]);
        return $res->search('LoadBalancerDescriptions[0].[DNSName, CanonicalHostedZoneNameID]');
    }

    public function elb2Alias($elb, $hostedZone, $name) {
        if (strpos($hostedZone, '.') !== false) {
            $hostedZoneId = $this->getHostedZoneIdByHostedZoneName($hostedZone);
        } else {
            $hostedZoneId = $hostedZone;
        }

        list($elbDNSName, $elbCanonicalHostedZoneNameID) = $this->getElbInfo($elb);

        $data = [
            'HostedZoneId' => $hostedZoneId,
            'ChangeBatch' => [
                'Comment' => 'Updated via StackFormation ('.date(DATE_ISO8601).')',
                'Changes' => [
                    [
                        'Action' => 'UPSERT',
                        'ResourceRecordSet' => [
                            'Name' => $name,
                            'Type' => 'A',
                            'AliasTarget' => [
                                'DNSName' => $elbDNSName,
                                'EvaluateTargetHealth' => false,
                                'HostedZoneId' => $elbCanonicalHostedZoneNameID,
                            ],
                            // 'SetIdentifier' => '<string>',
                            // 'TTL' => <integer>,
                        ],
                    ],
                ],
            ],
        ];

        $res = $this->getRoute53Client()->changeResourceRecordSets($data);

        return end(explode('/', $res->search('ChangeInfo.Id')));
    }

    public function getChange($changeId)
    {
        $result = $this->getRoute53Client()->getChange([
            'Id' => $changeId
        ]);
        return $result->search('ChangeInfo.Status');
    }

}
