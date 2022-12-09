<?php

namespace Doctrine1;

enum EnumSetImplementation: string
{
    case String = 'string';
    case PHPStan = 'phpstan';
    case Enum = 'enum';
}
