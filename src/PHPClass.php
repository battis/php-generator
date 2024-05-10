<?php

namespace Battis\PHPGenerator;

use Battis\DataUtilities\Path;
use Battis\PHPGenerator\Exceptions\PHPGeneratorException;
use Battis\PHPGenerator\Method\Parameter;

class PHPClass
{
    protected string $namespace;

    protected ?string $description;

    protected string $name;

    protected ?Type $baseType;

    /**
     * @var Type[] $uses
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

    /**
     * @param string $namespace
     * @param string $name
     * @param null|string|Type $baseType
     * @param string $description
     */
    public function __construct(
        string $namespace,
        string $name,
        $baseType = null,
        ?string $description = null
    ) {
        $this->namespace = $namespace;
        $this->name = $name;
        $this->baseType = is_string($baseType) ? new Type($baseType) : $baseType;
        $this->description = $description;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getType(): Type
    {
        return new Type(Path::join("\\", [$this->namespace, $this->name]));
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
        $this->properties = array_filter(
            $this->properties,
            fn(Property $p) => !$p->equals($property)
        );
    }

    public function addMethod(Method $method): void
    {
        $this->methods[] = $method;
    }

    public function getMethod(string $name): ?Method
    {
        foreach ($this->methods as $method) {
            if ($method->getName() === $name) {
                return $method;
            }
        }
        return null;
    }

    public function removeMethod(Method $method): void
    {
        // TODO do we need to be more discerning than just matching by method name? PHP is not.
        $this->methods = array_filter(
            $this->methods,
            fn(Method $m) => $m->getName() !== $method->getName()
        );
    }

    /**
     * @param string|\Battis\PHPGenerator\Type $use
     *
     * @return void
     */
    public function addUses($use): void
    {
        if (is_string($use)) {
            $use = new Type($use);
        }
        assert(
            !$use->isArray(),
            new PHPGeneratorException(
                "Cannot use an array type: " . $use->as(Type::FQN)
            )
        );
        assert(
            !$use->isMixed(),
            new PHPGeneratorException(
                "Cannot use a mixed type: " . $use->as(Type::FQN)
            )
        );

        foreach ($this->uses as $other) {
            if ($use->equals($other)) {
                return;
            }
        }
        if ($use->isImportable()) {
            $this->uses[] = $use;
        }
    }

    public function asImplementation(): string
    {
        if ($this->baseType) {
            $this->addUses($this->baseType);
        }
        foreach ($this->methods as $method) {
            $returnType = $method->getReturnType()->getType();
            if ($returnType->isImportable()) {
                $this->addUses($returnType);
            }
            $self = $this;

            array_map(function (Parameter $p) use ($self) {
                if ($p->getType()->isImportable()) {
                    $self->addUses($p->getType());
                }
            }, $method->getParameters());
        }

        sort($this->uses);
        $uses = [];
        $remap = [];
        foreach ($this->uses as $use) {
            if (strtolower($use->as(Type::SHORT)) === strtolower($this->name)) {
                $remap[$use->as(Type::FQN)] =
                  basename(dirname(str_replace("\\", "/", $use->as(Type::FQN)))) .
                  "_" .
                  $use->as(Type::SHORT);
                $uses[] =
                  "use " .
                  $use->as(Type::FQN) .
                  " as " .
                  $remap[$use->as(Type::FQN)] .
                  ";" .
                  PHP_EOL;
            } else {
                $uses[] = "use " . $use->as(Type::FQN) . ";" . PHP_EOL;
            }
        }

        $classDoc = new Doc();
        if ($this->description !== null) {
            $classDoc->addItem($this->description);
        }
        $properties = [];
        foreach ($this->properties as $prop) {
            if ($prop->isDocumentationOnly()) {
                $classDoc->addItem($prop->asPHPDocProperty());
            } else {
                $properties[] = $prop->asDeclaration($remap);
            }
        }
        $classDoc->addItem("@api");

        return "<?php" .
          PHP_EOL .
          PHP_EOL .
          "namespace " .
          $this->namespace .
          ";" .
          PHP_EOL .
          PHP_EOL .
          (empty($uses) ? "" : join($uses) . PHP_EOL) .
          $classDoc->asString(0) .
          "class $this->name " .
          ($this->baseType !== null
            ? "extends " . $this->baseType->as(Type::SHORT)
            : "") .
          PHP_EOL .
          "{" .
          PHP_EOL .
          (empty($properties) ? "" : join(PHP_EOL, $properties)) .
          (empty($this->methods)
            ? ""
            : PHP_EOL .
              join(
                  PHP_EOL,
                  array_map(
                      fn(Method $m) => $m->asImplementation($remap),
                      $this->methods
                  )
              )) .
          "}" .
          PHP_EOL;
    }

    public function mergeWith(PHPClass $other): void
    {
        assert(
            $this->namespace === $other->namespace,
            new PHPGeneratorException(
                "Namespace mismatch on merge: $this->namespace and $other->namespace"
            )
        );

        /**
         * TODO clean up logic
         */
        if ($this->baseType !== null && $other->baseType !== null) {
            if ($other->baseType->is_a($this->baseType)) {
                $this->baseType = $other->baseType;
            } elseif (
                $this->baseType !== $other->baseType &&
                !$this->baseType->is_a($other->baseType)
            ) {
                throw new PHPGeneratorException(
                    "Incompatible base types in merge: " .
                    $this->baseType->as(Type::FQN) .
                    " and " .
                    $other->baseType->as(Type::FQN)
                );
            }
        } else {
            throw new PHPGeneratorException(
                "Incompatible base types in merge: " .
                ($this->baseType === null ? "null" : $this->baseType->as(Type::FQN)) .
                " and " .
                ($other->baseType === null ? "null" : $other->baseType->as(Type::FQN))
            );
        }

        $thisProperties = array_map(
            fn(Property $p) => $p->getName(),
            $this->properties
        );
        $otherProperties = array_map(
            fn(Property $p) => $p->getName(),
            $other->properties
        );
        $duplicateProperties = array_intersect($thisProperties, $otherProperties);
        assert(
            count($duplicateProperties) === 0,
            new PHPGeneratorException(
                "Duplicate properties in merge: " .
                var_export($duplicateProperties, true)
            )
        );

        $thisMethods = array_map(fn(Method $m) => $m->getName(), $this->methods);
        $otherMethods = array_map(fn(Method $m) => $m->getName(), $other->methods);
        $duplicateMethods = array_intersect($thisMethods, $otherMethods);
        assert(
            count($duplicateMethods) === 0,
            new PHPGeneratorException(
                "Duplicate methods in merge: " . var_export($duplicateMethods, true)
            )
        );

        $this->description = join(PHP_EOL, [
          $this->description,
          $other->description,
        ]);
        foreach ($other->uses as $use) {
            $this->addUses($use);
        }
        $this->properties = array_merge($this->properties, $other->properties);
        $this->methods = array_merge($this->methods, $other->methods);
    }
}
