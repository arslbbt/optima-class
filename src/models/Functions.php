<?php

namespace optima\models;

use Yii;
use yii\base\Model;
use yii\helpers\Url;

class Functions extends Model
{

    public static function recaptcha($name= 'reCaptcha')
    {
        $recaptcha_site_key = isset(Yii::$app->params['recaptcha_site_key'])?Yii::$app->params['recaptcha_site_key']:"6Le9fqsUAAAAAN2KL4FQEogpmHZ_GpdJ9TGmYMrT";
        return \himiklab\yii2\recaptcha\ReCaptcha2::widget([
            'name' => 'reCaptcha',
            'siteKey' => $recaptcha_site_key, // unnecessary is reCaptcha component was set up
            'widgetOptions' => ['class' => 'col-sm-offset-3'],
        ]);
    }
    public static function siteSendEmail($it){
        $model = new ContactUs();
        $model->load(Yii::$app->request->get());
        $model->verifyCode=true;
        $model->reCaptcha=Yii::$app->request->get('reCaptcha'); 

        $model->sendMail();
        
        if (!$model->sendMail()) {
            $errors = 'Message not sent!';
            if(isset($model->errors) and count($model->errors) > 0){
                $errs = array();
                foreach($model->errors as $k => $err){
                    $errs[] = $err[0];
                }
                $errors = implode(',', $errs);
            }
            Yii::$app->session->setFlash('failure', $errors);
        } else {
            Yii::$app->session->setFlash('success', "Thank you for your message!");
        }

        return $it->redirect(Yii::$app->request->referrer);
    }
    
    public static function dynamicPage($it){
        $params = Yii::$app->params;
        $cmsModel = Slugs('page', $params);
        $url = explode('/', Yii::$app->request->url);
        $this_page = end($url);

        if(isset($cmsModel) and count($cmsModel) > 0)
        foreach($cmsModel as $row){
            if($row['slug_all'][strtoupper(Yii::$app->language)] == $this_page){
                $page_data = Cms::pageBySlug($this_page);
                if(isset($page_data) and isset($page_data['custom_settings']) and isset($page_data['custom_settings'][strtoupper(Yii::$app->language)]) and count($page_data['custom_settings'][strtoupper(Yii::$app->language)]) > 0)
                foreach($page_data['custom_settings'][strtoupper(Yii::$app->language)] as $custom_keys){
                    if($custom_keys['key'] == 'page_template'){
                        $page_template = $custom_keys['value'];
                    }
                }
            }
        }
        if(isset($page_template)){
            $ret = $it->render($page_template, [
                'page_data' => $page_data
            ]);
            return $ret;
        }

        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }
}
 