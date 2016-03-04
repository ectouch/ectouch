# ECTouch 产品使用说明


## ECTouch简介

ECTouch是上海商创网络科技有限公司推出的一款开源免费移动商城网店系统，可以在手机上面卖商品的电子商务软件系统。能够帮助企业和个人快速构建手机移动商城并减少二次开发带来的成本。

采用稳定的MVC框架开发，执行效率、扩展性、稳定性值得信赖。MVC是一种将应用程序的逻辑层和表现层进行分离的方法。MVC分层有助于管理复杂的应用程序，因为您可以在一个时间内专门关注一个方面。例如，您可以在不依赖业务逻辑的情况下专注于视图设计。同时也让应用程序的测试更加容易。MVC分层同时也简化了分组开发。不同的开发人员可同时开发视图、控制器逻辑和业务逻辑。

最新的国际标准HTML5+CSS3，在HTML5平台上，视频，音频，图象，动画，以及同设备的交互都被标准化，各个平台都正在完善对这一标准的支持，基于HTML5标准的一次开发，可以轻松的部署到各个平台，大大提高开发效率。

## 平台需求

1、Windows 平台：
IIS/Apache/Nginx + PHP5.2 + MySQL5.1

2、Linux/Unix 平台
Apache/Nginx + PHP5.2 + MySQL5.1 (PHP在非安全模式下运行)

建议使用平台：Linux + Apache2.2 + PHP5.2/PHP5.3 + MySQL5.1

3、PHP必须环境或启用的系统函数：
curl
allow_url_fopen
GD扩展库
MySQL扩展库
系统函数 —— phpinfo、dir

4、基本目录结构
* /
* /admin       默认后台管理目录（可任意改名）
* /data        静态资源和系统缓存、配置项目录[必须可写入]
* /include     核心程序目录
* /plugins     插件程序目录
* /themes      系统默认模板目录

5、PHP环境容易碰到的不兼容性问题
* data目录没写入权限，导致管理后台无法上传文件；
* php的上传的临时文件夹没设置好或没写入权限，这会导致文件上传的功能无法使用；
* 出现莫名的错误，如安装时显示空白，这样能是由于系统没装载mysql扩展导致的。
  
## 程序安装使用
* 安装并配置好ecshop程序
* 下载ECTouch程序解压到本地目录
* 上传程序目录中的/uploads到ecshop网站根目录下，并命名为mobile（原mobile目录重命名）
* 浏览器中访问http://www.domain.com/mobile/index.php(domain.com表示你的域名)
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

## 功能预览

![image](http://ectouch.cn/data/assets/qrcode.png)
