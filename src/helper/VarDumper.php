<?php

namespace rabbit\helper;

use rabbit\contract\Arrayable;
use rabbit\core\Context;
use rabbit\exception\InvalidArgumentException;

/**
 * Class VarDumper
 *
 * @package common\Helpers
 */
class VarDumper
{
    private $_objects;

    private $_output;

    private $_depth;

    /**
     * getDumper
     *
     * @return VarDumper
     */
    public static function getDumper(): self
    {
        if (($dumper = Context::get('vardumper')) !== null) {
            return $dumper;
        }
        $dumper = new static();
        Context::set('vardumper', $dumper);
        return $dumper;
    }

    /**
     * dumpAsString
     *
     * @param unknown $var
     * @param number $depth
     * @return string|mixed|unknown
     */
    public function dumpAsString($var, $depth = 10)
    {
        $this->_output = '';
        $this->_objects = [];
        $this->_depth = $depth;
        $this->dumpInternal($var, 0);

        return $this->_output;
    }

    /**
     * dumpInternal
     *
     * @param mixed $var variable to be dumped
     * @param int $level depth level
     */
    private function dumpInternal($var, $level)
    {
        switch (gettype($var)) {
            case 'boolean':
                $this->_output .= $var ? 'true' : 'false';
                break;
            case 'integer':
                $this->_output .= (string)$var;
                break;
            case 'double':
                $this->_output .= (string)$var;
                break;
            case 'string':
                $this->_output .= addslashes($var);
                break;
            case 'resource':
                $this->_output .= '{resource}';
                break;
            case 'NULL':
                $this->_output .= 'null';
                break;
            case 'unknown type':
                $this->_output .= '{unknown}';
                break;
            case 'array':
                if ($this->_depth <= $level) {
                    $this->_output .= '...';
                } elseif (empty($var)) {
                    $this->_output .= '';
                } else {
                    $keys = array_keys($var);
                    $spaces = str_repeat(' ', $level * 4);
                    $this->_output .= '[';
                    foreach ($keys as $key) {
                        $this->_output .= "\n" . $spaces . '    ';
                        $this->dumpInternal($key, 0);
                        $this->_output .= ' => ';
                        $this->dumpInternal($var[$key], $level + 1);
                    }
                    $this->_output .= "\n" . $spaces . ']';
                }
                break;
            case 'object':
                if (($id = array_search($var, $this->_objects, true)) !== false) {
                    $this->_output .= get_class($var) . '#' . ($id + 1) . '(...)';
                } elseif ($this->_depth <= $level) {
                    $this->_output .= get_class($var) . '(...)';
                } else {
                    $id = array_push($this->_objects, $var);
                    $className = get_class($var);
                    $spaces = str_repeat(' ', $level * 4);
                    $this->_output .= "$className#$id\n" . $spaces . '(';
                    if ('__PHP_Incomplete_Class' !== get_class($var) && method_exists($var, '__debugInfo')) {
                        $dumpValues = $var->__debugInfo();
                        if (!is_array($dumpValues)) {
                            throw new InvalidArgumentException('__debuginfo() must return an array');
                        }
                    } else {
                        $dumpValues = (array)$var;
                    }
                    foreach ($dumpValues as $key => $value) {
                        $keyDisplay = strtr(trim($key), "\0", ':');
                        $this->_output .= "\n" . $spaces . "    [$keyDisplay] => ";
                        $this->dumpInternal($value, $level + 1);
                    }
                    $this->_output .= "\n" . $spaces . ')';
                }
                break;
        }
    }

    /**
     * export
     *
     * @param $var
     * @return string
     * @throws \ReflectionException
     */
    public function export($var)
    {
        $this->_output = '';
        $this->exportInternal($var, 0);
        return $this->_output;
    }

    /**
     * exportInternal
     *
     * @param $var
     * @param $level
     * @throws \ReflectionException
     */
    private function exportInternal($var, $level)
    {
        switch (gettype($var)) {
            case 'NULL':
                $this->_output .= 'null';
                break;
            case 'array':
                if (empty($var)) {
                    $this->_output .= '[]';
                } else {
                    $keys = array_keys($var);
                    $outputKeys = ($keys !== range(0, count($var) - 1));
                    $spaces = str_repeat(' ', $level * 4);
                    $this->_output .= '[';
                    foreach ($keys as $key) {
                        $this->_output .= "\n" . $spaces . '    ';
                        if ($outputKeys) {
                            $this->exportInternal($key, 0);
                            $this->_output .= ' => ';
                        }
                        $this->exportInternal($var[$key], $level + 1);
                        $this->_output .= ',';
                    }
                    $this->_output .= "\n" . $spaces . ']';
                }
                break;
            case 'object':
                if ($var instanceof \Closure) {
                    $this->_output .= $this->exportClosure($var);
                } else {
                    try {
                        $output = 'unserialize(' . var_export(serialize($var), true) . ')';
                    } catch (\Exception $e) {
                        // serialize may fail, for example: if object contains a `\Closure` instance
                        // so we use a fallback
                        if ($var instanceof Arrayable) {
                            $this->exportInternal($var->toArray(), $level);
                            return;
                        } elseif ($var instanceof \IteratorAggregate) {
                            $varAsArray = [];
                            foreach ($var as $key => $value) {
                                $varAsArray[$key] = $value;
                            }
                            $this->exportInternal($varAsArray, $level);
                            return;
                        } elseif ('__PHP_Incomplete_Class' !== get_class($var) && method_exists($var, '__toString')) {
                            $output = var_export($var->__toString(), true);
                        } else {
                            $outputBackup = $this->_output;
                            $output = var_export($this->dumpAsString($var), true);
                            $this->_output = $outputBackup;
                        }
                    }
                    $this->_output .= $output;
                }
                break;
            default:
                $this->_output .= var_export($var, true);
        }
    }

    /**
     * exportClosure
     *
     * @param \Closure $closure
     * @return string
     * @throws \ReflectionException
     */
    private function exportClosure(\Closure $closure)
    {
        $reflection = new \ReflectionFunction($closure);

        $fileName = $reflection->getFileName();
        $start = $reflection->getStartLine();
        $end = $reflection->getEndLine();

        if ($fileName === false || $start === false || $end === false) {
            return 'function() {/* Error: unable to determine Closure source */}';
        }

        --$start;

        $source = implode("\n", array_slice(file($fileName), $start, $end - $start));
        $tokens = token_get_all('<?php ' . $source);
        array_shift($tokens);

        $closureTokens = [];
        $pendingParenthesisCount = 0;
        foreach ($tokens as $token) {
            if (isset($token[0]) && $token[0] === T_FUNCTION) {
                $closureTokens[] = $token[1];
                continue;
            }
            if ($closureTokens !== []) {
                $closureTokens[] = isset($token[1]) ? $token[1] : $token;
                if ($token === '}') {
                    $pendingParenthesisCount--;
                    if ($pendingParenthesisCount === 0) {
                        break;
                    }
                } elseif ($token === '{') {
                    $pendingParenthesisCount++;
                }
            }
        }

        return implode('', $closureTokens);
    }
}
