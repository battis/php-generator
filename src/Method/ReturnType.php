<?php

namespace Battis\PHPGenerator\Method;

use Battis\PHPGenerator\Base;

class ReturnType extends Base
{
    private string $type;

    private ?string $docType;

    private ?string $description;

    public static function from(string $type, ?string $description = null, ?string $docType = null): ReturnType
    {
        $returnType = new ReturnType();
        $returnType->type = $type;
        $returnType->description = $description;
        $returnType->docType = $docType;
        return $returnType;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDocTyoe(): ?string
    {
        return $this->docType;
    }

    public function asPHPDocReturn(): string
    {
        return "@return " . $this->typeAs($this->docType ?? $this->type, self::TYPE_ABSOLUTE) . ($this->description ? " $this->description" : "");
    }

    public function asPHPDocThrows(): string
    {
        return "@throws " . $this->typeAs($this->docType ?? $this->type, self::TYPE_ABSOLUTE) . ($this->description ? " $this->description" : "");
    }
}
