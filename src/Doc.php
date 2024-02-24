<?php

namespace Battis\PHPGenerator;

class Doc
{
    /** @var string[] $items */
    private array $items = [];

    public function addItem(string $item): void
    {
        $this->items[] = $this->prepareItem($item);
    }

    protected function prepareItem(string $item): string
    {
        // convert any newlines to HTML line breaks
        $item = preg_replace("/[\r\n]/m", "\n\n", $item);

        // remove multiple newlines
        $item = preg_replace("/[\n\r]+/", "\n", $item);

        // remove escapes introduced by conversion to markdown

        // strip trailing whitespace
        $item = preg_replace("/(.*)[ \t\f]+$/m", "$1", $item);
        return $item;
    }

    public function asString(int $level = 1, int $width = 78): string
    {
        if (empty($this->items)) {
            return "";
        }
        $prevDirective = null;
        $indent = "";
        for($i = 0; $i < $level; $i++) {
            $indent .= str_repeat(" ", 4);
        }
        $phpdoc = $indent . "/**" . PHP_EOL;
        foreach($this->items as $_item) {
            $directive = false;
            if (preg_match("/(@\w+)/i", $_item, $match)) {
                $directive = $match[1] ?? false;
            }
            $items = array_filter(
                explode("\n", $_item),
                // remove empty lines
                fn(string $i) => !empty(trim($i))
            );
            foreach($items as $item) {
                $item = "$indent * $item";
                if ($prevDirective !== null && ($directive !== $prevDirective) | $directive === false) {
                    $phpdoc .= "$indent *" . PHP_EOL;
                }
                $directiveIndent = $directive !== false ? str_repeat(" ", 2) : "";
                $wrapped = false;
                $w = $width - strlen("$indent * " . ($wrapped ? $directiveIndent : ""));
                while (strlen($item) > $width) {
                    $regex = "/^(" . $indent . " \* " . ($wrapped ? $directiveIndent : "") . "(($directive \S{" . ($w - strlen($directive)) . ",})|(.{1,$w})))(\s([\s\S]*))?$/m";
                    preg_match($regex, $item, $match);
                    // TODO tidy up this logic
                    if (array_key_exists(1, $match)) {
                        $phpdoc .= $match[1] . PHP_EOL;
                    } else {
                        $phpdoc .= $item;
                        $item = "";
                    }
                    if (array_key_exists(6, $match)) {
                        $item =  "$indent * " . $directiveIndent . $match[6];
                    } else {
                        $item = "";
                    }
                    $wrapped = true;
                }
                $phpdoc .=  $item . PHP_EOL;
                $prevDirective = $directive;
            }
        }
        $phpdoc .= "$indent */" . PHP_EOL;

        return $phpdoc;
    }
}
