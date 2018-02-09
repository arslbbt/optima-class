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
    public $enquiry;
    public $redirect_url;

    public function rules()
    {
        return [
            [['name', 'phone', 'call_remember', 'redirect_url'], 'safe'],
            [['first_name', 'last_name', 'email', 'enquiry'], 'required'],
            ['email', 'email'],
        ];
    }

    public function sendMail()
    {
        $settings = Cms::settings();
        if (isset($settings['general_settings']['admin_email']) && $settings['general_settings']['admin_email'] != '')
        {
            Yii::$app->mailer->compose('mail', ['model' => $this]) // a view rendering result becomes the message body here
                    ->setFrom($this->email)
                    ->setTo($settings['general_settings']['admin_email'])
                    ->setSubject('Contact')
                    ->send();
            return true;
        }
        else
        {
            return false;
        }
    }

}
