<?php

declare(strict_types=1);

namespace KayedSpace\N8n\Enums;

enum RequestMethod: string
{
    case Get = 'get';
    case Post = 'post';
    case Put = 'put';
    case Delete = 'delete';
    case Patch = 'patch';
    case Head = 'head';
    case Options = 'options';

    public static function isGet(string|self $value): bool
    {
        if (is_string($value)) {
            return self::Get->value === strtolower($value);
        }

        return $value === self::Get;
    }

    public static function isPost(string|self $value): bool
    {
        if (is_string($value)) {
            return self::Post->value === strtolower($value);
        }

        return $value === self::Post;
    }

    public static function isPut(string|self $value): bool
    {
        if (is_string($value)) {
            return self::Put->value === strtolower($value);
        }

        return $value === self::Put;
    }

    public static function isDelete(string|self $value): bool
    {
        if (is_string($value)) {
            return self::Delete->value === strtolower($value);
        }

        return $value === self::Delete;
    }

    public static function isPatch(string|self $value): bool
    {
        if (is_string($value)) {
            return self::Patch->value === strtolower($value);
        }

        return $value === self::Patch;
    }

    public static function isHead(string|self $value): bool
    {
        if (is_string($value)) {
            return self::Head->value === strtolower($value);
        }

        return $value === self::Head;
    }

    public static function isOptions(string|self $value): bool
    {
        if (is_string($value)) {
            return self::Options->value === strtolower($value);
        }

        return $value === self::Options;
    }
}
