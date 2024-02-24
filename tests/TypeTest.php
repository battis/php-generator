<?php

namespace Battis\PHPGenerator\Tests;

use Battis\PHPGenerator\Type;
use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{
    public function testAs()
    {
        $values = [
            "void" => ["void", Type::FQN],
            "string" => ["string", Type::ABSOLUTE],
            "int" => ["int", Type::SHORT],
            "float" => ["float", Type::PHP],

            "bool[]" => ["bool[]", Type::FQN],
            "float[]" => ["float[]", Type::ABSOLUTE],
            "object[]" => ["object[]", Type::SHORT],

            "A\\B\\C" => ["A\\B\\C", Type::FQN],
            "\\D\\E\\F" => ["D\\E\\F", Type::ABSOLUTE],
            "I" => ["G\\H\\I", Type::SHORT],

            "J\\K\\L" => ["\\J\\K\\L", Type::FQN],
            "M\\N\\O" => ["M\\N\\O\\", Type::FQN],
            "P\\Q\\R" => ["\\P\\Q\\R\\", Type::FQN],

            "array" => ["bool[]", Type::FQN | Type::PHP],
            "array" => ["float[]", Type::ABSOLUTE | Type::PHP],
            "array" => ["object[]", Type::SHORT | Type::PHP],

            "array" => ["A\\B\\C[]", Type::PHP],
            "array" => ["D\\E\\F[]", Type::FQN | Type::PHP],
            "array" => ["G\\H\\I[]", Type::ABSOLUTE | Type::PHP],
            "array" => ["J\\K\\L[]", Type::SHORT | Type::PHP],

            "array<string, int>" => ["array<string, int>", Type::FQN],
            "array<string, int>" => ["array<string, int>", Type::ABSOLUTE],
            "array<string, int>" => ["array<string, int>", Type::SHORT],
            "array" => ["array<string, int>", Type::PHP],

            "array{a: int, b: bool}" => ["array{a: int, b: bool}", Type::FQN],
            "array{a: int, b: bool}" => ["array{a: int, b: bool}", Type::ABSOLUTE],
            "array{a: int, b: bool}" => ["array{a: int, b: bool}", Type::SHORT],
            "array" => ["array{a: int, b: bool}", Type::PHP],

            "array<string, \\A\\B\\C>" => ["array<string, \\A\\B\\C>", Type::FQN],
            "array<string, \\A\\B\\C>" => [
            "array<string, \\A\\B\\C>",
            Type::ABSOLUTE,
            ],
            "array<string, \\A\\B\\C>" => ["array<string, \\A\\B\\C>", Type::SHORT],
            "array" => ["array<string, \\A\\B\\C>", Type::PHP],

            "'a'|'b'" => ["'a'|'b'", Type::FQN],
            "'a'|'b'" => ["'a'|'b'", Type::ABSOLUTE],
            "'a'|'b'" => ["'a'|'b'", Type::SHORT],
            "string" => ["'a'|'b'", Type::PHP],

            "int|string" => ["int|string", Type::FQN],
            "int|string" => ["int|string", Type::ABSOLUTE],
            "int|string" => ["int|string", Type::SHORT],
            "mixed" => ["int|string", Type::PHP],

            "\\A\\B|\\C\\D" => ["\\A\\B|\\C\\D", Type::FQN],
            "\\E\\F|\\G\\H" => ["\\E\\F|\\G\\H", Type::ABSOLUTE],
            "\\I\\J|\\K\\L" => ["\\I\\J|\\K\\L", Type::SHORT],
            "mixed" => ["\\M\\N|\\O\\P", Type::PHP],

            "array{a: \\A\\B, b: \\D\|E}" => [
            "array{a: \\A\\B, b: \\D\|E}",
            Type::FQN,
            ],
            "array{a: \\A\\B, b: \\D\|E}" => [
            "array{a: \\A\\B, b: \\D\|E}",
            Type::ABSOLUTE,
            ],
            "array{a: \\A\\B, b: \\D\|E}" => [
            "array{a: \\A\\B, b: \\D\|E}",
            Type::SHORT,
            ],
            "array" => ["array{a: \\A\\B, b: \\D\|E}", Type::PHP],
        ];
        foreach ($values as $expected => $arg) {
            $type = new Type($arg[0]);
            $this->assertEquals($expected, $type->as($arg[1]));
        }
    }
}
