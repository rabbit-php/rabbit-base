<?php


namespace rabbit\core;

/**
 * Class CoScheduler
 * @package rabbit\core
 */
class CoScheduler
{
    /** @var string */
    private $name;

    private $scheduler;

    /**
     * CoScheduler constructor.
     * @param string|null $name
     */
    public function __construct(string $name = null)
    {
        $this->name = $name ?? uniqid();
        $this->scheduler = new \Co\Scheduler;
    }

    /**
     * @param array $options
     */
    public function set(array $options)
    {
        $this->scheduler->set($options);
    }

    /**
     * @param callable $fn
     * @param mixed ...$args
     */
    public function add(callable $fn, ... $args)
    {
        $this->scheduler->add($fn, ...$args);
    }

    /**
     * @param int $n
     * @param callable $fn
     * @param mixed ...$args
     */
    public function parallel(int $n, callable $fn, ... $args)
    {
        $this->scheduler->parallel($n, $fn, ...$args);
    }

    /**
     * @return bool
     */
    public function start(): bool
    {
        return $this->scheduler->start();
    }
}
