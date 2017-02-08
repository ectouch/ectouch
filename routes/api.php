<?php

return [

    'api/v2' => 'ecapi/v2/default',

    // Other
    'api/v2/ecapi.article.show' => 'ecapi/v2/article/show',

    'api/v2/ecapi.notice.show' => 'ecapi/v2/notice/show',

    'api/v2/ecapi.order.notify.<code:\S+>' => 'ecapi/v2/order/notify',

    'api/v2/ecapi.product.intro.<id:\d+>' => 'ecapi/v2/goods/intro',

    'api/v2/ecapi.product.share.<id:\d+>' => 'ecapi/v2/goods/share',

    'api/v2/ecapi.auth.web' => 'ecapi/v2/user/web-oauth',

    'api/v2/ecapi.auth.web.callback/<vendor:\d+>' => 'ecapi/v2/user/web-callback',

    // Guest
    'api/v2/ecapi.access.dns' => 'ecapi/v2/access/dns',

    'api/v2/ecapi.access.batch' => 'ecapi/v2/access/batch',

    'api/v2/ecapi.category.list' => 'ecapi/v2/goods/category',

    'api/v2/ecapi.category.all.list' => 'ecapi/v2/goods/all-category',

    'api/v2/ecapi.product.list' => 'ecapi/v2/goods/index',

    'api/v2/ecapi.search.product.list' => 'ecapi/v2/goods/search',

    'api/v2/ecapi.review.product.list' => 'ecapi/v2/goods/review',

    'api/v2/ecapi.review.product.subtotal' => 'ecapi/v2/goods/subtotal',

    'api/v2/ecapi.recommend.product.list' => 'ecapi/v2/goods/recommend-list',

    'api/v2/ecapi.product.accessory.list' => 'ecapi/v2/goods/accessory-list',

    'api/v2/ecapi.product.get' => 'ecapi/v2/goods/info',

    'api/v2/ecapi.auth.weixin.mplogin' => 'ecapi/v2/user/weixin-mini-program-login',

    'api/v2/ecapi.auth.signin' => 'ecapi/v2/user/signin',

    'api/v2/ecapi.auth.social' => 'ecapi/v2/user/auth',

    'api/v2/ecapi.auth.default.signup' => 'ecapi/v2/user/signup-by-email',

    'api/v2/ecapi.auth.mobile.signup' => 'ecapi/v2/user/signup-by-mobile',

    'api/v2/ecapi.user.profile.fields' => 'ecapi/v2/user/fields',

    'api/v2/ecapi.auth.mobile.verify' => 'ecapi/v2/user/verify-mobile',

    'api/v2/ecapi.auth.mobile.send' => 'ecapi/v2/user/send-code',

    'api/v2/ecapi.auth.mobile.reset' => 'ecapi/v2/user/reset-password-by-mobile',

    'api/v2/ecapi.auth.default.reset' => 'ecapi/v2/user/reset-password-by-email',

    'api/v2/ecapi.cardpage.get' => 'ecapi/v2/card-page/view',

    'api/v2/ecapi.cardpage.preview' => 'ecapi/v2/card-page/preview',

    'api/v2/ecapi.config.get' => 'ecapi/v2/config/index',

    'api/v2/ecapi.article.list' => 'ecapi/v2/article/index',

    'api/v2/ecapi.brand.list' => 'ecapi/v2/brand/index',

    'api/v2/ecapi.search.keyword.list' => 'ecapi/v2/search/index',

    'api/v2/ecapi.region.list' => 'ecapi/v2/region/index',

    'api/v2/ecapi.invoice.type.list' => 'ecapi/v2/invoice/type',

    'api/v2/ecapi.invoice.content.list' => 'ecapi/v2/invoice/content',

    'api/v2/ecapi.invoice.status.get' => 'ecapi/v2/invoice/status',

    'api/v2/ecapi.notice.list' => 'ecapi/v2/notice/index',

    'api/v2/ecapi.banner.list' => 'ecapi/v2/banner/index',

    'api/v2/ecapi.version.check' => 'ecapi/v2/version/check',

    'api/v2/ecapi.recommend.brand.list' => 'ecapi/v2/brand/recommend',

    'api/v2/ecapi.message.system.list' => 'ecapi/v2/message/system',

    'api/v2/ecapi.message.count' => 'ecapi/v2/message/unread',

    'api/v2/ecapi.site.get' => 'ecapi/v2/site/index',

    'api/v2/ecapi.splash.list' => 'ecapi/v2/splash/index',

    'api/v2/ecapi.splash.preview' => 'ecapi/v2/splash/view',

    'api/v2/ecapi.theme.list' => 'ecapi/v2/theme/index',

    'api/v2/ecapi.theme.preview' => 'ecapi/v2/theme/view',

    'api/v2/ecapi.search.category.list' => 'ecapi/v2/goods/category-search',

    'api/v2/ecapi.order.reason.list' => 'ecapi/v2/order/reason-list',

    'api/v2/ecapi.search.shop.list' => 'ecapi/v2/shop/search',

    'api/v2/ecapi.recommend.shop.list' => 'ecapi/v2/shop/recommand',

    'api/v2/ecapi.shop.list' => 'ecapi/v2/shop/index',

    'api/v2/ecapi.shop.get' => 'ecapi/v2/shop/info',

    'api/v2/ecapi.areacode.list' => 'ecapi/v2/area-code/index',

    // Authorization
    'api/v2/ecapi.user.profile.get' => 'ecapi/v2/user/profile',

    'api/v2/ecapi.user.profile.update' => 'ecapi/v2/user/update-profile',

    'api/v2/ecapi.user.password.update' => 'ecapi/v2/user/update-password',

    'api/v2/ecapi.order.list' => 'ecapi/v2/order/index',

    'api/v2/ecapi.order.get' => 'ecapi/v2/order/view',

    'api/v2/ecapi.order.confirm' => 'ecapi/v2/order/confirm',

    'api/v2/ecapi.order.cancel' => 'ecapi/v2/order/cancel',

    'api/v2/ecapi.order.price' => 'ecapi/v2/order/price',

    'api/v2/ecapi.product.like' => 'ecapi/v2/goods/set-like',

    'api/v2/ecapi.product.unlike' => 'ecapi/v2/goods/set-unlike',

    'api/v2/ecapi.product.liked.list' => 'ecapi/v2/goods/liked-list',

    'api/v2/ecapi.order.review' => 'ecapi/v2/order/review',

    'api/v2/ecapi.order.subtotal' => 'ecapi/v2/order/subtotal',

    'api/v2/ecapi.payment.types.list' => 'ecapi/v2/order/payment-list',

    'api/v2/ecapi.payment.pay' => 'ecapi/v2/order/pay',

    'api/v2/ecapi.shipping.vendor.list' => 'ecapi/v2/shipping/index',

    'api/v2/ecapi.shipping.status.get' => 'ecapi/v2/shipping/info',

    'api/v2/ecapi.consignee.list' => 'ecapi/v2/consignee/index',

    'api/v2/ecapi.consignee.update' => 'ecapi/v2/consignee/modify',

    'api/v2/ecapi.consignee.add' => 'ecapi/v2/consignee/add',

    'api/v2/ecapi.consignee.delete' => 'ecapi/v2/consignee/remove',

    'api/v2/ecapi.consignee.setDefault' => 'ecapi/v2/consignee/set-default',

    'api/v2/ecapi.score.get' => 'ecapi/v2/score/view',

    'api/v2/ecapi.score.history.list' => 'ecapi/v2/score/history',

    'api/v2/ecapi.cashgift.list' => 'ecapi/v2/cash-gift/index',

    'api/v2/ecapi.cashgift.available' => 'ecapi/v2/cash-gift/available',

    'api/v2/ecapi.push.update' => 'ecapi/v2/message/update-deviceId',

    'api/v2/ecapi.cart.add' => 'ecapi/v2/cart/add',

    'api/v2/ecapi.cart.clear' => 'ecapi/v2/cart/clear',

    'api/v2/ecapi.cart.delete' => 'ecapi/v2/cart/delete',

    'api/v2/ecapi.cart.get' => 'ecapi/v2/cart/index',

    'api/v2/ecapi.cart.update' => 'ecapi/v2/cart/update',

    'api/v2/ecapi.cart.checkout' => 'ecapi/v2/cart/checkout',

    'api/v2/ecapi.cart.promos' => 'ecapi/v2/cart/promos',

    'api/v2/ecapi.product.purchase' => 'ecapi/v2/goods/purchase',

    'api/v2/ecapi.product.validate' => 'ecapi/v2/goods/check-product',

    'api/v2/ecapi.message.order.list' => 'ecapi/v2/message/order',

    'api/v2/ecapi.shop.watch' => 'ecapi/v2/shop/watch',

    'api/v2/ecapi.shop.unwatch' => 'ecapi/v2/shop/unwatch',

    'api/v2/ecapi.shop.watching.list' => 'ecapi/v2/shop/watching-list',

    'api/v2/ecapi.coupon.list' => 'ecapi/v2/coupon/index',

    'api/v2/ecapi.coupon.available' => 'ecapi/v2/coupon/available',

    'api/v2/ecapi.cart.flow' => 'ecapi/v2/cart/flow',

];
