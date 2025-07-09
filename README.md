# ThinkPHP DTO 组件

## 功能特性
- 🛡️ 基于 PHP 8 注解的自动验证系统
- 🎯 多验证组支持（不同场景使用不同验证规则）
- ⚡ 无缝集成 ThinkPHP8 参数解析
- 📦 类型安全的属性转换（自动处理 int/float/bool/array/DateTime 等类型）
- v2.0 **智能属性忽略功能**（按验证组动态忽略属性）
- v2.1 **强化类型安全转换引擎**
- v2.1 **优化验证逻辑执行效率**

## 📦 安装指南
```bash
composer require nice-yu/think-dto
```

## 🐞 bug 修复
1. **验证组穿透问题**  
   修复多验证组场景下忽略规则意外生效的问题

2. **空数组处理**  
   `null` → `[]` 的转换现在符合预期行为


---

## ✨ 新增特性

### 1. 智能验证忽略系统
#### 多级控制策略
| 控制层级       | 注解示例                          | 应用场景                 |
|----------------|-----------------------------------|------------------------|
| **类级别**     | `#[ValidatorIgnore]`             | 全局跳过验证            |
| **属性级**     | `#[ValidatorIgnore(groups: ['export'])]` | 敏感字段条件过滤        |

#### 典型应用
```php
#[ValidatorIgnore] // 内部系统跳过全部验证
class AuditDto extends Dto {
    #[Validator(rule: 'require')]
    #[Validator(rule: 'max:100')]
    public string $title;
    
    #[ValidatorIgnore(groups: ['report'])] // 报表场景跳过审计人验证
    #[Validator(rule: 'number')]
    public int $auditor_id;
}
```

### 2. 类型安全增强
- 严格模式下的类型转换
  ```php
  // 旧版: "123abc" → 123
  // 新版: "123abc" → 0 (严格模式)
  public int $id;
  ```
- 日期解析优化
  ```php
  // 支持更多格式自动标准化
  "9:30" → "当前日期 09:30:00" // ✅ 纯时分
  "9:30:11" → "当前日期 09:30:11" // ✅ 纯时间
  "2025-1-1" → "2025-01-01 00:00:00" // ✅ 纯日期
  "2025-1-1 9:30" → "2025-01-01 09:30" // ✅ 日期+时分
  "2025-1-1 01:23:45" → "2025-01-01 01:23:45" // ✅ 完整日期时间
  "2025/01/01 12.34.56" → "2025-01-01 12:34:56" // ✅ 非常规分隔符
  ```

---

## 使用示例

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

### 2. DTO 定义（忽略功能和验证功能）
```php
use NiceYu\ThinkDto\Dto;
use NiceYu\ThinkDto\Annotations\Validator;
use NiceYu\ThinkDto\Annotations\ValidatorIgnore;

class UserDto extends Dto
{
    #[Validator(rule: 'require', groups: ['api'])] // 只会在 api 组验证
    public string $username;
    
    #[ValidatorIgnore(groups: ['internal'])] // 组级别忽略验证
    #[Validator(rule: 'email', groups: ['api'])] // 只会在 api 组验证
    public string $email;
    
    #[ValidatorIgnore] // 全忽略验证
    public string $password; 
    
    // 这种也会忽略, 毕竟没有验证规则
    public string $pwdPassword;
}
```

---

## 最佳实践

### 目录结构规范

#### 1. 基础方案（简单项目）
```markdown
app/
└── dto/
    ├── User/
    │   ├── CreateDto.php      # 用户创建DTO
    │   ├── UpdateDto.php      # 用户更新DTO
    │   └── SearchDto.php      # 用户查询DTO
    └── Product/
        ├── CreateDto.php
        └── ListDto.php
```

#### 2. 推荐方案（中大型项目）
```markdown
app/
└── dto/
    ├── Request/              # 入参DTO
    │   ├── User/
    │   │   ├── CreateRequest.php
    │   │   └── UpdateRequest.php
    │   └── Product/
    │       ├── CreateRequest.php
    │       └── ListRequest.php
    │
    └── Response/             # 出参DTO
        ├── User/
        │   ├── DetailResponse.php
        │   └── ListResponse.php
        └── Product/
            ├── DetailResponse.php
            └── ListResponse.php
```
