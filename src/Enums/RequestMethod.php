<?php

declare(strict_types=1);

namespace KayedSpace\N8n\Enums;

use InvalidArgumentException;

enum RequestMethod: string
{
    case Get = 'get';
    case Post = 'post';
    case Put = 'put';
    case Delete = 'delete';
    case Patch = 'patch';
    case Head = 'head';
    case Options = 'options';

    /**
     * True when the given value refers to **this** method.
     */
    public function is(string|self $value): bool
    {
        return $this === self::parse($value);
    }

    public static function parse(string|self $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        $value = strtolower($value);

        return self::tryFrom($value)
            ?? throw new InvalidArgumentException("Unsupported HTTP method [{$value}]");
    }
}
