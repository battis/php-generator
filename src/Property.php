<?php

namespace Battis\PHPGenerator;

class Property
{
    public const NONE = 0;
    public const STATIC = 1;
    public const NULLABLE = 2;
    public const DOCUMENTATION_ONLY = 4;

    private Access $access;

    private Type $type;

    private string $name;

    private ?string $description;

    private ?string $defaultValue;

    private int $flags;

    /**
     * @param Access $access
     * @param string $name
     * @param string|\Battis\PHPGenerator\Type $type
     * @param ?string $description
     * @param ?string $defaultValue
     * @param int $flags
     */
    public function __construct(
        Access $access,
        string $name,
        $type,
        ?string $description = null,
        ?string $defaultValue = null,
        $flags = self::NONE
    ) {
        $this->access = $access;
        $this->name = $name;
        $this->type = is_string($type) ? new Type($type) : $type;
        $this->description = $description;
        $this->defaultValue = $defaultValue;
        $this->flags = $flags;
    }

    public function equals(Property $other): bool
    {
        return $this->name === $other->name && $this->type === $other->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function isDocumentationOnly(): bool
    {
        return ($this->flags & self::DOCUMENTATION_ONLY) == true;
    }

    public function asPHPDocProperty(): string
    {
        return trim(
            "@property " .
            ($this->flags & self::NULLABLE ? ($this->type->isMixed() ? "null|" : "?") : "") .
            $this->type->as(Type::ABSOLUTE) .
            " \$$this->name $this->description"
        );
    }

    /**
     * @param array<string, string> $remap
     *
     * @return string
     */
    public function asDeclaration(array $remap = []): string
    {
        $doc = new Doc();
        $doc->addItem(
            "@var " .
            ($this->flags & self::NULLABLE ? ($this->type->isMixed() ? "null|" : "?") : "") .
            $this->type->as(Type::ABSOLUTE) .
            " \$$this->name" .
            ($this->description !== null ? " $this->description" : "")
        );
        return $doc->asString() .
          "{$this->access->value} " .
          ($this->flags & self::STATIC ? "static " : "") .
          ($this->flags & self::NULLABLE ? "?" : "") .
          ($remap[$this->type->as(Type::FQN)] ??
            $this->type->as(Type::PHP | Type::SHORT)) .
          " \$$this->name" .
          ($this->defaultValue === null ? "" : " = $this->defaultValue") .
          ";" .
          PHP_EOL;
    }
}
