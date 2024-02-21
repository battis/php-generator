<?php

namespace Battis\PHPGenerator;

use Battis\PHPGenerator\Method\Parameter;
use Battis\PHPGenerator\Method\ReturnType;

class Method extends Base
{
    /**
     * @var 'public'|'protected'|'private' $access
     */
    protected string $access = "public";

    protected bool $static = false;

    protected ?string $description = null;

    protected string $name = "";

    /**
     * @var Parameter[] $parameters;
     */
    protected array $parameters = [];

    /** @var string $body */
    protected string $body = "";

    protected ReturnType $returnType;

    public function __construct() {
        $this->returnType = ReturnType::from('void');
    }

    /**
     * @var ReturnType[] $throws
     */
    protected array $throws = [];

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

    /**
     * @param string $name
     * @param ReturnType $returnType
     * @param string $body
     * @param ?string $description (Optional)
     * @param Parameter[] $parameters (Optional)
     * @param ReturnType[] $throws (Optional)
     */
    public static function private(
        string $name, 
        ReturnType $returnType, 
        string $body, 
        ?string $description =  null, 
        array $parameters = [], 
        array $throws = []
    ): Method {
        $method = new Method();
        $method->name = $name;
        $method->access = 'private';
        $method->returnType = $returnType;
        $method->body = $body;
        $method->description = $description;
        $method->parameters = $parameters;
        $method->throws = $throws;
        return $method;
    }

    /**
     * @param string $name
     * @param ReturnType $returnType
     * @param string $body
     * @param ?string $description (Optional)
     * @param Parameter[] $parameters (Optional)
     * @param ReturnType[] $throws (Optional)
     */
    public static function privateStatic(string $name, ReturnType $returnType, string $body, ?string $description = null, array $parameters = [], array $throws = []): Method
    {
        $method = self::private($name, $returnType, $body, $description, $parameters, $throws);
        $method->static = true;
        return $method;
    }

    /**
     * @param string $name
     * @param ReturnType $returnType
     * @param string $body
     * @param ?string $description (Optional)
     * @param Parameter[] $parameters (Optional)
     * @param ReturnType[] $throws (Optional)
     */
    public static function protected(string $name, ReturnType $returnType, string $body, ?string $description = null, array $parameters = [], array $throws = []): Method
    {
        $method = self::private($name, $returnType, $body, $description, $parameters, $throws);
        $method->access="protected";
        return $method;
    }
    
    /**
     * @param string $name
     * @param ReturnType $returnType
     * @param string $body
     * @param ?string $description (Optional)
     * @param Parameter[] $parameters (Optional)
     * @param ReturnType[] $throws (Optional)
     */
    public static function protectedStatic(string $name, ReturnType $returnType, string $body, ?string $description = null, array $parameters = [], array $throws = []): Method
    {
        $method = self::protected($name, $returnType, $body, $description, $parameters, $throws);
        $method->static = true;
        return $method;
    }

    /**
     * @param string $name
     * @param ReturnType $returnType
     * @param string $body
     * @param ?string $description (Optional)
     * @param Parameter[] $parameters (Optional)
     * @param ReturnType[] $throws (Optional)
     */
    public static function public(string $name, ReturnType $returnType, string $body, ?string $description = null, array $parameters = [], array $throws = []): Method
    {
        $method = self::private($name, $returnType, $body, $description, $parameters, $throws);
        $method->access="public";
        return $method;
    }

    /**
     * @param string $name
     * @param ReturnType $returnType
     * @param string $body
     * @param ?string $description (Optional)
     * @param Parameter[] $parameters (Optional)
     * @param ReturnType[] $throws (Optional)
     */
    public static function publicStatic(string $name, ReturnType $returnType, string $body, ?string $description = null, array $parameters = [], array $throws = []): Method
    {
        $method = self::public($name, $returnType, $body, $description, $parameters, $throws);
        $method->static = true;
        return $method;
    }

    /**
     * @param array<string,string> $remap
     *
     * @return string
     */
    public function asImplementation(array $remap = []): string
    {
        $params = [];
        $doc = new Doc();
        if ($this->description !== null) {
            $doc->addItem($this->description);
        }
        foreach($this->parameters as $param) {
            $params[] = $param->asDeclaration($remap);
            $doc->addItem($param->asPHPDocParam());
        }
        // TODO order parameters with required first
        $doc->addItem($this->returnType->asPHPDocReturn());
        foreach($this->throws as $throw) {
            $doc->addItem($throw->asPHPDocThrows());
        }
        $doc->addItem("@api");

        $body = $this->body;
        foreach($remap as $type => $alt) {
            $newBody = preg_replace("/(\W)(" . $this->typeAs($type, self::TYPE_SHORT) . ")(\W)/m", "$1$alt$3", $body);
            if ($newBody !== $body) {
                $body = $newBody;
                error_log("Dangerously remapping `" . $this->typeAs($type, self::TYPE_SHORT) . "` as `$alt` in {$this->name}()");
            }
        }

        return $doc->asString(1) .
            "$this->access " . ($this->static ? "static " : "") . "function $this->name(" . join(", ", $params) . "):" . ($remap[$this->returnType->getType()] ?? $this->typeAs($this->returnType->getType(), self::TYPE_SHORT)) . PHP_EOL .
            "{" . PHP_EOL .
            $body . PHP_EOL .
        "}" . PHP_EOL;
    }

    /**
     * @param array<string, string> $remap
     *
     * @return string
     */
    public function asJavascriptStyleImplementation(array $remap = []): string
    {
        $doc = new Doc();
        if ($this->description !== null) {
            $doc->addItem($this->description);
        }
        $optional = true;
        $parameters = [];
        $params = "";

        $body = $this->body;
        foreach($remap as $type => $alt) {
            $newBody = preg_replace("/(\W)(" . $this->typeAs($type, self::TYPE_SHORT) . ")(\W)/m", "$1$alt$3", $body);
            if ($newBody !== $body) {
                $body = $newBody;
                error_log("Dangerously remapping `" . $this->typeAs($type, self::TYPE_SHORT) . "` as `$alt` in {$this->name}()");
            }
        }

        if (!empty($this->parameters)) {
            $parametersDoc = [];
            $requestBody = null;
            $params = [];
            $paramNames = [];
            foreach($this->parameters as $parameter) {
                if ($parameter->getName() === 'requestBody') {
                    $requestBody = $parameter;
                } else {
                    $parameters[] = $parameter->getName() . ($parameter->isOptional() ? "?" : "") . ": " . $parameter->getType();
                    $parametersDoc[] = $parameter->getName() . ": " . ($parameter->getDescription() ?? $parameter->getType());
                    $optional = $optional && $parameter->isOptional();
                    $paramNames[] = $parameter->getName();
                }
            }
            if (!empty($parameters)) {
                $doc->addItem("@param array{" . join(", ", $parameters) . "} \$params An associative array" . PHP_EOL . "    - " . join(PHP_EOL . "    - ", $parametersDoc));
                $params[] = "array \$params" . ($optional ? " = []" : "");
            }
            if ($requestBody !== null) {
                $doc->addItem($requestBody->asPHPDocParam());
                $params[] = $requestBody->asDeclaration($remap);
            }
            $params = empty($params) ? "" : join(", ", $params);

            usort($paramNames, fn(string $a, string $b) => strlen($b) - strlen($a));
            foreach($paramNames as $p) {
                $body = str_replace("\$$p", "\$params[\"$p\"]", $body);
            }

        }
        $doc->addItem($this->returnType->asPHPDocReturn());
        foreach($this->throws as $throw) {
            $doc->addItem($throw->asPHPDocThrows());
        }
        $doc->addItem('@api');
        return $doc->asString(1) .
            "$this->access " . ($this->static ? "static " : "") . "function $this->name($params): " . ($remap[$this->returnType->getType()] ?? $this->typeAs($this->returnType->getType(), self::TYPE_SHORT)) . PHP_EOL .
            "{" . PHP_EOL .
            $body . PHP_EOL .
        "}" . PHP_EOL;
    }
}
