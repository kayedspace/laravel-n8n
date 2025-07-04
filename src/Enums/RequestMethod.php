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
}
