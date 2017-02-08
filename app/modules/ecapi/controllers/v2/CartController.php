<?php

namespace app\modules\ecapi\controllers;

use app\models\v2\Cart;

class CartController extends BaseController
{
    /**
     * POST ecapi.cart.get
     */

    public function actionIndex()
    {
        $response = Cart::getList();
        return $this->json($response);
    }

    /**
     * POST ecapi.cart.add
     */
    public function actionAdd()
    {
        $rules = [
            [['product', 'amount'], 'required'],
            ['product', 'integer', 'min' => 1],
            ['amount', 'integer']
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $response = Cart::add($this->validated);

        return $this->json($response);
    }

    /**
     * POST ecapi.cart.delete
     */

    public function actionDelete()
    {
        $rules = [
            ['good', 'required'],
            ['good', 'string', 'min' => 1],
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $response = Cart::remove($this->validated);

        return $this->json($response);
    }

    public function actionUpdate()
    {
        $rules = [
            [['good', 'amount'], 'required'],
            ['good', 'string', 'min' => 1],
            ['amount', 'integer', 'min' => 1]
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $response = Cart::updateAmount($this->validated);

        return $this->json($response);
    }

    /**
     * POST ecapi.cart.clear
     */

    public function actionClear()
    {
        $response = Cart::clear_cart();
        return $this->json($response);
    }

    /**
     * 订单确认页面
     */
    public function actionFlow(){
        $post = \Yii::$app->request->post();

        $response = Cart::flow($post);

        return $this->json($response);
    }

    /**
     * 提交订单
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function actionCheckout()
    {
        $rules = [
            [['consignee', 'shipping'], 'required'],
            [['consignee', 'shipping'], 'integer', 'min' => 1],
            [['comment'], 'string', 'min' => 1],
//            ['score', 'integer'],
            // ['cart_good_id', 'json'], TODO

            // "shop" => "integer|min:1",          // 店铺ID
            // "consignee" => "required|integer|min:1", // 收货人ID
            // "shipping" => "required|integer|min:1", // 快递ID
            // "invoice_type" => "string|min:1",           // 发票类型ID，如：公司、个人
            // "invoice_content" => "string|min:1",           // 发票内容ID，如：办公用品、礼品
            // "invoice_title" => "string|min:1",           // 发票抬头，如：xx科技有限公司
            // "coupon" => "string|min:1",          // 优惠券ID
            // "cashgift" => "string|min:1",          // 红包ID
            // "comment" => "string|min:1",           // 留言
            // "score" => "integer",                // 积分
            // "cart_good_id" => "required|json",         // 购物车商品id数组
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $response = Cart::checkout($this->validated);

        return $this->json($response);
    }

    /**
     * 购物车商品促销信息
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function actionPromos()
    {
        $rules = [
            ['cart_good_id', 'required'],
            // ['cart_good_id', 'json'], TODO

            // "cart_good_id" => "required|json",         // 购物车商品id数组
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $response = Cart::promos($this->validated);

        return $this->json($response);
    }

}
