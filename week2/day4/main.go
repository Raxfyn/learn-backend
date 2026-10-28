package main

import (
	"fmt"
	"log"

	"gorm.io/driver/mysql"
	"gorm.io/gorm"
)

type User struct {
	ID		uint		`gorm:"primaryKey"`
	Name	string		`gorm:"size:50;not null"`
}

func main() {
	dsn := "root:myzx@tcp(127.0.0.1:3306)/learn?charset=utf8mb4&parseTime=True&loc=Local"
	db, err := gorm.Open(mysql.Open(dsn), &gorm.Config{})
	if err != nil {
			log.Fatal("连接数据库失败：", err)
	}
	fmt.Println("连接成功")

	var users []User
	//if err := db.Find(&users).Error; err !=nil {
	if err := db.Debug().Select("id","name").Find(&users).Error; err!= nil {  //输出SQL在后面
	log.Fatal("查询失败: ", err)
	}
	for _, u := range users {
			fmt.Printf("id=%d name=%s\n", u.ID, u.Name)
	}
	
	newUser := User{Name: "zhaoliu"}
	if err := db.Debug().Create(&newUser).Error; err != nil {
		log.Fatal("插入失败:", err)
	}
	fmt.Printf("插入成功, 数据库分配的 id=%d\n", newUser.ID)
	/*
	var user1 User
	db.Debug().First(&user1)
	fmt.Printf("错误的写法查到: id =%d name=%s\n", user1.ID, user1.Name)

	var user2 User
	result := db.Where("id = ?", 3)
	if err := result.Debug().First(&user2).Error; err != nil{
		log.Println("查询失败:", err)
	}
	fmt.Printf("正确的写法查到: id=%d name=%s\n", user2.ID, user2.Name)
	*/
}