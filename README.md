# ThinkPHP DTO 组件

## 功能特性
- 🛡️ 基于 PHP 8 注解的自动验证系统
- 🎯 多验证组支持（不同场景使用不同验证规则）
- ⚡ 无缝集成 ThinkPHP 参数解析
- 📦 类型安全的属性转换（自动处理 int/float/bool/array/DateTime 等类型）

## 安装
```bash
composer require nice-yu/think-dto
```
## 使用示例

---
### 1. 基础用法
#### 控制器：
```php
use app\dto\UserDto;
use NiceYu\ThinkDto\Annotations\ValidatorGroup;

class UserController
{
    #[ValidatorGroup(['api'])]
    public function create(UserDto $dto)
    {
        return json($dto->toArray());
    }
}
```

### 2. DTO 定义
```php
use NiceYu\ThinkDto\Dto;
use NiceYu\ThinkDto\Annotations\Validator;

class UserDto extends Dto
{
    #[Validator(rule: 'require', groups: ['api'])]
    #[Validator(rule: 'max:25', groups: ['api'])]
    #[Validator(rule: 'min:6', groups: ['api'])]
    public string $username;
    
    #[Validator(rule: 'email', message: '邮箱格式不正确')]
    public string $email;
    
    public DateTime $created_at; // 自动转换日期格式
}
```

## 最佳实践
- 建议将 DTO 类存放在 app/dto 目录
- 使用 groups 参数区分不同场景的验证规则
- 继承 Dto 基类获得完整功能