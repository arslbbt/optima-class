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
        $url = Yii::$app->params['apiUrl'] . 'properties/view-by-ref&user=' . Yii::$app->params['user'] . '&ref=' . $ref;
        $JsonData = file_get_contents($url);
        $property = json_decode($JsonData);
        $return_data = [];
        $attachments = [];
        $features = [];
        $distances = [];
        $basic_info = [];
        $climate_control = [];
        $categories = [];
        $custom_pool = [];
        $custom_parking = [];
        $custom_features = [];

        if (isset($property->property->_id))
            $return_data['_id'] = $property->property->_id;
        if (isset($property->property->reference))
            $return_data['reference'] = $property->property->reference;
        if (isset($property->property->title->$lang) && $property->property->title->$lang != '')
            $return_data['title'] = $property->property->title->$lang;
        else
            $return_data['title'] = \Yii::t('app', $property->property->type_one) . ' ' . \Yii::t('app', 'in') . ' ' . \Yii::t('app', $property->property->location);
        if (isset($property->property->property_name))
            $return_data['property_name'] = $property->property->property_name;
        if (isset($property->property->latitude))
            $return_data['lat'] = $property->property->latitude;
        if (isset($property->property->longitude))
            $return_data['lng'] = $property->property->longitude;
        if (isset($property->property->bedrooms))
            $return_data['bedrooms'] = $property->property->bedrooms;
        if (isset($property->property->bathrooms))
            $return_data['bathrooms'] = $property->property->bathrooms;
        if (isset($property->property->currentprice))
            $return_data['currentprice'] = $property->property->currentprice;
        if (isset($property->property->currentprice))
            $return_data['price'] = number_format((int) $property->property->currentprice, 0, '', '.');
        if (isset($property->property->type_one))
            $return_data['type'] = $property->property->type_one;
        if (isset($property->property->type_one_key))
            $return_data['type_key'] = $property->property->type_one_key;
        if (isset($property->property->built))
            $return_data['built'] = $property->property->built;
        if (isset($property->property->plot))
            $return_data['plot'] = $property->property->plot;
        if (isset($property->property->year_built))
            $return_data['year_built'] = $property->property->year_built;
        if (isset($property->property->address_country))
            $return_data['country'] = $property->property->address_country;
        if (isset($property->property->description->$lang))
            $return_data['description'] = $property->property->description->$lang;
        if (isset($property->property->address_province))
            $return_data['province'] = $property->property->address_province;
        if (isset($property->property->address_city))
            $return_data['city'] = $property->property->address_city;
        if (isset($property->property->address_city))
            $return_data['city_key'] = $property->property->city;
        if (isset($property->property->location))
            $return_data['location'] = $property->property->location;
        if (isset($property->property->sale) && $property->property->sale == 1)
            $return_data['sale'] = $property->property->sale;
        if (isset($property->property->rent) && $property->property->rent == 1)
            $return_data['sale'] = $property->property->rent;
        if (isset($property->attachments) && count($property->attachments) > 0)
        {
            foreach ($property->attachments as $pic)
            {
                $attachments[] = Yii::$app->params['img_url'] . Yii::$app->params['agency'] . '&model_id=' . $pic->model_id . '&size=1200&name=' . $pic->file_md5_name;
            }
            $return_data['attachments'] = $attachments;
        }


        //Neighbourhood if exists
        if (isset($property->property->location) && $property->property->location != '')
        {
            $loc_key = $property->property->location_key;
            $slug = str_replace(' ', '-', strtolower($property->property->location));
            $slug .= '-';
            $slug .= $loc_key;
            $pagejson = file_get_contents('https://my.optima-crm.com/yiiapp/frontend/web/index.php?r=cms/page-by-slug&user=' . Yii::$app->params['user'] . '&slug=' . $slug);
            $page = json_decode($pagejson);
        }

        if ($page)
        {
            $neighbourhood = [];
            $loc_title = $page->title->$lang;
            if (isset($page->custom_settings))
            {
                foreach ($page->custom_settings as $setting)
                {
                    if ($setting->key == 'lg_slug')
                    {
                        $loc_group_title = ucwords(str_replace('-', ' ', $setting->value));
                    }
                }
            }
            $nh_title = $loc_title . ' | ' . $loc_group_title;
            if (isset($page->featured_image->$lang->name))
            {
                $nh_img_src = 'https://my.optima-crm.com/uploads/cms_pages/' . $page->_id . '/' . $page->featured_image->$lang->name;
            }
            $neighbourhood['title'] = $nh_title;
            $neighbourhood['img_src'] = $nh_img_src;
            $return_data['neighbourhood_data'] = $neighbourhood;
        }

        if (isset($property->property->feet_categories) && count($property->property->feet_categories) > 0)
        {
            foreach ($property->property->feet_categories as $key => $value)
            {
                if ($value == true)
                    $features[] = ucfirst(str_replace('_', ' ', $key));
            }
        }
        if (isset($property->property->feet_features) && count($property->property->feet_features) > 0)
        {
            foreach ($property->property->feet_features as $key => $value)
            {
                if ($value == true)
                    $features[] = ucfirst(str_replace('_', ' ', $key));
            }
        }
        if (isset($property->property->feet_climate_control) && count($property->property->feet_climate_control) > 0)
        {
            foreach ($property->property->feet_climate_control as $key => $value)
            {
                if ($value == true)
                    $features[] = ucfirst(str_replace('_', ' ', $key));
            }
        }
        if (isset($property->property->feet_kitchen) && count($property->property->feet_kitchen) > 0)
        {
            foreach ($property->property->feet_kitchen as $key => $value)
            {
                if ($value == true)
                    $features[] = ucfirst(str_replace('_', ' ', $key));
            }
        }
        if (isset($property->property->feet_setting) && count($property->property->feet_setting) > 0)
        {
            foreach ($property->property->feet_setting as $key => $value)
            {
                if ($value == true)
                    $features[] = ucfirst(str_replace('_', ' ', $key));
            }
        }
        if (isset($property->property->feet_orientation) && count($property->property->feet_orientation) > 0)
        {
            foreach ($property->property->feet_orientation as $key => $value)
            {
                if ($value == true)
                    $features[] = ucfirst(str_replace('_', ' ', $key));
            }
        }
        if (isset($property->property->feet_views) && count($property->property->feet_views) > 0)
        {
            foreach ($property->property->feet_views as $key => $value)
            {
                if ($value == true)
                    $features[] = ucfirst(str_replace('_', ' ', $key));
            }
        }
        if (isset($property->property->feet_utilities) && count($property->property->feet_utilities) > 0)
        {
            foreach ($property->property->feet_utilities as $key => $value)
            {
                if ($value == true)
                    $features[] = ucfirst(str_replace('_', ' ', $key));
            }
        }
        if (isset($property->property->feet_security) && count($property->property->feet_security) > 0)
        {
            foreach ($property->property->feet_security as $key => $value)
            {
                if ($value == true)
                    $features[] = ucfirst(str_replace('_', ' ', $key));
            }
        }
        if (isset($property->property->feet_furniture) && count($property->property->feet_furniture) > 0)
        {
            foreach ($property->property->feet_furniture as $key => $value)
            {
                if ($value == true)
                    $features[] = ucfirst(str_replace('_', ' ', $key));
            }
        }
        if (isset($property->property->feet_parking) && count($property->property->feet_parking) > 0)
        {
            foreach ($property->property->feet_parking as $key => $value)
            {
                if ($value == true)
                    $features[] = ucfirst(str_replace('_', ' ', $key));
            }
        }
        if (isset($property->property->feet_garden) && count($property->property->feet_garden) > 0)
        {
            foreach ($property->property->feet_garden as $key => $value)
            {
                if ($value == true)
                    $features[] = ucfirst(str_replace('_', ' ', $key));
            }
        }
        if (isset($property->property->feet_pool) && count($property->property->feet_pool) > 0)
        {
            foreach ($property->property->feet_pool as $key => $value)
            {
                if ($value == true)
                    $features[] = ucfirst(str_replace('_', ' ', $key));
            }
        }
        if (isset($property->property->feet_condition) && count($property->property->feet_condition) > 0)
        {
            foreach ($property->property->feet_condition as $key => $value)
            {
                if ($value == true)
                    $features[] = ucfirst(str_replace('_', ' ', $key));
            }
        }
        if (isset($property->property->distance_airport) && $property->property->distance_airport > 0)
        {
            $distances[] = 'Airport Distance ' . $property->property->distance_airport . ' km';
        }
        if (isset($property->property->distance_beach) && $property->property->distance_beach > 0)
        {
            $distances[] = 'Beach Distance ' . $property->property->distance_beach . ' km';
        }
        if (isset($property->property->distance_golf) && $property->property->distance_golf > 0)
        {
            $distances[] = 'Golf Distance ' . $property->property->distance_golf . ' km';
        }
        if (isset($property->property->distance_restaurant) && $property->property->distance_restaurant > 0)
        {
            $distances[] = 'Restaurant Distance ' . $property->property->distance_restaurant . ' km';
        }
        if (isset($property->property->distance_sea) && $property->property->distance_sea > 0)
        {
            $distances[] = 'Sea Distance ' . $property->property->distance_sea . ' km';
        }
        if (isset($property->property->distance_supermarket) && $property->property->distance_supermarket > 0)
        {
            $distances[] = 'Super Market Distance ' . $property->property->distance_supermarket . ' km';
        }
        if (isset($property->property->distance_next_town) && $property->property->distance_next_town > 0)
        {
            $distances[] = 'Next Town Distance ' . $property->property->distance_next_town . ' km';
        }
        if (isset($property->property->value_of_custom->garden) && count($property->property->value_of_custom->garden))
        {
            foreach ($property->property->value_of_custom->garden as $voc)
            {
                if (isset($voc->key) && isset($voc->value) && $voc->value)
                    $features[] = $voc->key;
            }
        }
        if (isset($property->property->value_of_custom->climate_control) && count($property->property->value_of_custom->climate_control))
        {
            foreach ($property->property->value_of_custom->climate_control as $voc)
            {
                if (isset($voc->key) && isset($voc->value) && $voc->value)
                    $climate_control[] = $voc->key;
            }
        }
        if (isset($property->property->value_of_custom->feet_custom_categories) && count($property->property->value_of_custom->feet_custom_categories))
        {
            foreach ($property->property->value_of_custom->feet_custom_categories as $voc)
            {
                if (isset($voc->key) && isset($voc->value) && $voc->value)
                    $categories[] = $voc->key;
            }
        }
        if (isset($property->property->value_of_custom->pool) && count($property->property->value_of_custom->pool))
        {
            foreach ($property->property->value_of_custom->pool as $voc)
            {
                if (isset($voc->key) && isset($voc->value) && $voc->value)
                    $custom_pool[] = $voc->key;
            }
        }
        if (isset($property->property->value_of_custom->parking) && count($property->property->value_of_custom->parking))
        {
            foreach ($property->property->value_of_custom->parking as $voc)
            {
                if (isset($voc->key) && isset($voc->value) && $voc->value)
                    $custom_parking[] = $voc->key;
            }
        }
        if (isset($property->property->value_of_custom->features) && count($property->property->value_of_custom->features))
        {
            foreach ($property->property->value_of_custom->features as $voc)
            {
                if (isset($voc->key) && isset($voc->value) && $voc->value)
                    $custom_features[] = $voc->key;
            }
        }
        if (isset($property->property->value_of_custom->basic_info) && count($property->property->value_of_custom->basic_info))
        {
            foreach ($property->property->value_of_custom->basic_info as $voc)
            {
                if (isset($voc->key) && isset($voc->value) && $voc->value)
                    $basic_info[] = $voc->key;
            }
        }
        $return_data['distances'] = $distances;
        $return_data['features'] = $features;
        $return_data['basic_info'] = $basic_info;
        $return_data['custom_features'] = $custom_features;
        $return_data['custom_pool'] = $custom_pool;
        $return_data['categories'] = $categories;
        $return_data['custom_parking'] = $custom_parking;
        $return_data['climate_control'] = $climate_control;
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
