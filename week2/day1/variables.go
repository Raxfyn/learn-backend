package main

import "fmt"

func main(){
	var name string = "Raxfyn"
	age :=30
	var score float64 =95.5
	isLearning := true

	fmt.Println("姓名：",name)
	fmt.Println("年龄：",age)
	fmt.Println("分数：",score)
	fmt.Println("正在学习：",isLearning)
	fmt.Println("姓名=%s，年龄=%d，分数=%.1f\n",name,age,score)
	
}
