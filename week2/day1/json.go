package main

import (
	"encoding/json"
	"fmt"
)

type User struct {
	ID			int			`json:"id"`
	Name		string		`json:"name"`
	Email		string		`json:"email"`
	Password 	string 		`json:"-"`				//“-”表示不参与JSON转换
	Age			int			`json:"age,omitempty"`	//零值时省略这个字段
}

func main() {
	u := User{ID: 1, Name: "张三", Email: "zhangsan@example.com", Password:"123456"}

	//结构体 -> JSON 字符串（序列化）
	data, err :=json.Marshal(u)
	if err != nil {
			fmt.Println("序列化失败：",err)
			return
	}
	fmt.Println("序列化结果:", string(data))

	//格式化输出，方便阅读
	pretty,_ :=json.MarshalIndent(u, "", "  ")
	fmt.Println("格式化输出:\n" + string(pretty))

	//JSON 字符串 -> 结构体（反序列化）
	var u2 User
	jsonStr := `{"id":2,"name":"李四","email":"lisi@example.com","age":25}`
	if err := json.Unmarshal([]byte(jsonStr), &u2); err!= nil {
		fmt.Println("反序列化失败：", err)
		return
	}
	fmt.Printf("反序列化结果：%+v\n",u2)
}