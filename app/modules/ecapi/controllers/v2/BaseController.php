<?php

namespace app\modules\ecapi\controllers;

use Yii;
use yii\base\DynamicModel;
use yii\web\Controller;
use yii\web\Response;
use app\models\ecapi\Foundation;
use app\helpers\Token;

class BaseController extends Controller
{
    public $validated;
    public $request;

    public $enableCsrfValidation = false;

    public function init()
    {
        $res = file_get_contents("php://input");
        if(!empty($res)){
            Yii::$app->request->setBodyParams(json_decode($res, 1));
        }

        require Yii::$app->basePath . '/helpers/common.php';

        $this->request = Yii::$app->request;
    }

    /**
     * 验证输入信息
     * @param  array $rules
     * @return response
     */
    public function validateInput($rules)
    {
        if (!Yii::$app->request->isPost) {
            return self::json(Foundation::formatError(Foundation::BAD_REQUEST, 'ERROR REQUEST METHOD.'));
        }

        $requests = $this->request->post();
        $validator = DynamicModel::validateData($requests, $rules);

        if ($validator->hasErrors()) {
            return self::json(Foundation::formatError(Foundation::INVALID_SESSION, $validator->getErrors()));
        } else {
            $this->validated = array_intersect_key($requests, $rules);
            $this->validated = $requests;
            return false;
        }
    }

    /**
     * 自定义验证
     */
    public function customValidate($requests, $rules)
    {
        $validator = Validator::make($requests, $rules);
        if ($validator->fails()) {
            return self::json(Foundation::formatError(Foundation::BAD_REQUEST, $validator->messages()->first()));
        } else {
            return false;
        }
    }

    /**
     * Validate the attribute is a valid JSON string.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    public function validateJson($attribute, $value)
    {
        if (! is_scalar($value) && ! method_exists($value, '__toString')) {
            return false;
        }

        json_decode($value);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->addError($attribute, 'Incorrect JSON.');
        }
    }

    /**
     * 返回Json数据
     * @param  array $data
     * @param  array $ext
     * @param  array $paged
     * @return json
     */
    public function json($body = false)
    {
        //过滤null为空字符串(需协调客户端兼容)
        // if ($body) {
        //     $body = format_array($body);
        // }

        // 写入日志
/*        if (config('app.debug')) {

            $debug_id = uniqid();

            Log::debug($debug_id, [
                'LOG_ID' => $debug_id,
                'IP_ADDRESS' => $this->request->userIP,
                'REQUEST_URL' => $this->request->fullUrl(),
                'AUTHORIZATION' => $this->request->header('X-' . config('app.name') . '-Authorization'),
                'REQUEST_METHOD' => $this->request->method(),
                'PARAMETERS' => $this->validated,
                'RESPONSES' => $body
            ]);

            $body['debug_id'] = $debug_id;
        }*/

        Yii::$app->response->format = Response::FORMAT_JSON;

        if (isset($body['error']) && $body['error']) {
            unset($body['error']);
            $response = $body;
//            $response->header('X-' . config('app.name') . '-ErrorCode', $body['error_code']);
//            $response->header('X-' . config('app.name') . '-ErrorDesc', urlencode($body['error_desc']));
        } else {
            $response = $body;
//            $response->header('X-' . config('app.name') . '-ErrorCode', 0);
        }

//        if (config('token.refresh')) {
//            if ($new_token = Token::refresh()) {
//                // 生成新token
//                $response->header('X-' . config('app.name') . '-New-Authorization', $new_token);
//            }
//        }

        return $response;
    }

}