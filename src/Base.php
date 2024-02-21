<?php

namespace Battis\PHPGenerator;

abstract class Base {
    protected const TYPE_SHORT = 0; 
    protected const TYPE_FQN = 1;
    protected const TYPE_ABSOLUTE = 3;
    
    protected function typeAs(string $type, int $flags = self::TYPE_FQN): string
    {
        if ($flags & self::TYPE_FQN) {
            $t = preg_replace("/(.+)\\[\]$/", "$1", $type);
            if (($flags & self::TYPE_ABSOLUTE) && !in_array($t, ['void', 'null','bool','int','float','string','array','object','callable','resource'])) {
                $type = "\\" . $type;
            }
        } else {
            $type = preg_replace("/^.+\\\\([^\\\\]+)$/", "$1", $type);
        }
        return $type;
    }

}
