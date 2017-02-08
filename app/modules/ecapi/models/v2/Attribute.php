<?php

namespace app\modules\ecapi\models\v2;

use Yii;

/**
 * This is the model class for table "{{%attribute}}".
 *
 * @property integer $attr_id
 * @property integer $cat_id
 * @property string $attr_name
 * @property integer $attr_input_type
 * @property integer $attr_type
 * @property string $attr_values
 * @property integer $attr_index
 * @property integer $sort_order
 * @property integer $is_linked
 * @property integer $attr_group
 */
class Attribute extends Foundation
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%attribute}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cat_id', 'attr_input_type', 'attr_type', 'attr_index', 'sort_order', 'is_linked', 'attr_group'], 'integer'],
            [['attr_values'], 'required'],
            [['attr_values'], 'string'],
            [['attr_name'], 'string', 'max' => 60],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'attr_id' => 'Attr ID',
            'cat_id' => 'Cat ID',
            'attr_name' => 'Attr Name',
            'attr_input_type' => 'Attr Input Type',
            'attr_type' => 'Attr Type',
            'attr_values' => 'Attr Values',
            'attr_index' => 'Attr Index',
            'sort_order' => 'Sort Order',
            'is_linked' => 'Is Linked',
            'attr_group' => 'Attr Group',
        ];
    }

    /**
     * 是否存在规格
     * @access      public
     * @param       array       $goods_attr_id_array        一维数组
     * @return      string
     */
    public static function is_property($goods_attr_id_array, $sort = 'asc')
    {
        if (empty($goods_attr_id_array))
        {
            return $goods_attr_id_array;
        }
        $prefix = Yii::$app->db->tablePrefix;

        //重新排序
        $row = self::find()
            ->select([$prefix.'attribute.attr_type',$prefix.'goods_attr.attr_value',$prefix.'goods_attr.goods_attr_id'])
            ->leftJoin($prefix.'goods_attr',[$prefix.'goods_attr.attr_id'=>$prefix.'attribute.attr_id'])
            ->where([$prefix.'attribute.attr_type' => 1])
            ->where(['in', $prefix.'goods_attr.goods_attr_id', $goods_attr_id_array])
            ->orderBy($prefix.'attribute.attr_id ' . $sort)
            ->all();

        $return_arr = array();
        foreach ($row as $value)
        {
            $return_arr['sort'][]   = $value['goods_attr_id'];

            $return_arr['row'][$value['goods_attr_id']]    = $value;
        }

        if(!empty($return_arr))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    /**
     * 获得指定的商品属性
     *
     * @access      public
     * @param       array       $arr        规格、属性ID数组
     * @param       type        $type       设置返回结果类型：pice，显示价格，默认；no，不显示价格
     *
     * @return      string
     */
    public static function get_goods_attr_info($arr, $type = 'pice')
    {
        $attr   = '';
        $prefix = Yii::$app->db->tablePrefix;

        if (!empty($arr))
        {
            $fmt = "%s:%s[%s] \n";

            $res = GoodsAttr::find()
                ->select([$prefix.'goods_attr.attr_price', $prefix.'goods_attr.attr_value', $prefix.'attribute.attr_name as name'])
                ->where(['in', $prefix.'goods_attr.goods_attr_id',$arr])
                ->leftJoin($prefix.'attribute',$prefix.'attribute.attr_id ='. $prefix.'goods_attr.attr_id')
                ->asArray()
                ->all();
            foreach ($res as $key => $row) {

                $attr_price = round(floatval($row['attr_price']), 2);
                $attr .= sprintf($fmt, $row['name'], $row['attr_value'], $attr_price);
            }
            $attr = str_replace('[0]', '', $attr);
        }

        return $attr;
    }
}
