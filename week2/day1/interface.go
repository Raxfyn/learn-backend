package main

import "fmt"

//接口：定义“会做什么”，不定义“是什么”
type Speaker interface{
	Speak() string
}

type Dog struct {
	Name string
}

type Cat struct {
	Name string
}

func (d  Dog) Speak() string {
	return d.Name + ": 汪汪"
}

func (c Cat) Speak() string {
	return c.Name + ": 喵喵"
}

//参数类型是接口，任何实现了Speak（）的类型都能传进来
func say(s Speaker) {
	fmt.Println(s.Speak())
}

func main() {
	d := Dog{Name: "旺财"}
	c := Cat{Name: "咪咪"}

	say(d)
	say(c)

// 接口切片：里面可以放任何实现了Speaker的类型
	speakers := []Speaker{d, c}
	for _,s := range speakers{
			fmt.Println(s.Speak())
	}
}