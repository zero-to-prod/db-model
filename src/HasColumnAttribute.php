<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel;

use ReflectionEnum;
use ReflectionException;

/** Reads the #[Column] attribute of the case it is called on. */
trait HasColumnAttribute
{
    /** @param  list<mixed>  $arguments */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->attribute($method);
    }

    public function attribute(string $attribute): mixed
    {
        return $this->arguments()[$attribute] ?? null;
    }

    /**
     * The validation rules the column constrains a value to. Anything beyond
     * the schema itself — uniqueness, confirmation, a format the database does
     * not enforce — is the request's to declare.
     *
     * @return list<string>
     *
     * @throws ReflectionException
     */
    public function rules(): array
    {
        $arguments = $this->arguments();
        $ColumnType = $this->columnType();
        $length = $arguments[Column::length] ?? null;

        $rules = [
            ($arguments[Column::nullable] ?? false) === true ? 'nullable' : 'required',
            $ColumnType->rule(),
        ];

        if ($ColumnType->php() === PhpType::string && is_int($length)) {
            $rules[] = 'max:'.$length;
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ReflectionException
     */
    protected function arguments(): array
    {
        $arguments = new ReflectionEnum(self::class)
            ->getCase($this->name)
            ->getAttributes(Column::class)[0]
            ->getArguments()[0];

        return is_array($arguments) ? $arguments : [];
    }

    protected function columnType(): ColumnType
    {
        $type = $this->arguments()[Column::type] ?? null;

        return ColumnType::from(is_string($type) ? $type : '');
    }
}
