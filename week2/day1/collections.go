package main

import "fmt"

func main(){
	//切片：长度可变的序列，类似其他语言的数组
	names := []string{"张三", "李四", "王五"}
	fmt.Println("切片：", names, "长度：", len(names))
	
	notes := []int{1, 2, 3, 4, 5}
	fmt.Println("切片：", notes, "长度：", len(notes), "容量：", cap(notes))
	notes = append(notes,6)
	fmt.Println("追加后：", notes, "长度：", len(notes), "容量：", cap(notes))

	//用range遍历，i是索引，name是元素
	for i, name := range names {
		fmt.Printf("索引 %d: %s\n", i, name)
	}

	//如果不需要索引，用_忽略
	for _, note := range notes {
		fmt.Println("笔记：", note)
	}	

	//键值对，类似PHP的关联数组
	scores := map[string] int{
			"数学": 95,
			"语文": 88,
	}
	scores["英语"] = 92
	fmt.Println("map:", scores)
	fmt.Println("数学成绩：", scores["数学"])

	//判断 key 是否存在(ok模式),重要
	if v, ok := scores["物理"]; ok {
		fmt.Println("物理成绩：", v)
	} else {
		fmt.Println("没有物理成绩")
	}
	
	//遍历 map
	for subject, score := range scores {
		fmt.Printf("%s: %d\n", subject,score)
	}
}
