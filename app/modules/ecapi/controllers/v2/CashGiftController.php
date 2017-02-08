<?php

namespace app\modules\ecapi\controllers;

use app\models\v2\BonusType;
use app\models\v2\Features;

class CashGiftController extends BaseController
{

    /**
     * POST ecapi.cashgift.list
     */
    public function actionIndex()
    {
        $rules = [
            // 'page' => 'required|integer|min:1',
            // 'per_page' => 'required|integer|min:1',
            // 'status' => 'required|integer',
            [['page', 'per_page', 'status'], 'required'],
            [['page', 'per_page'], 'integer', 'min' => 1],
            ['status', 'integer'],
        ];

        if ($res = Features::check('cashgift')) {
            return $this->json($res);
        }

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $model = BonusType::getListByUser($this->validated);

        return $this->json($model);
    }

    /**
     * POST ecapi.cashgift.available
     */
    public function actionAvailable()
    {
        $rules = [
            // 'page' => 'required|integer|min:1',
            // 'per_page' => 'required|integer|min:1',
            // 'total_price' => 'required|numeric|min:0',
            [['page', 'per_page', 'total_price'], 'required'],
            [['page', 'per_page'], 'integer', 'min' => 1],
            ['total_price', 'number', 'min' => 0],
        ];

        if ($res = Features::check('cashgift')) {
            return $this->json($res);
        }

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $model = BonusType::getAvailableListByUser($this->validated);

        return $this->json($model);
    }

}
