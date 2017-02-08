<?php

namespace app\modules\ecapi\controllers;

use app\models\v2\UserAddress;
use app\models\v2\Features;

class ConsigneeController extends BaseController
{

    /**
     * POST ecapi.consignee.list
     */
    public function actionIndex()
    {
        $data = UserAddress::getList($this->validated);
        return $this->json($data);
    }

    /**
     * POST ecapi.consignee.add
     */
    public function actionAdd()
    {
        $rules = [
            // 'name' => 'required|string|min:2|max:15',
            // 'mobile' => 'numeric|required_without:tel',
            // 'tel' => 'string|required_without:mobile',
            // 'zip_code' => 'numeric',
            // 'region' => 'required|integer|min:1',
            // 'address' => 'required|string',
            // 'identity' => 'string|min:2|max:19',

            [['name', 'mobile', 'region', 'address'], 'required'],
            ['name', 'string', 'min' => 2, 'max' => 15],
            ['mobile', 'match', 'pattern' => '/^13[0-9]{9}|15[012356789][0-9]{8}|18[0-9]{9}|14[579][0-9]{8}|17[0-9]{9}$/'],
            ['tel', 'match', 'pattern' => '/^(0[0-9]{2,3}/-)?([2-9][0-9]{6,7})+(/-[0-9]{1,4})?$/'],
            ['zip_code', 'number'],
            ['region', 'integer', 'min' => 1],
            ['address', 'string'],
            // ['identity', 'string', 'min' => 2, 'max' => 19],
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }
        $data = UserAddress::add($this->validated);

        return $this->json($data);
    }

    /**
     * POST ecapi.consignee.delete
     */
    public function actionRemove()
    {
        $rules = [
            // 'consignee' => 'required|integer|min:1',
            ['consignee', 'required'],
            ['consignee', 'integer', 'min' => 1],
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = UserAddress::remove($this->validated);
        return $this->json($data);
    }

    /**
     * POST ecapi.consignee.update
     */
    public function actionModify()
    {
        $rules = [
            // 'consignee' => 'required|integer|min:1',
            // 'name' => 'required|string|min:2|max:15',
            // 'mobile' => 'numeric|required_without:tel',
            // 'tel' => 'string|required_without:mobile',
            // 'zip_code' => 'numeric',
            // 'region' => 'required|integer|min:1',
            // 'address' => 'required|string',
            // 'identity' => 'string|min:2|max:19',

            [['name', 'mobile', 'region', 'address', 'consignee'], 'required'],
            ['consignee', 'integer', 'min' => 1],
            ['name', 'string', 'min' => 2, 'max' => 15],
            ['mobile', 'match', 'pattern' => '/^13[0-9]{9}|15[012356789][0-9]{8}|18[0-9]{9}|14[579][0-9]{8}|17[0-9]{9}$/'],
            ['tel', 'match', 'pattern' => '/^(0[0-9]{2,3}/-)?([2-9][0-9]{6,7})+(/-[0-9]{1,4})?$/'],
            ['zip_code', 'number'],
            ['region', 'integer', 'min' => 1],
            ['address', 'string'],
            // ['identity', 'string', 'min' => 2, 'max' => 19],
        ];


        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = UserAddress::modify($this->validated);

        return $this->json($data);
    }

    /**
     * POST ecapi.consignee.setDefault
     */
    public function actionSetDefault()
    {
        $rules = [
            // 'consignee' => 'required|integer|min:1',
            ['consignee', 'required'],
            ['consignee', 'integer', 'min' => 1],
        ];

        if ($res = Features::check('address.default')) {
            return $this->json($res);
        }

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = UserAddress::setDefault($this->validated);

        return $this->json($data);
    }
}
