<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%delivery_order}}".
 *
 * @property string $delivery_id
 * @property string $delivery_sn
 * @property string $order_sn
 * @property string $order_id
 * @property string $invoice_no
 * @property string $add_time
 * @property integer $shipping_id
 * @property string $shipping_name
 * @property string $user_id
 * @property string $action_user
 * @property string $consignee
 * @property string $address
 * @property integer $country
 * @property integer $province
 * @property integer $city
 * @property integer $district
 * @property string $sign_building
 * @property string $email
 * @property string $zipcode
 * @property string $tel
 * @property string $mobile
 * @property string $best_time
 * @property string $postscript
 * @property string $how_oos
 * @property string $insure_fee
 * @property string $shipping_fee
 * @property string $update_time
 * @property integer $suppliers_id
 * @property integer $status
 * @property integer $agency_id
 */
class DeliveryOrder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%delivery_order}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['delivery_sn', 'order_sn'], 'required'],
            [['order_id', 'add_time', 'shipping_id', 'user_id', 'country', 'province', 'city', 'district', 'update_time', 'suppliers_id', 'status', 'agency_id'], 'integer'],
            [['insure_fee', 'shipping_fee'], 'number'],
            [['delivery_sn', 'order_sn'], 'string', 'max' => 20],
            [['invoice_no'], 'string', 'max' => 50],
            [['shipping_name', 'sign_building', 'best_time', 'how_oos'], 'string', 'max' => 120],
            [['action_user'], 'string', 'max' => 30],
            [['consignee', 'email', 'zipcode', 'tel', 'mobile'], 'string', 'max' => 60],
            [['address'], 'string', 'max' => 250],
            [['postscript'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'delivery_id' => Yii::t('app', 'Delivery ID'),
            'delivery_sn' => Yii::t('app', 'Delivery Sn'),
            'order_sn' => Yii::t('app', 'Order Sn'),
            'order_id' => Yii::t('app', 'Order ID'),
            'invoice_no' => Yii::t('app', 'Invoice No'),
            'add_time' => Yii::t('app', 'Add Time'),
            'shipping_id' => Yii::t('app', 'Shipping ID'),
            'shipping_name' => Yii::t('app', 'Shipping Name'),
            'user_id' => Yii::t('app', 'User ID'),
            'action_user' => Yii::t('app', 'Action User'),
            'consignee' => Yii::t('app', 'Consignee'),
            'address' => Yii::t('app', 'Address'),
            'country' => Yii::t('app', 'Country'),
            'province' => Yii::t('app', 'Province'),
            'city' => Yii::t('app', 'City'),
            'district' => Yii::t('app', 'District'),
            'sign_building' => Yii::t('app', 'Sign Building'),
            'email' => Yii::t('app', 'Email'),
            'zipcode' => Yii::t('app', 'Zipcode'),
            'tel' => Yii::t('app', 'Tel'),
            'mobile' => Yii::t('app', 'Mobile'),
            'best_time' => Yii::t('app', 'Best Time'),
            'postscript' => Yii::t('app', 'Postscript'),
            'how_oos' => Yii::t('app', 'How Oos'),
            'insure_fee' => Yii::t('app', 'Insure Fee'),
            'shipping_fee' => Yii::t('app', 'Shipping Fee'),
            'update_time' => Yii::t('app', 'Update Time'),
            'suppliers_id' => Yii::t('app', 'Suppliers ID'),
            'status' => Yii::t('app', 'Status'),
            'agency_id' => Yii::t('app', 'Agency ID'),
        ];
    }
}
