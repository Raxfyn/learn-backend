package service

import (
	"apidemo/model"
	"apidemo/repository"
	"fmt"
)

type UserService struct {
	repo *repository.UserRepository
}

func NewUserService(repo *repository.UserRepository) *UserService {
	return &UserService{repo: repo}
}

func (s *UserService) CreateUser(name string) (*model.User, error) {
	//业务规则1:name 不能为空
	if name == "" {
		return nil, fmt.Errorf("name cannot be empty")
	}

	user := &model.User{Name: name}
	// 业务规则2:强制ID=0，交给数据库自增
	user.ID = 0

	if err := s.repo.Create(user); err != nil {
		return nil, err
	}

	return user, nil
}

func (s *UserService) ListUsers() ([]model.User, error) {
	return s.repo.FindAll()
}
