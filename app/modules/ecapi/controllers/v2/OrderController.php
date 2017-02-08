<?php

namespace app\modules\ecapi\controllers;

use app\models\v2\Order;
use app\models\v2\Payment;
use app\models\v2\Features;

class OrderController extends BaseController
{
    /**
     * POST /ecapi.order.list
     */
    public function actionIndex()
    {
        $rules = [
            // 'page' => 'required|integer|min:1',
            // 'per_page' => 'required|integer|min:1',
            // 'status' => 'integer|min:0',

            [['page', 'per_page'], 'required'],
            [['page', 'per_page'], 'integer', 'min' => 1],
            ['status', 'integer', 'min' => 0]
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Order::getList($this->validated);
        return $this->json($data);
    }

    /**
     * POST /ecapi.order.get
     */
    public function actionView()
    {
        $rules = [
            // 'order' => 'required|integer|min:1',

            ['order', 'required'],
            ['order', 'integer', 'min' => 1]
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Order::getInfo($this->validated);
        return $this->json($data);
    }

    /**
     * POST /ecapi.order.confirm
     */
    public function actionConfirm()
    {
        $rules = [
            // 'order' => 'required|integer|min:1',

            ['order', 'required'],
            ['order', 'integer', 'min' => 1]
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Order::confirm($this->validated);
        return $this->json($data);
    }

    /**
     * POST /ecapi.order.cancel
     */
    public function actionCancel()
    {
        $rules = [
            // 'order' => 'required|integer|min:1',
            // 'reason' => 'required|integer|min:1',

            [['order'], 'required'],
            [['order'], 'integer', 'min' => 1]
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Order::cancel($this->validated);
        return $this->json($data);
    }

    /**
     * POST /ecapi.order.price
     */
    public function actionPrice()
    {
        $rules = [
            // "shop" => "integer|min:1",          // 店铺ID
            // "consignee" => "integer|min:1",          // 收货人ID
            // "shipping" => "integer|min:1",          // 快递ID
            // "coupon" => "string|min:1",           // 优惠券ID
            // "cashgift" => "string|min:1",           // 红包ID
            // "score" => "integer",                // 积分
            // "order_product" => "required|string",        // 商品id数组

            ['order_product', 'required'],
            [['shop', 'consignee', 'shipping'], 'integer', 'min' => 1],
            [['coupon', 'cashgift'], 'string', 'min' => 1],
            ['score', 'integer'],
            ['order_product', 'string'],
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Order::price($this->validated);
        return $this->json($data);
    }

    /**
     * POST /ecapi.order.review
     */
    public function actionReview()
    {
        $rules = [
            // 'order' => 'required|integer|min:1',
            // 'review' => 'required|json',
            // 'is_anonymous' => 'required|integer|in:0,1',

            [['order', 'review', 'is_anonymous'], 'required'],
            ['order', 'integer', 'min' => 1],
            // ['review', 'json'], TODO
            ['is_anonymous', 'integer'],
            ['is_anonymous', 'in', 'range' => [0, 1]]

        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $items = json_decode($this->validated['review'], true);

        $items_rules = [
            // '*.goods' => 'required|integer|min:1',
            // '*.grade' => 'required|integer|in:1,2,3',
            // '*.content' => 'string',

            [['*.goods', '*.grade'], 'required'],
            ['*.goods', 'integer', 'min' => 1],
            ['*.grade', 'integer'],
            ['*.grade', 'in', 'range' => [1, 2, 3]],
            ['*.content', 'string']
        ];

        if ($error = $this->customValidate($items, $items_rules)) {
            return $error;
        }

        $data = Order::review($this->validated, $items);
        return $this->json($data);
    }

    /**
     * POST /ecapi.payment.types.list
     */
    public function actionPaymentList()
    {
        $rules = [
            // 'shop' => 'integer|min:1',

            ['shop', 'integer', 'min' => 1]
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Payment::getList($this->validated);
        return $this->json($data);
    }

    /**
     * POST /ecapi.payment.pay
     */
    public function actionPay()
    {
        $rules = [
            // 'order' => 'required|integer|min:1',
            // 'code' => 'required|string|in:',
            // 'openid' => 'required_if:code,wxpay.web|string',
            // 'channel' => 'string',
            // 'referer' => 'string',

            [['order', 'code'], 'required'],
            ['order', 'integer', 'min' => 1],
            [['openid'], 'string'],
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        extract($this->validated);
        $payarr = ['alipay.app' => 'pay.alipay', 'wxpay.app' => 'pay.weixin', 'unionpay.app' => 'pay.unionpay', 'cod.app' => 'pay.cod', 'wxpay.web' => 'pay.wxweb', 'teegon.wap' => 'pay.teegon'];

        if ($res = Features::check($payarr[$code])) {
            return $this->json($res);
        }

        $data = Payment::pay($this->validated);
        return $this->json($data);
    }

    /**
     * POST /order/notify/:code
     */
    public function actionNotify($code)
    {
        Payment::notify($code);
    }

    /**
     * POST /ecapi.order.reason.list
     */
    public function actionReasonList()
    {
        return $this->json(Order::getReasonList());
    }

    /**
     * POST /ecapi.order.subtotal
     */
    public function actionSubtotal()
    {
        return $this->json(Order::subtotal());
    }
}
