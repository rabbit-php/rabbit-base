<?php


namespace rabbit\contract;

use rabbit\App;

/**
 * Interface TaskInterface
 * @package rabbit\contract
 */
abstract class AbstractTask
{
    /** @var array */
    protected $taskList = [];
    /** @var string */
    protected $logKey = 'Task';
    /** @var string */
    protected $taskName;

    /**
     * AbstractTask constructor.
     * @param array $taskList
     */
    public function __construct(string $name = null)
    {
        $this->taskName = $name ?? uniqid();
    }

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
    public function finish(\Swoole\Server $serv, int $task_id, string $data): void
    {
    }


    /**
     * @param float $timeout
     * @return array
     */
    public function start(float $timeout = 0.5): array
    {
        App::info('Task' . " $this->taskName " . 'start count=' . count($this->taskList), $this->logKey);
        $result = App::getServer()->taskCo($this->taskList, $timeout);
        App::info('Task' . " $this->taskName " . 'finish!', $this->logKey);
        return is_array($result) ? $result : [$result];
    }

    /**
     * @param $task
     * @return AbstractTask
     */
    public function addTask($task): self
    {
        $this->taskList[] = $task;
        return $this;
    }
}