<?php

namespace optima\models;

use Yii;
use yii\base\Model;
use optima\models\Cms;

class ContactUs extends Model
{

    public $name;
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $call_remember;
    public $message;
    public $redirect_url;
    public $reference;
    public $verifyCode;

    public function rules()
    {
        return [
            [['name', 'phone', 'call_remember', 'redirect_url', 'reference'], 'safe'],
            [['first_name', 'last_name', 'email', 'message'], 'required'],
            ['email', 'email'],
            ['verifyCode', 'captcha'],
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            'verifyCode' => 'Verification Code',
        ];
    }

    public function sendMail()
    {
        $settings = Cms::settings();
        if ($this->validate() && isset($settings['general_settings']['admin_email']) && $settings['general_settings']['admin_email'] != '')
        {
            Yii::$app->mailer->compose('mail', ['model' => $this]) // a view rendering result becomes the message body here
                    ->setFrom(Yii::$app->params['from_email'])
                    ->setTo($settings['general_settings']['admin_email'])
                    ->setSubject('Contact')
                    ->send();
            $this->saveAccount();
            return true;
        }
        else
        {
            return false;
        }
    }

    public function saveAccount()
    {
        if (!$this->name)
            $this->name = $this->first_name . ' ' . $this->last_name;
        $post_items = [];
        foreach ($this as $key => $value)
        {
            $post_items[] = $key . '=' . $value;
        }

        $post_string = implode('&', $post_items);
        $post_string .= '&source=web-client';

        $url=Yii::$app->params['apiUrl']."accounts/index&user=" . Yii::$app->params['user'];
        $curl_connection = curl_init($url);

        curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl_connection, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
        curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
        curl_exec($curl_connection);
        curl_errno($curl_connection) . '-' . curl_error($curl_connection);
        curl_close($curl_connection);
    }

}
