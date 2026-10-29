package main

import "fmt"

//chan<- int 表示：这个函数只能往通道里发
func producer(ch chan<- int) {
	for i := 1;i <= 5; i++{
		ch <- i //发送：把i放进通道
	}
	close(ch) //关闭通道，告诉接收方“没有更多数据了”
}

func main() {
	ch := make(chan int) //无缓冲通道

	go producer(ch)

	//接收：通道关闭后，range 循环会自动结束
	for v := range ch {
			fmt.Println("收到：", v)
	}
	fmt.Println("通道已关闭")
}