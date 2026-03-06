<?php

namespace App\Service;

use RetailCrm\Api\Factory\SimpleClientFactory;
use RetailCrm\Api\Model\Request\Users\UserGroupsRequest;

class GroupService
{
    private $client;

    public function __construct(string $crmUrl, string $crmApiKey)
    {
        $this->client = SimpleClientFactory::createClient($crmUrl, $crmApiKey);
    }

    public function findGroupById(int $id)
    {
        $groupRequest = new UserGroupsRequest();
        $groups = $this->client->users->userGroups($groupRequest)->groups;
        foreach ($groups as $group) {
            if ($group->id == $id) {
                return $group;
            }
        }
        return null;
    }
}
