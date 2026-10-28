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
/*
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
*/

package main

import (
	"log"
	"net/http"

	"github.com/gin-gonic/gin"
	"gorm.io/driver/mysql"
	"gorm.io/gorm"
)

type User struct {
	ID		uint		`json:"id" gorm:"primaryKey"`
	Name	string		`json:"name" gorm:"size:50;not null"`
}

var db *gorm.DB

func main() {
	dsn := "root:myzx@tcp(127.0.0.1:3306)/learn?charset=utf8mb4&parseTime=True&loc=Local"
	var err error
	db, err = gorm.Open(mysql.Open(dsn), &gorm.Config{})
	if err != nil {
			log.Fatal("连接数据库失败:", err)
	}
	log.Println("数据库连接成功")

	r := gin.Default()

	r.GET("/", func(c *gin.Context) {
			c.String(http.StatusOK, "Hello, Gin!")
	})

	r.GET("/api/users", func(c *gin.Context) {
		var users []User
		if err := db.Find(&users).Error; err != nil {
			log.Println("查询失败:", err)
			c.JSON(http.StatusInternalServerError, gin.H{"error":"查询失败"})
			return
		}
		c.JSON(http.StatusOK, users)
	})
	
	r.POST("/api/users", func(c *gin.Context) {
			var u User
			if err := c.ShouldBindJSON(&u); err != nil {
				c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
				return
			}
			u.ID = 0 //	强制清空客户端传来的id，一律交给数据库自增
			if err := db.Create(&u).Error; err != nil {
				log.Println("创建失败", err)
				c.JSON(http.StatusInternalServerError, gin.H{"error":"创建失败"})
				return
			}
			c.JSON(http.StatusCreated, u)
	})

	r.Run(":8080")
}