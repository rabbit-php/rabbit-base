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
     * @param \Swoole\Server $serv
     * @param int $task_id
     * @param string $data
     */
    abstract public function finish(\Swoole\Server $serv, int $task_id, string $data): void;

    /**
     * @param array $tasks
     * @param float $timeout
     * @return array
     */
    public static function taskCo(array $tasks, float $timeout = 0.5): array
    {
        $result = App::getServer()->taskCo($tasks, $timeout);
        return is_array($result) ? $result : [$result];
    }
}