<?php

namespace App\Types;

enum FieldType: string
{
    case STRING = 'string';
    case INTEGER = 'int';
    case BOOLEAN = 'bool';
    case FLOAT = 'float';
    case DATETIME = 'datetime';
    case ENUM = 'enum';
    case ARRAY = 'array';
}
