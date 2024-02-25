<?php

namespace Battis\PHPGenerator;

use Battis\PHPGenerator\Exceptions\PHPGeneratorException;
use Battis\PHPGenerator\Method\Parameter;
use Battis\PHPGenerator\Method\ReturnType;

class Method
{
    public const NONE = 0;
    public const STATIC = 1;
    public const ABSTRACT = 2;
    public const API = 4;
    public const DOCUMENTATION_ONLY = 8;

    protected Access $access;

    protected int $flags;

    protected ?string $description;

    protected string $name;

    /**
     * @var Parameter[] $parameters;
     */
    protected array $parameters;

    protected ?string $body;

    protected ReturnType $returnType;

    /**
     * @var \Battis\PHPGenerator\Method\ReturnType[] $throws
     */
    protected array $throws = [];

    /**
     * @param \Battis\PHPGenerator\Access $access
     * @param string $name
     * @param Parameter[] $parameters
     * @param string|\Battis\PHPGenerator\Type|\Battis\PHPGenerator\Method\ReturnType $returnType
     * @param ?string $body
     * @param ?string $description
     * @param string[]|\Battis\PHPGenerator\Type[]|\Battis\PHPGenerator\Method\ReturnType[] $throws
     * @param int $flags
     */
    public function __construct(
        Access $access,
        string $name,
        array $parameters = [],
        $returnType = "void",
        ?string $body = null,
        ?string $description = null,
        array $throws = [],
        int $flags = self::NONE
    ) {
        $this->access = $access;
        $this->name = $name;
        $this->parameters = $parameters;
        $this->returnType =
          $returnType instanceof ReturnType
            ? $returnType
            : new ReturnType($returnType);
        $this->body = $body;
        $this->description = $description;
        $this->throws = array_map(
            /**
             * @$param string|\Battis\PHPGenerator\Type|\Battis\PHPGenerator\Method\ReturnType $throw
             * @return \Battis\PHPGenerator\Method\ReturnType
             * @psalm-suppress MissingClosureParamType, MixedArgument
             */
            function ($throw) {
                if ($throw instanceof ReturnType) {
                    return $throw;
                }
                return new ReturnType($throw);
            },
            $throws
        );
        $this->flags = $flags;

        assert(
            !($flags & self::ABSTRACT && $flags & self::STATIC),
            new PHPGeneratorException("PHP does not support abstract static methods")
        );
        assert(
            !($flags & self::ABSTRACT && $this->body !== null),
            new PHPGeneratorException("Abstract methods may not have bodies")
        );
        assert(
            !($flags & self::ABSTRACT && $this->access === Access::Private),
            new PHPGeneratorException("Private methods may not be abstract")
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getReturnType(): ReturnType
    {
        return $this->returnType;
    }

    /**
     * @return Parameter[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function addParameter(Parameter $parameter): void
    {
        $this->parameters[] = $parameter;
    }

    public function asPHPDocMethod(): string
    {
        assert($this->access === Access::Public, new PHPGeneratorException("Non-public documentation-only magic methods cannot exist"));
        assert(
            $this->flags & self::DOCUMENTATION_ONLY,
            new Exceptions\PHPGeneratorException(
                "{$this->name}() must be implemented"
            )
        );
        assert(
            $this->flags & self::ABSTRACT,
            new Exceptions\PHPGeneratorException(
                "{$this->name}() is abstract and cannot be a documentation-only magic method"
            )
        );
        return "@method " .
          ($this->flags & self::STATIC ? "static " : "") .
          $this->returnType->getType()->as(Type::ABSOLUTE) .
          " $this->name(" .
          join(
              ", ",
              array_map(
                  fn(Parameter $parameter) => $parameter
                  ->getType()
                  ->as(Type::ABSOLUTE) .
                  " " .
                  $parameter->getName(),
                  $this->parameters
              )
          ) .
          ")" .
          ($this->description !== null ? " $this->description" : "");
    }

    /**
     * @param array<string, string> $remap [FQN => Short]
     *
     * @return string
     */
    public function asImplementation(array $remap = []): string
    {
        assert(
            ($this->flags & self::DOCUMENTATION_ONLY) === 0,
            new Exceptions\PHPGeneratorException(
                "{$this->name}() is a documentation-only magic method"
            )
        );
        $params = [];
        $doc = new Doc();
        if ($this->description !== null) {
            $doc->addItem($this->description);
        }
        foreach ($this->parameters as $param) {
            $params[] = $param->asDeclaration($remap);
            $doc->addItem($param->asPHPDocParam());
        }
        // TODO order parameters with required first
        $doc->addItem($this->returnType->asPHPDocReturn());
        foreach ($this->throws as $throw) {
            $doc->addItem($throw->asPHPDocThrows());
        }

        if ($this->flags & self::API) {
            $doc->addItem("@api");
        }

        $body = $this->body;
        if ($body !== null) {
            foreach ($remap as $type => $alt) {
                $type = new Type($type);
                $newBody = preg_replace(
                    "/(\W)(" . $type->as(Type::SHORT) . ")(\W)/m",
                    "$1$alt$3",
                    $body
                );
                if ($newBody !== $body) {
                    $body = $newBody;
                    error_log(
                        "Dangerously remapping `" .
                        $type->as(Type::SHORT) .
                        "` as `$alt` in {$this->name}()"
                    );
                }
            }
        }

        return $doc->asString(1) .
          ($this->flags & self::ABSTRACT ? "abstract " : "") .
          "{$this->access->value} " .
          ($this->flags & self::STATIC ? "static " : "") .
          "function $this->name(" .
          join(", ", $params) .
          "):" .
          ($remap[$this->returnType->getType()->as(Type::FQN)] ??
            $this->returnType->getType()->as(Type::SHORT | Type::PHP)) .
          PHP_EOL .
          (($this->flags & self::ABSTRACT) == false
            ? "{" . PHP_EOL . $body . PHP_EOL . "}"
            : ";") .
          PHP_EOL;
    }
}
