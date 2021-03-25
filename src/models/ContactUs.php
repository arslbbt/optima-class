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
    public $home_phone;
    public $call_remember;
    public $message;
    public $redirect_url;
    public $url;
    public $attach;
    public $reference;
    public $agency_set_ref;
    public $verifyCode;
    public $transaction;
    public $property_type;
    public $bedrooms;
    public $bathrooms;
    public $pool;
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
    public $accept_cookie_text;
    public $accept_cookie;
    public $get_updates;
    public $html_content;
    public $booking_period;
    public $guests;
    public $transaction_types;
    public $subscribe;
    public $booking_enquiry;
    public $sender_first_name;
    public $sender_last_name;
    public $sender_email;
    public $sender_phone;
    public $assigned_to;
    public $news_letter;
    public $arrival_date;
    public $buy_price_from;
    public $buy_price_to;
    public $ltrent_price_from;
    public $ltrent_price_to;
    public $strent_price_from;
    public $strent_price_to;
    public $departure_date;
    public $contact_check_1;
    public $contact_check_2;
    public $contact_check_3;
    public $gdpr_status;
    public $cv_file;
    public $language;
    public $listing_agency_email;
    public $buyer;
    public $mobile_phone;
    public $lgroups;
    public $reCaptcha;
    public $reCaptcha3;
    public $resume;
    public $application;
    public $feet_setting;
    public $feet_views;
    public $sub_types;
    public $feet_categories;
    public $parking;
    public $office;
    public $p_type;
    public $year_built_from;
    public $year_built_to;
    public $built_size_from;
    public $built_size_to;
    public $plot_size_from;
    public $plot_size_to;
    public $usefull_area_from;
    public $usefull_area_to;
    public $building_style;
    public $gated_comunity;
    public $elevator;
    public $settings;
    public $orientation;
    public $views;
    public $garden;
    public $only_golf_properties;
    public $only_off_plan;
    public $buy_from_date;
    public $condition;
    public $countries;
    public $regions;
    public $provinces;
    public $cities;
    public $locations;
    public $urbanization;
    public $furniture;
    public $occupancy_status;
    public $legal_status;
    public $total_floors;
    public $mooring_type;
    public $only_projects;
    public $only_holiday_homes;
    public $only_bank_repossessions;
    public $own;
    public $custom_categories;
    public $min_sleeps;
    public $id_number;
    public $country;
    public $postal_code;
    public $infants;
    public $appt;
    public $visit_date;

    const SCENARIO_V3 = 'v3validation';

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_V3] = ['reCaptcha3'];
        return $scenarios;
    }

    public function rules()
    {
        return [
            [['name', 'mobile_phone', 'phone', 'home_phone' , 'office', 'infants', 'call_remember', 'appt', 'visit_date', 'to_email', 'html_content', 'source', 'owner', 'lead_status', 'language', 'parking', 'redirect_url', 'attach', 'postal_code', 'reference', 'transaction', 'property_type', 'bedrooms', 'bathrooms', 'pool', 'address', 'house_area', 'plot_area', 'price', 'price_reduced', 'close_to_sea', 'sea_view', 'exclusive_property', 'accept_cookie', 'accept_cookie_text', 'get_updates', 'booking_period', 'guests', 'transaction_types', 'subscribe', 'booking_enquiry', 'sender_first_name', 'sender_last_name', 'sender_email', 'sender_phone', 'assigned_to', 'news_letter', 'arrival_date', 'buy_price_from', 'country', 'buy_price_to', 'ltrent_price_from', 'ltrent_price_to', 'strent_price_from', 'strent_price_to', 'departure_date', 'contact_check_1', 'contact_check_2', 'contact_check_3', 'resume', 'application', 'cv_file', 'gdpr_status', 'buyer', 'listing_agency_email', 'lgroups', 'feet_setting', 'feet_views', 'sub_types', 'feet_categories', 'p_type', 'year_built_from', 'year_built_to', 'plot_size_from', 'plot_size_to', 'built_size_from', 'built_size_to', 'usefull_area_from', 'usefull_area_to', 'building_style', 'gated_comunity', 'elevator', 'settings', 'orientation', 'views', 'garden', 'only_golf_properties', 'only_off_plan', 'buy_from_date', 'countries', 'regions', 'provinces', 'cities', 'locations', 'urbanization', 'furniture', 'condition', 'occupancy_status', 'legal_status', 'total_floors', 'mooring_type', 'only_projects', 'only_holiday_homes', 'only_bank_repossessions', 'own', 'min_sleeps', 'id_number', 'custom_categories'], 'safe'],
            ['first_name', 'required', 'message' => Yii::t('app', 'first name cannot be blank.')],
            ['last_name', 'required', 'message' => Yii::t('app', 'last name cannot be blank.')],
            ['email', 'required', 'message' => Yii::t('app', 'email cannot be blank.')],
            ['message', 'required', 'message' => Yii::t('app', 'message cannot be blank.')],
            ['verifyCode', 'required', 'message' => Yii::t('app', 'the verification code is incorrect.')],
            ['accept_cookie', 'required', 'on' => 'toAcceptCookie'],
            ['email', 'email'],
            [['resume', 'application'], 'file', 'skipOnEmpty' => true, 'extensions' => 'jpg, png, pdf, txt'],
            [['cv_file'], 'file', 'skipOnEmpty' => true],

            [
                ['reCaptcha'], isset(Yii::$app->params['recaptcha_secret_site_key']) ? \himiklab\yii2\recaptcha\ReCaptchaValidator2::className() : 'safe',
                'when' => function ($model) {
                    if (!isset(Yii::$app->params['recaptcha_secret_site_key']) || $model->reCaptcha == 'nulll') {
                        $return = false;
                    } else {
                        $return = true;
                    }
                    return $return;
                }
            ],

            [
                ['reCaptcha3'], \himiklab\yii2\recaptcha\ReCaptchaValidator3::className(),
                'secret' => isset(Yii::$app->params['recaptcha_v3_secret_key']) ? Yii::$app->params['recaptcha_v3_secret_key'] : "6LfdYakZAAAAAFHMsVwjMmZaNCCJo-jqdVDx2uxl",
                'threshold' => isset(Yii::$app->params['threshold']) ? Yii::$app->params['threshold'] : 0.8,
                'action' => 'captchaloaded',
                'on' => self::SCENARIO_V3
            ],

            [['verifyCode'], 'captcha', 'when' => function ($model) {
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
    public function attributeLabels()
    {
        return [
            'verifyCode' => Yii::t('app', strtolower('Verification Code')),
            'first_name' => Yii::t('app', strtolower('First Name')),
            'last_name' => Yii::t('app', strtolower('Last Name')),
            'email' => Yii::t('app', strtolower('Email')),
            'message' => Yii::t('app', strtolower('Message')),
        ];
    }

    public function uploadvalidate()
    {
        if ($this->cv_file != '') {
            $this->cv_file->saveAs('uploads/' . $this->cv_file->baseName . '.' . $this->cv_file->extension);
        }
        // if ($this->validate()) {
        //     $this->cv_file->saveAs('uploads/' . $this->cv_file->baseName . '.' . $this->cv_file->extension);
        //     return true;
        // } else {
        //     return false;
        // }
    }

    public function sendMail()
    {
        //if you wanna pass email with name format will me:  Your Name[Your@email.address]
        $settings = Cms::settings();
        if (isset($settings['general_settings']['admin_email']) && $settings['general_settings']['admin_email'] != '') {
            $ae_array = explode(',', $settings['general_settings']['admin_email']);

            foreach ($ae_array as $k) {
                $arr = explode('[', $k);

                if (count($arr) > 0) {
                    if (isset($arr[1])) {
                        $formatted_email = true;
                        $ae_arr = [str_replace(']', '', $arr[1]) => $arr[0]];
                        break;
                    }
                }
            }
            if (isset($formatted_email)) {
                $ae_array = $ae_arr;
            }
        }
        if (isset($ae_array) && is_array($ae_array)) {
            $from_email = $ae_array;
        } elseif (isset($ae_array[0])) {
            $from_email = trim($ae_array[0]);
        } else {
            $ae_array = explode(',', Yii::$app->params['from_email']);
            $from_email = Yii::$app->params['from_email'];
        }

        if ($this->validate() && isset($ae_array)) {
            if (isset($this->attach) && $this->attach == 1) {
                $webroot = Yii::getAlias('@webroot');
                if (is_dir($webroot . '/uploads/pdf')) {

                    Yii::$app->mailer->compose()
                        ->setFrom($from_email)
                        ->setTo($this->email)
                        ->setSubject('Thank you for contacting us')
                        ->setHtmlBody(isset($settings['email_response'][strtoupper(\Yii::$app->language)]) ? $settings['email_response'][strtoupper(\Yii::$app->language)] : 'Thank you for contacting us')
                        ->attach($webroot . '/uploads/pdf/property.pdf')
                        ->send();
                    Yii::$app->mailer->compose('mail', ['model' => $this]) // a view rendering result becomes the message body here
                        ->setFrom(isset($this->email) ? $this->email : '')
                        ->setTo(isset($ae_array) ? $ae_array : '')
                        ->setSubject(isset($settings['email_response_subject'][strtoupper(\Yii::$app->language)]) ? $settings['email_response_subject'][strtoupper(\Yii::$app->language)] : (isset($settings['email_response_subject'][0]) ? $settings['email_response_subject'][0]['key'] : 'Web enquiry'))
                        ->send();
                    $this->saveAccount();
                    if (isset($this->sender_first_name) || isset($this->sender_last_name) || isset($this->sender_email) || isset($this->sender_phone)) {
                        Yii::$app->mailer->compose('mail', ['model' => $this]) // a view rendering result becomes the message body here
                            ->setFrom(isset($ae_array[0]) ? trim($ae_array[0]) : Yii::$app->params['from_email'])
                            ->setTo($this->sender_email)
                            ->setSubject('Suggested property')
                            ->attach($webroot . '/uploads/pdf/property.pdf')
                            ->send();
                        $this->saveSenderAccount();
                    }
                }
            } else if (isset($this->subscribe) && $this->subscribe == 1) {
                $subscribe_msg = '';
                $subscribe_subject = '';
                $logo = 'https://my.optima-crm.com/uploads/cms_settings/' . $settings['_id'] . '/' . $settings['header']['logo']['name'];
                foreach ($settings['custom_settings'] as $setting) {
                    if ($setting['key'] == 'subscribe') {
                        $subscribe_msg = \Yii::t('app', $setting['value']);
                    }
                    if ($setting['key'] == 'newsletter_subject') {
                        $subscribe_subject = \Yii::t('app', $setting['value']);
                    }
                }
                $htmlBody = $subscribe_msg . '<br><br><br><br> <img style="width:40%" src=' . $logo . '> ';
                $email_response = isset($settings['email_response'][strtoupper(\Yii::$app->language)]) ? $settings['email_response'][strtoupper(\Yii::$app->language)] : 'Thank you for Subscribing';

                Yii::$app->mailer->compose('mail', ['model' => $this]) // a view rendering result becomes the message body here
                    ->setFrom(isset($this->email) ? $this->email : '')
                    ->setTo(isset($ae_array) ? $ae_array : '')
                    ->setSubject('Subscribing newsletter Email')
                    ->setHtmlBody($this->email . ' would like to be added to your newsletters')
                    ->send();
                Yii::$app->mailer->compose()
                    ->setFrom(isset($ae_array[0]) ? trim($ae_array[0]) : Yii::$app->params['from_email'])
                    ->setTo($this->email)
                    ->setSubject($subscribe_subject != '' ? $subscribe_subject : 'Thank you for contacting us')
                    ->setHtmlBody($subscribe_msg != '' ? $htmlBody : $email_response)
                    ->send();
                $this->saveAccount();
            } else if (isset($this->booking_enquiry) && $this->booking_enquiry == 1) {
                $html = '';
                if (isset($this->first_name) && $this->first_name != '') {
                    $html .= 'First Name: ' . $this->first_name;
                }
                if (isset($this->last_name) && $this->last_name != '') {
                    $html .= '<br>';
                    $html .= 'Last Name : ' . $this->last_name;
                }
                if (isset($this->email) && $this->email != '') {
                    $html .= '<br>';
                    $html .= 'Email: ' . $this->email;
                }
                if (isset($this->phone) && $this->phone != '') {
                    $html .= '<br>';
                    $html .= 'Phone: ' . $this->phone;
                }
                if (isset($this->language) && $this->language != '') {
                    $html .= '<br>';
                    $html .= 'Language: ' . $this->language;
                }
                if (isset($this->agency_set_ref) && $this->agency_set_ref != '') {
                    $html .= '<br>';
                    $html .= 'Prop. Ref : ' . $this->agency_set_ref;
                }
                if (!(isset($this->agency_set_ref) && $this->agency_set_ref != '') && isset($this->reference) && $this->reference != '') {
                    $html .= '<br>';
                    $html .= 'Prop.Ref : ' . $this->reference;
                }
                if (isset($this->arrival_date) && $this->arrival_date != '') {
                    $html .= '<br>';
                    $html .= 'Arrival Date : ' . $this->arrival_date;
                }
                if (isset($this->departure_date) && $this->departure_date != '') {
                    $html .= '<br>';
                    $html .= 'Departure Date : ' . $this->departure_date;
                }
                if (isset($this->guests) && $this->guests != '') {
                    $html .= '<br>';
                    $html .= 'Guests: ' . $this->guests;
                }
                if (isset($this->message) && $this->message != '') {
                    $html .= '<br>';
                    $html .= 'Message: ' . $this->message;
                }

                if (isset($this->html_content) && $this->html_content != '') {
                    $html .= '<br>';
                    $html .= 'Price: ' . $this->html_content;
                }
                $call_rememeber = '';
                if (isset($this->call_remember) && $this->call_remember == 0) {
                    $call_rememeber = '9:00 to 18:00';
                } else if (isset($this->call_remember) && $this->call_remember == 'After 18:00') {
                    $call_rememeber = 'After 18:00';
                }
                Yii::$app->mailer->compose('mail', ['model' => $this]) // a view rendering result becomes the message body here
                    ->setFrom(isset($this->email) ? $this->email : '')
                    ->setTo(isset($ae_array) ? $ae_array : '')
                    ->setSubject('Booking Enquiry')
                    ->setHtmlBody($html)
                    ->send();
                Yii::$app->mailer->compose()
                    ->setFrom(isset($ae_array[0]) ? trim($ae_array[0]) : Yii::$app->params['from_email'])
                    ->setTo($this->email)
                    ->setSubject('Thank you for contacting us')
                    ->setHtmlBody(isset($settings['email_response'][strtoupper(\Yii::$app->language)]) ? $settings['email_response'][strtoupper(\Yii::$app->language)] : 'Thank you for Subscribing')
                    ->send();
                $this->saveAccount();
            } elseif (isset($_GET['ContactUs']['file_link'])) {
                $file = $_GET['ContactUs']['file_link'];
                $subscribe_subject = '';
                $lngn = 0; //isset(\Yii::$app->language)&& strtoupper(\Yii::$app->language)=='ES'?1:0;

                foreach ($settings['custom_settings'] as $setting) {
                    if (isset($setting['key']) && $setting['key'] == 'enquiry_subject') {
                        $subscribe_subject = \Yii::t('app', $setting['value']);
                    }
                }
                $htmlBody = '';
                if (isset($settings['email_response'][strtoupper(\Yii::$app->language)])) {
                    $htmlBody = $settings['email_response'][strtoupper(\Yii::$app->language)];
                    if ($this->reference != '') {
                        $htmlBody = '<br>' . \Yii::t('app', strtolower('Enquiry about property')) . ' (' . \Yii::t('app', strtolower('Ref')) . ' : ' . $this->reference . ')<br><br>' . $htmlBody;
                    }
                }
                Yii::$app->mailer->compose('mail', ['model' => $this]) // a view rendering result becomes the message body here
                    ->setFrom(isset($this->email) ? $this->email : '')
                    ->setTo(isset($ae_array) ? $ae_array : '')
                    ->setCc(isset($this->listing_agency_email) && $this->listing_agency_email != '' ? $this->listing_agency_email : [])
                    ->setSubject(isset($settings['email_response_subject'][strtoupper(\Yii::$app->language)]) ? $settings['email_response_subject'][strtoupper(\Yii::$app->language)] : (isset($settings['email_response_subject'][0]) ? $settings['email_response_subject'][0]['key'] : 'Web enquiry'))
                    ->send();
                Yii::$app->mailer->compose()
                    ->setFrom($from_email)
                    ->setTo($this->email)
                    ->setSubject(isset($settings['email_response_subject'][strtoupper(\Yii::$app->language)]) ? $settings['email_response_subject'][strtoupper(\Yii::$app->language)] : (isset($settings['email_response_subject'][0]) ? $settings['email_response_subject'][0]['key'] : 'Thank you for contacting us'))
                    ->setHtmlBody(isset($htmlBody) && isset($_GET['ContactUs']['file_link']) ? "<a href=" . $_GET['ContactUs']['file_link'] . ">Download File</a><br>" .  $htmlBody : 'Thank you for contacting us')
                    ->send();
                $this->saveAccount();

                if (isset($this->sender_first_name) || isset($this->sender_last_name) || isset($this->sender_email) || isset($this->sender_phone))
                    $this->saveSenderAccount();
            } else {
                $subscribe_subject = '';
                $lngn = 0; //isset(\Yii::$app->language)&& strtoupper(\Yii::$app->language)=='ES'?1:0;
                if (isset($settings['custom_settings'])) {
                    foreach ($settings['custom_settings'] as $setting) {
                        if (isset($setting['key']) && $setting['key'] == 'enquiry_subject') {
                            $subscribe_subject = \Yii::t('app', $setting['value']);
                        }
                    }
                }
                if (isset($settings['email_response'][strtoupper(\Yii::$app->language)])) {
                    $htmlBody = $settings['email_response'][strtoupper(\Yii::$app->language)];
                    if ($this->reference != '') {
                        $htmlBody = '<br>' . \Yii::t('app', strtolower('Enquiry about property')) . ' (' . \Yii::t('app', strtolower('Ref')) . ' : ' . $this->reference . ')<br><br>' . $htmlBody;
                    }
                }
                Yii::$app->mailer->compose('mail', ['model' => $this]) // a view rendering result becomes the message body here
                    ->setFrom(isset($this->email) ? $this->email : '')
                    ->setTo(isset($ae_array) ? $ae_array : '')
                    ->setCc(isset($this->listing_agency_email) && $this->listing_agency_email != '' ? $this->listing_agency_email : [])
                    ->setSubject(isset($settings['email_response_subject'][strtoupper(\Yii::$app->language)]) ? $settings['email_response_subject'][strtoupper(\Yii::$app->language)] : (isset($settings['email_response_subject'][0]) ? $settings['email_response_subject'][0]['key'] : 'Web enquiry'))
                    ->send();
                Yii::$app->mailer->compose()
                    ->setFrom($from_email)
                    ->setTo($this->email)
                    ->setSubject(isset($settings['email_response_subject'][strtoupper(\Yii::$app->language)]) ? $settings['email_response_subject'][strtoupper(\Yii::$app->language)] : (isset($settings['email_response_subject'][0]) ? $settings['email_response_subject'][0]['key'] : 'Thank you for contacting us'))
                    ->setHtmlBody(isset($htmlBody) ? $htmlBody : 'Thank you for contacting us')
                    ->send();
                $this->saveAccount();

                if (isset($this->sender_first_name) || isset($this->sender_last_name) || isset($this->sender_email) || isset($this->sender_phone))
                    $this->saveSenderAccount();
            }

            return true;
        } else {
            return false;
        }
    }

    public function saveAccount()
    {
        $settings = Cms::settings();

        $call_rememeber = '';
        if (isset($this->call_remember) && $this->call_remember == '9:00 to 18:00') {
            $call_rememeber = 'call me back:  9:00 to 18:00';
        } else if (isset($this->call_remember) && $this->call_remember == 'After 18:00') {
            $call_rememeber = 'call me back: After 18:00';
        } else if (isset($this->call_remember) && $this->call_remember != '') {
            $call_rememeber = $this->call_remember;
        }
        if ($this->owner)
            $url = Yii::$app->params['apiUrl'] . "owners/index&user=" . Yii::$app->params['user'];
        else
            $url = Yii::$app->params['apiUrl'] . "accounts/index&user=" . Yii::$app->params['user'];
        $fields = array(
            'forename' => $this->first_name,
            'surname' => $this->last_name,
            'email' => $this->email,
            'office' => $this->office,
            'gdpr_status' => $this->gdpr_status,
            'source' => isset($this->source) ? $this->source : urlencode('web-client'),
            'lead_status' => isset($this->lead_status) ? $this->lead_status : '1001',
            'message' => $this->message,
            'phone' => $this->phone,
            'home_phone' => isset($this->home_phone) ? $this->home_phone : null ,
            'country ' => isset($this->country) ? $this->country : null,
            'appt'  => isset($this->appt) ? $this->appt : null,
            'date'  => isset($this->visit_date) ? $this->visit_date : null,
            'mobile_phone' => isset($this->mobile_phone) ? $this->mobile_phone : null,
            'id_number' => isset($this->id_number) ? $this->id_number : null,
            'min_sleeps' => isset($this->min_sleeps) ? $this->min_sleeps : null,
            'postal_code' => isset($this->postal_code) ? $this->postal_code : null,
            'address' => isset($this->address) ? $this->address : null,
            'property' => isset($this->reference) ? $this->reference : null,
            'newsletter' => isset($this->news_letter) && $this->news_letter == true ? $this->news_letter : false,
            'assigned_to' => isset($this->assigned_to) ? $this->assigned_to : null,
            'rent_from_date' => isset($this->arrival_date) ? $this->arrival_date : null,
            'rent_to_date' => isset($this->departure_date) ? $this->departure_date : null,
            'types' => isset($this->property_type) ? (is_array($this->property_type) ? implode(",", $this->property_type) : $this->property_type) : null,
            'p_type' => isset($this->p_type) ? $this->p_type : null,
            'min_bedrooms' => isset($this->bedrooms) ? $this->bedrooms : null,
            'min_bathrooms' => isset($this->bathrooms) ? $this->bathrooms : null,
            'budget_min' => isset($this->buy_price_from) && $this->buy_price_from != '' ? $this->buy_price_from : null,
            'budget_max' => isset($this->buy_price_to) && $this->buy_price_to != '' ? $this->buy_price_to : null,
            'long_term_Rent_price_low' => isset($this->ltrent_price_from) ? $this->ltrent_price_from : null,
            'long_term_Rent_price_high' => isset($this->ltrent_price_to) ? $this->ltrent_price_to : null,
            'st_budget_min' => isset($this->strent_price_from) ? $this->strent_price_from : null,
            'st_budget_max' => isset($this->strent_price_to) ? $this->strent_price_to : null,
            'transaction_types' => isset($this->transaction_types) ? (is_array($this->transaction_types) ? implode(",", $this->transaction_types) : $this->transaction_types) : null,
            'to_email' => isset($settings['general_settings']['admin_email']) ? $settings['general_settings']['admin_email'] : null,
            'html_content' => isset($this->html_content) ? $this->html_content : null,
            'lgroups' => isset($this->lgroups) ? (is_array($this->lgroups) ? implode(",", $this->lgroups) : $this->lgroups) : null,
            'comments' => isset($call_rememeber) && $call_rememeber != '' ? $call_rememeber : (isset($this->message) ?  $this->message : null),
            'language' => isset($this->language) ? $this->language : strtoupper(\Yii::$app->language),
            'sub_types' => isset($this->sub_types) ? (is_array($this->sub_types) ? implode(",", $this->sub_types) : $this->sub_types) : null,
            'feet_setting' => isset($this->feet_setting) ? (is_array($this->feet_setting) ? implode(",", $this->feet_setting) : $this->feet_setting) : null,
            'feet_categories' => isset($this->feet_categories) ? (is_array($this->feet_categories) ? implode(",", $this->feet_categories) : $this->feet_categories) : null,
            'custom_categories' => isset($this->custom_categories) ? (is_array($this->custom_categories) ? implode(",", $this->custom_categories) : $this->custom_categories) : null,
            'feet_views' => isset($this->feet_views) ? (is_array($this->feet_views) ? implode(",", $this->feet_views) : $this->feet_views) : null,
            'parking' => isset($this->parking) ? (is_array($this->parking) ? implode(",", $this->parking) : $this->parking) : null,
            'pool' => isset($this->pool) ? (is_array($this->pool) ? implode(",", $this->pool) : $this->pool) : null,
            'year_built_from' => isset($this->year_built_from) ? $this->year_built_from : null,
            'year_built_to' => isset($this->year_built_to) ? $this->year_built_to : null,
            'min_plot_size' => isset($this->plot_size_from) ? $this->plot_size_from : null,
            'plot_size_to' => isset($this->plot_size_to) ? $this->plot_size_to : null,
            'built_size_to' => isset($this->built_size_to) ? $this->built_size_to : null,
            'built_size_from' => isset($this->built_size_from) ? $this->built_size_from : null,
            'usefull_area_from' => isset($this->usefull_area_from) ? $this->usefull_area_from : null,
            'usefull_area_to' => isset($this->usefull_area_to) ? $this->usefull_area_to : null,
            'building_style' => isset($this->building_style) ? (is_array($this->building_style) ? implode(",", $this->building_style) : $this->building_style) : null,
            'gated_comunity' => isset($this->gated_comunity) ? $this->gated_comunity : null,
            'elevator' => isset($this->elevator) ? $this->elevator : null,
            'settings' => isset($this->settings) ? (is_array($this->settings) ? implode(",", $this->settings) : $this->settings) : null,
            'orientation' => isset($this->orientation) ? (is_array($this->orientation) ? implode(",", $this->orientation) : $this->orientation) : null,
            'views' => isset($this->views) ? (is_array($this->views) ? implode(",", $this->views) : $this->views) : null,
            'garden' => isset($this->garden) ? (is_array($this->garden) ? implode(",", $this->garden) : $this->garden) : null,
            'furniture' => isset($this->furniture) ? (is_array($this->furniture) ? implode(",", $this->furniture) : $this->furniture) : null,
            'condition' => isset($this->condition) ? (is_array($this->condition) ? implode(",", $this->condition) : $this->condition) : null,
            'only_golf_properties' => isset($this->only_golf_properties) ? $this->only_golf_properties : null,
            'only_off_plan' => isset($this->only_off_plan) ? $this->only_off_plan : null,
            'buy_from_date' => isset($this->buy_from_date) ? $this->buy_from_date : null,
            'countries' => isset($this->countries) ? (is_array($this->countries) ? implode(",", $this->countries) : $this->countries) : null,
            'regions' => isset($this->regions) ? (is_array($this->regions) ? implode(",", $this->regions) : $this->regions) : null,
            'provinces' => isset($this->provinces) ? (is_array($this->provinces) ? implode(",", $this->provinces) : $this->provinces) : null,
            'cities' => isset($this->cities) ? (is_array($this->cities) ? implode(",", $this->cities) : $this->cities) : null,
            'locations' => isset($this->locations) ? (is_array($this->locations) ? implode(",", $this->locations) : $this->locations) : null,
            'urbanization' => isset($this->urbanization) ? (is_array($this->urbanization) ? implode(",", $this->urbanization) : $this->urbanization) : null,
            'occupancy_status' => isset($this->occupancy_status) ? $this->occupancy_status : null,
            'legal_status' => isset($this->legal_status) ? $this->legal_status : null,
            'total_floors' => isset($this->total_floors) ? $this->total_floors : null,
            'only_projects' => isset($this->only_projects) ? $this->only_projects : null,
            'only_holiday_homes' => isset($this->only_holiday_homes) ? $this->only_holiday_homes : null,
            'only_bank_repossessions' => isset($this->only_bank_repossessions) ? $this->only_bank_repossessions : null,
            'mooring_type' => isset($this->mooring_type) ? (is_array($this->mooring_type) ? implode(",", $this->mooring_type) : $this->mooring_type) : null,
        );
        $curl = new \linslin\yii2\curl\Curl();
        $response = $curl->setPostParams($fields)->post($url);
        $res = json_decode($response);
        $owner_id = $res->_id;
        return $res->_id;
    }

    public function saveSenderAccount()
    {
        $settings = Cms::settings();

        $url = Yii::$app->params['apiUrl'] . "accounts/index&user=" . Yii::$app->params['user'];
        $fields = array(
            '
            ' => isset($this->sender_first_name) ? $this->sender_first_name : null,
            'surname' => isset($this->sender_last_name) ? $this->sender_last_name : null,
            'email' => isset($this->sender_email) ? $this->sender_email : null,
            'gdpr_status' => $this->gdpr_status,
            'source' => isset($this->source) ? $this->source : urlencode('web-client'),
            'lead_status' => isset($this->lead_status) ? $this->lead_status : '1001',
            'message' => $this->message,
            'phone' => isset($this->sender_phone) ? $this->sender_phone : null,
            'property' => isset($this->reference) ? $this->reference : null,
            'transaction_types' => isset($this->transaction_types) ? (is_array($this->transaction_types) ? implode(",", $this->transaction_types) : $this->transaction_types) : null,
            'to_email' => isset($settings['general_settings']['admin_email']) ? $settings['general_settings']['admin_email'] : null,
            'html_content' => isset($this->html_content) ? $this->html_content : null,
            'comments' => isset($call_rememeber) && $call_rememeber != '' ? $call_rememeber : (isset($this->guests) ? 'Number of Guests: ' . $this->guests : null),
        );
        $curl = new \linslin\yii2\curl\Curl();
        $response = $curl->setPostParams($fields)->post($url);
    }
}
