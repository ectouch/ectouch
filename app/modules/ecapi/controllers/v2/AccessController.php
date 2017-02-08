<?php

namespace app\modules\ecapi\controllers;

use app\models\v2\Foundation;

class AccessController extends BaseController
{

    public function actionDns()
    {
        $hosts = json_decode(config('app.hosts'), true);
        return $this->json(['hosts' => $hosts]);
    }

    public function actionBatch()
    {
        $rules = [
            ['batch', 'required'],
            // ['batch', 'json'] TODO
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $batch = json_decode($this->validated['batch'], true);

        if (!is_array($batch)) {
            return $this->json(Foundation::formatError(10000));
        }

        $batch_data = [];

        foreach ($batch as $key => $value) {

            $header_arr = [];
            if ($headers = @json_decode($value['header'], true)) {
                foreach ($headers as $header_key => $header_value) {
                    $header_arr[] = $header_key . ': ' . $header_value;
                }
            }

            $res = curl_request(url($value['name']), $value['method'], @json_decode($value['data'], true), $header_arr);

            if (isset($res['error']) && $res['error']) {
                $res['is_batch'] = 1;

                return $this->json($res);
            }

            $batch_data[] = [
                'seq' => $value['seq'],
                'name' => $value['name'],
                'data' => $res
            ];
        }

        return $this->json(['batch' => $batch_data]);
    }
}
