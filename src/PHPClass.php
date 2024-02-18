<?php

namespace Battis\PHPGenerator;

use Battis\DataUtilities\Path;

class PHPClass extends Base
{
    protected string $namespace = "";

    protected ?string $description = "";

    protected string $name = "";

    protected string $baseType = "";

    /**
     * @var string[] $uses
     */
    protected array $uses = [];

    /**
     * @var Property[] $properties
     */
    protected array $properties = [];

    /**
     * @var Method[]
     */
    protected array $methods = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getType(): string
    {
        return Path::join("\\", [$this->namespace, $this->name]);
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function addProperty(Property $property): void
    {
        $this->properties[] = $property;
    }

    public function removeProperty(Property $property): void
    {
        $this->properties = array_filter($this->properties, fn(Property $p) => !$p->equals($property));
    }

    public function addMethod(Method $method): void
    {
        $this->methods[] = $method;
    }

    public function getMethod(string $name): ?Method
    {
        foreach($this->methods as $method) {
            if ($method->getName() === $name) {
                return $method;
            }
        }
        return null;
    }

    public function removeMethod(Method $method)
    {
        // TODO do we need to be more discerning than just matching by method name? PHP is not.
        $this->methods = array_filter($this->methods, fn(Method $m) => $m->getName() !== $method->getName());
    }

    public function addUses(string $type): void
    {
        $this->uses[] = $type;
    }

    public function __toString()
    {
        $this->addUses($this->baseType);
        sort($this->uses);
        $this->uses = array_unique($this->uses);
        $uses = [];
        $remap = [];
        foreach($this->uses as $use) {
            if ($this->typeAs($use, self::TYPE_SHORT) === $this->name) {
                $remap[$use] = $this->typeAs($use, self::TYPE_SHORT) . "Disambiguate";
                $uses[] = "use $use as $remap[$use];" . PHP_EOL;
            } else {
                $uses[] = "use $use;" . PHP_EOL;
            }
        }

        $classDoc = new Doc();
        if ($this->description !== null) {
            $classDoc->addItem($this->description);
        }
        $properties = [];
        foreach($this->properties as $prop) {
            if ($prop->isDocumentationOnly()) {
                $classDoc->addItem($prop->asPHPDocProperty());
            } else {
                $properties[] = $prop->asDeclaration($remap);
            }
        }
        $classDoc->addItem("@api");

        return "<?php" . PHP_EOL . PHP_EOL .
        "namespace " . $this->namespace . ";" . PHP_EOL . PHP_EOL .
        (empty($uses) ? "" : join($uses) . PHP_EOL) .
        $classDoc->asString(0) .
        "class $this->name extends " . $this->typeAs($this->baseType, self::TYPE_SHORT) . PHP_EOL .
        "{" . PHP_EOL .
        (empty($properties) ? "" : join(PHP_EOL, $properties)) .
        (empty($this->methods) ? "" : PHP_EOL . join(PHP_EOL, array_map(fn(Method $m) => $m->asImplementation($remap), $this->methods))) .
        "}" . PHP_EOL;
    }
}
