# 需求文档

## 简介

本项目旨在将ECTouch电商系统从PHP 5.3+升级到PHP 8.4版本。ECTouch是一个基于MVC架构的开源电商平台，包含前台商城、后台管理、API接口等模块。升级将移除对旧版PHP的兼容性代码,采用PHP 8.4的现代语法和特性,提升系统性能、安全性和可维护性。

## 术语表

- **ECTouch系统**: 基于PHP开发的电商平台系统
- **mysqli扩展**: PHP的MySQL改进扩展,用于数据库操作
- **MVC架构**: Model-View-Controller设计模式
- **类型声明**: PHP中为函数参数和返回值指定数据类型的语法
- **命名参数**: PHP 8.0+引入的按参数名传递参数的特性
- **构造器属性提升**: PHP 8.0+引入的在构造函数中直接声明和初始化属性的语法
- **联合类型**: PHP 8.0+支持的多类型声明语法
- **只读属性**: PHP 8.1+引入的readonly关键字,防止属性被修改
- **枚举类型**: PHP 8.1+引入的enum类型
- **弃用语法**: 在新版PHP中不再推荐使用的语法特性

## 需求

### 需求 1

**用户故事:** 作为系统维护者,我希望更新composer配置以要求PHP 8.4,以便明确系统的运行环境要求。

#### 验收标准

1. WHEN系统检查PHP版本要求时 THEN ECTouch系统应要求PHP版本为8.4或更高
2. WHEN开发者查看composer.json时 THEN配置文件应明确声明php版本要求为>=8.4.0
3. WHEN系统文档被更新时 THEN README.md应反映新的PHP 8.4最低版本要求

### 需求 2

**用户故事:** 作为开发者,我希望移除所有已弃用的PHP语法,以便代码符合PHP 8.4标准并避免运行时警告。

#### 验收标准

1. WHEN代码使用字符串偏移访问时 THEN ECTouch系统应使用方括号语法而非花括号语法
2. WHEN代码进行类型比较时 THEN ECTouch系统应避免使用已弃用的比较运算符
3. WHEN代码定义函数参数时 THEN ECTouch系统应移除动态属性的隐式创建
4. WHEN代码使用null值时 THEN ECTouch系统应正确处理null传递给非空参数的情况
5. WHEN代码调用函数时 THEN ECTouch系统应移除所有对已弃用函数的调用

### 需求 3

**用户故事:** 作为开发者,我希望为核心类添加现代PHP类型声明,以便提高代码的类型安全性和IDE支持。

#### 验收标准

1. WHEN定义类方法时 THEN ECTouch系统应为所有公共方法添加参数类型声明
2. WHEN定义类方法时 THEN ECTouch系统应为所有公共方法添加返回类型声明
3. WHEN方法可能返回多种类型时 THEN ECTouch系统应使用联合类型声法
4. WHEN属性被声明时 THEN ECTouch系统应为类属性添加类型声明
5. WHEN构造函数定义属性时 THEN ECTouch系统应使用构造器属性提升语法

### 需求 4

**用户故事:** 作为开发者,我希望优化数据库操作类以使用PHP 8.4特性,以便提升数据库操作的性能和安全性。

#### 验收标准

1. WHEN数据库类执行查询时 THEN ECTouch系统应使用mysqli预处理语句防止SQL注入
2. WHEN数据库连接被建立时 THEN ECTouch系统应使用命名参数提高代码可读性
3. WHEN数据库错误发生时 THEN ECTouch系统应使用异常处理而非错误输出
4. WHEN查询结果被获取时 THEN ECTouch系统应使用类型化的返回值
5. WHEN数据库类被实例化时 THEN ECTouch系统应移除对旧版MySQL版本检查的代码

### 需求 5

**用户故事:** 作为开发者,我希望更新错误处理机制以使用现代异常,以便更好地追踪和处理错误。

#### 验收标准

1. WHEN系统遇到错误时 THEN ECTouch系统应抛出类型化的异常而非使用echo输出
2. WHEN数据库操作失败时 THEN ECTouch系统应抛出DatabaseException异常
3. WHEN文件操作失败时 THEN ECTouch系统应抛出FileException异常
4. WHEN验证失败时 THEN ECTouch系统应抛出ValidationException异常
5. WHEN异常被抛出时 THEN ECTouch系统应包含详细的错误上下文信息

### 需求 6

**用户故事:** 作为开发者,我希望利用PHP 8.4的新特性优化代码,以便提升系统性能和代码质量。

#### 验收标准

1. WHEN代码使用数组操作时 THEN ECTouch系统应使用数组展开运算符简化代码
2. WHEN代码需要空值合并时 THEN ECTouch系统应使用null合并运算符
3. WHEN代码进行字符串操作时 THEN ECTouch系统应使用str_contains等现代字符串函数
4. WHEN代码定义常量时 THEN ECTouch系统应考虑使用枚举类型替代常量组
5. WHEN代码需要不可变数据时 THEN ECTouch系统应使用readonly属性

### 需求 7

**用户故事:** 作为系统管理员,我希望更新安装程序以检测PHP 8.4环境,以便确保系统在正确的环境中安装。

#### 验收标准

1. WHEN安装程序运行时 THEN ECTouch系统应检查PHP版本是否为8.4或更高
2. WHEN安装程序检查扩展时 THEN ECTouch系统应验证mysqli扩展已启用
3. WHEN安装程序检查环境时 THEN ECTouch系统应移除对已弃用PHP特性的检查
4. WHEN安装失败时 THEN ECTouch系统应提供清晰的PHP版本不兼容错误消息
5. WHEN安装成功时 THEN ECTouch系统应在配置文件中记录PHP版本信息

### 需求 8

**用户故事:** 作为开发者,我希望更新代码中的PHP版本检查逻辑,以便移除对旧版本的兼容性判断。

#### 验收标准

1. WHEN代码检查PHP版本时 THEN ECTouch系统应移除所有PHP 5.x和7.x版本的条件判断
2. WHEN代码检查MySQL版本时 THEN ECTouch系统应移除对MySQL 4.x版本的兼容性代码
3. WHEN代码使用函数时 THEN ECTouch系统应移除function_exists等版本兼容性检查
4. WHEN代码初始化时 THEN ECTouch系统应假定所有PHP 8.4特性可用
5. WHEN代码执行时 THEN ECTouch系统应移除microtime等旧版本兼容性处理

### 需求 9

**用户故事:** 作为开发者,我希望优化自动加载机制,以便利用PHP 8.4的性能改进。

#### 验收标准

1. WHEN类被加载时 THEN ECTouch系统应使用PSR-4自动加载标准
2. WHEN命名空间被使用时 THEN ECTouch系统应为所有新代码添加适当的命名空间
3. WHEN类文件被组织时 THEN ECTouch系统应遵循现代PHP项目结构
4. WHEN composer自动加载时 THEN ECTouch系统应优化autoload配置以提升性能
5. WHEN类被实例化时 THEN ECTouch系统应移除手动require/include语句

### 需求 10

**用户故事:** 作为测试人员,我希望系统在PHP 8.4环境下正常运行,以便验证升级的正确性。

#### 验收标准

1. WHEN系统启动时 THEN ECTouch系统应在PHP 8.4环境下无错误加载
2. WHEN用户访问前台时 THEN ECTouch系统应正常显示商品列表和详情页面
3. WHEN管理员登录后台时 THEN ECTouch系统应正常显示管理界面
4. WHEN执行数据库操作时 THEN ECTouch系统应正确执行增删改查操作
5. WHEN系统运行时 THEN ECTouch系统应无PHP弃用警告或错误信息
