package main

import "fmt"

type Speaker interface{
	Speak() string
}

type Dog struct { Name string }
type Cat struct { Name string }

func (d Dog) Speak() string { return d.Name + ": 汪汪" }
func (c Cat) Speak() string { return c.Name + ": 喵喵" }

//类型断言：安全写法带 ok
func check(s Speaker) {
	if d, ok := s.(Dog); ok {
			fmt.Println("这是狗🐶，名字是", d.Name)
	} else {
			fmt.Println("这不是狗🐶")
	}
}

//type switch：按具体类型分支处理
func describe(s Speaker) {
	switch v := s.(type) {
	case Dog:
			fmt.Println("Dog, 名字:",v.Name)
	case Cat:
			fmt.Println("Cat, 名字:",v.Name)
	default:
			fmt.Println("未知类型")
	}
}

func main() {
	var s Speaker = Dog{Name: "旺财"}

	//方式一：带ok的安全断言
	d, ok := s.(Dog)
	fmt.Println("断言为 Dog:", d, "ok =",ok)

	//断言成错误类型不会panic，ok为false，d是零值
	c, ok := s.(Cat)
	fmt.Println("断言为 Cat:", c, "ok =", ok)

	//方式二：直接断言，类型不符会panic（不推荐）
	//d2 := s.(Cat) //取消注释会 panic

	check(s)
	check(Cat{Name: "咪咪"})
	describe(s)
	describe(Cat{Name: "咪咪"})
}