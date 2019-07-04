<?php

namespace optima\models;

use Yii;
use yii\base\Model;
use yii\helpers\Url;

class Functions extends Model
{

    public static function recaptcha($name= 'reCaptcha')
    {
        $recaptcha_site_key = Yii::$app->params['recaptcha_site_key'];
        if(empty($recaptcha_site_key)) $recaptcha_site_key = "6Le9fqsUAAAAAN2KL4FQEogpmHZ_GpdJ9TGmYMrT";

        return \himiklab\yii2\recaptcha\ReCaptcha2::widget([
            'name' => 'reCaptcha',
            'siteKey' => $recaptcha_site_key, // unnecessary is reCaptcha component was set up
            'widgetOptions' => ['class' => 'col-sm-offset-3'],
        ]);
    }
}
