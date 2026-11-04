<?php

$config = "Production"; //全局变量

function showConfig()
{
    /*
    //尝试直接读全局变量
    echo "Config is: " . $config . "\n";
*/
    //global声明使用全局变量
    global $config;
    echo "Config is: " . $config . "\n";
}

showConfig();
