<?php


namespace rabbit\contract;

use rabbit\App;

/**
 * Interface TaskInterface
 * @package rabbit\contract
 */
abstract class AbstractTask
{
    /**
     * @param int $task_id
     * @param int $from_id
     * @param $data
     * @return mixed
     */
    abstract public function handle(int $task_id, int $from_id, $data);

    /**
     * @param array $tasks
     * @param float $timeout
     * @return array
     */
    public static function task(array $tasks, float $timeout = 0.5): array
    {
        return App::getServer()->taskCo($tasks, $timeout);
    }
}