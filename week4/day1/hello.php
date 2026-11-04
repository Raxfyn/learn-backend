<?php

$name = "World";
echo "Hello, $name!\n";

//类型声明(PHP 8+ 推荐写法)
function add(int $a, int $b): int
{
    return $a + $b;
}

echo "3 + 5 = " . add(3, 5) . "\n";
