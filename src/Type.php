<?php

namespace Battis\PHPGenerator;

class Type
{
    public const SHORT = 1;
    public const FQN = 2;
    public const ABSOLUTE = 4;
    public const PHP = 8;

    private string $fqn = "";

    public function __construct(string $fqn)
    {
        $this->fqn = $fqn;
        if (!$this->isArray() && !$this->isMixed()) {
            $this->fqn = trim($this->fqn, "\\");
        }
    }
    
    public function equals(Type $other): bool {
        return $this->fqn === $other->fqn;
    }

    public function isPrimitive(): bool
    {
        return in_array($this->fqn, ['void', 'null','bool','int','float','string','array','object','callable','resource']);
    }

    public function as(int $flags = self::FQN): string
    {
        if ($flags & self::PHP) {
            // simplify psalm-style and phpdoc types to PHP parent type
            if ($this->isArray()) {
                return 'array';
            } elseif (strpos($this->fqn, "|") != false) {
                if (strpos($this->fqn, '"') === false && strpos($this->fqn, "'") === false) {
                    return 'mixed';
                } else {
                    return 'string';
                }
            }
        }

        if ($this->isMixed()) {
            return $this->fqn;
        }

        $t = $this->getArrayElementType();
        if (
            $this->isPrimitive() ||
            ($this->isArray() && ($t === null || $t->isPrimitive()))
        ) {
            // not a namespaced type so nothing to do
            return $this->fqn;
        }

        if ($flags & self::ABSOLUTE) {
            return "\\" . $this->fqn;
        }

        if ($flags & self::SHORT) {
            $parts = explode("\\", $this->fqn);
            return array_pop($parts);
        }

        return $this->fqn;
    }

    /**
     * @param class-string|Type $class
     *
     * @return bool
     */
    public function is_a($class): bool
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        return is_a($this->fqn, is_string($class) ? $class : $class->as(Type::ABSOLUTE), true);
    }

    public function isImportable(): bool
    {
        return !$this->isArray() && !$this->isMixed() && !$this->isPrimitive();
    }

    public function isArray(): bool
    {
        return substr($this->fqn, -2) === '[]' || preg_match("/^array[^a-zA-Z0-9_]/", $this->fqn);
    }

    public function isMixed(): bool
    {
        return strpos($this->fqn, "|") !== false;
    }

    public function getArrayElementType(): ?Type
    {
        if (substr($this->fqn, -2) === '[]') {
            return new Type(substr($this->fqn, 0, -2));
        }
        return null;
    }
}
