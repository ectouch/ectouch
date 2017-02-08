<?php

namespace app\modules\ecapi\models\v2;

use app\modules\ecapi\models\v2\ShopConfig;
use app\modules\ecapi\models\BaseModel;

class Invoice
{
     public static function getTypeList()
     {
         if ($model = ShopConfig::findByCode('invoice_type')) {

             $model = unserialize($model);

             $data = [];
             for($i = 0; $i < count($model['type']); $i++){
                 $data[$i]['id'] = $i + 1;
                 $data[$i]['name'] = $model['type'][$i];
                 $data[$i]['tax'] = $model['rate'][$i]/100;
             }
             return BaseModel::formatBody(['types' => $data]);
         }
     }


     public static function getContentList()
     {
         if ($model = ShopConfig::findByCode('invoice_content')) {

             $model = explode("\n", str_replace("\r", '', $model));

             $data = [];
             for($i = 0; $i < count($model); $i++){
                 $data[$i]['id'] = $i + 1;
                 $data[$i]['name'] = $model[$i];
             }
             return BaseModel::formatBody(['contents' => $data]);
         }
     }

     public static function getStatus()
     {
       	  if($model = ShopConfig::findByCode('can_invoice')){
             return BaseModel::formatBody(['is_provided' => intval($model)]);
          }
     }
}
