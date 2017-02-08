<?php

namespace app\modules\ecapi\models\v2;

use Yii;

/**
 * This is the model class for table "{{%goods_attr}}".
 *
 * @property string $goods_attr_id
 * @property string $goods_id
 * @property integer $attr_id
 * @property string $attr_value
 * @property string $attr_price
 */
class GoodsAttr extends Foundation
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%goods_attr}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['goods_id', 'attr_id'], 'integer'],
            [['attr_value'], 'required'],
            [['attr_value'], 'string'],
            [['attr_price'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'goods_attr_id' => 'Goods Attr ID',
            'goods_id' => 'Goods ID',
            'attr_id' => 'Attr ID',
            'attr_value' => 'Attr Value',
            'attr_price' => 'Attr Price',
        ];
    }

    /**
     * 根据商品id获取属性
     */
    public static function getProperById($id){
        $prefix = Yii::$app->db->tablePrefix;

        $data = self::find()
            ->select([$prefix.'goods_attr.attr_id', 'goods_attr_id', 'attr_value', 'attr_price', $prefix.'attribute.attr_type', $prefix.'attribute.attr_group', $prefix.'attribute.attr_name'])
            ->where(['goods_id'=>$id])
            ->leftJoin($prefix.'attribute', $prefix.'attribute.attr_id = '.$prefix.'goods_attr.attr_id')
            ->asArray()
            ->all();

        $arr['pro'] = array();     // 属性
        $arr['spe'] = array();     // 规格
        foreach ($data AS $row) {

            $row['attr_value'] = str_replace("\n", '<br />', $row['attr_value']);
            $attr_price = $row['attr_price'];

            if ($row['attr_type'] == 0) {
                $arr['pro'][$row['attr_id']]['name'] = $row['attr_name'];
                $arr['pro'][$row['attr_id']]['value'] = $row['attr_value'];

            }elseif ($row['attr_type'] == 1) {
                $arr['spe'][$row['attr_id']]['attr_type'] = $row['attr_type'];
                $arr['spe'][$row['attr_id']]['name'] = $row['attr_name'];
                $arr['spe'][$row['attr_id']]['values'][] = array(
                    'label' => $row['attr_value'],
                    'price' => $attr_price,
                    'format_price' => sprintf('%.2f', abs($attr_price)),
                    'id' => $row['goods_attr_id']
                );

            }

        }
        return $arr;
    }
    /**
     * 获得指定的规格的价格
     *
     * @access  public
     * @param   mix     $property   规格ID的数组或者逗号分隔的字符串
     * @return  void
     */
    public static function property_price($property)
    {
        if (!empty($property))
        {
            if(is_array($property))
            {
                foreach($property as $key=>$val)
                {
                    if (strpos($val,',')) {
                        $property = explode(',',$val);
                    }else{
                        $property[$key]=addslashes($val);
                    }
                }
            }
            else
            {
                $property = addslashes($property);
            }

            $price = self::find()->where(['in', 'goods_attr_id', $property])->sum('attr_price');
        }
        else
        {
            $price = 0;
        }

        return empty($price) ? 0 : $price;
    }

}
