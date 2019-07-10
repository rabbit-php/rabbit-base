<?php


namespace rabbit\contract;


use rabbit\App;
use rabbit\helper\TaskGroup;

abstract class AbstractTaskGroup
{
    /** @var array */
    protected $taskList = [];
    /** @var string */
    protected $logKey = 'Task';
    /** @var string */
    protected $taskName;
    /** @var int */
    private $group = 0;

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
     * @param float $timeout
     */
    public function startGroup(float $timeout = 0.5): void
    {
        $success = 0;
        $failed = [];
        foreach ($this->taskList as $items) {
            $taskGroup = new TaskGroup();
            foreach ($items as $task) {
                $task = array_merge($task, [
                    function (\Swoole\Server $serv, $task_id, $data) use ($taskGroup) {
                        $taskGroup->push($data);
                    }
                ]);
                App::getServer()->task(...$task);
                $taskGroup->add();
            }
            App::info('Task start file count=' . $taskGroup->getCount(), $this->logKey);
            $result = $taskGroup->wait($timeout);
            foreach ($result as $res) {
                $success += $res['success'];
                $failed = array_merge($failed, $res['failed']);
            }
        }
        $failed = implode(' & ', $failed);
        App::info('Task finish! success=' . $success . ' failed=' . (empty($failed) ? 0 : $failed), $this->logKey);
    }

    /**
     * @param $task
     * @param int $group
     * @return AbstractTask
     */
    public function addGroup($task, int $dst_worker_id = -1, int $group = 0): self
    {
        $this->group = $group;
        if ($group > 0) {
            if (!empty($this->taskList) && count($this->taskList[count($this->taskList) - 1]) < $group) {
                $this->taskList[count($this->taskList) - 1][] = [$task, $dst_worker_id];
                return $this;
            }
        }
        $this->taskList[] = [$task, $dst_worker_id];
        return $this;
    }
}