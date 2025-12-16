# 更新日志 (Changelog)

本文档记录 ECTouch 项目的所有重要变更。

格式基于 [Keep a Changelog](https://keepachangelog.com/zh-CN/1.0.0/)，
版本号遵循 [语义化版本](https://semver.org/lang/zh-CN/)。

---

## [3.0.0] - 2024-12-16

### 重大变更 (Breaking Changes)

#### PHP 版本要求
- **最低 PHP 版本提升至 8.4.0**
- 不再支持 PHP 5.x 和 PHP 7.x
- 移除所有旧版本兼容代码

#### 数据库扩展
- **必须使用 mysqli 扩展**
- 完全移除对旧 mysql 扩展的支持
- 所有数据库操作使用 mysqli_* 函数

#### 语法变更
- 移除字符串花括号访问语法 `$str{0}`，统一使用 `$str[0]`
- 移除 `get_magic_quotes_gpc()` 函数调用
- 移除所有 PHP 版本检查代码
- 移除 MySQL 4.x 版本兼容代码

### 新增功能 (Added)

#### 类型系统
- ✨ 为所有核心类添加完整的类型声明
- ✨ 使用构造器属性提升语法 (PHP 8.0+)
- ✨ 使用 readonly 属性和类 (PHP 8.1+)
- ✨ 使用联合类型声明 (PHP 8.0+)
- ✨ 添加 `declare(strict_types=1)` 严格类型模式

#### 异常处理系统
- ✨ 新增 `ECTouchException` 基础异常类
- ✨ 新增 `DatabaseException` 数据库异常类
- ✨ 新增 `FileException` 文件操作异常类
- ✨ 新增 `ValidationException` 验证异常类
- ✨ 新增 `ConfigException` 配置异常类
- ✨ 异常类支持上下文信息 (context)

#### 配置类
- ✨ 新增 `DatabaseConfig` 只读配置类
- ✨ 新增 `AppConfig` 应用配置类
- ✨ 使用命名参数提高配置可读性

#### 错误日志
- ✨ 新增 `ErrorLogger` 错误日志类
- ✨ 支持结构化日志记录
- ✨ 自动记录异常上下文信息

### 改进 (Changed)

#### 数据库类 (MySQL)
- 🔄 重构 `include/classes/mysql.php`
- 🔄 重构 `include/libraries/EcsMysql.class.php`
- 🔄 所有方法添加类型声明
- 🔄 使用异常替代错误输出
- 🔄 简化字符集设置逻辑
- 🔄 优化连接管理

#### 辅助函数
- 🔄 重构 `include/helpers/function.php`
- 🔄 重构 `include/helpers/base_helper.php`
- 🔄 所有函数添加类型声明
- 🔄 使用 null 合并运算符 (`??`)
- 🔄 使用 `str_contains()` 替代 `strpos()`
- 🔄 使用 `match` 表达式替代 `switch`

#### 字符串处理
- 🔄 `msubstr()` - 添加类型声明，使用 match 表达式
- 🔄 `byte_format()` - 添加类型声明
- 🔄 `sub_str()` - 添加类型声明
- 🔄 `str_len()` - 添加类型声明，返回 int
- 🔄 `mysql_like_quote()` - 添加类型声明，使用 str_contains()

#### IP 和网络函数
- 🔄 `get_client_ip()` - 添加类型声明，使用 null 合并运算符
- 🔄 `real_ip()` - 添加类型声明
- 🔄 `real_server_ip()` - 添加类型声明

#### 文件操作函数
- 🔄 `copy_dir()` - 添加类型声明，使用异常处理
- 🔄 `del_dir()` - 添加类型声明，使用异常处理
- 🔄 `get_extension()` - 添加类型声明
- 🔄 `file_mode_info()` - 添加类型声明
- 🔄 `make_dir()` - 添加类型声明，返回 bool
- 🔄 `move_upload_file()` - 添加类型声明，返回 bool

#### URL 和路由函数
- 🔄 `url()` - 添加类型声明
- 🔄 `U()` - 添加类型声明
- 🔄 `parse_name()` - 添加类型声明
- 🔄 `is_ssl()` - 添加类型声明，返回 bool

#### 配置函数
- 🔄 `C()` - 添加类型声明，使用 null 合并运算符
- 🔄 `load_file()` - 添加类型声明

#### 加密解密函数
- 🔄 `ec_encode()` - 添加类型声明，优化字符串访问
- 🔄 `ec_decode()` - 添加类型声明，优化字符串访问

#### 邮件函数
- 🔄 `send_mail()` - 添加类型声明，返回 bool，使用异常处理

#### 编码转换函数
- 🔄 `ecs_iconv()` - 添加类型声明
- 🔄 `json_str_iconv()` - 添加类型声明
- 🔄 `to_utf8_iconv()` - 添加类型声明

#### 安装程序
- 🔄 更新 `install/index.php` - 添加 PHP 8.4 版本检查
- 🔄 更新 `install/main.php` - 验证 mysqli 扩展

#### 引导系统
- 🔄 更新 `include/bootstrap.php` - 添加严格类型模式
- 🔄 优化自动加载机制
- 🔄 移除版本检查代码

#### 控制器和模型
- 🔄 为所有控制器添加类型声明
- 🔄 为所有模型添加类型声明
- 🔄 使用异常处理错误

### 移除 (Removed)

#### 已弃用的语法
- ❌ 移除字符串花括号访问 `$str{$index}`
- ❌ 移除 `get_magic_quotes_gpc()` 调用
- ❌ 移除动态属性的隐式创建

#### 版本兼容代码
- ❌ 移除所有 PHP 5.x 版本检查
- ❌ 移除所有 PHP 7.x 版本检查
- ❌ 移除 `function_exists()` 兼容性检查
- ❌ 移除 MySQL 4.x 版本兼容代码
- ❌ 移除 EC_CHARSET 相关旧逻辑

#### 错误处理
- ❌ 移除直接 echo 错误信息的代码
- ❌ 移除 `@` 错误抑制符的不当使用

### 修复 (Fixed)

#### 语法错误
- 🐛 修复所有字符串花括号访问语法错误
- 🐛 修复类型不匹配导致的错误
- 🐛 修复 null 值传递给非空参数的错误

#### 数据库
- 🐛 修复数据库连接错误处理
- 🐛 修复查询错误处理
- 🐛 修复字符集设置问题

#### 文件操作
- 🐛 修复文件权限检查
- 🐛 修复目录创建错误处理

### 安全性 (Security)

- 🔒 使用类型声明提高类型安全性
- 🔒 使用异常处理避免信息泄露
- 🔒 改进 SQL 注入防护
- 🔒 改进文件操作安全性

### 性能 (Performance)

- ⚡ PHP 8.4 JIT 编译器支持
- ⚡ OPcache 优化
- ⚡ 减少不必要的版本检查
- ⚡ 优化字符串操作
- ⚡ 优化数据库查询

### 文档 (Documentation)

- 📝 新增 API 文档 (`API-DOCUMENTATION.md`)
- 📝 新增升级指南 (`UPGRADE-GUIDE.md`)
- 📝 更新 README.md
- 📝 新增变更日志 (`CHANGELOG.md`)
- 📝 更新安装说明

### 依赖更新 (Dependencies)

- ⬆️ 更新 `phpoffice/phpspreadsheet` 到 ^5.3 (替代 PHPExcel)
- ⬆️ 更新 `filp/whoops` 到 ^2.15
- ⬆️ 更新 `aliyuncs/oss-sdk-php` 到 ^2.7
- ⬆️ 更新 `riverslei/payment` 到 ^5.0

### 测试 (Testing)

- ✅ 添加属性基测试 (Property-Based Testing)
- ✅ 语法检查通过
- ✅ 类型检查通过
- ✅ 功能测试通过

---

## 升级说明

### 从旧版本升级

**重要:** 本版本是一个重大版本升级，不向后兼容。升级前请：

1. **完整备份数据库和文件**
2. **在测试环境中先测试升级**
3. **检查所有第三方插件的兼容性**
4. **阅读升级指南** (`UPGRADE-GUIDE.md`)

### 最低要求

- PHP 8.4.0 或更高
- MySQL 5.5+ (推荐 5.7+ 或 MariaDB 10.2+)
- mysqli 扩展
- 其他必需的 PHP 扩展 (curl, gd, mbstring, json, openssl)

### 快速升级步骤

```bash
# 1. 备份
mysqldump -u user -p database > backup.sql
tar -czf backup.tar.gz /path/to/ectouch/

# 2. 升级 PHP 到 8.4
# (具体命令取决于操作系统)

# 3. 更新文件
# 下载新版本并替换文件 (保留 data 目录)

# 4. 更新依赖
composer install --no-dev

# 5. 清除缓存
rm -rf data/caches/*

# 6. 测试
# 访问网站并测试所有功能
```

详细步骤请参考 `UPGRADE-GUIDE.md`。

---

## 贡献者

感谢所有为本次升级做出贡献的开发者。

---

## 支持

- 官网: http://www.ectouch.cn
- 论坛: http://bbs.ecmoban.com
- GitHub: https://github.com/ectouch/ectouch

---

## 许可证

ECTouch 遵循 [GPL-3.0](https://opensource.org/licenses/GPL-3.0) 开源协议。

---

**注意:** 本变更日志记录了从旧版本到 PHP 8.4 兼容版本的所有重要变更。如需了解更详细的技术信息，请参考 API 文档和设计文档。
