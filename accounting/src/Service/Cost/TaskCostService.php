<?php

namespace Accounting\Service\Cost;

class TaskCostService
{
    public function getTaskCost(): string
    {
        return (string) rand(-10, -20);
    }

    public function getCompletedTaskCost(): string
    {
        return (string) rand(20, 40);
    }
}