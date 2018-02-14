<?php

namespace optima\models;

use Yii;
use yii\base\Model;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class Developments extends Model
{

    public static function findAll($query)
    {
        $lang = \Yii::$app->language;
        $query .= self::setQuery();
        $url = Yii::$app->params['apiUrl'] . 'constructions&user=' . Yii::$app->params['user'] . $query;
        $JsonData = file_get_contents($url);
        $apiData = json_decode($JsonData);
        $return_data = [];

        foreach ($apiData as $property)
        {
            $data = [];
            $features = [];
            if (isset($property->property->hidden_reference) && $property->property->hidden_reference != '')
                $data['id'] = $property->property->hidden_reference;

            if (isset($property->property->title->$lang) && $property->property->title->$lang != '')
                $data['title'] = $property->property->title->$lang;

            if (isset($property->property->description->$lang) && $property->property->description->$lang != '')
                $data['content'] = $property->property->description->$lang;

            if (isset($property->property->type) && $property->property->type != '')
                $data['type'] = implode(', ', $property->property->type);

            if (isset($property->property->phase_low_price_from) && $property->property->phase_low_price_from != '')
                $data['price_from'] = number_format((int) $property->property->phase_low_price_from, 0, '', '.');

            if (isset($property->attachments) && count($property->attachments) > 0)
            {
                $attachments = [];
                foreach ($property->attachments as $pic)
                {
                    $attachments[] = Yii::$app->params['dev_img'] . Yii::$app->params['agency'] . '&model_id=' . $pic->model_id . '&size=1200&name=' . $pic->file_md5_name;
                }
                $data['attachments'] = $attachments;
            }

            $return_data[] = $data;
        }

        return $return_data;
    }

    public static function findOne($reference)
    {
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

        if (isset($property->property->description->$lang))
            $return_data['description'] = $property->property->description->$lang;

        if (isset($property->property->latitude))
            $return_data['lat'] = $property->property->latitude;
        if (isset($property->property->longitude))
            $return_data['lng'] = $property->property->longitude;

        if (isset($property->attachments) && count($property->attachments) > 0)
        {
            foreach ($property->attachments as $pic)
            {
                $attachments[] = Yii::$app->params['dev_img'] . Yii::$app->params['agency'] . '&model_id=' . $pic->model_id . '&size=1200&name=' . $pic->file_md5_name;
            }
            $return_data['attachments'] = $attachments;
        }

            $features=[];
            $setting=[];
            $views=[];
            if (isset($property->property->setting) && count($property->property->setting) > 0)
            {
                foreach ($property->property->setting as $key => $value)
                {
                    if ($value == true)
                        $setting[] = ucfirst(str_replace('_', ' ', $key));
                }
            }
            if (isset($property->property->views) && count($property->property->views) > 0)
            {
                foreach ($property->property->views as $key => $value)
                {
                    if ($value == true)
                        $views[] = ucfirst(str_replace('_', ' ', $key));
                }
            }
            if (isset($property->property->general_features) && count($property->property->general_features) > 0)
            {
                foreach ($property->property->general_features as $key => $value)
                {
                    if ($value == true)
                        $features[] = ucfirst(str_replace('_', ' ', $key));
                }
            }
            $properties=[];
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
                if (isset($value->attachments) && count($value->attachments) > 0)
                {
                    $attachments = [];
                    foreach ($value->attachments as $pic)
                    {
                        $attachments[] = Yii::$app->params['img_url'] . Yii::$app->params['agency'] . '&model_id=' . $pic->model_id . '&size=400&name=' . $pic->file_md5_name;
                    }
                    $data['attachments'] = $attachments;
                }
                $properties[]=$data;
            }

            $return_data['property_features']=[];
            $return_data['property_features']['features'] = $features;
            $return_data['property_features']['setting']=$setting;
            $return_data['property_features']['views']=$views;
            $return_data['properties']=$properties;
        return $return_data;
    }

    public static function setQuery()
    {
        $get = Yii::$app->request->get();
        $query = '';
        /*
         * transaction 1 = Rental
         * transaction 2 = Bank repossessions
         * transaction 3 = New homes
         * transaction 4 = Resale
         */
        if (isset($get["transaction"]) && $get["transaction"] != "")
        {
            if ($get["transaction"] == '1')
                $query .= '&rent=1';
//            if ($get["transaction"] == '2')

            if ($get["transaction"] == '3')
                $query .= '&new_construction=1';
            if ($get["transaction"] == '4')
                $query .= '&sale=1';
        }
        if (isset($get["province"]) && $get["province"] != "")
        {
            if (is_array($get["province"]) && count($get["province"]))
            {
                foreach ($get["province"] as $value)
                {
                    if ($value != '')
                        $query .= '&address_province[]=' . $value;
                }
            }
        }
        if (isset($get["location"]) && $get["location"] != "")
        {
            if (is_array($get["location"]) && count($get["location"]))
            {
                foreach ($get["location"] as $value)
                {
                    if ($value != '')
                        $query .= '&location[]=' . $value;
                }
            }
        }
        if (isset($get["type"]) && is_array($get["type"]) && $get["type"] != "")
        {
            foreach ($get["type"] as $key => $value)
            {
                if ($value != '')
                    $query .= '&type_one[]=' . $value;
            }
        }
        if (isset($get["location_group"]) && is_array($get["location_group"]) && count($get["location_group"]) > 0)
        {
            foreach ($get["location_group"] as $key => $value)
            {
                $query .= '&location_group[]=' . $value;
            }
        }
        if (isset($get["bedrooms"]) && $get["bedrooms"] != "")
        {
            $query .= '&bedrooms[]=' . $get["bedrooms"] . '&bedrooms[]=50';
        }
        if (isset($get["bathrooms"]) && $get["bathrooms"] != "")
        {
            $query .= '&bathrooms[]=' . $get["bathrooms"] . '&bathrooms[]=50';
        }
        if (isset($get["price_from"]) && $get["price_from"] != "")
        {
            $query .= '&currentprice[]=' . $get["price_from"];
        }
        if (isset($get["price_from"]) && $get["price_from"] == "" && $get["price_to"] != "")
        {
            $query .= '&currentprice[]=0';
        }
        if (isset($get["price_to"]) && $get["price_to"] != "")
        {
            $query .= '&currentprice[]=' . $get["price_to"];
        }
        if (isset($get["price_to"]) && $get["price_to"] == "" && $get["price_from"] != "")
        {
            $query .= '&currentprice[]=100000000';
        }
        if (isset($get["orientation"]) && $get["orientation"] != "")
        {
            $query .= '&orientation[]=' . $get['orientation'];
        }
        if (isset($get["usefull_area"]) && $get["usefull_area"] != "")
        {
            $query .= '&usefull_area=' . $get['usefull_area'];
        }
        if (isset($get["communal_pool"]) && $get["communal_pool"] != "" && $get["communal_pool"])
        {
            $query .= '&pool[]=pool_communal';
        }
        if (isset($get["new_property"]) && $get["new_property"] != "" && $get["new_property"])
        {
            $query .= '&conditions[]=never_lived';
        }
        if (isset($get["reference"]) && $get["reference"] != "")
        {
            $query .= '&reference=' . $get['reference'];
        }
        return $query;
    }

}