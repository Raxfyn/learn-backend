<?php

class User
{
    public $id;
    public $name;

    //构造函数（PHP 8+ 新写法，在属性定义时自动赋值）
    public function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    //方法
    public function greet(): string
    {
        return "Hello, I am {$this->name} (ID: {$this->id})";
    }
}

// 实例化
$admin = new User(1, "Admin");
echo $admin->greet() . "\n";

//PHP的动态特性：给对象临时加个属性
$admin->login_time = time();
echo "Login time: " . date("Y-m-d H:i:s", $admin->login_time) . "\n";

//注意：如果访问不存在的属性，PHP不会报错，而是返回null（或触发Notice）
echo "Email: " . ($admin->email ?? "No email") . "\n";
