package main

import(
	"errors"
	"fmt"
)

//普通函数：接收两个int，返回一个int
func add(a, b int) int{
	return a + b
}

//多返回值函数：返回“结果”和“错误”
func divide(a,b float64) (float64,error){
	if b == 0 {
		return 0,errors.New("除数不能为0")
	}
	return a / b ,nil
}

func main() {
	sum := add(3, 5)
	fmt.Println("3 + 5=", sum)

	//正常情况
	result,err := divide(10, 2)
	if err !=nil{
		fmt.Println("出错了:", err)
	} else {
		fmt.Println("10 / 2 =",result)
	}

	// 异常情况
	result, err = divide(10, 0)
	if err != nil {
		fmt.Println("出错了：",err)
	} else {
		fmt.Println("10 / 0",result)
	}
}

