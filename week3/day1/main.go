package main

import (
	"log"

	"apidemo/handler"
	"apidemo/repository"
	"apidemo/service"

	"github.com/gin-gonic/gin"
	"gorm.io/driver/mysql"
	"gorm.io/gorm"
)

func main() {
	//1. 连接数据库
	dsn := "root:myzx@tcp(127.0.0.1:3306)/learn?charset=utf8mb4&parseTime=True&loc=Local"
	db, err := gorm.Open(mysql.Open(dsn), &gorm.Config{})
	if err != nil {
		log.Fatal("连接数据库失败：", err)
	}
	log.Println("数据库连接成功")

	//2. 创建各层实例（依赖注入）
	userRepo := repository.NewUserRepository(db)
	userSvc := service.NewUserService(userRepo)
	useHandler := handler.NewUserHandler(userSvc)

	//3.注册路由
	r := gin.Default()
	r.POST("/api/users", useHandler.CreateUser)
	r.GET("/api/users", useHandler.ListUsers)

	//4.启动服务
	r.Run(":8080")
}
