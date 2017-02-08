<?php

namespace app\modules\ecapi\controllers;

use app\models\v2\Invoice;

class InvoiceController extends BaseController {

    /**
    * POST ecapi.invoice.type.list
    */
    public function actionType()
    {
        $data = Invoice::getTypeList();
        return $this->json($data);
    }

    /**
    * POST ecapi.invoice.content.list
    */
    public function actionContent()
    {
        $data = Invoice::getContentList();
        return $this->json($data);
    }

    /**
    * POST ecapi.invoice.status.get
    */
    public function actionStatus()
    {
        $data = Invoice::getStatus();
        return $this->json($data);
    }
}
