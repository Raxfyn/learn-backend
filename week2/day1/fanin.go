package main

import (
	"fmt"
	"sync"
	"time"
)

type Result struct {
	Name string
	Score int
}

func fetch(name string,results chan<- Result, wg *sync.WaitGroup) {
	defer wg.Done()
	time.Sleep(100 * time.Millisecond) //模拟一次耗时查询
	results <- Result{Name: name, Score: len(name) *10}
}

func main() {
	names := []string{"张三", "李四", "王五", "赵六"}
	results := make(chan Result, len(names)) //带缓冲：容量4

	var wg sync.WaitGroup
	for _, n := range names {
		wg.Add(1)
		go fetch(n, results, &wg)
	}
	
	wg.Wait() //等所有goroutine结束
	close(results) //再关闭通道

	total := 0
	for r := range results {
		fmt.Println(r.Name,r.Score)
		total += r.Score
	}
	fmt.Println("总分", total)
}