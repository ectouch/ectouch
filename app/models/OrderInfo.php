<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%order_info}}".
 *
 * @property string $order_id
 * @property string $order_sn
 * @property string $user_id
 * @property integer $order_status
 * @property integer $shipping_status
 * @property integer $pay_status
 * @property string $consignee
 * @property integer $country
 * @property integer $province
 * @property integer $city
 * @property integer $district
 * @property string $address
 * @property string $zipcode
 * @property string $tel
 * @property string $mobile
 * @property string $email
 * @property string $best_time
 * @property string $sign_building
 * @property string $postscript
 * @property integer $shipping_id
 * @property string $shipping_name
 * @property integer $pay_id
 * @property string $pay_name
 * @property string $how_oos
 * @property string $how_surplus
 * @property string $pack_name
 * @property string $card_name
 * @property string $card_message
 * @property string $inv_payee
 * @property string $inv_content
 * @property string $goods_amount
 * @property string $shipping_fee
 * @property string $insure_fee
 * @property string $pay_fee
 * @property string $pack_fee
 * @property string $card_fee
 * @property string $money_paid
 * @property string $surplus
 * @property string $integral
 * @property string $integral_money
 * @property string $bonus
 * @property string $order_amount
 * @property integer $from_ad
 * @property string $referer
 * @property string $add_time
 * @property string $confirm_time
 * @property string $pay_time
 * @property string $shipping_time
 * @property integer $pack_id
 * @property integer $card_id
 * @property string $bonus_id
 * @property string $invoice_no
 * @property string $extension_code
 * @property string $extension_id
 * @property string $to_buyer
 * @property string $pay_note
 * @property integer $agency_id
 * @property string $inv_type
 * @property string $tax
 * @property integer $is_separate
 * @property string $parent_id
 * @property string $discount
 */
class OrderInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%order_info}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'order_status', 'shipping_status', 'pay_status', 'country', 'province', 'city', 'district', 'shipping_id', 'pay_id', 'integral', 'from_ad', 'add_time', 'confirm_time', 'pay_time', 'shipping_time', 'pack_id', 'card_id', 'bonus_id', 'extension_id', 'agency_id', 'is_separate', 'parent_id'], 'integer'],
            [['goods_amount', 'shipping_fee', 'insure_fee', 'pay_fee', 'pack_fee', 'card_fee', 'money_paid', 'surplus', 'integral_money', 'bonus', 'order_amount', 'tax', 'discount'], 'number'],
            [['agency_id', 'inv_type', 'tax', 'discount'], 'required'],
            [['order_sn'], 'string', 'max' => 20],
            [['consignee', 'zipcode', 'tel', 'mobile', 'email', 'inv_type'], 'string', 'max' => 60],
            [['address', 'postscript', 'card_message', 'referer', 'invoice_no', 'to_buyer', 'pay_note'], 'string', 'max' => 255],
            [['best_time', 'sign_building', 'shipping_name', 'pay_name', 'how_oos', 'how_surplus', 'pack_name', 'card_name', 'inv_payee', 'inv_content'], 'string', 'max' => 120],
            [['extension_code'], 'string', 'max' => 30],
            [['order_sn'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'order_id' => Yii::t('app', 'Order ID'),
            'order_sn' => Yii::t('app', 'Order Sn'),
            'user_id' => Yii::t('app', 'User ID'),
            'order_status' => Yii::t('app', 'Order Status'),
            'shipping_status' => Yii::t('app', 'Shipping Status'),
            'pay_status' => Yii::t('app', 'Pay Status'),
            'consignee' => Yii::t('app', 'Consignee'),
            'country' => Yii::t('app', 'Country'),
            'province' => Yii::t('app', 'Province'),
            'city' => Yii::t('app', 'City'),
            'district' => Yii::t('app', 'District'),
            'address' => Yii::t('app', 'Address'),
            'zipcode' => Yii::t('app', 'Zipcode'),
            'tel' => Yii::t('app', 'Tel'),
            'mobile' => Yii::t('app', 'Mobile'),
            'email' => Yii::t('app', 'Email'),
            'best_time' => Yii::t('app', 'Best Time'),
            'sign_building' => Yii::t('app', 'Sign Building'),
            'postscript' => Yii::t('app', 'Postscript'),
            'shipping_id' => Yii::t('app', 'Shipping ID'),
            'shipping_name' => Yii::t('app', 'Shipping Name'),
            'pay_id' => Yii::t('app', 'Pay ID'),
            'pay_name' => Yii::t('app', 'Pay Name'),
            'how_oos' => Yii::t('app', 'How Oos'),
            'how_surplus' => Yii::t('app', 'How Surplus'),
            'pack_name' => Yii::t('app', 'Pack Name'),
            'card_name' => Yii::t('app', 'Card Name'),
            'card_message' => Yii::t('app', 'Card Message'),
            'inv_payee' => Yii::t('app', 'Inv Payee'),
            'inv_content' => Yii::t('app', 'Inv Content'),
            'goods_amount' => Yii::t('app', 'Goods Amount'),
            'shipping_fee' => Yii::t('app', 'Shipping Fee'),
            'insure_fee' => Yii::t('app', 'Insure Fee'),
            'pay_fee' => Yii::t('app', 'Pay Fee'),
            'pack_fee' => Yii::t('app', 'Pack Fee'),
            'card_fee' => Yii::t('app', 'Card Fee'),
            'money_paid' => Yii::t('app', 'Money Paid'),
            'surplus' => Yii::t('app', 'Surplus'),
            'integral' => Yii::t('app', 'Integral'),
            'integral_money' => Yii::t('app', 'Integral Money'),
            'bonus' => Yii::t('app', 'Bonus'),
            'order_amount' => Yii::t('app', 'Order Amount'),
            'from_ad' => Yii::t('app', 'From Ad'),
            'referer' => Yii::t('app', 'Referer'),
            'add_time' => Yii::t('app', 'Add Time'),
            'confirm_time' => Yii::t('app', 'Confirm Time'),
            'pay_time' => Yii::t('app', 'Pay Time'),
            'shipping_time' => Yii::t('app', 'Shipping Time'),
            'pack_id' => Yii::t('app', 'Pack ID'),
            'card_id' => Yii::t('app', 'Card ID'),
            'bonus_id' => Yii::t('app', 'Bonus ID'),
            'invoice_no' => Yii::t('app', 'Invoice No'),
            'extension_code' => Yii::t('app', 'Extension Code'),
            'extension_id' => Yii::t('app', 'Extension ID'),
            'to_buyer' => Yii::t('app', 'To Buyer'),
            'pay_note' => Yii::t('app', 'Pay Note'),
            'agency_id' => Yii::t('app', 'Agency ID'),
            'inv_type' => Yii::t('app', 'Inv Type'),
            'tax' => Yii::t('app', 'Tax'),
            'is_separate' => Yii::t('app', 'Is Separate'),
            'parent_id' => Yii::t('app', 'Parent ID'),
            'discount' => Yii::t('app', 'Discount'),
        ];
    }
}
