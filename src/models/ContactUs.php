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
    public $lead_status;
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
            [['name', 'phone', 'call_remember', 'redirect_url', 'reference','lead_status'], 'safe'],
            [['first_name', 'last_name', 'email', 'message'], 'required'],
            ['email', 'email'],
            [['verifyCode'], 'captcha', 'when' => function($model)
                {
                    if ($model->verifyCode == 'null')
                    {
                            $return = false;
                    }
                    else
                    {
                        $return = true;
                    }
                    return $return;
                }],
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
            ->setSubject('Fcs contact us email')
            ->send();

            Yii::$app->mailer->compose()
            ->setFrom(Yii::$app->params['from_email'])
            ->setTo($this->email)
            ->setSubject('Thank you for contacting us')
            ->setHtmlBody(isset($settings['email_response'][\Yii::$app->language])?$settings['email_response'][\Yii::$app->language]:'Thank you for contacting us')
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
    $url=Yii::$app->params['apiUrl']."accounts/index&user=" . Yii::$app->params['user'];
    $fields = array(
        'forename' => urlencode($this->first_name),
        'surname' => urlencode($this->last_name),
        'email' => urlencode($this->email),
        'source' => urlencode('web-client'),
        'lead_status'=>isset($this->lead_status)?$this->lead_status:'1001',
        'message' => urlencode($this->message),
        'phone' => urlencode($this->phone)
    );
    $fields_string='';
    foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
    rtrim($fields_string, '&');

    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_POST, count($fields));
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

    $result = curl_exec($ch);
    curl_close($ch);
}

}
