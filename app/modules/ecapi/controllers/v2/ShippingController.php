<?php

namespace app\modules\ecapi\controllers;

use app\models\v2\Shipping;
use app\models\v2\Features;

class ShippingController extends BaseController
{
    /**
     * POST ecapi.shipping.vendor.list
     */
    public function actionIndex()
    {
        $rules = [
            // 'shop' => 'integer|min:1',
            // 'address' => 'required|integer|min:1',
            // 'products' => 'required|string',

            [['address', 'products'], 'required'],
            [['shop', 'address'], 'integer', 'min' => 1],
            ['products', 'string'],
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Shipping::findAll($this->validated);

        return $this->json($data);
    }

    /**
     * POST ecapi.shipping.status.get
     */
    public function actionInfo()
    {
        $rules = [
            // 'order_id' => 'required|int',

            ['order_id', 'required'],
            ['order_id', 'integer'],
        ];

        if ($res = Features::check('logistics')) {
            return $this->json($res);
        }

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Shipping::getDeliveyInfo($this->validated);

        return $this->json($data);
    }
}
