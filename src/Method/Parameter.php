<?php

namespace Battis\PHPGenerator\Method;

use Battis\PHPGenerator\Type;

class Parameter
{
    public const NONE = 0;
    public const NULLABLE = 1;

    private string $name;

    private Type $type;

    private ?string $defaultValue;

    private ?string $description;

    private int $flags;

    /**
     * @param string $name
     * @param string|\Battis\PHPGenerator\Type $type
     * @param ?string $defaultValue
     * @param ?string $description
     * @param int $flags
     */
    public function __construct(
        string $name,
        $type,
        ?string $defaultValue = null,
        ?string $description = null,
        int $flags = self::NONE
    ) {
        $this->name = $name;
        $this->type = is_string($type) ? new Type($type) : $type;
        $this->defaultValue = $defaultValue;
        $this->description = $description;
        $this->flags = $flags;
    }

    public function isNullable(): bool
    {
        return ($this->flags & self::NULLABLE) == true;
    }

    public function isOptional(): bool
    {
        return $this->defaultValue !== null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function asPHPDocParam(): string
    {
        return "@param " .
          ($this->flags & self::NULLABLE ? ($this->type->isMixed() ? "null|" : "?") : "") .
          $this->type->as(Type::ABSOLUTE) .
          " \$$this->name" .
          ($this->defaultValue !== null
            ? " (Optional, default `$this->defaultValue`)"
            : "") .
          ($this->description !== null ? " $this->description" : "");
    }

    /**
     * @param array<string, string> $remap
     *
     * @return string
     */
    public function asDeclaration(array $remap = []): string
    {
        return ($this->flags & self::NULLABLE ? "?" : "") .
          ($remap[$this->type->as(Type::FQN)] ??
            $this->type->as(Type::SHORT | Type::PHP)) .
          " \$$this->name" .
          ($this->defaultValue !== null ? " = $this->defaultValue" : "");
    }
}
