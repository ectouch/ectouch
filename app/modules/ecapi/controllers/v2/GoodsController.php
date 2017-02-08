<?php

namespace app\modules\ecapi\controllers;

use Yii;
use app\models\v2\Goods;
use app\models\v2\GoodsCategory;

class GoodsController extends BaseController
{
    /**
     * POST /ecapi.product.list
     */
    public function actionIndex()
    {
        $rules =  [
            [['page', 'per_page'], 'required'],
            [['page', 'per_page', 'brand', 'category', 'shop'], 'integer', 'min'=> 1],
//            [['keyword', 'sort_key', 'sort_value'], 'string', 'min'=> 1]
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Goods::getList($this->validated);

        return $this->json($data);
    }

    /**
     * POST /ecapi.search.product.list
     */
    public function actionSearch()
    {
        $rules = [
            'page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:1',
            'brand' => 'integer|min:1',
            'category' => 'integer|min:1',
            'shop' => 'integer|min:1',
            'keyword' => 'string|min:1',
            'sort_key' => 'string|min:1',
            'sort_value' => 'required_with:sort_key|string|min:1',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Goods::getList($this->validated);

        return $this->json($data);
    }

    /**
     * POST /ecapi.product.like
     */
    public function actionSetLike()
    {
        $rules = [
            'product' => 'required|integer|min:1',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = CollectGoods::setLike($this->validated);

        return $this->json($data);
    }

    /**
     * POST /ecapi.product.unlike
     */
    public function actionSetUnlike()
    {
        $rules = [
            'product' => 'required|integer|min:1',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = CollectGoods::setUnlike($this->validated);

        return $this->json($data);
    }

    /**
     * POST /ecapi.product.liked.list
     */
    public function actionLikedList()
    {
        $rules = [
            'page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:1',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = CollectGoods::getList($this->validated);

        return $this->json($data);
    }

    /**
     * 商品评价
     * @return [type] [description]
     */
    public function actionReview()
    {

        $rules = [
            'page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:1',
            'product' => 'required|integer|min:1',
            'grade' => 'integer|in:0,1,2,3',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }
        $data = Comment::getReview($this->validated);

        return $this->json($data);
    }

    /**
     * 评价统计
     * @return [type] [description]
     */
    public function actionSubtotal()
    {

        $rules = [
            'product' => 'required|integer|min:1',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }
        $data = Comment::getSubtotal($this->validated);

        return $this->json($data);
    }


    public function actionRecommendList()
    {
        $rules = [
            'page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:1',
            'product' => 'integer|min:1',
            'brand' => 'integer|min:1',
            'category' => 'integer|min:1',
            'shop' => 'integer|min:1',
            'sort_key' => 'string|min:1',
            'sort_value' => 'required_with:sort_key|string|min:1',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Goods::getRecommendList($this->validated);

        return $this->json($data);
    }

    public function actionAccessoryList()
    {
        $rules = [
            'page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:1',
            'product' => 'required|integer|min:1',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Goods::getAccessoryList($this->validated);

        return $this->json($data);
    }


    public function actionBrandList()
    {
        $rules = [
            'page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:1',
        ];

    }

    public function actionCategory()
    {
//        $rules = [
//            'page' => 'required|integer|min:1',
//            'per_page' => 'required|integer|min:1',
//            'category' => 'integer|min:1',
//            'shop' => 'integer|min:1',
//            // 'paging'     => 'required',
//        ];
        $rules = [
            [['page', 'per_page'], 'required'],
            [['page', 'per_page', 'category', 'shop'], 'integer'],
            [['page', 'per_page', 'category', 'shop'], 'integer', 'min'=>1],
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $attributes = Yii::$app->request->post();
        $data = GoodsCategory::getList($attributes);

        return $this->json($data);
    }

    /**
     * 获取所有分类
     */
    public function actionAllCategory(){
        $rules = [
            [['page', 'per_page'], 'required'],
            [['page', 'per_page', 'category', 'shop'], 'integer'],
            [['page', 'per_page', 'category', 'shop'], 'integer', 'min'=>1],
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $attributes = Yii::$app->request->post();
        $attributes['id'] = 0;

        $data = GoodsCategory::getAllList($attributes);
        $left = array();
        $right = array();
        foreach ($data as $k => $v) {
            if(!empty($v['child'])){
                $temp = array();
                foreach ($v['child'] as $ck => $cv) {
                    $temp[$ck] = $cv;
                }
                array_push($right, $temp);
                unset($v['child']);
            }
            array_push($left, $v);
        }
        unset($data);

        return $this->json(array('left'=>$left, 'right'=>$right));

    }


    public function actionCategorySearch()
    {
        $rules = [
            'page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:1',
            'category' => 'integer|min:1',
            'shop' => 'integer|min:1',
            'keyword' => 'string',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = GoodsCategory::getList($this->validated);

        return $this->json($data);
    }

    /**
     * 商品详情
     */
    public function actionInfo()
    {
        $rules = [
            ['product' , 'required'],
            ['product' , 'integer', 'min'=>1]
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Goods::getInfo($this->validated);

        return $this->json($data);
    }

    public function actionIntro($id)
    {
        return Goods::getIntro($id);
    }

    public function actionShare($id)
    {
        return Goods::getShare($id);
    }

    public function actionPurchase()
    {
        $rules = [
            "shop" => "integer|min:1",          // 店铺ID
            "consignee" => "required|integer|min:1", // 收货人ID
            "shipping" => "required|integer|min:1", // 快递ID
            "invoice_type" => "string|min:1",           // 发票类型ID，如：公司、个人
            "invoice_content" => "string|min:1",           // 发票内容ID，如：办公用品、礼品
            "invoice_title" => "string|min:1",           // 发票抬头，如：xx科技有限公司
            "coupon" => "string|min:1",           // 优惠券ID
            "cashgift" => "string|min:1",           // 红包ID
            "comment" => "string|min:1",           // 留言
            "score" => "integer",                 // 积分
            "property" => "required|string",         // 用户选择的属性ID
            "product" => "required|integer|min:1",         // 商品ID
            "amount" => "required|integer|min:1",         // 数量
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $response = Goods::purchase($this->validated);

        return $this->json($response);
    }


    public function actionCheckProduct()
    {
        $rules = [
            "product" => "required|json",
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $response = Products::validateProducts($this->validated);

        return $this->json($response);
    }
}
