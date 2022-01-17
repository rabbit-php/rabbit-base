<?php

declare(strict_types=1);

namespace Rabbit\Base\Table;

use Swoole\Table as SwooleTable;

/**
 * Class Table
 * @package Rabbit\Base\Table
 */
final class Table
{

    /**
     * 一个单位长度的int类型
     */
    const ONE_INT_LENGTH = 1;

    /**
     * 两个单位长度的int类型
     */
    const TWO_INT_LENGTH = 2;

    /**
     * 四个单位长度的int类型
     */
    const FOUR_INT_LENGTH = 4;

    /**
     * 八个单位长度的int类型
     */
    const EIGHT_INT_LENGTH = 8;

    /**
     * int类型
     */
    const TYPE_INT = SwooleTable::TYPE_INT;

    /**
     * string类型
     */
    const TYPE_STRING = SwooleTable::TYPE_STRING;

    /**
     * float类型
     */
    const TYPE_FLOAT = SwooleTable::TYPE_FLOAT;

    public readonly SwooleTable $table;

    /**
     * Table constructor.
     * @param string $name
     * @param int $size
     * @param array $columns
     */
    public function __construct(public string $name = '', public int $size = 0, private array $columns = [])
    {
        if ($size % 1024 !== 0) {
            throw new \InvalidArgumentException("swoole_table::size error ：$size");
        }
        $this->table = new SwooleTable($size);
    }

    /**
     * 返回列字段数组
     *
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * 设置内存表字段结构
     *
     * @param array $columns 字段数组
     *
     * @return Table;
     */
    public function setColumns(array $columns): Table
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * 内存表增加一列
     *
     * @param string $name 列名
     * @param int $type 类型
     * @param int $size 最大长度，单位为字节
     * @return void
     */
    public function column(string $name, int $type, int $size = 0): void
    {
        $this->columns[$name] = [$type, $size];
    }

    /**
     * 创建内存表
     * @return bool
     */
    public function create(): bool
    {
        foreach ($this->columns as $field => $fieldValue) {
            $args = [$field, ...$fieldValue];
            $args = $this->checkColumn(...$args);
            $this->createColumn(...$args);
        }

        return $this->table->create();
    }

    /**
     * @param string $name
     * @param int $type
     * @param int $size
     * @return array
     */
    private function checkColumn(string $name, int $type, int $size): array
    {
        switch ($type) {
            case self::TYPE_INT:
                if (!in_array(
                    $size,
                    [self::ONE_INT_LENGTH, self::TWO_INT_LENGTH, self::FOUR_INT_LENGTH, self::EIGHT_INT_LENGTH]
                )) {
                    $size = 4;
                }
                break;
            case self::TYPE_STRING:
                if ($size < 0) {
                    throw new \RuntimeException('Size not be allow::' . $size);
                }
                break;
            case self::TYPE_FLOAT:
                $size = 8;
                break;
            default:
                throw new \RuntimeException('Undefind Column-Type::' . $type);
        }
        return [$name, $type, $size];
    }

    /**
     * @param string $name
     * @param int $type
     * @param int $size
     */
    private function createColumn(string $name, int $type, int $size)
    {
        $this->table->column($name, $type, $size);
    }

    /**
     * 设置行数据
     *
     * @param string $key 索引键
     * @param array $array 数据
     *
     * @return bool
     */
    public function set(string $key, array $array): bool
    {
        return $this->table->set($key, $array);
    }

    /**
     * 原子自增操作
     *
     * @param string $key 索引键
     * @param string $column 列名
     * @param int|float $incrby 增量。如果列为整形，$incrby必须为int型，如果列为浮点型，$incrby必须为float类型
     *
     * @return bool
     */
    public function incr(string $key, string $column, $incrby = 1): bool
    {
        return $this->table->incr($key, $column, $incrby);
    }

    /**
     * 原子自减操作
     *
     * @param string $key 索引键
     * @param string $column 列名
     * @param int|float $incrby 增量。如果列为整形，$incrby必须为int型，如果列为浮点型，$incrby必须为float类型
     *
     * @return bool|int 返回false执行失败，成功返回整数结果值
     */
    public function decr(string $key, string $column, $incrby = 1)
    {
        return $this->table->decr($key, $column, $incrby);
    }

    /**
     * 获取一行数据
     *
     * @param string $key 索引键
     * @param string $field 列名
     *
     * @return array
     */
    public function get(string $key, $field = null)
    {
        return $field ? $this->table->get($key, $field) : $this->table->get($key);
    }

    /**
     * 检查table中是否存在某一个key
     *
     * @param string $key 索引键
     * @return bool
     */
    public function exist(string $key): bool
    {
        return $this->table->exist($key);
    }

    /**
     * 删除数据
     *
     * @param string $key 索引键
     *
     * @return bool
     */
    public function del(string $key): bool
    {
        return $this->table->del($key);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->table->count();
    }

    /**
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call(string $method, array $args = [])
    {
        if (method_exists($this, $method)) {
            return $this->$method(...$args);
        }
        throw new \RuntimeException('Call a not exists method.');
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        $method = 'get' . ucfirst($name);
        if (!method_exists($this, $method)) {
            throw new \RuntimeException('Call undefind property::' . $name);
        }

        return $this->$method();
    }
}
