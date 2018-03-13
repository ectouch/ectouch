# ECTouch v2

ECTouch是一款开源的电商系统，为中小企业提供最佳的新零售解决方案。

采用稳定的MVC框架开发，执行效率、扩展性、稳定性值得信赖。MVC是一种将应用程序的逻辑层和表现层进行分离的方法。MVC分层有助于管理复杂的应用程序，因为您可以在一个时间内专门关注一个方面。例如，您可以在不依赖业务逻辑的情况下专注于视图设计。同时也让应用程序的测试更加容易。MVC分层同时也简化了分组开发。不同的开发人员可同时开发视图、控制器逻辑和业务逻辑。

## 平台需求

- IIS/Apache/Nginx
- PHP5.3+
- MySQL 5.1+

建议使用平台：Linux + Apache2.2 + PHP5.6 + MySQL5.5

### PHP必须环境或启用的系统函数：

curl
allow_url_fopen
GD扩展库
MySQL扩展库
系统函数 —— phpinfo、dir

### 基本目录结构
* /
* /admin       默认后台管理目录（可任意改名）
* /api         API 通知
* /data        静态资源和系统缓存、配置项目录[必须可写入]
* /images      商品图片目录
* /include     核心程序目录
* /install     程序安装器
* /plugins     插件程序目录
* /themes      系统默认模板目录
* /vendor      第三方包

### PHP环境容易碰到的不兼容性问题
* data目录没写入权限，导致管理后台无法上传文件；
* php的上传的临时文件夹没设置好或没写入权限，这会导致文件上传的功能无法使用；
* 出现莫名的错误，如安装时显示空白，这样能是由于系统没装载mysql扩展导致的。
  
## 程序安装使用
* 浏览器中访问http://www.domain.com/install/index.php(domain.com表示你的域名)
* 首次访问会进入安装界面，按照安装提示说明进行程序安装

## 特色功能

* 手机短信验证码功能
* 支持微信商城对接及微信支付
* 订单包裹跟踪
* 手机端第三方登录
* 整站模块自适应宽度
* 支持无线支付宝支付
* 所有数据与ecshop后台无缝对接

## 社区交流

* ECTouch官方主站       http://www.ectouch.cn
* 技术支持论坛          http://bbs.ecmoban.com
* 上海商创网络科技      http://www.ecmoban.com

## 常见问题整理

http://bbs.ecmoban.com/thread-28767-1-1.html

## 安全&缺陷

如果你发现了一个安全漏洞，请发送邮件到 wanganlin@ecmoban.com。所有的安全漏洞都将及时得到解决。

## License

ECTouch 遵循 [GPL license](https://opensource.org/licenses/GPL-3.0) 开源协议。
