package main

import (
	"fmt"
	"os"
)

func readFile(path string) error {
	f, err := os.Open(path)
	if err != nil {
		return err
	}
	//无论后面怎么返回，函数结束前都会执行这行
	defer f.Close()

	buf := make([]byte, 100)
	n,err := f.Read(buf)
	if err != nil {
		return err
	}
	fmt.Println("读取内容(前100字节):")
	fmt.Println(string(buf[:n]))
	return nil
}

func order() {
	for i := 1; i<=3; i++{
			defer fmt.Println("defer 执行:", i)
	}
	fmt.Println("oeder 函数体结束")
}

func deferTrap() {
	x := 1
	defer fmt.Println("defer 里的x =", x)
	x=100
	fmt.Println("函数里的 x =",x)
}

func main() {
	err := readFile("defer.go")
	fmt.Println("readFile 返回:", err)

	order()
	fmt.Println("main 结束")
	deferTrap()
}