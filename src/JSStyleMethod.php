<?php

namespace Battis\PHPGenerator;

class JSStyleMethod extends Method
{
    /**
     * @param array<string, string> $remap
     *
     * @return string
     */
    public function asImplementation(array $remap = []): string
    {
        $doc = new Doc();
        if ($this->description !== null) {
            $doc->addItem($this->description);
        }
        $optional = true;
        $parameters = [];
        $params = "";

        $body = $this->body ?? "";
        foreach($remap as $type => $alt) {
            $newBody = preg_replace("/(\W)(" . (new Type($type))->as(Type::SHORT) . ")(\W)/m", "$1$alt$3", $body);
            if ($newBody !== $body) {
                $body = $newBody;
                error_log("Dangerously remapping `" . (new Type($type))->as(Type::SHORT) . "` as `$alt` in {$this->name}()");
            }
        }

        if (!empty($this->parameters)) {
            $parametersDoc = [];
            $requestBody = null;
            $params = [];
            foreach($this->parameters as $parameter) {
                if ($parameter->getName() === 'requestBody') {
                    $requestBody = $parameter;
                } else {
                    $parameters[] = $parameter->getName() . ($parameter->isOptional() ? "?" : "") . ": " . $parameter->getType()->as(Type::ABSOLUTE);
                    $parametersDoc[] = $parameter->getName() . ": " . ($parameter->getDescription() ?? $parameter->getType()->as(Type::ABSOLUTE));
                    $optional = $optional && $parameter->isOptional();
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

        }
        $doc->addItem($this->returnType->asPHPDocReturn());
        foreach($this->throws as $throw) {
            $doc->addItem($throw->asPHPDocThrows());
        }
        return $doc->asString(1) .
            "{$this->access->value} " . ($this->flags & Method::STATIC ? "static " : "") . "function $this->name($params): " . ($remap[$this->returnType->getType()->as(Type::FQN)] ?? $this->returnType->getType()->as(Type::SHORT | Type::PHP)) . PHP_EOL .
            (($this->flags & self::ABSTRACT) == false
            ? "{" . PHP_EOL . $body . PHP_EOL . "}"
            : ";")  . PHP_EOL;
    }
}
