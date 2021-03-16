<?php

declare(strict_types=1);

namespace Rabbit\Base\Core;

class StringBuilder
{
    protected array $body = [];

    public function __construct(string $str = null)
    {
        $str && $this->body[] = $str;
    }

    public function isEmpty(): bool
    {
        return empty($this->body);
    }

    public function append(string $str): self
    {
        $this->body[] = $str;
        return $this;
    }

    public function appendLine(string $str): self
    {
        $str .= PHP_EOL;
        $this->append($str);
        return $this;
    }

    public function appendFormat(string $str, ...$args): self
    {
        $str = sprintf($str, $args);
        $this->append($str);
        return $this;
    }

    public function toString(): string
    {
        return implode('', $this->body);
    }

    public function __toString()
    {
        return implode('', $this->body);
    }
}
