package main

import "fmt"

type Student struct {
	Name string
	Scores map[string] int  //科目 -> 分数
}

//计算平均分
func (s Student) Average() float64 {
	total := 0
	for _,score := range s.Scores {
			total += score
	}
	//TODO 1:返回平均分
	Ave := float64(total) / float64(len(s.Scores))
	return Ave 
}

func main() {
	students := []Student{
			{Name: "张三",Scores: map[string]int{"数学": 90, "语文": 85}},
			{Name: "李四",Scores: map[string]int{"数学": 70, "语文": 95}},
			{Name: "王五",Scores: map[string]int{"数学": 88, "语文": 79}},
	}

	//TODO2:遍历students，打印每个人的姓名和平均分
	for _, s := range students {
		fmt.Printf("姓名: %s, 平均分：%.1f\n",s.Name,s.Average())
	}
	
}