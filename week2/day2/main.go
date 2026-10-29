package main

import (
	"fmt"

	"hello/greeting"
)

func main() {
	fmt.Println(greeting.Hello("Raxfyn"))
	//fmt.Println(greeting.secret())  //取消注释会编译报错：secret 不可访问
}