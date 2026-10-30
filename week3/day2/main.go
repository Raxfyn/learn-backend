package main

import (
	"net/http"
	"strings"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/golang-jwt/jwt/v5"
)

// 密钥：只有服务器知道，绝对不能泄露、不能进paylo
var secretKey = []byte("my-super-secret-key-do-not-leak")

// Claims = payload 里放什么
type Claims struct {
	UserID               uint   `json:"user_id"`
	Name                 string `json:"name"`
	jwt.RegisteredClaims        //内嵌标准字段：exp、iat等
}

func issueToken(userID uint, name string) (string, error) {
	claims := Claims{
		UserID: userID,
		Name:   name,
		RegisteredClaims: jwt.RegisteredClaims{
			ExpiresAt: jwt.NewNumericDate(time.Now().Add(2 * time.Hour)), //2小时后过期
			IssuedAt:  jwt.NewNumericDate(time.Now()),
		},
	}
	token := jwt.NewWithClaims(jwt.SigningMethodHS256, claims)
	return token.SignedString(secretKey) //用密钥算出signature
}

// 中间件：所有受保护路由公用这一份验证逻辑
func AuthMiddleware() gin.HandlerFunc {
	return func(c *gin.Context) {
		auth := c.GetHeader("Authorization")
		tokenStr := strings.TrimPrefix(auth, "Bearer ")
		if tokenStr == auth || tokenStr == "" {
			c.AbortWithStatusJSON(http.StatusUnauthorized, gin.H{"error": "缺少或格式错误的 Authorization 头"})
			return
		}

		var claims Claims
		token, err := jwt.ParseWithClaims(tokenStr, &claims, func(t *jwt.Token) (interface{}, error) {
			return secretKey, nil //用同一把密钥验签
		})
		if err != nil || !token.Valid {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "token 无效或已过期"})
			return
		}

		c.Set("claims", claims) //验证通过，把身份交给下游handler
		c.Next()                //放行，继续执行后面的handler
	}
}

func profileHandler(c *gin.Context) {
	claims, _ := c.Get("claims") //从上下文取中间件放好的身份
	cl := claims.(Claims)
	c.JSON(http.StatusOK, gin.H{
		"message": "验证通过",
		"user_id": cl.UserID,
		"name":    cl.Name,
	})
}

func main() {
	r := gin.Default()

	//模拟登陆
	r.POST("/login", func(c *gin.Context) {
		var req struct {
			Username string `json:"username"`
			Password string `json:"password"`
		}
		if err := c.ShouldBindJSON(&req); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		if req.Username != "admin" || req.Password != "123456" {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "用户名或密码错误"})
			return
		}
		token, err := issueToken(1, "admin")
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}
		c.JSON(http.StatusOK, gin.H{"token": token})
	})

	//受保护路由组：组内所有路由自动套上中间件
	protected := r.Group("/api")
	protected.Use(AuthMiddleware())
	protected.GET("/profile", profileHandler)

	/*
		//受保护接口：必须出示手环token
		r.GET("/api/profile", func(c *gin.Context) {
			auth := c.GetHeader("Authorization")
			tokenStr := strings.TrimPrefix(auth, "Bearer ")
			if tokenStr == auth || tokenStr == "" {
				c.JSON(http.StatusUnauthorized, gin.H{"error": "缺少或格式错误的Authorization 头"})
				return
			}

			var claims Claims
			token, err := jwt.ParseWithClaims(tokenStr, &claims, func(t *jwt.Token) (interface{}, error) {
				return secretKey, nil //用同一把密钥验签
			})
			if err != nil || !token.Valid {
				c.JSON(http.StatusUnauthorized, gin.H{"error": "token 无效或已过期"})
				return
			}
			c.JSON(http.StatusOK, gin.H{
				"message": "验证通过",
				"user_id": claims.UserID,
				"name":    claims.Name,
				"exp":     claims.ExpiresAt.Time,
			})

		})
	*/

	r.Run(":8080")
}
