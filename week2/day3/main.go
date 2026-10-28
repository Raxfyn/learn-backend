/*
package main

import (
	"encoding/json"
	"fmt"
	"log"
	"net/http"
)

type User struct {
	ID		int		`json:"id"`
	Name	string	`json:"name"`
}

func main() {
	//路由1:根路径
	http.HandleFunc("/", func(w http.ResponseWriter, r *http.Request) {
		fmt.Fprintln(w, "Hello, HTTP!:")
	})

	//路由2:返回 JSON 列表
	http.HandleFunc("/api/users", func(w http.ResponseWriter, r *http.Request){
			if r.Method != http.MethodGet {
					http.Error(w, "method not allowed", http.StatusMethodNotAllowed)
					return
			}
			users := []User{
					{ID: 1, Name: "张三"},
					{ID: 2, Name: "李四"},
			}
			w.Header().Set("Content-Type", "application/json")
			json.NewEncoder(w).Encode(users)
	})
	addr := ":8080"
	log.Println("服务器启动: http://localhost" + addr)
	log.Fatal(http.ListenAndServe(addr, nil))
}
*/

//Gin 重写
package main

import (
	"net/http"

	"github.com/gin-gonic/gin"
)

type User struct {
	ID		int		`json:"id"`
	Name	string	`json:"name"`
}

//用切片模拟数据库
var users = []User{
	{ID: 1, Name: "张三"},
	{ID: 2, Name: "李四"},
}

func main() {
	r := gin.Default() //自带日志和panic恢复中间件

	r.GET("/", func(c *gin.Context) {
			c.String(http.StatusOK, "Hello, Gin!")
	})

	r.GET("/api/users", func(c *gin.Context) {
			c.JSON(http.StatusOK, users) //结构体编译为JSON
	})

	r.POST("/api/users", func(c *gin.Context) {
			var u User
			if err := c.ShouldBindJSON(&u); err != nil {
				c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
				return
			}
			users = append(users, u)
			c.JSON(http.StatusCreated, u)
	})

	r.Run(":8080")
}