<?php

namespace app\modules\ecapi\controllers;

use app\models\v2\Push;
use app\models\v2\Device;

class MessageController extends BaseController {

    /**
    * POST ecapi.message.system.list
    */
    public function actionSystem()
    {
        $rules = [
            // 'page'      => 'required|integer|min:1',
            // 'per_page'  => 'required|integer|min:1',

            [['page', 'per_page'], 'required'],
            [['page', 'per_page'], 'integer', 'min' => 1],
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $model = Push::getSystemList($this->validated);

        return $this->json($model);
    }

    /**
    * POST ecapi.message.order.list
    */
    public function actionOrder()
    {
        $rules = [
            // 'page'      => 'required|integer|min:1',
            // 'per_page'  => 'required|integer|min:1',

            [['page', 'per_page'], 'required'],
            [['page', 'per_page'], 'integer', 'min' => 1],
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $model = Push::getOrderList($this->validated);

        return $this->json($model);
    }

    /**
    * POST ecapi.message.unread
    */
    public function actionUnread()
    {
        $rules = [
            // 'after' => 'required|string',
            // 'type'  => 'int',

            ['after', 'required'],
            ['after', 'string'],
            ['type', 'integer'],
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $model = Push::unread($this->validated);

        return $this->json($model);
    }

    /**
    * POST ecapi.push.update
    */
    public function actionUpdateDeviceId()
    {
        $rules = [
            // 'device_id' => 'required|string',

            ['device_id', 'required'],
            ['device_id', 'string'],
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $model = Device::updateDevice($this->validated);
        return $this->json($model);
    }
}
