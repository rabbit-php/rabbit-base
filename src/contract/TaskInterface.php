<?php


namespace rabbit\contract;

/**
 * Interface TaskInterface
 * @package rabbit\contract
 */
interface TaskInterface
{
    /**
     * @param int $task_id
     * @param int $from_id
     * @param $data
     * @return mixed
     */
    public function handle(int $task_id, int $from_id, $data);

    /**
     * @param array $tasks
     * @param float $timeout
     * @return array
     */
    public function task(array $tasks, float $timeout = 0.5): array;
}