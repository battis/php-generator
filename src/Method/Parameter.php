<?php

namespace Battis\PHPGenerator\Method;

use Battis\PHPGenerator\Base;

class Parameter extends Base
{
    private string $name = "";

    private string $type = "";

    private ?string $docType = null;

    private ?string $description =  null;

    private bool $optional = false;

    public function isOptional(): bool
    {
        return $this->optional;
    }

    public function setDocType(string $docType): void
    {
        $this->docType = $docType;
    }

    public static function from(string $name, string $type, ?string $description = null, bool $optional = false): Parameter
    {
        $parameter = new Parameter();
        $parameter->name = $name;
        $parameter->type = $type;
        $parameter->description = $description;
        $parameter->optional = $optional;
        return $parameter;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function asPHPDocParam(): string
    {
        return trim("@param " . ($this->optional ? "?" : "") . $this->typeAs($this->docType ?? $this->type, self::TYPE_ABSOLUTE) . " \$$this->name $this->description");
    }

    /**
     * @param array<string, string> $remap
     *
     * @return string
     */
    public function asDeclaration(array $remap = []): string
    {
        return ($this->optional ? "?" : "") . ($remap[$this->type] ?? $this->typeAs($this->type, self::TYPE_SHORT)) . " \$$this->name" . ($this->optional ? " = null" : "");
    }
}
