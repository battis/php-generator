<?php

namespace Battis\PHPGenerator;

class Property extends Base
{
    /**
     * @var 'public'|'protected'|'private' $access
     */
    private string $access = 'public';

    private bool $static = false;

    private string $type;

    private ?string $docType = null;

    private string $name;

    private ?string $description = null;

    private ?string $defaultValue = null;

    private bool $documentationOnly = false;

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

    public function setDocType(string $docType)
    {
        $this->docType = $docType;
    }

    public function isDocumentationOnly(): bool
    {
        return $this->documentationOnly;
    }
    
    public static function private(string $name, string $type, ?string $description = null, ?string $defaultValue = null, bool $documentationOnly = false): Property
    {
        $property = new Property();
        $property->name = $name;
        $property->type = $type;
        $property->description = $description;
        $property->defaultValue = $defaultValue;
        $property->access = 'private';
        $property->documentationOnly = $documentationOnly;
        return $property;
    }

    public static function privateStatic(string $name, string $type, ?string $description = null, ?string $defaultValue = null, bool $documentationOnly = false): Property
    {
        $property = self::private($name, $type, $description, $defaultValue, $documentationOnly);
        $property->static = true;
        return $property;
    }

    public static function protected(string $name, string $type, ?string $description = null, ?string $defaultValue = null, bool $documentationOnly = false): Property
    {
        $property = self::private($name, $type, $description, $defaultValue, $documentationOnly);
        $property->access = 'protected';
        return $property;
    }

    public static function protectedStatic(string $name, string $type, ?string $description = null, ?string $defaultValue = null, bool $documentationOnly = false): Property
    {
        $property = self::protected($name, $type, $description, $defaultValue, $documentationOnly);
        $property->static = true;
        return $property;
    }
    
    public static function public(string $name, string $type, ?string $description = null, ?string $defaultValue = null, bool $documentationOnly = false): Property
    {
        $property = self::private($name, $type, $description, $defaultValue, $documentationOnly);
        $property->access = 'public';
        return $property;
    }

    public static function publicStatic(string $name, string $type, ?string $description = null, ?string $defaultValue = null, bool $documentationOnly = false): Property
    {
        $property = self::public($name, $type, $description, $defaultValue, $documentationOnly);
        $property->static = true;
        return $property;
    }
    public function asPHPDocProperty(): string
    {
        return trim("@property " . $this->typeAs($this->docType ?? $this->type, self::TYPE_ABSOLUTE) . " \$$this->name $this->description");
    }

    /**
     * @param array<string, string> $remap
     *
     * @return string
     */
    public function asDeclaration(array $remap = []): string
    {
        $doc = new Doc();
        $doc->addItem(trim("@var " . $this->typeAs($this->docType ?? $this->type, self::TYPE_ABSOLUTE) . " \$$this->name $this->description"));
        return $doc->asString() . "$this->access " . ($this->static ? "static " : "") . ($remap[$this->type] ?? $this->typeAs($this->type, self::TYPE_SHORT)) . " \$$this->name" . (empty($this->defaultValue) ? "" : " = $this->defaultValue") . ";" . PHP_EOL;
    }
}
