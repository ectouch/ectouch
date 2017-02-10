# ECTouch v3

- [接入准备](#introduction)
    - [概述](#overview)
    - [接入流程](#process)
    - [公共参数](#parameter)
    - [签名算法](#signature)
    - [调用示例](#quick_start)
- [API类别](#api)
    - [商品](#item)
    - [类目](#category)
    - [交易](#trade)
    - [用户](#user)
    - [评论](#comment)
    - [营销](#promotion)
    - [支付](#payment)
    - [微信](#wechat)
    - [商家](#seller)
    - [门店](#outlet)
    - [核销](#virtualcode)
    - [店铺](#shop)
    - [工具](#tool)
- [错误码](#error_code)
- [注意事项](#notice)
- [常见问题](apidoc?detail=faq.md)

<a name="introduction"></a>
## 接入准备

<a name="overview"></a>
### 概述

我们为商家提供的丰富API，涵盖ECTouch各个核心业务流程，基于这些内容可开发各类应用，解决店铺管理、营销推广、数据分析等方面的问题，以实现WAP站点和客户端及单页应用等多种形式的应用接入。如果您是富有企业信息系统开发经验的传统软件厂商，您还可以基于ECTouch API为商家提供包括但不限于BI、ERP、DRP、CRM、SCM 等。

ECTouch的API是基于HTTP协议来调用的，开发者应用可以根据ECTouch的协议来封装HTTP请求进行调用，以下主要是针对自行封装HTTP请求进行API调用的原理进行详细解说。

#### 基本特性

- 服务端采用Yii2框架版本，运用api机制来实现跨产品和跨终端产品的研发和发布。
- 客户端推荐采用vue.js框架进行产品需求研发。

#### 依赖包

- php采用composer安装依赖，配置文件composer.json
- 静态资源采用bower安装依赖，配置文件bower.json和.bowerrc
- 本地打包工具采用webpack & mix，配置文件webpack.mix.js
- 前端资源依赖nodejs，配置文件package.json

#### 目录架构

```
wwwroot  WEB部署目录（或者子目录）
├─app             应用目录
├─bootstrap       启动文件目录
├─config          配置文件目录
├─database        数据迁移目录
├─public          应用入口目录
├─resources       资源文件目录
├─routes          路由配置目录
├─storage         缓存存储目录
├─tests           测试目录
├─vendor          第三包目录
├─.bowerrc        bower配置文件
├─.gitignore      git忽略文件
├─README.md       说明文件
├─artisan         控制台脚本
├─bower.json      bower包配置
├─composer.json   composer配置文件
├─gulpfile.js     gulpfile配置文件
├─package.json    node包配置文件
```

### 安装

下载压缩包安装：
> http://www.ectouch.cn/download

或者Git克隆安装；
> git clone https://github.com/ectouch/ectouch

更新依赖包：
> composer udpate


<a name="process"></a>
### 接入流程

根据ECTouch的协议：填充参数 > 生成签名 > 拼装HTTP请求 > 发起HTTP请求> 得到HTTP响应 > 解释json/xml结果。

<a name="parameter"></a>
### 公共参数

| 参数名称 | 参数类型 | 是否必须 | 参数描述 |
| -------- | -------- | -------- | -------- |
| method | String | 是 | API接口名称。 |
| app_key | String | 是 | ECTouch分配给应用的AppId。 |
| session | String | 否 | 用户登录成功后的授权信息。当此API的标签上注明："需要授权"，则此参数必传；"不需要授权"，则此参数不需要传；"可选授权"，则此参数为可选。 |
| timestamp | String | 是 | 时间戳，格式为yyyy-MM-dd HH:mm:ss，时区为GMT+8，例如：2016-01-01 12:00:00。服务端允许客户端请求最大时间误差为10分钟。 |
| format | String | 否 | 响应格式。默认为xml格式，可选值：xml，json。 |
| v | String | 是 | API协议版本，可选值：1.0。 |
| sign | String | 是 | API输入参数签名结果，签名算法参照下面的介绍。 |
| sign_method | String | 是 | 签名的摘要算法，可选值为：md5。 |

### 业务参数

API调用除了必须包含公共参数外，如果API本身有业务级的参数也必须传入，每个API的业务级参数请参见各个 API 内的参数说明。

<a name="signature"></a>
### 签名算法

为了防止API调用过程中被黑客恶意篡改，调用任何一个API都需要携带签名，服务端会根据请求参数，对签名进行验证，签名不合法的请求将会被拒绝。目前支持的签名算法有：MD5(sign_method=md5)，签名大体过程如下：

对所有API请求参数（包括公共参数和业务参数，但除去sign参数和byte[]类型的参数），根据参数名称的ASCII码表的顺序排序。如：foo=1, bar=2, foo_bar=3, foobar=4排序后的顺序是bar=2, foo=1, foo_bar=3, foobar=4。
将排序好的参数名和参数值拼装在一起，根据上面的示例得到的结果为：bar2foo1foo_bar3foobar4。
把拼装好的字符串采用utf-8编码，使用签名算法对编码后的字节流进行摘要。如果使用MD5算法，则需要在拼装的字符串前后加上app的secret后，再进行摘要，如：md5(secret+bar2foo1foo_bar3foobar4+secret)；
说明：MD5是128位长度的摘要算法，用16进制表示，一个十六进制的字符能表示4个位，所以签名后的字符串长度固定为32个十六进制字符。

<a name="quick_start"></a>
### 调用示例

下面将以ectouch.item.get调用为例，具体步骤如下：

#### Step 1: 设置参数值

公共参数：
```
method = "ectouch.item.get"
app_key = "12345678"
session = "test"
timestamp = "2016-01-01 12:00:00"
format = "json"
v = "1.0"
sign_method = "md5"
```
业务参数：
```
goods_id = 11223344
```

#### Step 2: 按ASCII顺序排序

```
app_key = "12345678"
format = "json"
goods_id = 11223344
method = "ectouch.item.get"
session = "test"
sign_method = "md5"
timestamp = "2016-01-01 12:00:00"
v = "1.0"
```

#### Step 3: 拼接参数名与参数值

```
app_key12345678formatjsongoods_id11223344methodectouch.item.getsessiontestsign_methodmd5timestamp2016-01-01 12:00:00v1.0
```

#### Step 4: 生成签名

假设app的secret为helloworld，则签名结果为：md5(helloworld+按顺序拼接好的参数名与参数值+helloworld) = "D1F578E6E6EE4E7B85D3B94970328EEC"

#### Step 5: 组装HTTP请求

将所有参数名和参数值采用utf-8进行URL编码（参数顺序可随意，但必须要包括签名参数），然后通过GET或POST（含byte[]类型参数）发起请求，如：

```
http://api.ectouch.cn/?method=ectouch.item.get&app_key=12345678&session=test&timestamp=2016-01-01+12%3A00%3A00&format=json&v=1.0&sign_method=md5&goods_id=11223344&sign=D1F578E6E6EE4E7B85D3B94970328EEC
```


<a name="api"></a>
## API类别

<a name="item"></a>
### 商品

| API列表 | 描述 |
| -------- | -------- |
| [ectouch.item.get](apidoc?detail=ectouch.item.get.md) | 获取单个商品信息 |
| [ectouch.item.add](apidoc?detail=ectouch.item.add.md) | 新增一个商品 |
| [ectouch.item.update](apidoc?detail=ectouch.item.update.md) | 更新单个商品信息 |
| [ectouch.item.delete](apidoc?detail=ectouch.item.delete.md) | 删除一个商品 |
| [ectouch.item.sku.get](apidoc?detail=ectouch.item.sku.get.md) | 获取单个商品SKU |
| [ectouch.item.sku.update](apidoc?detail=ectouch.item.sku.update.md) | 更新单个商品SKU |
| [ectouch.items.get](apidoc?detail=ectouch.items.get.md) | 获取商品列表 |

<a name="category"></a>
### 类目

| API列表 | 描述 |
| -------- | -------- |
| [ectouch.category.get](apidoc?detail=ectouch.category.get.md) | 获取单个分类信息 |
| [ectouch.categories.get](apidoc?detail=ectouch.categories.get.md) | 获取分类列表 |
| [ectouch.brand.category.get](apidoc?detail=ectouch.brand.category.get.md) | 获取品牌分类 |
| [ectouch.brand.get](apidoc?detail=ectouch.brand.get.md) | 获取单个品牌信息 |
| [ectouch.brands.get](apidoc?detail=ectouch.brands.get.md) | 获取品牌列表 |

<a name="trade"></a>
### 交易

| API列表 | 描述 |
| -------- | -------- |
| [ectouch.cart.get](apidoc?detail=ectouch.cart.get.md) | 获取购物车商品 |
| [ectouch.cart.add](apidoc?detail=ectouch.cart.add.md) | 添加一个商品到购物车 |
| [ectouch.cart.update](apidoc?detail=ectouch.cart.update.md) | 更新购物车商品数目 |
| [ectouch.cart.delete](apidoc?detail=ectouch.cart.delete.md) | 从购物车中删除一商品 |
| [ectouch.cart.clear](apidoc?detail=ectouch.cart.clear.md) | 清空购物车中的商品 |
| [ectouch.cart.collect](apidoc?detail=ectouch.cart.collect.md) | 将商品移至收藏夹 |
| [ectouch.cart.package.add](apidoc?detail=ectouch.cart.package.add.md) | 添加礼包到购物车 |
| [ectouch.cart.favourable.add](apidoc?detail=ectouch.cart.favourable.add.md) | 添加优惠活动到购物车 |
| [ectouch.flow.check](apidoc?detail=ectouch.flow.check.md) | 检查订单数据 |
| [ectouch.flow.shipping.update](apidoc?detail=ectouch.flow.shipping.update.md) | 改变配送方式 |
| [ectouch.flow.insure.update](apidoc?detail=ectouch.flow.insure.update.md) | 选定/取消配送保价 |
| [ectouch.flow.payment.update](apidoc?detail=ectouch.flow.payment.update.md) | 改变支付方式 |
| [ectouch.flow.pack.update](apidoc?detail=ectouch.flow.pack.update.md) | 改变包装 |
| [ectouch.flow.card.update](apidoc?detail=ectouch.flow.card.update.md) | 改变贺卡 |
| [ectouch.flow.surplus.update](apidoc?detail=ectouch.flow.surplus.update.md) | 改变余额 |
| [ectouch.flow.integral.update](apidoc?detail=ectouch.flow.integral.update.md) | 改变积分 |
| [ectouch.flow.bonus.update](apidoc?detail=ectouch.flow.bonus.update.md) | 改变红包优惠券 |
| [ectouch.flow.needinv.update](apidoc?detail=ectouch.flow.needinv.update.md) | 改变发票设置 |
| [ectouch.flow.oos.update](apidoc?detail=ectouch.flow.oos.update.md) | 改变缺货处理方式 |
| [ectouch.order.add](apidoc?detail=ectouch.order.add.md) | 提交订单数据 |
| [ectouch.order.get](apidoc?detail=ectouch.order.get.md) | 获取单个订单 |
| [ectouch.order.update](apidoc?detail=ectouch.order.update.md) | 更新单个订单 |
| [ectouch.order.cancel](apidoc?detail=ectouch.order.cancel.md) | 取消单个订单 |
| [ectouch.order.merge](apidoc?detail=ectouch.order.merge.md) | 合并两个订单 |
| [ectouch.order.again](apidoc?detail=ectouch.order.again.md) | 订单商品添加到购物车 |
| [ectouch.order.pay](apidoc?detail=ectouch.order.pay.md) | 获取订单支付信息 |
| [ectouch.order.reminder](apidoc?detail=ectouch.order.reminder.md) | 订单发货提醒 |
| [ectouch.order.express](apidoc?detail=ectouch.order.express.md) | 订单快递追踪 |
| [ectouch.order.received](apidoc?detail=ectouch.order.received.md) | 订单确认收货 |
| [ectouch.orders.get](apidoc?detail=ectouch.orders.get.md) | 获取订单列表 |

<a name="user"></a>
### 用户

| API列表 | 描述 |
| -------- | -------- |
| [ectouch.user.get](apidoc?detail=ectouch.user.get.md) | 获取用户信息 |
| [ectouch.user.signup](apidoc?detail=ectouch.user.signup.md) | 用户注册 |
| [ectouch.user.signin](apidoc?detail=ectouch.user.signin.md) | 用户登录 |
| [ectouch.user.update](apidoc?detail=ectouch.user.update.md) | 更新用户资料 |
| [ectouch.user.signup.fields](apidoc?detail=ectouch.user.signup.fields.md) | 获取注册字段 |
| [ectouch.user.password.update](apidoc?detail=ectouch.user.password.update.md) | 修改会员密码 |
| [ectouch.user.forget](apidoc?detail=ectouch.user.forget.md) | 找回密码修改密码 |
| [ectouch.user.bind](apidoc?detail=ectouch.user.bind.md) | 绑定注册 |
| [ectouch.user.logout](apidoc?detail=ectouch.user.logout.md) | 用户注销 |
| [ectouch.user.address.get](apidoc?detail=ectouch.user.address.get.md) | 获取用户收货地址 |
| [ectouch.user.address.add](apidoc?detail=ectouch.user.address.add.md) | 添加用户收货地址 |
| [ectouch.user.address.update](apidoc?detail=ectouch.user.address.update.md) | 更新用户收货地址 |
| [ectouch.user.address.delete](apidoc?detail=ectouch.user.address.delete.md) | 删除用户收货地址 |
| [ectouch.user.address.default](apidoc?detail=ectouch.user.address.default.md) | 设置默认收货地址 |
| [ectouch.user.collect.add](apidoc?detail=ectouch.user.collect.add.md) | 用户收藏单个商品 |
| [ectouch.user.collect.delete](apidoc?detail=ectouch.user.collect.delete.md) | 用户删除收藏商品 |
| [ectouch.user.collects.get](apidoc?detail=ectouch.user.collects.get.md) | 用户收藏列表 |
| [ectouch.user.attention.add](apidoc?detail=ectouch.user.attention.add.md) | 添加关注商品 |
| [ectouch.user.attention.delete](apidoc?detail=ectouch.user.attention.delete.md) | 取消关注商品 |
| [ectouch.user.account.log](apidoc?detail=ectouch.user.account.log.md) | 获取会员充值提现记录 |
| [ectouch.user.account.deposit](apidoc?detail=ectouch.user.account.deposit.md) | 创建会员充值申请 |
| [ectouch.user.account.raply](apidoc?detail=ectouch.user.account.raply.md) | 创建会员提现申请 |
| [ectouch.user.account.detail](apidoc?detail=ectouch.user.account.detail.md) | 获取帐户资金明细 |
| [ectouch.user.account.pay](apidoc?detail=ectouch.user.account.pay.md) | 会员充值付款 |
| [ectouch.user.account.cancel](apidoc?detail=ectouch.user.account.cancel.md) | 会员充值/提现申请取消 |
| [ectouch.user.bonus.get](apidoc?detail=ectouch.user.bonus.get.md) | 会员红包列表 |
| [ectouch.user.bonus.add](apidoc?detail=ectouch.user.bonus.add.md) | 添加一个红包 |
| [ectouch.user.booking.add](apidoc?detail=ectouch.user.booking.add.md) | 添加缺货登记 |
| [ectouch.user.booking.get](apidoc?detail=ectouch.user.booking.get.md) | 显示缺货登记列表 |
| [ectouch.user.booking.delete](apidoc?detail=ectouch.user.booking.delete.md) | 删除缺货登记 |
| [ectouch.user.tag.get](apidoc?detail=ectouch.user.tag.get.md) | 标签云列表 |
| [ectouch.user.tag.add](apidoc?detail=ectouch.user.tag.add.md) | 添加标签云 |
| [ectouch.user.tag.delete](apidoc?detail=ectouch.user.tag.delete.md) | 删除标签 |
| [ectouch.user.affiliate](apidoc?detail=ectouch.user.affiliate.md) | 用户推荐分享 |
| ~~[ectouch.user.validate.email](apidoc?detail=ectouch.user.validate.email.md)~~ | 验证用户注册邮件 |
| ~~[ectouch.user.history.clear](apidoc?detail=ectouch.user.history.clear.md)~~ | 清除商品浏览历史 |

<a name="comment"></a>
### 评论

| API列表 | 描述 |
| -------- | -------- |
| [ectouch.comment.get](apidoc?detail=ectouch.comment.get.md) | 显示评论列表 |
| [ectouch.comment.add](apidoc?detail=ectouch.comment.add.md) | 发表商品评论 |
| [ectouch.comment.delete](apidoc?detail=ectouch.comment.delete.md) | 删除评论 |
| [ectouch.message.get](apidoc?detail=ectouch.message.get.md) | 显示留言列表 |
| [ectouch.message.add](apidoc?detail=ectouch.message.add.md) | 提交留言反馈 |
| [ectouch.message.delete](apidoc?detail=ectouch.message.delete.md) | 删除留言 |

<a name="promotion"></a>
### 营销

| API列表 | 描述 |
| -------- | -------- |
| [ectouch.promotion.activity](apidoc?detail=ectouch.promotion.activity.md) | 优惠活动 |
| [ectouch.promotion.auction](apidoc?detail=ectouch.promotion.auction.md) | 拍卖活动 |
| [ectouch.promotion.group_buy](apidoc?detail=ectouch.promotion.group_buy.md) | 团购活动 |
| [ectouch.promotion.exchange](apidoc?detail=ectouch.promotion.exchange.md) | 积分兑换 |
| [ectouch.promotion.topic](apidoc?detail=ectouch.promotion.topic.md) | 专题汇 |
| [ectouch.promotion.bargain](apidoc?detail=ectouch.promotion.bargain.md) | 砍价活动 |
| [ectouch.promotion.article](apidoc?detail=ectouch.promotion.article.md) | 社区资讯 |
| [ectouch.promotion.distribution](apidoc?detail=ectouch.promotion.distribution.md) | 分销活动 |
| [ectouch.promotion.crowd_funding](apidoc?detail=ectouch.promotion.crowd_funding.md) | 微众筹 |
| [ectouch.promotion.spell_group](apidoc?detail=ectouch.promotion.spell_group.md) | 拼团 |
| [ectouch.promotion.package](apidoc?detail=ectouch.promotion.package.md) | 超值礼包 |
| [ectouch.promotion.wholesale](apidoc?detail=ectouch.promotion.wholesale.md) | 批发活动 |
| [ectouch.promotion.snatch](apidoc?detail=ectouch.promotion.snatch.md) | 夺宝奇兵 |
| [ectouch.promotion.check_in](apidoc?detail=ectouch.promotion.check_in.md) | 每日签到 |
| [ectouch.promotion.shark_it_off](apidoc?detail=ectouch.promotion.shark_it_off.md) | 摇一摇 |
| [ectouch.promotion.paying_agent](apidoc?detail=ectouch.promotion.paying_agent.md) | 订单代付 |
| [ectouch.promotion.egg_frenzy](apidoc?detail=ectouch.promotion.egg_frenzy.md) | 砸金蛋 |
| [ectouch.promotion.scratch_card](apidoc?detail=ectouch.promotion.scratch_card.md) | 刮刮卡 |
| [ectouch.promotion.big_wheel](apidoc?detail=ectouch.promotion.big_wheel.md) | 大转盘 |
| [ectouch.promotion.coupon](apidoc?detail=ectouch.promotion.coupon.md) | 领取优惠券 |

<a name="payment"></a>
### 支付

| API列表 | 描述 |
| -------- | -------- |
| [ectouch.pay.qrcode](apidoc?detail=ectouch.pay.qrcode.md) | 支付二维码 |

<a name="wechat"></a>
### 微信公众号

| API列表 | 描述 |
| -------- | -------- |
| [ectouch.wechat.oauth](apidoc?detail=ectouch.wechat.oauth.md) | 微信授权 |
| [ectouch.wechat.jssdk](apidoc?detail=ectouch.wechat.jssdk.md) | 微信JSSDK |
| [ectouch.wechat.userinfo.get](apidoc?detail=ectouch.wechat.userinfo.get.md) | 微信用户信息 |

<a name="seller"></a>
### 商家

| API列表 | 描述 |
| -------- | -------- |
| [ectouch.sellers.get](apidoc?detail=ectouch.sellers.get.md) | 店铺街 |
| [ectouch.seller.get](apidoc?detail=ectouch.seller.get.md) | 商家店铺详情 |
| [ectouch.seller.merchant](apidoc?detail=ectouch.seller.merchant.md) | 入驻商家信息 |

<a name="outlet"></a>
### 门店

| API列表 | 描述 |
| -------- | -------- |
| [ectouch.store.list](apidoc?detail=ectouch.store.list.md) | 门店列表 |
| [ectouch.store.get](apidoc?detail=ectouch.store.get.md) | 门店详情 |

<a name="virtualcode"></a>
### 核销

| API列表 | 描述 |
| -------- | -------- |
| [ectouch.virtualcode.get](apidoc?detail=ectouch.virtualcode.get.md) | 核销订单信息 |
| [ectouch.virtualcode.apply](apidoc?detail=ectouch.virtualcode.apply.md) | 使用核销验证码 |

<a name="shop"></a>
### 店铺

| API列表 | 描述 |
| -------- | -------- |
| [ectouch.shop.config](apidoc?detail=ectouch.shop.config.md) | 系统配置 |
| [ectouch.shop.shipping](apidoc?detail=ectouch.shop.shipping.md) | 配送方式 |
| [ectouch.shop.payment](apidoc?detail=ectouch.shop.payment.md) | 支付方式 |
| [ectouch.shop.ad](apidoc?detail=ectouch.shop.ad.md) | 手机广告 |
| [ectouch.shop.help](apidoc?detail=ectouch.shop.help.md) | 商店帮助 |

<a name="tool"></a>
### 工具

| API列表 | 描述 |
| -------- | -------- |
| [ectouch.tool.region](apidoc?detail=ectouch.tool.region.md) | 地区 |
| [ectouch.tool.chat](apidoc?detail=ectouch.tool.chat.md) | 在线客服 |
| [ectouch.tool.sms](apidoc?detail=ectouch.tool.sms.md) | 短信发送 |
| [ectouch.search.keywords](apidoc?detail=ectouch.search.keywords.md) | 搜索关键词 |


<a name="error_code"></a>
## 错误码

目前开发者调用API可能出现的错误有两类：全局错误、业务错误。

1、全局错误

错误码小于100(不包含15,40,41错误码)的调用错误，这种错误一般是由于用户的请求不符合各种基本校验而引起的。用户遇到这些错误的返回首先检查应用的权限、频率等情况，然后参照文档检验一下传入的参数是否完整且合法。

2、业务错误

业务级错误是传入的参数缺失，有误或格式错误等原因造成的错误。因此开发者应该根据错误信息检验是否传入了相应的信息，对于这一类错误建议改正后再重试。错误响应是用户和服务器交互失败的最直接展示，在调用API服务时，如果调用失败，请尽量保留下错误日志以便进行后面的错误追查。 

3、全局错误返回码

| 错误码 | 错误描述 | 解决方案 |
| -------- | -------- | -------- |
| -1 | 系统错误 | 系统内部错误，请直接联系技术支持，或邮件给网站管理员 |
| 40001 |	未指定AppId | 请求时传入AppId |
| 40002 |	无效的App | 填写有效的AppId |
| 40003 |	无效的时间参数 | 以当前时间重新发起请求；如果系统时间和服务器时间误差超过10分钟，请调整系统时间 |
| 40004 |	请求没有签名 | 请使用协议规范对请求中的参数进行签名 |
| 40005 |	签名校验失败 | 检查 AppId 和 AppSecret 是否正确；如果是自行开发的协议封装，请检查代码 |
| 40006 |	未指定请求的API接口名称 | 指定API接口名称 |
| 40007 |	请求非法的API接口名称 | 检查请求的API接口名称的值 |

4、错误返回结果

| 名称 | 类型 | 是否必须 | 描述 |
| -------- | -------- | -------- | -------- |
| code | Number | 是 | 错误编号 |
| msg | String | 是 | 错误信息 |
| params | List | 是 | 请求参数列表 |

5、返回结果示例

```
{
    "error_response": {
        "code": 40002,
        "msg": "invalid app",
        "params": {
            "app_id": "6000800060008000",
            "method": "ectouch.item.get",
            "timestamp": "2016-01-20 20:38:42",
            "format": "json",
            "sign_method": "md5",
            "v": "1.0",
            "sign": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
        }
    }
}
```

<a name="notice"></a>
## 注意事项

所有的请求和响应数据编码皆为utf-8格式，URL里的所有参数名和参数值请做URL编码。如果请求的Content-Type是application/x-www-form-urlencoded，则HTTP Body体里的所有参数值也做URL编码；如果是multipart/form-data格式，每个表单字段的参数值无需编码，但每个表单字段的charset部分需要指定为utf-8。

参数名与参数值拼装起来的URL长度小于1024个字符时，可以用GET发起请求；参数类型含byte[]类型或拼装好的请求URL过长时，必须用POST发起请求。所有API都可以用POST发起请求。
