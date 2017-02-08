<?php

namespace app\modules\ecapi\models\v2;

use app\modules\ecapi\models\BaseModel;
use app\modules\ecapi\helpers\Token;

class CollectGoods extends BaseModel
{
    protected $connection = 'shop';

    protected $table      = 'collect_goods';

    protected $primaryKey = 'rec_id';

    public    $timestamps = false;

    protected $guarded = [];


    public static function getList(array $attributes)
    {
        extract($attributes);

        $uid = Token::authorization();
        $model = self::where(['user_id' => $uid])->with('goods')->orderBy('add_time', 'DESC');

        //paged
        $total = $model->count();
        $data = $model->paginate($per_page)
            ->toArray();

        //format
        $goods = [];
        foreach ($data['data'] as $key => $value) {
            $goods[$key] = $data['data'][$key]['goods'];
        }

        return self::formatBody(['products' => $goods, 'paged' => self::formatPaged($page, $per_page, $total)]);
    }

    /**
    * 获取当前用户收藏此商品状态
    *
    * @access public
    * @param integer $goods_id
    * @return integer
    */
    public static function getIsLiked($goods_id)
    {
        $uid = Token::authorization();
        if ($model = self::where(['user_id' => $uid])->where(['goods_id' => $goods_id])->first()) {
            return true;
        }
        return false;
    }

    public static function setLike(array $attributes)
    {
        extract($attributes);

        $uid = Token::authorization();
        $num = CollectGoods::where(['user_id' => $uid, 'goods_id' => $product])->count();

        //因为有网站和手机 所以可能$num大于1
        if($num == 0){
            $model = new CollectGoods;
            $model->user_id             = $uid;
            $model->goods_id            = $product;
            $model->add_time            = time();
            $model->is_attention        = 1;

            if ($model->save()){
                return self::formatBody(['is_liked' =>true ]);
            }else{
                return self::formatError(self::UNKNOWN_ERROR);
            }
        }elseif ($num >0 ) {
            return self::formatBody(['is_liked' =>true ]);
        }

    }

    public static function setUnlike(array $attributes)
    {
        extract($attributes);

        $uid = Token::authorization();
        $model = self::where(['user_id' => $uid, 'goods_id' => $product]);
        $num = $model->count();

        if ($num == 1)
        {
            $model->delete();
        }
        else if ($num > 1)
        {
            for ($i=0; $i < $num; $i++) {
                $model = $model->first();
                $model->delete();
            }
        }
        if($model->count() == 0){
            return self::formatBody(['is_liked' =>false ]);
        }
    }

    public function goods()
    {
      return $this->hasOne('app\models\v2\Goods', 'goods_id', 'goods_id');
    }

}
