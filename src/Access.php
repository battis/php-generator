<?php

namespace Battis\PHPGenerator;

enum Access: string
{
    case Public = 'public';
    case Protected = 'protected';
    case Private = 'private';
}
