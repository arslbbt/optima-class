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
            //$row['slug_all'][strtoupper(Yii::$app->language)];
            if(isset($row['slug_all']) and isset($row['slug_all'][strtoupper(Yii::$app->language)]) and $row['slug_all'][strtoupper(Yii::$app->language)] == $this_page){
                $page_data = Cms::pageBySlug($this_page);
                if(isset($page_data) and isset($page_data['custom_settings']) and isset($page_data['custom_settings'][strtoupper(Yii::$app->language)]) and count($page_data['custom_settings'][strtoupper(Yii::$app->language)]) > 0)
                foreach($page_data['custom_settings'][strtoupper(Yii::$app->language)] as $custom_keys){
                    if($custom_keys['key'] == 'page_template'){
                        $page_template = $custom_keys['value'];
                        
                    }
                    if($custom_keys['key'] == 'custom_post_id'){
                        $custom_post_id = $custom_keys['value'];   
                    }
                }
            }
        }
        /*if(isset($page_template)){
            $ret = $it->render($page_template, [
                'page_data' => $page_data
            ]);
            return $ret;
        }*/
        if(isset($page_template)){   
            try
            {
                $custom_post_id = Cms::postTypes($custom_post_id);
                $ret = $it->render($page_template, [
                    'page_data' => $page_data,
                    'custom_post_id' => $custom_post_id
                ]);
                return $ret;
            }
            catch (ViewNotFoundException $e)
            {
                //die;
            }
        }
        return $it->render('404', []);
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
    public static function getCRMData($url, $cache = true, $fields = array(), $auth = false){
        return Functions::getCurlData($url, $cache);
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $url = Yii::$app->params['apiUrl'] . 'cms/setting&user=' . Yii::$app->params['user'] . '&id=' . Yii::$app->params['template'];
            
            $file_data =
            //file_get_contents($url);
            Functions::getCurlData($url);

            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = 
            file_get_contents($file);
            //Functions::getCRMData($file);
        }
        return Functions::getCurlData($url, $cache);
    }
    public static function getCurlData($url, $cache = true, $fields = array(), $auth = false){
    
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_VERBOSE, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        
        if($auth){
            curl_setopt($curl, CURLOPT_USERPWD, "$auth");
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }
    
        if($fields){        
            $fields_string = http_build_query($fields);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $fields_string);
        }
        
        $response = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header_string = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        
        $header_rows = explode(PHP_EOL, $header_string);
        //$header_rows = array_filter($header_rows, trim);
        $i = 0;
        foreach((array)$header_rows as $hr){
            $colonpos = strpos($hr, ':');
            $key = $colonpos !== false ? substr($hr, 0, $colonpos) : (int)$i++;
            $headers[$key] = $colonpos !== false ? trim(substr($hr, $colonpos+1)) : $hr;
        }
        $j=0;
        foreach((array)$headers as $key => $val){
            $vals = explode(';', $val);
            if(count($vals) >= 2){
                unset($headers[$key]);
                foreach($vals as $vk => $vv){
                    $equalpos = strpos($vv, '=');
                    $vkey = $equalpos !== false ? trim(substr($vv, 0, $equalpos)) : (int)$j++;
                    $headers[$key][$vkey] = $equalpos !== false ? trim(substr($vv, $equalpos+1)) : $vv;
                }
            }
        }
        //print_rr($headers);
        curl_close($curl);
        // echo $body;
        // die;
        return $body;
    }
}
 