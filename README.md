# ThinkPHP DTO 组件

## 功能特性
- 🛡️ 基于 PHP 8 注解的自动验证系统
- 🎯 多验证组支持（不同场景使用不同验证规则）
- ⚡ 无缝集成 ThinkPHP8 参数解析
- 📦 类型安全的属性转换（自动处理 int/float/bool/array/DateTime 等类型）
- 🔍 **新增：智能属性忽略功能**（按验证组动态忽略属性）

## 安装
```bash
composer require nice-yu/think-dto
```

---

## 更新说明

### v2.0 版本更新
1. **新增验证组忽略功能**：
    - 通过 `ValidatorIgnore` 注解实现特定场景下属性忽略
    - 支持类级别和方法级别的全局忽略
    - 示例：
        ```php
          #[ValidatorIgnore] // 类级别全局忽略
          class UserDto extends Dto {
              #[ValidatorIgnore(groups: ['api'])] // 仅对api组忽略
              public string $internal_id;
          }
        ```

2. **修复关键问题**：
    - 修正验证组忽略逻辑中的循环中断问题
    - 优化类型转换的性能和安全性

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

### 2. DTO 定义（新增忽略功能）
```php
use NiceYu\ThinkDto\Dto;
use NiceYu\ThinkDto\Annotations\Validator;
use NiceYu\ThinkDto\Annotations\ValidatorIgnore;

class UserDto extends Dto
{
    #[Validator(rule: 'require|max:25', groups: ['api'])]
    public string $username;
    
    #[ValidatorIgnore(groups: ['internal'])] // 组级别忽略验证
    #[Validator(rule: 'email', groups: ['api'])]
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
### 忽略策略优化
| 策略类型      | 适用场景           | 示例代码                                       |
|-----------|----------------|--------------------------------------------|
| **类级别忽略** | 整个DTO在特定场景跳过验证 | `#[ValidatorIgnore(groups: ['internal'])]` |
| **属性级忽略** | 敏感/临时字段        | `#[ValidatorIgnore(groups: ['export'])]`   |
| **条件忽略**  | 动态业务场景         | 通过`empty($ignore->groups)`判断               |

### 版本升级建议
```diff
# composer.json
"require": {
-    "nice-yu/think-dto": "^1.0",
+    "nice-yu/think-dto": "^2.0",
}
```