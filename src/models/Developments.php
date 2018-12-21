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
class Developments extends Model {

    public static function findAll($query,$cache=false) {
        $lang = \Yii::$app->language;
        $langugesSystem = Cms::SystemLanguages();
        $query .= self::setQuery();
        $url = Yii::$app->params['apiUrl'] . 'constructions&user=' . Yii::$app->params['user'] . $query;
        if($cache==true){
            $JsonData = self::DoCache($query,$url);
        }else{
            $JsonData = file_get_contents($url);
        }
        $apiData = json_decode($JsonData);
        $return_data = [];

        foreach ($apiData as $property) {
            $data = [];
            $features = [];
            if (isset($property->total_properties)) {
                $data['total_properties'] = $property->total_properties;
            }
            if (isset($property->property->reference) && $property->property->reference != '')
                $data['id'] = $property->property->reference;

            if (isset($property->property->title->$lang) && $property->property->title->$lang != '')
                $data['title'] = $property->property->title->$lang;

            if (isset($property->property->description->$lang) && $property->property->description->$lang != '')
                $data['content'] = $property->property->description->$lang;

            if (isset($property->property->type) && $property->property->type != '')
                $data['type'] = implode(', ', $property->property->type);

            if (isset($property->property->phase_low_price_from) && $property->property->phase_low_price_from != '')
                $data['price_from'] = number_format((int) $property->property->phase_low_price_from, 0, '', '.');

            if (isset($property->property->bedrooms_from) && $property->property->bedrooms_from > 0) {
                $data['bedrooms'] = $property->property->bedrooms_from;
            }
            if (isset($property->property->bathrooms_from) && $property->property->bathrooms_from > 0) {
                $data['bathrooms'] = $property->property->bathrooms_from;
            }
            if (isset($property->property->built_size_from) && $property->property->built_size_from > 0) {
                $data['built'] = $property->property->built_size_from;
            }
            if (isset($property->attachments) && count($property->attachments) > 0) {
                $attachments = [];
                foreach ($property->attachments as $pic) {
                    $attachments[] = Yii::$app->params['dev_img'] . '/' . $pic->model_id . '/1200/' . $pic->file_md5_name;
                }
                $data['attachments'] = $attachments;
            }
//        start slug_all
            foreach ($langugesSystem as $lang_sys) {
                $lang_sys_key = $lang_sys['key'];
                $lang_sys_internal_key = isset($lang_sys['internal_key']) ? $lang_sys['internal_key'] : '';
                if (isset($property->property->perma_link->$lang_sys_key) && $property->property->perma_link->$lang_sys_key != '') {
                    $slugs[$lang_sys_internal_key] = $property->property->perma_link->$lang_sys_key;
                } else if (isset($property->property->title->$lang_sys_key) && $property->property->title->$lang_sys_key != '') {
                    $slugs[$lang_sys_internal_key] = $property->property->title->$lang_sys_key;
                }
            }
//        end slug_all
            $data['slug_all'] = $slugs;
            $return_data[] = $data;
        }

        return $return_data;
    }

    public static function findOne($reference) {
        $langugesSystem = Cms::SystemLanguages();
        $ref = $reference;
        $lang = \Yii::$app->language;
        $url = Yii::$app->params['apiUrl'] . 'constructions/view-by-ref&user=' . Yii::$app->params['user'] . '&ref=' . $ref;
        $JsonData = file_get_contents($url);
        $property = json_decode($JsonData);
        $return_data = [];
        $attachments = [];

        if (isset($property->property->_id))
            $return_data['_id'] = $property->property->_id;
        if (isset($property->property->reference))
            $return_data['reference'] = $property->property->reference;

        if (isset($property->property->title->$lang) && $property->property->title->$lang != '')
            $return_data['title'] = $property->property->title->$lang;
        else
            $return_data['title'] = 'N/A';
        if (isset($property->property->phase_low_price_from) && $property->property->phase_low_price_from != '')
            $return_data['price_from'] = number_format((int) $property->property->phase_low_price_from, 0, '', '.');

        if (isset($property->property->phase_heigh_price_from) && $property->property->phase_heigh_price_from != '')
           $return_data['price_to'] = number_format((int) $property->property->phase_heigh_price_from, 0, '', '.');

        if (isset($property->property->description->$lang))
            $return_data['description'] = $property->property->description->$lang;
        if ((isset($property->property->alternative_latitude) && $property->property->alternative_latitude != '') && (isset($property->property->alternative_longitude) && $property->property->alternative_longitude != '')) {
            if (isset($property->property->alternative_latitude))
                $return_data['lat'] = $property->property->alternative_latitude;
            if (isset($property->property->alternative_longitude))
                $return_data['lng'] = $property->property->alternative_longitude;
        }else {
            if (isset($property->property->latitude))
                $return_data['lat'] = $property->property->latitude;
            if (isset($property->property->longitude))
                $return_data['lng'] = $property->property->longitude;
        }
        if (isset($property->property->location))
        {
            $return_data['location'] = $property->property->location;
            $return_data['location_key'] = isset($property->property->location_key) ? $property->property->location_key : '';
        }
        if(isset($property->property->bedrooms_from) && $property->property->bedrooms_from > 0)
        {
            $return_data['bedrooms_from'] = $property->property->bedrooms_from;
        }
        if(isset($property->property->bedrooms_to) && $property->property->bedrooms_to > 0)
        {
            $return_data['bedrooms_to'] = $property->property->bedrooms_to;
        }
        if(isset($property->property->bathrooms_from) && $property->property->bathrooms_from > 0)
        {
            $return_data['bathrooms_from'] = $property->property->bathrooms_from;
        }
        if(isset($property->property->bathrooms_to) && $property->property->bathrooms_to > 0)
        {
            $return_data['bathrooms_to'] = $property->property->bathrooms_to;
        }
        if(isset($property->property->built_size_from) && $property->property->built_size_from > 0)
        {
            $return_data['built_size_from'] = $property->property->built_size_from;
        }
        if(isset($property->property->built_size_to) && $property->property->built_size_to > 0)
        {
            $return_data['built_size_to'] = $property->property->built_size_to;
        }
        if (isset($property->attachments) && count($property->attachments) > 0) {
            foreach ($property->attachments as $pic) {
                $attachments[] = Yii::$app->params['dev_img'] . '/' . $pic->model_id . '/1200/' . $pic->file_md5_name;
            }
            $return_data['attachments'] = $attachments;
        }
        $features = [];
        $setting = [];
        $views = [];
        if (isset($property->property->setting) && count($property->property->setting) > 0) {
            foreach ($property->property->setting as $key => $value) {
                if ($value == true)
                    $setting[] = ucfirst(str_replace('_', ' ', $key));
            }
        }
        if (isset($property->property->views) && count($property->property->views) > 0) {
            foreach ($property->property->views as $key => $value) {
                if ($value == true)
                    $views[] = ucfirst(str_replace('_', ' ', $key));
            }
        }
        if (isset($property->property->general_features) && count($property->property->general_features) > 0) {
            foreach ($property->property->general_features as $key => $value) {
                if ($key == 'kitchens' && $value != '') {
                    $features[] = \Yii::t('app', 'kitchens') . ': '. \Yii::t('app', strtolower($value));
                }
                if ($key == 'floors' && $value != '') {
                    $features[] = \Yii::t('app', 'floors') . ': '. \Yii::t('app', strtolower($value));
                }
                if ($key == 'furniture' && $value != 'No') {
                    $features[] = \Yii::t('app', 'furniture') . ': '. \Yii::t('app', strtolower($value));
                } else {
                    if ($key == true && $key != 'furniture' &&  $key != 'kitchens' &&  $key != 'floors' )
                        $features[] = ucfirst(str_replace('_', ' ', $key));
                }
            }
        }
        $properties = [];
        foreach ($property->properties as $key => $value) {
            if (isset($value->property->currentprice) && $value->property->currentprice > 0)
                $data['currentprice'] = str_replace(',', '.', (number_format((int) ($value->property->currentprice))));
            if (isset($value->property->type_one))
                $data['type'] = $value->property->type_one;
            if (isset($value->property->location))
                $data['location'] = $value->property->location;
            if (isset($value->property->reference))
                $data['id'] = $value->property->reference;
            if (isset($value->property->title->$lang) && $value->property->title->$lang != '')
                $data['title'] = $value->property->title->$lang;
            else if (isset($value->property->location))
                $data['title'] = \Yii::t('app', $value->property->type_one) . ' ' . \Yii::t('app', 'in') . ' ' . \Yii::t('app', $value->property->location);
            if (isset($value->attachments) && count($value->attachments) > 0) {
                $attachments = [];
                foreach ($value->attachments as $pic) {
                    $attachments[] = Yii::$app->params['img_url'] . '/' . $pic->model_id . '/1200/' . $pic->file_md5_name;
                }
                $data['attachments'] = $attachments;
            }
            $properties[] = $data;
        }
//        start slug_all
        $slugs = [];
        foreach ($langugesSystem as $lang_sys) {
            $lang_sys_key = $lang_sys['key'];
            $lang_sys_internal_key = $lang_sys['internal_key'];
            if (isset($property->property->perma_link->$lang_sys_key) && $property->property->perma_link->$lang_sys_key != '') {
                $slugs[$lang_sys_internal_key] = $property->property->perma_link->$lang_sys_key;
            } else if (isset($property->property->title->$lang_sys_key) && $property->property->title->$lang_sys_key != '') {
                $slugs[$lang_sys_internal_key] = $property->property->title->$lang_sys_key;
            }
        }
        $return_data['slug_all'] = $slugs;
//        end slug_all
        $return_data['property_features'] = [];
        $return_data['property_features']['features'] = $features;
        $return_data['property_features']['setting'] = $setting;
        $return_data['property_features']['views'] = $views;
        $return_data['properties'] = $properties;
        return $return_data;
    }

    public static function setQuery() {
        $get = Yii::$app->request->get();
        $query = '';

        if (isset($get["province"]) && $get["province"] != "") {
            if (is_array($get["province"]) && count($get["province"])) {
                foreach ($get["province"] as $value) {
                    if ($value != '')
                        $query .= '&address_province[]=' . $value;
                }
            }
        }
        if (isset($get["location"]) && $get["location"] != "") {
            if (is_array($get["location"]) && count($get["location"])) {
                foreach ($get["location"] as $value) {
                    if ($value != '')
                        $query .= '&location[]=' . $value;
                }
            }
        }
        if (isset($get["type"]) && is_array($get["type"]) && $get["type"] != "") {
            foreach ($get["type"] as $key => $value) {
                if ($value != '')
                    $query .= '&type[]=' . $value;
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
        if (isset($get["price_from"]) && $get["price_from"] != "") {
            $query .= '&phase_low_price_from=' . $get["price_from"];
        }
        if (isset($get["price_from"]) && $get["price_from"] == "" && isset($get["price_to"]) && $get["price_to"] != "") {
            $query .= '&phase_low_price_from=0';
        }
        if (isset($get["price_to"]) && $get["price_to"] != "") {
            $query .= '&phase_heigh_price_from=' . $get["price_to"];
        }
        if (isset($get["price_to"]) && $get["price_to"] == "" && $get["price_from"] != "") {
            $query .= '&phase_heigh_price_from=100000000';
        }
        if (isset($get["orientation"]) && $get["orientation"] != "") {
            $query .= '&orientation[]=' . $get['orientation'];
        }
        if (isset($get["usefull_area"]) && $get["usefull_area"] != "") {
            $query .= '&usefull_area=' . $get['usefull_area'];
        }
        if (isset($get["communal_pool"]) && $get["communal_pool"] != "" && $get["communal_pool"]) {
            $query .= '&pool[]=pool_communal';
        }
        if (isset($get["new_property"]) && $get["new_property"] != "" && $get["new_property"]) {
            $query .= '&conditions[]=never_lived';
        }
        if (isset($get["reference"]) && $get["reference"] != "") {
            $query .= '&reference=' . $get['reference'];
        }
        return $query;
    }
    public static function DoCache($query,$url) {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
            mkdir($webroot . '/uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        $file = $webroot . '/uploads/temp/develop_'.json_encode($query).'.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = file_get_contents($url);
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        return $file_data;
    }
}
