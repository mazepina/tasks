<?php

namespace App\Service;

use RetailCrm\Api\Enum\ByIdentifier;
use RetailCrm\Api\Factory\SimpleClientFactory;
use RetailCrm\Api\Model\Entity\Tasks\Task;
use RetailCrm\Api\Model\Filter\Customers\CustomerFilter;
use RetailCrm\Api\Model\Filter\Tasks\TaskFilter;
use RetailCrm\Api\Model\Request\BySiteRequest;
use RetailCrm\Api\Model\Request\Customers\CustomersRequest;
use RetailCrm\Api\Model\Request\Tasks\TasksRequest;

class TasksService
{
    private $client;
    /**
     * @var GroupService
     */
    private $groupService;

    public function __construct(GroupService $groupService, string $crmUrl, string $crmApiKey)
    {
        $this->client = SimpleClientFactory::createClient($crmUrl, $crmApiKey);
        $this->groupService = $groupService;
    }


    public function getTasksArray(\DateTime $end): array
    {
        $start = clone $end;
        $timezone = new \DateTimeZone('Chile/EasterIsland');
        $start->setTimezone($timezone);
        $end->setTimezone($timezone);

        $start->setTime(0,0);
        $end->setTime(23,59);

        $tasks = $this->getTasks($start, $end);

        $rows = [];
        foreach ($tasks as $task) {
            if (!isset($task->datetime)) {
                continue;
            }

            $row = [
                'date' => null,
                'task' => $task->text,
                'commentary' => $task->commentary,
                'customer' => null,
                'phone' => null,
                'performer' => null
            ];

            if ($task->customer) {
                $row['customer'] = $this->getCustomer($task);
            }

            if ($task->performer) {
                $row['performer'] = $this->getPerformer($task);
            }

            if ($task->datetime) {
                $row['date'] = $task->datetime->format('d.m.Y H:i:s');
            }

            $row['phone'] = $this->getClientPhone($task);

            $rows[] = $row;

        }

        return $rows;
    }

    private function getClientPhone(Task $task)
    {
        $customerRequest = (new BySiteRequest(ByIdentifier::ID));
        $customerResponse = $this->client->customers->get($task->customer->id, $customerRequest);
        if (count($customerResponse->customer->phones) > 0) {
             return array_shift($customerResponse->customer->phones)->number;
        }
        return null;
    }

    private function getTasks(\DateTime $start, \DateTime $end): array
    {
        $request = new TasksRequest();
        $filter = new TaskFilter();
        $filter->status = 'performing';

        $filter->dateFrom = $start->format('Y-m-d H:i:s');
        $filter->dateTo = $end->format('Y-m-d H:i:s');

        $request->filter = $filter;
        $request->limit = 100;

        return $this->client->tasks->list($request)->tasks;
    }

    private function getCustomer(Task $task)
    {
        $request = new CustomersRequest();
        $filter = new CustomerFilter();
        $filter->ids = [$task->customer->id];
        $request->filter = $filter;
        $response = $this->client->customers->list($request);
        $customer = array_shift($response->customers);
        return $customer->firstName;
    }

    private function getPerformer(Task $task): string
    {
        $performer = null;
        if ($task->performerType == 'group') {
            $group = $this->groupService->findGroupById($task->performer);
            $performer = $group->name;
        }
        if ($task->performerType == 'user') {
            $performer = $this->client->users->get($task->performer)->user->firstName;
        }
        return $performer;
    }
}
