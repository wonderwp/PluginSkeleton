<?php

declare(strict_types=1);

namespace WonderWp\Component\DependencyInjection {
    if (!interface_exists(SingletonInterface::class, false)) {
        interface SingletonInterface
        {
            public static function getInstance();
        }
    }

    if (!trait_exists(SingletonTrait::class, false)) {
        trait SingletonTrait
        {
            protected static $instance;

            public static function getInstance()
            {
                if (null === static::$instance) {
                    static::$instance = static::buildInstance();
                }

                return static::$instance;
            }

            public static function buildInstance()
            {
                return new static();
            }

            public static function resetInstance(): void
            {
                static::$instance = null;
            }
        }
    }

    if (!class_exists(Container::class, false)) {
        class Container implements SingletonInterface, \ArrayAccess
        {
            use SingletonTrait;

            private array $values = [];

            public function offsetExists($offset): bool
            {
                return array_key_exists($offset, $this->values);
            }

            public function offsetGet($offset): mixed
            {
                return $this->values[$offset];
            }

            public function offsetSet($offset, $value): void
            {
                $this->values[$offset] = $value;
            }

            public function offsetUnset($offset): void
            {
                unset($this->values[$offset]);
            }
        }
    }
}

namespace WonderWp\Component\HttpFoundation {
    if (!class_exists(Request::class, false)) {
        class Request
        {
            private static $instance;

            public static function getInstance()
            {
                if (null === self::$instance) {
                    self::$instance = new self();
                }

                return self::$instance;
            }

            public static function resetInstance(): void
            {
                self::$instance = null;
            }
        }
    }
}

namespace {
    if (!function_exists('do_action')) {
        function do_action($hook, ...$args)
        {
            $GLOBALS['wwp_test_do_action'][] = ['hook' => $hook, 'args' => $args];
        }
    }
}
