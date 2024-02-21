<?php

namespace Battis\PHPGenerator\Method;

use Battis\PHPGenerator\Base;

class ReturnType extends Base
{
    private string $type;

    private ?string $docType;
    
    private bool $nullable = false;

    private ?string $description;

    public static function from(string $type, ?string $description = null, ?string $docType = null, bool $nullable = false): ReturnType
    {
        $returnType = new ReturnType();
        $returnType->type = $type;
        $returnType->description = $description;
        $returnType->docType = $docType;
        $returnType->nullable = $nullable;
        return $returnType;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDocType(): ?string
    {
        return $this->docType;
    }

    public function asPHPDocReturn(): string
    {
        return "@return " . ($this->nullable ? "?":"").$this->typeAs($this->docType ?? $this->type, self::TYPE_ABSOLUTE) . ($this->description ? " $this->description" : "");
    }

    public function asPHPDocThrows(): string
    {
        return "@throws " . ($this->nullable ? "?":"").$this->typeAs($this->docType ?? $this->type, self::TYPE_ABSOLUTE) . ($this->description ? " $this->description" : "");
    }
}
