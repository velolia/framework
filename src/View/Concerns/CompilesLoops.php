<?php

declare(strict_types=1);

namespace Velolia\View\Concerns;

trait CompilesLoops
{
    protected function compileLoops(string $value): string
    {
        $value = $this->compileForeach($value);
        $value = $this->compileEndforeach($value);
        $value = $this->compileForelse($value);
        $value = $this->compileEndforelse($value);
        return $value;
    }

    protected function compileForeach(string $value): string
    {
        static $foreachCounter = 0;
        
        return preg_replace_callback('/@foreach\s*\((.+?)\)/', function ($matches) use (&$foreachCounter) {
            $fullMatch = $matches[0];
            $startPos = strpos($fullMatch, '(');
            $expression = $this->extractBalancedExpression(substr($fullMatch, $startPos));
            
            preg_match('/(.+?)\s+as\s+(.+)/', $expression, $parts);
            
            if (count($parts) === 3) {
                $i = $foreachCounter++;
                $idx = "__loopIdx{$i}";
                $arr = "__loopArr{$i}";
                $count = "__loopCount{$i}";
                
                $arrayExpr = trim($parts[1]);
                $iteratorVars = trim($parts[2]);
                
                return "<?php \${$idx} = 0; \${$arr} = {$arrayExpr}; "
                    . "if (!is_array(\${$arr}) && !(\${$arr} instanceof \\Traversable)) { "
                    . "  \${$arr} = []; "
                    . "} "
                    . "\${$count} = is_countable(\${$arr}) ? count(\${$arr}) : 0; "
                    . "foreach(\${$arr} as {$iteratorVars}): "
                    . "\$loop = (object)[ "
                        . "'index' => \${$idx}, "
                        . "'iteration' => \${$idx} + 1, "
                        . "'remaining' => \${$count} - \${$idx} - 1, "
                        . "'count' => \${$count}, "
                        . "'first' => \${$idx} === 0, "
                        . "'last' => \${$idx} === \${$count} - 1, "
                        . "'odd' => (\${$idx} + 1) % 2 !== 0, "
                        . "'even' => (\${$idx} + 1) % 2 === 0 "
                    . "]; \${$idx}++; ?>";
            }
            
            return "<?php foreach({$expression}): ?>";
        }, $value);
    }

    protected function compileEndforeach(string $value): string
    {
        return preg_replace("/@endforeach/", "<?php endforeach; ?>", $value);
    }

    protected function compileForelse(string $value): string
    {
        static $forelseCounter = 0;
        
        return preg_replace_callback('/@forelse\s*\((.+?)\)/', function ($matches) use (&$forelseCounter) {
            $fullMatch = $matches[0];
            $startPos = strpos($fullMatch, '(');
            $expression = $this->extractBalancedExpression(substr($fullMatch, $startPos));
            
            preg_match('/(.+?)\s+as\s+(.+)/', $expression, $parts);
            
            if (count($parts) === 3) {
                $i = $forelseCounter++;
                $idx = "__loopIdx{$i}";
                $arr = "__loopArr{$i}";
                $count = "__loopCount{$i}";
                $empty = "__loopEmpty{$i}";
                
                $arrayExpr = trim($parts[1]);
                $iteratorVars = trim($parts[2]);
                
                return "<?php \${$arr} = {$arrayExpr}; "
                    . "if (!is_array(\${$arr}) && !(\${$arr} instanceof \\Traversable)) { "
                    . "  \${$arr} = []; "
                    . "} "
                    . "\${$count} = is_countable(\${$arr}) ? count(\${$arr}) : iterator_count(\${$arr}); "
                    . "\${$empty} = \${$count} === 0; "
                    . "if (!\${$empty}): "
                    . "\${$idx} = 0; "
                    . "foreach(\${$arr} as {$iteratorVars}): "
                    . "\$loop = (object)[ "
                        . "'index' => \${$idx}, "
                        . "'iteration' => \${$idx} + 1, "
                        . "'remaining' => \${$count} - \${$idx} - 1, "
                        . "'count' => \${$count}, "
                        . "'first' => \${$idx} === 0, "
                        . "'last' => \${$idx} === \${$count} - 1, "
                        . "'odd' => (\${$idx} + 1) % 2 !== 0, "
                        . "'even' => (\${$idx} + 1) % 2 === 0 "
                    . "]; \${$idx}++; ?>";
            }
            
            return "<?php foreach({$expression}): ?>";
        }, $value);
    }

    protected function compileEndforelse(string $value): string
    {
        $value = preg_replace("/@empty/", "<?php endforeach; ?><?php else: ?>", $value);
        return preg_replace("/@endforelse/", "<?php endif; ?>", $value);
    }

    protected function extractBalancedExpression(string $str): string
    {
        $depth = 0;
        $start = false;
        $result = '';
        
        for ($i = 0; $i < strlen($str); $i++) {
            $char = $str[$i];
            
            if ($char === '(') {
                $depth++;
                $start = true;
            }
            
            if ($start) {
                $result .= $char;
            }
            
            if ($char === ')') {
                $depth--;
                if ($depth === 0) {
                    break;
                }
            }
        }
        
        return substr($result, 1, -1);
    }
}