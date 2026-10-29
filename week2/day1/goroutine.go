package main

import (
	"fmt"
	"sync"
)

func task(name string,wg *sync.WaitGroup) {
	defer wg.Done() //函数结束时通知WaitGroup：这个任务做完了
	for i := 1; i <= 3; i ++{
		fmt.Println(name, "第", i, "步")
	}
}

func main() {
	var wg sync.WaitGroup

	wg.Add(2) //告诉WaitGroup：要等2个任务

	go task("A", &wg) //加go关键字，函数在独立的goroutine里运行
	go task("B", &wg)

	wg.Wait() //阻塞，直到计数器归零
	fmt.Println("全部完成")
}