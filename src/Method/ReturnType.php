<?php

namespace Battis\PHPGenerator\Method;

use Battis\PHPGenerator\Type;

class ReturnType
{
    public const NONE = 0;
    public const NULLABLE = 1;

    private Type $type;

    private ?string $description;

    private int $flags;

    /**
     * @param string|\Battis\PHPGenerator\Type $type
     * @param string $description
     * @param int $flags
     */
    public function __construct($type = "void", ?string $description = null, int $flags = self::NONE)
    {
        $this->type = $type instanceof Type ? $type : new Type($type);
        $this->description = $description;
        $this->flags = $flags;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function asPHPDocReturn(): string
    {
        return "@return " .
            (($this->flags & self::NULLABLE) ? ($this->type->isMixed() ? "null|" : "?") : "") . $this->type->as(Type::ABSOLUTE) .
            ($this->description !== null ? " $this->description" : "");
    }

    public function asPHPDocThrows(): string
    {
        return "@throws " .
            $this->type->as(Type::ABSOLUTE) .
            ($this->description !== null ? " $this->description" : "");
    }
}
