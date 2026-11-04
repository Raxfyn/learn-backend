<?php

//1.索引数组（像Go的slice）
$fruits = ["apple", "banana", "cherry"];
echo "水果列表";
print_r($fruits);  //print_r是PHP里打印复杂数据的利器

//2.关联数组（像Go的map）
$user = [
    "id" => 1,
    "name" => "admin",
    "role" => "user",
];
echo "\n用户信息: " . $user["name"] . "\n";

//3.遍历
echo "\n水果遍历:\n";
foreach ($fruits as $fruit) {
    echo "- $fruit\n";
}

echo "\n用户遍历:\n";
foreach ($user as $key => $value) {
    echo "$key: $value\n";
}

//4. 动态添加
$fruits[] = "durian"; //自动追加末尾
echo "\n追加后: " . end($fruits) . "\n";
