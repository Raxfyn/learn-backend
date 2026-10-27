package main

import "fmt"

//定义结构体类型 User
type User struct {
	Name string
	Age int
	Email string
}

//方法：接收者是值类型 u User
func (u User) Introduce() string {
	return fmt.Sprintf("我是%s, 今年%d岁, 邮箱%s", u.Name, u.Age, u.Email)
}

//方法：接收者是指针类型 u *User，可以修改原对象
func (u *User) SetAge(age int) {
	u.Age = age
}

func main() {
	//字面量创建
	u := User{
			Name:	"Raxfyn",
			Age:	30,
			Email:	"raxfyn@example.com",
	}
	fmt.Println(u.Name)
	fmt.Println(u.Introduce())

	//通过指针方法修改
	u.SetAge(31)
	fmt.Println(u.Introduce())
	
	//零值结构体：没赋值的字段是各自类型的零值
	var u2 User
	u2.Name = "张三"
	fmt.Println(u2.Name, u2.Age, u2.Email)	
}