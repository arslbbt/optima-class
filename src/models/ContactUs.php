<?php

namespace optima\models;

use Yii;
use yii\base\Model;
use optima\models\Cms;

class ContactUs extends Model {

    public $name;
    public $first_name;
    public $last_name;
    public $lead_status;
    public $email;
    public $phone;
    public $call_remember;
    public $message;
    public $redirect_url;
    public $attach;
    public $reference;
    public $verifyCode;
    public $transaction;
    public $property_type;
    public $bedrooms;
    public $bathrooms;
    public $swimming_pool;
    public $address;
    public $house_area;
    public $plot_area;
    public $price;
    public $price_reduced;
    public $close_to_sea;
    public $sea_view;
    public $exclusive_property;
    public $to_email;
    public $owner;
    public $source;
    public $accept_cookie;
    public $get_updates;
    public $html_content;
    public $booking_period;
    public $guests;
    public $transaction_types;
    public $subscribe;
    public $booking_enquiry;

    public function rules() {
        return [
                [['name', 'phone', 'call_remember', 'to_email', 'html_content', 'source', 'owner', 'lead_status', 'redirect_url', 'attach', 'reference', 'transaction', 'property_type', 'bedrooms', 'bathrooms', 'swimming_pool', 'address', 'house_area', 'plot_area', 'price', 'price_reduced', 'close_to_sea', 'sea_view', 'exclusive_property', 'accept_cookie', 'get_updates', 'booking_period', 'guests', 'transaction_types', 'subscribe', 'booking_enquiry'], 'safe'],
                [['first_name', 'last_name', 'email', 'message'], 'required'],
                ['email', 'email'],
                [['verifyCode'], 'captcha', 'when' => function($model) {
                    if ($model->verifyCode == 'null') {
                        $return = false;
                    } else {
                        $return = true;
                    }
                    return $return;
                }],
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels() {
        return [
            'verifyCode' => 'Verification Code',
            'first_name' => Yii::t('app', strtolower('First Name')),
            'last_name' => Yii::t('app', strtolower('Last Name')),
            'email' => Yii::t('app', strtolower('Email')),
            'message' => Yii::t('app', strtolower('Message')),
        ];
    }

    public function sendMail() {
        $settings = Cms::settings();
        if ($this->validate() && isset($settings['general_settings']['admin_email']) && $settings['general_settings']['admin_email'] != '') {
            if (isset($this->attach) && $this->attach == 1) {
                $webroot = Yii::getAlias('@webroot');
                if (is_dir($webroot . '/uploads/pdf')) {
                    Yii::$app->mailer->compose('mail', ['model' => $this]) // a view rendering result becomes the message body here
                            ->setFrom(Yii::$app->params['from_email'])
                            ->setTo($settings['general_settings']['admin_email'])
                            ->setSubject('Web enquiry')
                            ->attach($webroot . '/uploads/pdf/property.pdf')
                            ->send();
                    Yii::$app->mailer->compose()
                            ->setFrom(Yii::$app->params['from_email'])
                            ->setTo($this->email)
                            ->setSubject('Thank you for contacting us')
                            ->setHtmlBody(isset($settings['email_response'][strtoupper(\Yii::$app->language)]) ? $settings['email_response'][strtoupper(\Yii::$app->language)] : 'Thank you for contacting us')
                            ->attach($webroot . '/uploads/pdf/property.pdf')
                            ->send();
                }
            } else if (isset($this->subscribe) && $this->subscribe == 1) {
                Yii::$app->mailer->compose('mail', ['model' => $this]) // a view rendering result becomes the message body here
                        ->setFrom(Yii::$app->params['from_email'])
                        ->setTo($settings['general_settings']['admin_email'])
                        ->setSubject('Subscribing newsletter Email')
                        ->setHtmlBody($this->email . ' would like to be added to your newsletters')
                        ->send();
                Yii::$app->mailer->compose()
                        ->setFrom(Yii::$app->params['from_email'])
                        ->setTo($this->email)
                        ->setSubject('Thank you for contacting us')
                        ->setHtmlBody(isset($settings['email_response'][strtoupper(\Yii::$app->language)]) ? $settings['email_response'][strtoupper(\Yii::$app->language)] : 'Thank you for Subscribing')
                        ->send();
            } else if (isset($this->booking_enquiry) && $this->booking_enquiry == 1) {
                $html = '';
                if(isset($this->first_name) && $this->first_name != '')
                {
                    $html .= 'First Name: ' . $this->first_name;
                }
                if(isset($this->last_name) && $this->last_name != '')
                {
                    $html .= '<br>';
                    $html .= 'Last Name : ' . $this->last_name;
                }
                if(isset($this->email) && $this->email != '')
                {
                    $html .= '<br>';
                    $html .= 'Email: ' . $this->email;
                }
                if(isset($this->guests) && $this->guests != '')
                {
                    $html .= '<br>';
                    $html .= 'Guests: ' . $this->guests;
                }
                if(isset($this->message) && $this->message != '')
                {
                    $html .= '<br>';
                    $html .= 'Message: ' . $this->message;
                }
                $call_rememeber = '';
                if (isset($this->call_remember) && $this->call_remember == 0) {
                    $call_rememeber = '9:00 to 18:00';
                } else if (isset($this->call_remember) && $this->call_remember == 'After 18:00') {
                    $call_rememeber = 'After 18:00';
                }
                Yii::$app->mailer->compose('mail', ['model' => $this]) // a view rendering result becomes the message body here
                        ->setTo($settings['general_settings']['admin_email'])
                        ->setTo('sirajulhaq363@gmail.com')
                        ->setSubject('Booking Enquiry')
                        ->setHtmlBody($html)
                        ->send();
                Yii::$app->mailer->compose()
                        ->setFrom(Yii::$app->params['from_email'])
                        ->setTo($this->email)
                        ->setSubject('Thank you for contacting us')
                        ->setHtmlBody(isset($settings['email_response'][strtoupper(\Yii::$app->language)]) ? $settings['email_response'][strtoupper(\Yii::$app->language)] : 'Thank you for Subscribing')
                        ->send();
                $this->saveAccount();
            } else {
                Yii::$app->mailer->compose('mail', ['model' => $this]) // a view rendering result becomes the message body here
                        ->setFrom(Yii::$app->params['from_email'])
                        ->setTo($settings['general_settings']['admin_email'])
                        ->setSubject(isset($setting['email_response_subject'][0])?$setting['email_response_subject'][0]['key']:'Web enquiry')
                        ->send();
                Yii::$app->mailer->compose()
                        ->setFrom(Yii::$app->params['from_email'])
                        ->setTo($this->email)
                        ->setSubject('Thank you for contacting us')
                        ->setHtmlBody(isset($settings['email_response'][strtoupper(\Yii::$app->language)]) ? $settings['email_response'][strtoupper(\Yii::$app->language)] : 'Thank you for contacting us')
                        ->send();
                $this->saveAccount();
            }

            return true;
        } else {
            return false;
        }
    }

    public function saveAccount() {
        $call_rememeber = '';
        if (isset($this->call_remember) && $this->call_remember == 0) {
            $call_rememeber = '9:00 to 18:00';
        } else if (isset($this->call_remember) && $this->call_remember == 'After 18:00') {
            $call_rememeber = 'After 18:00';
        }
        if ($this->owner)
            $url = Yii::$app->params['apiUrl'] . "owners/index&user=" . Yii::$app->params['user'];
        else
            $url = Yii::$app->params['apiUrl'] . "accounts/index&user=" . Yii::$app->params['user'];
        $fields = array(
            'forename' => urlencode($this->first_name),
            'surname' => urlencode($this->last_name),
            'email' => urlencode($this->email),
            'source' => isset($this->source) ? $this->source : urlencode('web-client'),
            'lead_status' => isset($this->lead_status) ? $this->lead_status : '1001',
            'message' => urlencode($this->message),
            'phone' => urlencode($this->phone),
            'property' => isset($this->reference) ? $this->reference : null,
            'transaction_types' => isset($this->transaction_types) ? $this->transaction_types : '',
            'to_email' => isset($settings['general_settings']['admin_email']) ? $settings['general_settings']['admin_email'] : '',
            'html_content' => isset($this->html_content) ? $this->html_content : '',
            'comments' => isset($call_rememeber) && $call_rememeber != '' ? $call_rememeber : (isset($this->guests) ? 'Number of Guests: ' . $this->guests : ''),
        );
        $curl = new \linslin\yii2\curl\Curl();
        $response = $curl->setPostParams($fields)->post($url);
    }

}
