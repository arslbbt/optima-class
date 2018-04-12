<?php

namespace optima\models;

use Yii;
use yii\base\Model;
use optima\models\Cms;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class Properties extends Model {

    public static function findAll($query) {
        $lang = strtoupper(\Yii::$app->language);
        $query .= self::setQuery();
        $url = Yii::$app->params['apiUrl'] . 'properties&user=' . Yii::$app->params['user'] . $query;
        $JsonData = file_get_contents($url);
        $apiData = json_decode($JsonData);
        $settings = Cms::settings();

        $get = Yii::$app->request->get();
        /* to set the display price
         * transaction 1 = Rental
         * transaction 4 = Resale
         */
        $rent = false;
        $strent = false;
        $ltrent = false;
        $sale = true;
        if (isset($get["transaction"]) && $get["transaction"] != "") {
            if ($get["transaction"] == '1') {
                $rent = true;
            } elseif ($get["transaction"] == '5') {
                $rent = true;
                $strent = true;
            } elseif ($get["transaction"] == '6') {
                $rent = true;
                $ltrent = true;
            } else {
                $sale = true;
            }
        }

        $return_data = [];

        foreach ($apiData as $property) {
            $data = [];
            $features = [];
            if (isset($property->total_properties)) {
                $data['total_properties'] = $property->total_properties;
            }
            if (isset($property->property->_id)) {
                $data['_id'] = $property->property->_id;
            }
            if (isset($property->property->reference)) {
                $data['id'] = $property->property->reference;
            }
            if (isset($settings['general_settings']['reference']) && $settings['general_settings']['reference'] != 'reference') {
                $ref = $settings['general_settings']['reference'];
                $data['reference'] = $property->property->$ref;
            } else {
                $data['reference'] = $property->agency_code . '-' . $property->property->reference;
            }

            if (isset($property->property->title->$lang) && $property->property->title->$lang != '') {
                $data['title'] = $property->property->title->$lang;
            } elseif (isset($property->property->location)) {
                $data['title'] = isset($property->property->type_one)?\Yii::t('app', strtolower($property->property->type_one)):\Yii::t('app', 'N/A') . ' ' . \Yii::t('app', 'in') . ' ' . \Yii::t('app', $property->property->location);
            }

            if (isset($property->property->status)) {
                $data['status'] = $property->property->status;
            }
            if (isset($property->property->type_one)) {
                $data['type'] = $property->property->type_one;
            }
            $agency=Yii::$app->params['agency'];
            if (isset($property->property->latitude) && $property->property->latitude!='') {
                $data['lat'] = $property->property->latitude;
            }elseif(isset($property->property->private_info_object->$agency->latitude)){
                $data['lat']=$property->property->private_info_object->$agency->latitude;
            }

            if (isset($property->property->longitude) && $property->property->longitude!='') {
                $data['lng'] = $property->property->longitude;
            }elseif(isset($property->property->private_info_object->$agency->longitude)){
                $data['lng'] = $property->property->private_info_object->$agency->longitude;
            }
            if (isset($property->property->description->$lang)) {
                $data['description'] = $property->property->description->$lang;
            }
            if (isset($property->property->location)) {
                $data['location'] = $property->property->location;
            }
            if (isset($property->property->region)) {
                $data['region'] = $property->property->region;
            }
            if (isset($property->property->address_country)) {
                $data['country'] = $property->property->address_country;
            }
            if (isset($property->property->address_city)) {
                $data['city'] = $property->property->address_city;
            }
            if (isset($property->property->sale) && $property->property->sale == 1) {
                $data['sale'] = $property->property->sale;
            }
            if (isset($property->property->rent) && $property->property->rent == 1) {
                $data['rent'] = $property->property->rent;
            }
            if (isset($property->property->bedrooms) && $property->property->bedrooms > 0) {
                $data['bedrooms'] = $property->property->bedrooms;
            }
            if (isset($property->property->bathrooms) && $property->property->bathrooms > 0) {
                $data['bathrooms'] = $property->property->bathrooms;
            }
            if ($rent) {
                if ($ltrent && isset($property->property->lt_rental) && $property->property->lt_rental == true && isset($property->property->period_seasons->{'0'}->new_price)) {
                    $data['price'] = ($property->property->period_seasons->{'0'}->new_price!=0)?number_format((int) $property->property->period_seasons->{'0'}->new_price, 0, '', '.') . ' ' . Yii::t('app', 'per_month'):'';
                } elseif ($strent && isset($property->property->st_rental) && $property->property->st_rental == true && isset($property->property->rental_seasons)) {
                    $st_price=[];
                    foreach($property->property->rental_seasons as $seasons){
                        $st_price[]=['price'=>isset($seasons->new_price)?$seasons->new_price:'','period'=>isset($seasons->period)?$seasons->period:'','seasons'=>isset($seasons->seasons)?$seasons->seasons:''];
                    }
                    $data['price'] = ($st_price[0]['price']!=0)?number_format((int) $st_price[0]['price'], 0, '', '.') . ' ' . Yii::t('app', str_replace('_', ' ', (isset($st_price[0]['period'])?$st_price[0]['period']:''))):'';
                    $data['seasons'] = isset($st_price[0]['seasons'])?$st_price[0]['seasons']:'';
                }
            } else {
                if (isset($property->property->currentprice)) {
                    $data['price'] = ($property->property->currentprice!=0)?number_format((int) $property->property->currentprice, 0, '', '.'):'';
                }
            }
            if (isset($property->property->currentprice) && $property->property->currentprice > 0) {
                $data['currentprice'] = str_replace(',', '.', (number_format((int) ($property->property->currentprice))));
            }
            if (isset($property->property->oldprice->price) && $property->property->oldprice->price > 0) {
                $data['oldprice'] = str_replace(',', '.', (number_format((int) ($property->property->oldprice->price))));
            }
            if (isset($property->property->built) && $property->property->built > 0) {
                $data['built'] = $property->property->built;
            }
            if (isset($property->property->plot) && $property->property->plot > 0) {
                $data['plot'] = $property->property->plot;
            }
            if (isset($property->property->custom_categories)) {
                $data['categories'] = $property->property->custom_categories;
            }
            if (isset($property->property->terrace) && count($property->property->terrace) > 0 && isset($property->property->terrace->value) && $property->property->terrace->value > 0) {
                $data['terrace'] = $property->property->terrace->value;
            }
            if (isset($property->attachments) && count($property->attachments) > 0) {
                $attachments = [];
                foreach ($property->attachments as $pic) {
                    $attachments[] = Yii::$app->params['img_url'] . '/' . $pic->model_id . '/1200/' . $pic->file_md5_name;
                }
                $data['attachments'] = $attachments;
            }
            $categories = [];
            $features = [];
            $climate_control = [];
            $kitchen = [];
            $setting = [];
            $orientation = [];
            $views = [];
            $utilities = [];
            $security = [];
            $furniture = [];
            $parking = [];
            $garden = [];
            $pool = [];
            $condition = [];
            if (isset($property->property->feet_categories) && count($property->property->feet_categories) > 0) {
                foreach ($property->property->feet_categories as $key => $value) {
                    if ($value == true) {
                        $categories[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_features) && count($property->property->feet_features) > 0) {
                foreach ($property->property->feet_features as $key => $value) {
                    if ($value == true) {
                        $features[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_climate_control) && count($property->property->feet_climate_control) > 0) {
                foreach ($property->property->feet_climate_control as $key => $value) {
                    if ($value == true) {
                        $climate_control[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_kitchen) && count($property->property->feet_kitchen) > 0) {
                foreach ($property->property->feet_kitchen as $key => $value) {
                    if ($value == true) {
                        $kitchen[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_setting) && count($property->property->feet_setting) > 0) {
                foreach ($property->property->feet_setting as $key => $value) {
                    if ($value == true) {
                        $setting[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_orientation) && count($property->property->feet_orientation) > 0) {
                foreach ($property->property->feet_orientation as $key => $value) {
                    if ($value == true) {
                        $orientation[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_views) && count($property->property->feet_views) > 0) {
                foreach ($property->property->feet_views as $key => $value) {
                    if ($value == true) {
                        $views[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_utilities) && count($property->property->feet_utilities) > 0) {
                foreach ($property->property->feet_utilities as $key => $value) {
                    if ($value == true) {
                        $utilities[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_security) && count($property->property->feet_security) > 0) {
                foreach ($property->property->feet_security as $key => $value) {
                    if ($value == true) {
                        $security[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_furniture) && count($property->property->feet_furniture) > 0) {
                foreach ($property->property->feet_furniture as $key => $value) {
                    if ($value == true) {
                        $furniture[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_parking) && count($property->property->feet_parking) > 0) {
                foreach ($property->property->feet_parking as $key => $value) {
                    if ($value == true) {
                        $parking[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_garden) && count($property->property->feet_garden) > 0) {
                foreach ($property->property->feet_garden as $key => $value) {
                    if ($value == true) {
                        $garden[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_pool) && count($property->property->feet_pool) > 0) {
                foreach ($property->property->feet_pool as $key => $value) {
                    if ($value == true) {
                        $pool[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_condition) && count($property->property->feet_condition) > 0) {
                foreach ($property->property->feet_condition as $key => $value) {
                    if ($value == true) {
                        $condition[] = $key;
                    }
                }
            }
            $data['property_features'] = [];
            $data['property_features']['features'] = $features;
            $data['property_features']['categories'] = $categories;
            $data['property_features']['climate_control'] = $climate_control;
            $data['property_features']['kitchen'] = $kitchen;
            $data['property_features']['setting'] = $setting;
            $data['property_features']['orientation'] = $orientation;
            $data['property_features']['views'] = $views;
            $data['property_features']['utilities'] = $utilities;
            $data['property_features']['security'] = $security;
            $data['property_features']['parking'] = $parking;
            $data['property_features']['garden'] = $garden;
            $data['property_features']['pool'] = $pool;
            $data['property_features']['condition'] = $condition;
            $return_data[] = $data;
        }
        return $return_data;
    }

    public static function findOne($reference, $with_booking = false) {
        $ref = $reference;
        $lang = strtoupper(\Yii::$app->language);
        if (isset($with_booking) && $with_booking == true) {
            $url = Yii::$app->params['apiUrl'] . 'properties/view-by-ref&with_booking=true&user=' . Yii::$app->params['user'] . '&ref=' . $ref;
        } else
            $url = Yii::$app->params['apiUrl'] . 'properties/view-by-ref&user=' . Yii::$app->params['user'] . '&ref=' . $ref;
        $JsonData = file_get_contents($url);
        $property = json_decode($JsonData);

        $settings = Cms::settings();

        $return_data = [];
        $attachments = [];
        $floor_plans = [];
        $booked_dates = [];

        if (isset($property->property->_id)) {
            $return_data['_id'] = $property->property->_id;
        }
        if (isset($property->property->reference)) {
            $return_data['id'] = $property->property->reference;
        }

        if (isset($settings['general_settings']['reference']) && $settings['general_settings']['reference'] != 'reference') {
            $ref = $settings['general_settings']['reference'];
            $return_data['reference'] = $property->property->$ref;
        } else {
            $return_data['reference'] = $property->agency_code . '-' . $property->property->reference;
        }
        $title = 'title';
        $description = 'description';
        $price = 'sale';
        if (isset($property->property->rent) && $property->property->rent == true) {
            $title = 'rental_title';
            $description = 'rental_description';
            $price = 'rent';
        }
        if (isset($property->property->$title->$lang) && $property->property->$title->$lang != '') {
            $return_data['title'] = $property->property->$title->$lang;
        } else {
            $return_data['title'] = \Yii::t('app', strtolower($property->property->type_one)) . ' ' . \Yii::t('app', 'in') . ' ' . \Yii::t('app', $property->property->location);
        }
        if (isset($property->property->listing_agent)) {
            $return_data['listing_agent'] = $property->property->listing_agent;
        }        
        if (isset($property->property->property_name)) {
            $return_data['property_name'] = $property->property->property_name;
        }
        if (isset($property->property->latitude)) {
            $return_data['lat'] = $property->property->latitude;
        }
        if (isset($property->property->longitude)) {
            $return_data['lng'] = $property->property->longitude;
        }
        if (isset($property->property->bedrooms)) {
            $return_data['bedrooms'] = $property->property->bedrooms;
        }
        if (isset($property->property->bathrooms)) {
            $return_data['bathrooms'] = $property->property->bathrooms;
        }
        if (isset($property->property->currentprice)) {
            $return_data['currentprice'] = $property->property->currentprice;
        }

        if ($price == 'rent') {
            if (isset($property->property->st_rental) && $property->property->st_rental == true && isset($property->property->rental_seasons)) {
                $st_price=[];
                foreach($property->property->rental_seasons as $seasons){
                    $st_price[]=['price'=>isset($seasons->new_price)?$seasons->new_price:'','period'=>isset($seasons->period)?$seasons->period:'','seasons'=>isset($seasons->seasons)?$seasons->seasons:''];
                }
                $return_data['price'] = ($st_price[0]['price']!=0)?number_format((int) $st_price[0]['price'], 0, '', '.') . ' ' . Yii::t('app', str_replace('_', ' ', (isset($st_price[0]['period'])?$st_price[0]['period']:''))):'';
                $return_data['seasons'] = isset($st_price[0]['seasons'])?$st_price[0]['seasons']:'';
            } 
             elseif (isset($property->property->lt_rental) && $property->property->lt_rental == true && isset($property->property->period_seasons)) {
                $st_price=[];
                foreach($property->property->period_seasons as $seasons){
                    $st_price[]=['price'=>isset($seasons->new_price)?$seasons->new_price:'','period'=>isset($seasons->period)?$seasons->period:'','seasons'=>isset($seasons->seasons)?$seasons->seasons:''];
                }
                $return_data['price'] = ($st_price[0]['price']!=0)?number_format((int) $st_price[0]['price'], 0, '', '.') . ' ' . Yii::t('app', str_replace('_', ' ', (isset($st_price[0]['period'])?$st_price[0]['period']:''))):'';
                $return_data['seasons'] = isset($st_price[0]['seasons'])?$st_price[0]['seasons']:'';
            }
        } else {
            if (isset($property->property->currentprice)) {
                $return_data['price'] = ($property->property->currentprice!=0)?number_format((int) $property->property->currentprice, 0, '', '.'):'';
            }
        }
        if (isset($property->property->type_one)) {
            $return_data['type'] = $property->property->type_one;
        }
        if (isset($property->property->type_one_key)) {
            $return_data['type_key'] = $property->property->type_one_key;
        }
        if (isset($property->property->built)) {
            $return_data['built'] = $property->property->built;
        }
        if (isset($property->property->plot)) {
            $return_data['plot'] = $property->property->plot;
        }
        if (isset($property->property->year_built)) {
            $return_data['year_built'] = $property->property->year_built;
        }
        if (isset($property->property->address_country)) {
            $return_data['country'] = $property->property->address_country;
        }
        if (isset($property->property->$description->$lang)) {
            $return_data['description'] = $property->property->$description->$lang;
        }
        if (isset($property->property->address_province)) {
            $return_data['province'] = $property->property->address_province;
        }
        if (isset($property->property->address_city)) {
            $return_data['city'] = $property->property->address_city;
        }
        if (isset($property->property->city)) {
            $return_data['city_key'] = $property->property->city;
        }
        if (isset($property->property->location)) {
            $return_data['location'] = $property->property->location;
            $return_data['location_key'] = $property->property->location_key;
        }
        if (isset($property->property->energy_certificate) && $property->property->energy_certificate != '') {
            if ($property->property->energy_certificate == 'X' || $property->property->energy_certificate == 'x') {
                $return_data['energy_certificate'] = strtolower('In Progress');
            } else if ($property->property->energy_certificate == 'Not available') {
                $return_data['energy_certificate'] = strtolower('In Progress');
            } else if ($property->property->energy_certificate == 'In Process') {
                $return_data['energy_certificate'] = strtolower('In Progress');
            } else
                $return_data['energy_certificate'] = $property->property->energy_certificate;
        } else {
            $return_data['energy_certificate'] = strtolower('In Progress');
        }
        if (isset($property->property->sale) && $property->property->sale == 1) {
            $return_data['sale'] = $property->property->sale;
        }
        if (isset($property->property->rent) && $property->property->rent == 1) {
            $return_data['rent'] = $property->property->rent;
        }
        if (isset($property->property->st_rental) && $property->property->st_rental == 1) {
            $return_data['st_rental'] = $property->property->st_rental;
        }
        if (isset($property->property->lt_rental) && $property->property->lt_rental == 1) {
            $return_data['lt_rental'] = $property->property->lt_rental;
        }
        if (isset($property->property->new_construction) && $property->property->new_construction == 1) {
            $return_data['new_construction'] = $property->property->new_construction;
        }
        if (isset($property->property->sale) && isset($property->property->new_construction) && $property->property->sale == 1 && $property->property->new_construction == 1) {
            $return_data['new_construction'] = $property->property->new_construction;
        }
        if (isset($property->property->seo_title->$lang) && $property->property->seo_title->$lang != '') {
            $return_data['meta_title'] = $property->property->seo_title->$lang;
        }
        if (isset($property->property->seo_description->$lang) && $property->property->seo_description->$lang != '') {
            $return_data['meta_desc'] = $property->property->seo_description->$lang;
        }
        if (isset($property->property->keywords->$lang) && $property->property->keywords->$lang != '') {
            $return_data['meta_keywords'] = $property->property->keywords->$lang;
        }
        if (isset($property->property->custom_categories)) {
            $return_data['categories'] = $property->property->custom_categories;
        }

        if (isset($property->attachments) && count($property->attachments) > 0) {
            foreach ($property->attachments as $pic) {
                $url =  Yii::$app->params['img_url'] . '/' . $pic->model_id . '/1200/' . $pic->file_md5_name;
                $attachments[] = $url;
            }
            $return_data['attachments'] = $attachments;
        }

        if (isset($property->documents) && count($property->documents) > 0) {
            foreach ($property->documents as $pic) {
                if (isset($pic->identification_type) && $pic->identification_type == 'FP') {
                    $floor_plans[] = 'https://my.optima-crm.com/uploads/properties_images/' . $pic->model_id . '/' . $pic->file_md5_name;
                }
            }
            $return_data['floor_plans'] = $floor_plans;
        }
        if (isset($property->bookings) && count($property->bookings) > 0) {
            foreach ($property->bookings as $booking) {
                if (isset($booking->date_from) && $booking->date_from != '' && isset($booking->date_until) && $booking->date_until != '') {
                    for ($i = $booking->date_from; $i <= $booking->date_until; $i += 86400) {
                        $booked_dates[] = date("m-d-Y", $i);
                    }
                }
            }
            $return_data['booked_dates'] = $booked_dates;
        }
        if (isset($property->property->videos) && count($property->property->videos) > 0 && (is_array($property->property->videos) || is_object($property->property->videos))) {
            $videosArr = [];
            foreach ($property->property->videos as $video) {
                if (isset($video->status) && $video->status == 1 && isset($video->url->$lang) && $video->url->$lang != '') {
                    $videosArr[] = $video->url->$lang;
                }
            }
            $return_data['videos'] = $videosArr;
        }
        $categories = [];
        $features = [];
        $climate_control = [];
        $kitchen = [];
        $setting = [];
        $orientation = [];
        $views = [];
        $utilities = [];
        $security = [];
        $furniture = [];
        $parking = [];
        $garden = [];
        $pool = [];
        $condition = [];
        if (isset($property->property->feet_categories) && count($property->property->feet_categories) > 0) {
            foreach ($property->property->feet_categories as $key => $value) {
                if ($value == true) {
                    $categories[] = $key;
                }
            }
        }
        if (isset($property->property->feet_features) && count($property->property->feet_features) > 0) {
            foreach ($property->property->feet_features as $key => $value) {
                if ($value == true) {
                    $features[] = $key;
                }
            }
        }
        if (isset($property->property->feet_climate_control) && count($property->property->feet_climate_control) > 0) {
            foreach ($property->property->feet_climate_control as $key => $value) {
                if ($value == true) {
                    $climate_control[] = $key;
                }
            }
        }
        if (isset($property->property->feet_kitchen) && count($property->property->feet_kitchen) > 0) {
            foreach ($property->property->feet_kitchen as $key => $value) {
                if ($value == true && $key!='quantity') {
                    $kitchen[] = $key;
                }
            }
        }
        if (isset($property->property->feet_setting) && count($property->property->feet_setting) > 0) {
            foreach ($property->property->feet_setting as $key => $value) {
                if ($value == true) {
                    $setting[] = $key;
                }
            }
        }
        if (isset($property->property->feet_orientation) && count($property->property->feet_orientation) > 0) {
            foreach ($property->property->feet_orientation as $key => $value) {
                if ($value == true) {
                    $orientation[] = $key;
                }
            }
        }
        if (isset($property->property->feet_views) && count($property->property->feet_views) > 0) {
            foreach ($property->property->feet_views as $key => $value) {
                if ($value == true) {
                    $views[] = $key;
                }
            }
        }
        if (isset($property->property->feet_utilities) && count($property->property->feet_utilities) > 0) {
            foreach ($property->property->feet_utilities as $key => $value) {
                if ($value == true) {
                    $utilities[] = $key;
                }
            }
        }
        if (isset($property->property->feet_security) && count($property->property->feet_security) > 0) {
            foreach ($property->property->feet_security as $key => $value) {
                if ($value == true) {
                    $security[] = $key;
                }
            }
        }
        if (isset($property->property->feet_furniture) && count($property->property->feet_furniture) > 0) {
            foreach ($property->property->feet_furniture as $key => $value) {
                if ($value == true) {
                    $furniture[] = $key;
                }
            }
        }
        if (isset($property->property->feet_parking) && count($property->property->feet_parking) > 0) {
            foreach ($property->property->feet_parking as $key => $value) {
                if ($value == true) {
                    $parking[] = $key;
                }
            }
        }
        if (isset($property->property->feet_garden) && count($property->property->feet_garden) > 0) {
            foreach ($property->property->feet_garden as $key => $value) {
                if ($value == true) {
                    $garden[] = $key;
                }
            }
        }
        if (isset($property->property->feet_pool) && count($property->property->feet_pool) > 0) {
            foreach ($property->property->feet_pool as $key => $value) {
                if ($value == true) {
                    $pool[] = $key;
                }
            }
        }
        if (isset($property->property->feet_condition) && count($property->property->feet_condition) > 0) {
            foreach ($property->property->feet_condition as $key => $value) {
                if ($value == true) {
                    $condition[] = $key;
                }
            }
        }
        $return_data['property_features'] = [];
        $return_data['property_features']['features'] = $features;
        $return_data['property_features']['categories'] = $categories;
        $return_data['property_features']['climate_control'] = $climate_control;
        $return_data['property_features']['kitchen'] = $kitchen;
        $return_data['property_features']['setting'] = $setting;
        $return_data['property_features']['orientation'] = $orientation;
        $return_data['property_features']['views'] = $views;
        $return_data['property_features']['utilities'] = $utilities;
        $return_data['property_features']['security'] = $security;
        $return_data['property_features']['parking'] = $parking;
        $return_data['property_features']['garden'] = $garden;
        $return_data['property_features']['pool'] = $pool;
        $return_data['property_features']['furniture'] = $furniture;
        $return_data['property_features']['condition'] = $condition;
        return $return_data;
    }

    public static function setQuery() {
        $get = Yii::$app->request->get();
        $query = '';
        /*
         * transaction 1 = Rental
         * transaction 2 = Bank repossessions
         * transaction 3 = New homes
         * transaction 4 = Resale
         * transaction 5 = short term rental
         * transaction 6 = long term rental
         * transaction 7 = Resale in Categories
         */
        if (isset($get["transaction"]) && $get["transaction"] != "") {
            if ($get["transaction"] == '1') {
                $query .= '&rent=1';
            }
            if ($get["transaction"] == '5') {
                $query .= '&rent=1&st_rental=1';
            }
            if ($get["transaction"] == '6') {
                $query .= '&rent=1&lt_rental=1';
            }
            if ($get["transaction"] == '2') {
                $query .= '&categories[]=repossession';
            }

            if ($get["transaction"] == '3' || $get["transaction"] == '7') {
                $query .= '&new_construction=1';
            }
            if ($get["transaction"] == '4') {
                $query .= '&sale=1';
            }
            // if ($get["transaction"] == '7') {
            //     $query .= '&categories[]=resale';
            // }
        }
        if (isset($get['province']) && $get['province'] != '') {
            if (is_array($get["province"]) && count($get["province"])) {
                foreach ($get["province"] as $value) {
                    if ($value != '') {
                        $query .= '&address_province[]=' . $value;
                    }
                }
            } else {
            $query .= '&address_province[]=' . $get['province'];    
            }
        }
        if (isset($get["location"]) && $get["location"] != "") {
            if (is_array($get["location"]) && count($get["location"])) {
                foreach ($get["location"] as $value) {
                    if ($value != '') {
                        $query .= '&location[]=' . $value;
                    }
                }
            }
        }
        if (isset($get["type"]) && is_array($get["type"]) && $get["type"] != "") {
            foreach ($get["type"] as $key => $value) {
                if ($value != '') {
                    $query .= '&type_one[]=' . $value;
                }
            }
        }
        if (isset($get["location_group"]) && is_array($get["location_group"]) && count($get["location_group"]) > 0) {
            foreach ($get["location_group"] as $key => $value) {
                $query .= '&location_group[]=' . $value;
            }
        }
        if (isset($get["bedrooms"]) && $get["bedrooms"] != "") {
            $query .= '&bedrooms[]=' . $get["bedrooms"] . '&bedrooms[]=50';
        }
        if (isset($get["bathrooms"]) && $get["bathrooms"] != "") {
            $query .= '&bathrooms[]=' . $get["bathrooms"] . '&bathrooms[]=50';
        }
        if (isset($get["transaction"]) && $get["transaction"] != '' && $get["transaction"] == '1') {
            if (isset($get["price_from"]) && $get["price_from"] != "") {
                $query .= '&lt_new_price[]=' . $get["price_from"];
            }
            if (isset($get["price_from"]) && $get["price_from"] == "" && $get["price_to"] != "") {
                $query .= '&lt_new_price[]=0';
            }
            if (isset($get["price_to"]) && $get["price_to"] != "") {
                $query .= '&lt_new_price[]=' . $get["price_to"];
            }
            if (isset($get["price_to"]) && $get["price_to"] == "" && $get["price_from"] != "") {
                $query .= '&lt_new_price[]=100000000';
            }
        } else {
            if (isset($get["price_from"]) && $get["price_from"] != "") {
                $query .= '&currentprice[]=' . $get["price_from"];
            }
            if (isset($get["price_from"]) && $get["price_from"] == "" && $get["price_to"] != "") {
                $query .= '&currentprice[]=0';
            }
            if (isset($get["price_to"]) && $get["price_to"] != "") {
                $query .= '&currentprice[]=' . $get["price_to"];
            }
            if (isset($get["price_to"]) && $get["price_to"] == "" && $get["price_from"] != "") {
                $query .= '&currentprice[]=100000000';
            }
        }
        if (isset($get["orientation"]) && $get["orientation"] != "") {
            $query .= '&orientation[]=' . $get['orientation'];
        }
        if (isset($get["usefull_area"]) && $get["usefull_area"] != "") {
            $query .= '&usefull_area=' . $get['usefull_area'];
        }
        if (isset($get["min_useful_area"]) && $get["min_useful_area"] != "") {
            $query .= '&min_useful_area=' . $get['min_useful_area'];
        }
        if (isset($get["plot"]) && $get["plot"] != "") {
            $query .= '&plot=' . $get['plot'];
        }
        if (isset($get["min_plot"]) && $get["min_plot"] != "") {
            $query .= '&min_plot=' . $get['min_plot'];
        }
        if (isset($get["communal_pool"]) && $get["communal_pool"] != "" && $get["communal_pool"]) {
            $query .= '&pool[]=pool_communal';
        }
        if (isset($get["private_pool"]) && $get["private_pool"] != "" && $get["private_pool"]) {
            $query .= '&pool[]=pool_private';
        }
        if (isset($get["sold"]) && $get["sold"] != '' && $get["sold"]) {
            $query .= '&sale=true';
        }
        if (isset($get["rented"]) && $get["rented"] != '' && $get["rented"]) {
            $query .= '&rent=true';
        }
        if (isset($get["distressed"]) && $get["distressed"] != '' && $get["distressed"]) {
            $query .= '&categories[]=distressed';
        }
        if (isset($get["exclusive"]) && $get["exclusive"] != '' && $get["exclusive"]) {
            $query .= '&exclusive=true';
        }
        if (isset($get["first_line_beach"]) && $get["first_line_beach"] != '' && $get["first_line_beach"]) {
            $query .= '&categories[]=beachfront';
        }
        if (isset($get["price_reduced"]) && $get["price_reduced"] != '' && $get["price_reduced"]) {
            $query .= '&categories[]=reduced';
        }
        if (isset($get["close_to_sea"]) && $get["close_to_sea"] != '' && $get["close_to_sea"]) {
            $query .= '&settings[]=close_to_sea';
        }
        if (isset($get["sea_view"]) && $get["sea_view"] != '' && $get["sea_view"]) {
            $query .= '&views[]=sea';
        }
        if (isset($get["pool"]) && $get["pool"] != '' && $get["pool"]) {
            $query .= '&pool[]=pool_private';
        }
        if (isset($get["storage_room"]) && $get["storage_room"] != '' && $get["storage_room"]) {
            $query .= '&features[]=storage_room';
        }
        if (isset($get["garage"]) && $get["garage"] != '' && $get["garage"]) {
            $query .= '&parking[]=garage';
        }
        if (isset($get["parking"]) && $get["parking"] != '' && $get["parking"]) {
            $query .= '&parking[]=private';
        }
        if (isset($get["urbanisation"]) && $get["urbanisation"] != '') {
            $query .= '&urbanisation='.$get['urbanisation'];
        }
        if (isset($get["new_property"]) && $get["new_property"] != "" && $get["new_property"]) {
            $query .= '&conditions[]=never_lived';
        }
        if (isset($get["reference"]) && $get["reference"] != "") {
            $query .= '&reference=' . $get['reference'];
        }
        if (isset($get["st_rental"]) && $get["st_rental"] != "") {
            $query .= '&st_rental=1';
        }
        if (isset($get["lt_rental"]) && $get["lt_rental"] != "") {
            $query .= '&lt_rental=1';
        }
        if (isset($get["ids"]) && $get["ids"] != "") {
            $query .= '&favourite_ids=' . $get["ids"];
        }
        if (isset($get['orderby']) && !empty($get['orderby'])) {
            if ($get['orderby'] == 'dateASC') {
                $query .= '&orderby[]=created_at&orderby[]=ASC';
            } elseif ($get['orderby'] == 'dateDESC') {
                $query .= '&orderby[]=created_at&orderby[]=DESC';
            } elseif ($get['orderby'] == 'priceASC') {
                $query .= '&orderby[]=currentprice&orderby[]=ASC';
            } elseif ($get['orderby'] == 'priceDESC') {
                $query .= '&orderby[]=currentprice&orderby[]=DESC';
            } elseif ($get['orderby'] == 'bedsDESC') {
                $query .= '&orderby[]=bedrooms&orderby[]=DESC';
            } elseif ($get['orderby'] == 'bedsASC') {
                $query .= '&orderby[]=bedrooms&orderby[]=ASC';
            }
        }
        return $query;
    }

    public static function findAllWithLatLang() {
        $webroot = Yii::getAlias('@webroot');
        $url = Yii::$app->params['apiUrl'] . 'properties/properties-with-latlang&user=' . Yii::$app->params['user'];
        if (!is_dir($webroot . '/uploads/')) {
            mkdir($webroot . '/uploads/');
        }
        if (!is_dir($webroot . '/uploads/temp/')) {
            mkdir($webroot . '/uploads/temp/');
        }
        $file = $webroot . '/uploads/temp/properties-latlong.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $file_data = file_get_contents($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, true);
    }
    public static function getAgent($id)
    {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
            mkdir($webroot . '/uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        $file = $webroot . '/uploads/temp/agent_' . str_replace(' ', '_', strtolower($id)) . '.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/get-listing-agent&user=' . $id);
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }
}
