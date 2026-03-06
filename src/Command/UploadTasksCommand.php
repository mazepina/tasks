<?php

namespace App\Command;

use App\Service\ExcelService;
use App\Service\TasksService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UploadTasksCommand extends Command
{
    protected static $defaultName = 'tasks:upload';
    /**
     * @var ExcelService
     */
    private $excelService;
    /**
     * @var TasksService
     */
    private $tasksService;

    private $projectDir;

    public function __construct(string $name = null, ExcelService $excelService, TasksService $tasksService, $projectDir)
    {
        parent::__construct($name);

        $this->excelService = $excelService;
        $this->tasksService = $tasksService;
        $this->projectDir = $projectDir;
    }

    protected function configure()
    {
        parent::configure();

        $this->addArgument('day', InputArgument::OPTIONAL, 'Upload for day number');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $end = new \DateTime();

        if ($input->getArgument('day')) {
            $end->setDate($end->format('Y'), $end->format('m'), $input->getArgument('day'));
        }

        $date = clone $end;
        $rows = $this->tasksService->getTasksArray($end);
        $date = $date->format('d');
        $this->excelService->writeXLSX($this->projectDir . '/public/tasks-' . $date . '.xlsx', $rows);

        return 0;
    }

}
