<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel;

use ReflectionEnum;

/** The column types a table enum may declare, and what each one maps onto. */
enum ColumnType: string
{
    #[PhpType(PhpType::string)]
    case varchar = 'varchar';
    #[PhpType(PhpType::string)]
    case mediumtext = 'mediumtext';
    #[PhpType(PhpType::string)]
    case text = 'text';
    #[PhpType(PhpType::string)]
    case longtext = 'longtext';
    #[PhpType(PhpType::int)]
    case tinyint = 'tinyint';
    #[PhpType(PhpType::int)]
    case int = 'int';
    #[PhpType(PhpType::int)]
    case bigint = 'bigint';
    #[PhpType(PhpType::DateTimeInterface)]
    case timestamp = 'timestamp';
    #[PhpType(PhpType::string)]
    case char = 'char';
    #[PhpType(PhpType::string)]
    case json = 'json';

    /** The native PHP type that carries this column type. */
    public function php(): string
    {
        return new ReflectionEnum(self::class)
            ->getCase($this->name)
            ->getAttributes(PhpType::class)[0]
            ->newInstance()
            ->type;
    }

    /** The validation rule that constrains a value of that native PHP type. */
    public function rule(): string
    {
        return match ($this->php()) {
            PhpType::int => 'integer',
            PhpType::DateTimeInterface => 'date',
            default => 'string',
        };
    }
}
