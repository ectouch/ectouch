<?php

namespace app\modules\ecapi\models\v2;

use app\modules\ecapi\helpers\Header;

class Version extends Foundation {

    protected $connection = 'shop';
    
    protected $table      = 'version';

    public  $timestamps   = true;

    protected $appends = ['download_url'];

    protected $visible = ['version', 'download_url', 'content'];

    public static function checkVersion()
    {
        $arr = Header::getUserAgent();
        $ver = Header::getVer();

        $platform = Header::getUserAgent('Platform');
        switch ($platform) {
            case 'ios':
                $platform = 1;
                break;
            case 'android':
                $platform = 2;
                break;
            default:
                $platform = 0;
                break;
        }

        if (is_array($arr) && isset($arr['Platform']) && !empty($ver)) {
            $model = Version::where('platform',  $platform)->orderBy('version', 'DESC')->first();

            if(isset($model->version) && version_compare($ver, $model->version) < 0){
                return self::formatBody(['version_info' => $model]);
            }
        }

        return self::formatBody(['version_info' => null]);
    }

    public function getDownloadUrlAttribute()
    {
        return $this->attributes['url'];
    }

}
