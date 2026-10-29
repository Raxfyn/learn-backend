package greeting

import "fmt"

//Hello 首字母大写 = 公开函数，其他包可以调用
func Hello(name string) string {
	return fmt.Sprintf("你好，%s", name)
}

//secret 首字母小写 = 私有函数，只能在这个包内使用
func secret() string {
	return "内部使用"
}