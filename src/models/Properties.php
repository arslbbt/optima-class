<?php

namespace optima\models;

use Yii;
use yii\base\Model;
use optima\models\Cms;
use yii\helpers\ArrayHelper;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class Properties extends Model
{

    public static function findAll($query)
    {
        $langugesSystem = Cms::SystemLanguages();
        $lang = strtoupper(\Yii::$app->language);
        $contentLang = $lang;
        foreach ($langugesSystem as $sysLang)
        {
            if ((isset($sysLang['internal_key']) && $sysLang['internal_key'] != '') && $lang == $sysLang['internal_key'])
            {
                $contentLang = $sysLang['key'];
            }
        }
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
        if (isset($get["transaction"]) && $get["transaction"] != "")
        {
            if ($get["transaction"] == '1')
            {
                $rent = true;
            }
            elseif ($get["transaction"] == '5')
            {
                $rent = true;
                $strent = true;
            }
            elseif ($get["transaction"] == '6')
            {
                $rent = true;
                $ltrent = true;
            }
            else
            {
                $sale = true;
            }
        }
        if (isset($get["rent"]) && $get['rent'] != "")
        {
            $rent = true;
        }
        if (isset($get["st_rental"]) && $get['st_rental'] != "")
        {
            $rent = true;
            $strent = true;
        }

        $return_data = [];

        foreach ($apiData as $property)
        {
            $title = 'title';
            $description = 'description';
            $price = 'sale';
            $seo_title = 'seo_title';
            $seo_description = 'seo_description';
            $keywords = 'keywords';
            $perma_link = 'perma_link';
            if (isset($property->property->rent) && $property->property->rent == true)
            {
                $title = 'rental_title';
                $description = 'rental_description';
                $price = 'rent';
                $seo_title = 'rental_seo_title';
                $seo_description = 'rental_seo_description';
                $keywords = 'rental_keywords';
                $perma_link = 'rental_perma_link';
            }
            $data = [];
            $features = [];
            if (isset($property->total_properties))
            {
                $data['total_properties'] = $property->total_properties;
            }
            if (isset($property->property->_id))
            {
                $data['_id'] = $property->property->_id;
            }
            if (isset($property->property->reference))
            {
                $data['id'] = $property->property->reference;
            }
            if (isset($settings['general_settings']['reference']) && $settings['general_settings']['reference'] != 'reference')
            {
                $ref = $settings['general_settings']['reference'];
                if (isset($property->property->$ref))
                    $data['reference'] = $property->property->$ref;
                else
                    $data['reference'] = $property->agency_code . '-' . $property->property->reference;
            }
            else
            {
                $data['reference'] = $property->agency_code . '-' . $property->property->reference;
            }

            if (isset($property->property->$title->$contentLang) && $property->property->$title->$contentLang != '')
            {
                $data['title'] = $property->property->$title->$contentLang;
            }
            elseif (isset($property->property->location) && isset($property->property->type_one))
            {
                $data['title'] = (isset($property->property->type_one) ? \Yii::t('app', strtolower($property->property->type_one)) : \Yii::t('app', 'N/A')) . ' ' . \Yii::t('app', 'in') . ' ' . \Yii::t('app', $property->property->location);
            }

            if (isset($property->property->status))
            {
                $data['status'] = $property->property->status;
            }
            if (isset($property->property->type_one))
            {
                $data['type'] = $property->property->type_one;
            }
            $agency = Yii::$app->params['agency'];
            if (isset($property->property->latitude) && $property->property->latitude != '')
            {
                $data['lat'] = $property->property->latitude;
            }
            elseif (isset($property->property->private_info_object->$agency->latitude))
            {
                $data['lat'] = $property->property->private_info_object->$agency->latitude;
            }

            if (isset($property->property->longitude) && $property->property->longitude != '')
            {
                $data['lng'] = $property->property->longitude;
            }
            elseif (isset($property->property->private_info_object->$agency->longitude))
            {
                $data['lng'] = $property->property->private_info_object->$agency->longitude;
            }
            if (isset($property->property->$description->$lang))
            {
                $data['description'] = $property->property->$description->$lang;
            }
            if (isset($property->property->location))
            {
                $data['location'] = $property->property->location;
            }
            if (isset($property->property->region))
            {
                $data['region'] = $property->property->region;
            }
            if (isset($property->property->address_country))
            {
                $data['country'] = $property->property->address_country;
            }
            if (isset($property->property->address_city))
            {
                $data['city'] = $property->property->address_city;
            }
            if (isset($property->property->sale) && $property->property->sale == 1)
            {
                $data['sale'] = $property->property->sale;
            }
            if (isset($property->property->rent) && $property->property->rent == 1)
            {
                $data['rent'] = $property->property->rent;
                if (isset($property->property->st_rental) && $property->property->st_rental == 1)
                    $data['st_rental'] = $property->property->st_rental;
                if (isset($property->property->lt_rental) && $property->property->lt_rental == 1)
                    $data['lt_rental'] = $property->property->lt_rental;
            }
            if (isset($property->property->bedrooms) && $property->property->bedrooms > 0)
            {
                $data['bedrooms'] = $property->property->bedrooms;
            }
            if (isset($property->property->bathrooms) && $property->property->bathrooms > 0)
            {
                $data['bathrooms'] = $property->property->bathrooms;
            }
            if (isset($property->property->sleeps) && $property->property->sleeps > 0)
            {
                $data['sleeps'] = $property->property->sleeps;
            }
            if (isset($property->property->st_rental) && $property->property->st_rental == true && isset($property->property->rental_seasons))
            {
            $todaydate = strtotime(date("Y/m/d"));
            $gdprice = [];
            $zstprice = 0;
            
            foreach ($property->property->rental_seasons as $seasons)
            {
                if(isset($seasons->period_from) && ($todaydate >= $seasons->period_from) && ($todaydate <= $seasons->period_to))
                {
                    if(isset($seasons->gross_day_price))
                    $gdprice[] = $seasons->gross_day_price;
                }
            }
            if(count($gdprice) > 0)
            $zstprice = min($gdprice);
            $atprice = 0;
            if (isset($property->bookings_extras) && count((array)$property->bookings_extras) > 0) {
                foreach ($property->bookings_extras as $booking_extra) {
                    if (isset($booking_extra->add_to_price) && $booking_extra->add_to_price == true) {
                        $atprice = $atprice + (isset($booking_extra->price) ? $booking_extra->price : 0);
                    }
                }
            }
            $data['zariko_st_price'] = $zstprice + $atprice;
            }
            if ($rent)
            {
                if ($ltrent && isset($property->property->lt_rental) && $property->property->lt_rental == true && isset($property->property->period_seasons->{'0'}->new_price))
                {
                    $data['price'] = ($property->property->period_seasons->{'0'}->new_price != 0) ? number_format((int) $property->property->period_seasons->{'0'}->new_price, 0, '', '.') . ' ' . Yii::t('app', 'per_month') : '';
                }
                elseif ($strent && isset($property->property->st_rental) && $property->property->st_rental == true && isset($property->property->rental_seasons))
                {
                    $st_price = [];
                    foreach ($property->property->rental_seasons as $seasons)
                    {
                        $st_price[] = ['price' => isset($seasons->new_price) ? $seasons->new_price : '', 'period' => isset($seasons->period) ? $seasons->period : '', 'seasons' => isset($seasons->seasons) ? $seasons->seasons : ''];
                    }
                    $data['price'] = ($st_price[0]['price'] != 0) ? number_format((int) $st_price[0]['price'], 0, '', '.') . ' ' . Yii::t('app', str_replace('_', ' ', (isset($st_price[0]['period']) ? $st_price[0]['period'] : ''))) : '';
                    $data['seasons'] = isset($st_price[0]['seasons']) ? $st_price[0]['seasons'] : '';
                    $data['season_data'] = ArrayHelper::toArray($property->property->rental_seasons);
                }
            }
            else
            {
                if (isset($property->property->currentprice))
                {
                    $data['price'] = ($property->property->currentprice != 0) ? number_format((int) $property->property->currentprice, 0, '', '.') : '';
                }
            }


            if ($rent)
            {
                if (isset($property->property->st_rental) && $property->property->st_rental == true && isset($property->property->rental_seasons))
                {
                    $st_price = [];
                    foreach ($property->property->rental_seasons as $seasons)
                    {
                        $st_price[] = ['price' => isset($seasons->new_price) ? $seasons->new_price : '', 'period' => isset($seasons->period) ? $seasons->period : '', 'seasons' => isset($seasons->seasons) ? $seasons->seasons : ''];
                    }
                    $data['season_data'] = ArrayHelper::toArray($property->property->rental_seasons);
                }
                elseif (isset($property->property->lt_rental) && $property->property->lt_rental == true && isset($property->property->period_seasons))
                {
                    $st_price = [];
                    foreach ($property->property->period_seasons as $seasons)
                    {
                        $st_price[] = ['price' => isset($seasons->new_price) ? $seasons->new_price : '', 'period' => isset($seasons->period) ? $seasons->period : '', 'seasons' => isset($seasons->seasons) ? $seasons->seasons : ''];
                    }
                    $data['season_data'] = ArrayHelper::toArray($property->property->period_seasons);
                }
            }
            else
            {
                if (isset($property->property->currentprice))
                {
                    $data['price'] = ($property->property->currentprice != 0) ? number_format((int) $property->property->currentprice, 0, '', '.') : '';
                }
            }



            if (isset($property->property->currentprice) && $property->property->currentprice > 0)
            {
                $data['currentprice'] = str_replace(',', '.', (number_format((int) ($property->property->currentprice))));
            }
            if (isset($property->property->oldprice->price) && $property->property->oldprice->price > 0)
            {
                $data['oldprice'] = str_replace(',', '.', (number_format((int) ($property->property->oldprice->price))));
            }
            if (isset($property->property->oldprice->price_on_demand) && $property->property->oldprice->price_on_demand == true)
            {
                $data['price_on_demand'] = true;
            }
            if (isset($property->property->sale) && $property->property->sale == 1 || isset($property->property->transfer) && $property->property->transfer == 1)
            {
                if (isset($property->property->currentprice) && $property->property->currentprice > 0)
                {
                    $data['saleprice'] = str_replace(',', '.', (number_format((int) ($property->property->currentprice))));
                }
            }
            if (isset($property->property->rent) && $property->property->rent == 1)
            {
                if (isset($property->property->lt_rental) && $property->property->lt_rental == true && isset($property->property->period_seasons) && is_array($property->property->period_seasons) && $property->property->period_seasons[0]->new_price)
                {
                    $data['ltprice'] = ($property->property->period_seasons[0]->new_price != 0) ? number_format((int) $property->property->period_seasons[0]->new_price, 0, '', '.') . ' ' . Yii::t('app', 'per_month') : '';
                    $data['lt_price'] = ($property->property->period_seasons[0]->new_price != 0) ? number_format((int) $property->property->period_seasons[0]->new_price, 0, '', '.') . ' € ' . Yii::t('app', 'per_month') : '';
                }
                if (isset($property->property->lt_rental) && $property->property->lt_rental == true && isset($property->property->period_seasons) && is_object($property->property->period_seasons) && $property->property->period_seasons->{0}->new_price)
                {
                    $data['ltprice'] = ($property->property->period_seasons->{0}->new_price != 0) ? number_format((int) $property->property->period_seasons->{0}->new_price, 0, '', '.') . ' ' . Yii::t('app', 'per_month') : '';
                    $data['lt_price'] = ($property->property->period_seasons->{0}->new_price != 0) ? number_format((int) $property->property->period_seasons->{0}->new_price, 0, '', '.') . ' € ' . Yii::t('app', 'per_month') : '';
                }
                if (isset($property->property->st_rental) && $property->property->st_rental == true && isset($property->property->rental_seasons))
                {
                    $st_price = [];
                    foreach ($property->property->rental_seasons as $seasons)
                    {
                        $st_price[] = ['price' => isset($seasons->new_price) ? $seasons->new_price : '', 'period' => isset($seasons->period) ? $seasons->period : '', 'seasons' => isset($seasons->seasons) ? $seasons->seasons : ''];
                    }
                    $data['price_per_day'] = (isset($st_price[0]['price']) && $st_price[0]['price'] != 0) ? number_format((int) $st_price[0]['price'], 0, '', '.') : '';
                    $data['period'] = (isset($st_price[0]['period']) ? $st_price[0]['period'] : '');
                    $data['stprice'] = (isset($st_price[0]['price']) && $st_price[0]['price'] != 0) ? number_format((int) $st_price[0]['price'], 0, '', '.') . ' € ' . Yii::t('app', str_replace('_', ' ', (isset($st_price[0]['period']) ? $st_price[0]['period'] : ''))) : '';
                }
            }

            if (isset($property->property->built) && $property->property->built > 0)
            {
                $data['built'] = $property->property->built;
            }
            if (isset($property->property->plot) && $property->property->plot > 0)
            {
                $data['plot'] = $property->property->plot;
            }
            if (isset($property->property->custom_categories) && is_array($property->property->custom_categories))
            {
                $cats = self::Categories();
                $catsArr = [];
                foreach ($property->property->custom_categories as $catdata)
                {
                    if (isset($cats[$catdata]))
                        $catsArr[] = $cats[$catdata];
                }
                $data['categories'] = $catsArr;
            }
            if (isset($property->property->terrace->{0}->terrace) && $property->property->terrace->{0}->terrace > 0)
            {
                $data['terrace'] = $property->property->terrace->{0}->terrace;
            }
            if (isset($property->property->updated_at) && $property->property->updated_at != '')
            {
                $data['updated_at'] = $property->property->updated_at;
            }
            if (isset($property->bookings_extras) && count($property->bookings_extras) > 0)
            {
                $data['booking_extras'] = ArrayHelper::toArray($property->bookings_extras);
            }
            if (isset($property->bookings_cleaning) && count($property->bookings_cleaning) > 0)
            {
                $data['booking_cleaning'] = ArrayHelper::toArray($property->bookings_cleaning);
            }
            if (isset($property->property->location_group) && $property->property->location_group != 'N/A')
            {
                $data['location_group'] = $property->property->location_group;
            }
            if (isset($property->property->address_province))
            {
                $data['province'] = $property->property->address_province;
            }
            $slugs = [];
            foreach ($langugesSystem as $lang_sys)
            {

                $lang_sys_key = $lang_sys['key'];
                $lang_sys_internal_key = isset($lang_sys['internal_key']) && $lang_sys['internal_key'] != '' ? $lang_sys['internal_key'] : '';
                if (isset($property->property->$perma_link->$lang_sys_key) && $property->property->$perma_link->$lang_sys_key != '')
                {
                    $slugs[$lang_sys_internal_key] = $property->property->$perma_link->$lang_sys_key;
                }
                else if (isset($property->property->$title->$lang_sys_key) && $property->property->$title->$lang_sys_key != '')
                {
                    $slugs[$lang_sys_internal_key] = $property->property->$title->$lang_sys_key;
                }
                else
                {
                    if (isset($property->property->type_one) && $property->property->type_one != '' && isset($slugs[$lang_sys_internal_key]))
                        $slugs[$lang_sys_internal_key] = $property->property->type_one . ' ' . 'in' . ' ';
                    if (isset($property->property->location) && $property->property->location != '' && isset($slugs[$lang_sys_internal_key]))
                        $slugs[$lang_sys_internal_key] = $slugs[$lang_sys_internal_key] . $property->property->location;
                }
            }
//        end slug_all
            $data['slug_all'] = $slugs;
            if (isset($property->attachments) && count($property->attachments) > 0)
            {
                $attachments = [];
                foreach ($property->attachments as $pic)
                {
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
            if (isset($property->property->feet_categories))
            {
                foreach ($property->property->feet_categories as $key => $value)
                {
                    if ($value == true)
                    {
                        $categories[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_features))
            {
                foreach ($property->property->feet_features as $key => $value)
                {
                    if ($value == true)
                    {
                        $features[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_climate_control))
            {
                foreach ($property->property->feet_climate_control as $key => $value)
                {
                    if ($value == true)
                    {
                        $climate_control[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_kitchen))
            {
                foreach ($property->property->feet_kitchen as $key => $value)
                {
                    if ($value == true)
                    {
                        $kitchen[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_setting))
            {
                foreach ($property->property->feet_setting as $key => $value)
                {
                    if ($value == true)
                    {
                        $setting[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_orientation))
            {
                foreach ($property->property->feet_orientation as $key => $value)
                {
                    if ($value == true)
                    {
                        $orientation[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_views))
            {
                foreach ($property->property->feet_views as $key => $value)
                {
                    if ($value == true)
                    {
                        $views[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_utilities))
            {
                foreach ($property->property->feet_utilities as $key => $value)
                {
                    if ($value == true)
                    {
                        $utilities[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_security))
            {
                foreach ($property->property->feet_security as $key => $value)
                {
                    if ($value == true)
                    {
                        $security[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_furniture))
            {
                foreach ($property->property->feet_furniture as $key => $value)
                {
                    if ($value == true)
                    {
                        $furniture[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_parking))
            {
                foreach ($property->property->feet_parking as $key => $value)
                {
                    if ($value == true)
                    {
                        $parking[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_garden))
            {
                foreach ($property->property->feet_garden as $key => $value)
                {
                    if ($value == true)
                    {
                        $garden[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_pool))
            {
                foreach ($property->property->feet_pool as $key => $value)
                {
                    if ($value == true)
                    {
                        $pool[] = $key;
                    }
                }
            }
            if (isset($property->property->feet_condition))
            {
                foreach ($property->property->feet_condition as $key => $value)
                {
                    if ($value == true)
                    {
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

    public static function findOne($reference, $with_booking = false, $with_locationgroup = false, $rent = false, $with_construction = false)
    {
        $langugesSystem = Cms::SystemLanguages();
        $lang = strtoupper(\Yii::$app->language);
        $contentLang = $lang;
        foreach ($langugesSystem as $sysLang)
        {
            if ((isset($sysLang['internal_key']) && $sysLang['internal_key'] != '') && $lang == $sysLang['internal_key'])
            {
                $contentLang = $sysLang['key'];
            }
        }
        $ref = $reference;

        if (isset($with_booking) && $with_booking == true)
        {
            $url = Yii::$app->params['apiUrl'] . 'properties/view-by-ref&with_booking=true&user=' . Yii::$app->params['user'] . '&ref=' . $ref . '&ip=' . \Yii::$app->getRequest()->getUserIP();
        }
        elseif ($with_locationgroup == true)
        {
            $url = Yii::$app->params['apiUrl'] . 'properties/view-by-ref&user=' . Yii::$app->params['user'] . '&ref=' . $ref . '&with_locationgroup=true&ip=' . \Yii::$app->getRequest()->getUserIP();
        }
        elseif ($with_construction == true)
        {
            $url = Yii::$app->params['apiUrl'] . 'properties/view-by-ref&user=' . Yii::$app->params['user'] . '&ref=' . $ref . '&with_construction=true&ip=' . \Yii::$app->getRequest()->getUserIP();
        }
        else
            $url = Yii::$app->params['apiUrl'] . 'properties/view-by-ref&user=' . Yii::$app->params['user'] . '&ref=' . $ref . '&ip=' . \Yii::$app->getRequest()->getUserIP();
        $JsonData = file_get_contents($url);
        $property = json_decode($JsonData);
        $settings = Cms::settings();
        $construction = [];
        $return_data = [];
        $attachments = [];
        $attachment_descriptions = [];
        $attachment_alt_descriptions = [];
        $floor_plans = [];
        $floor_plans_with_description = [];
        $booked_dates = [];
        $distances = [];
        if (!isset($property->property))
        {
            throw new \yii\web\NotFoundHttpException();
        }
        if (isset($property->property->_id))
        {
            $return_data['_id'] = $property->property->_id;
        }
        if (isset($property->property->reference))
        {
            $return_data['id'] = $property->property->reference;
        }

        if (isset($settings['general_settings']['reference']) && $settings['general_settings']['reference'] != 'reference')
        {
            $ref = $settings['general_settings']['reference'];
            if (isset($property->property->$ref))
                $return_data['reference'] = $property->property->$ref;
            else
                $return_data['reference'] = $property->agency_code . '-' . $property->property->reference;
        }
        else
        {
            $return_data['reference'] = $property->agency_code . '-' . $property->property->reference;
        }
        $sale_rent = "sale";
        if (isset($property->property->rent) && $property->property->rent == true)
        {
            $sale_rent = "rent";
        }
        if (isset($property->property->rent) && $property->property->rent == true && isset($property->property->sale) && $property->property->sale == true)
        {
            $sale_rent = "sale";
        }
        if ($rent == true)
        {
            $sale_rent = "rent";
        }
        $title = 'title';
        $description = 'description';
        $price = 'sale';
        $seo_title = 'seo_title';
        $seo_description = 'seo_description';
        $keywords = 'keywords';
        if ($sale_rent == 'rent')
        {
            $title = 'rental_title';
            $description = 'rental_description';
            $price = 'rent';
            $seo_title = 'rental_seo_title';
            $seo_description = 'rental_seo_description';
            $keywords = 'rental_keywords';
        }
        //    start slug_all
        $slugs = [];
        foreach ($langugesSystem as $lang_sys)
        {
            $lang_sys_key = $lang_sys['key'];
            $lang_sys_internal_key = isset($lang_sys['internal_key']) ? $lang_sys['internal_key'] : '';
            if (isset($property->property->perma_link->$lang_sys_key) && $property->property->perma_link->$lang_sys_key != '')
            {
                $slugs[$lang_sys_internal_key] = $property->property->perma_link->$lang_sys_key;
            }
            else if (isset($property->property->$title->$lang_sys_key) && $property->property->$title->$lang_sys_key != '')
            {
                $slugs[$lang_sys_internal_key] = $property->property->$title->$lang_sys_key;
            }
            elseif(isset($slugs[$lang_sys_internal_key]))
            {
                if (isset($property->property->type_one) && $property->property->type_one != '')
                    $slugs[$lang_sys_internal_key] = $property->property->type_one . ' ' . 'in' . ' ';
                if (isset($property->property->location) && $property->property->location != '')
                    $slugs[$lang_sys_internal_key] = $slugs[$lang_sys_internal_key] . $property->property->location;
            }
        }
//        end slug_all
        $return_data['slug_all'] = $slugs;
        if (isset($property->property->$title->$contentLang) && $property->property->$title->$contentLang != '')
        {
            $return_data['title'] = $property->property->$title->$contentLang;
        }
        else
        {
            if (isset($property->property->location) && $property->property->location != '')
            {
                $return_data['title'] = \Yii::t('app', strtolower($property->property->type_one)) . ' ' . \Yii::t('app', 'in') . ' ' . \Yii::t('app', $property->property->location);
            }
            else
            {
                $return_data['title'] = \Yii::t('app', strtolower($property->property->type_one));
            }
        }
        if (isset($property->property->$title->$contentLang) && $property->property->$title->$contentLang != '')
        {
            $return_data['slug'] = $property->property->$title->$contentLang;
        }
        if (isset($property->property->listing_agent))
        {
            $return_data['listing_agent'] = $property->property->listing_agent;
        }
        if (isset($property->property->property_name))
        {
            $return_data['property_name'] = $property->property->property_name;
        }
        if (isset($property->property->latitude))
        {
            $return_data['lat'] = $property->property->latitude;
        }
        if (isset($property->property->longitude))
        {
            $return_data['lng'] = $property->property->longitude;
        }
        if (isset($property->property->bedrooms))
        {
            $return_data['bedrooms'] = $property->property->bedrooms;
        }
        if (isset($property->property->bathrooms))
        {
            $return_data['bathrooms'] = $property->property->bathrooms;
        }
        if (isset($property->property->terrace->{0}->terrace) && $property->property->terrace->{0}->terrace > 0)
        {
            $return_data['terrace'] = $property->property->terrace->{0}->terrace;
        }
        if (isset($property->property->sleeps) && $property->property->sleeps > 0)
        {
            $return_data['sleeps'] = $property->property->sleeps;
        }
        if (isset($property->property->oldprice->price) && $property->property->oldprice->price > 0)
        {
            $return_data['oldprice'] = str_replace(',', '.', (number_format((int) ($property->property->oldprice->price))));
        }
        if (isset($property->property->oldprice->price_on_demand) && $property->property->oldprice->price_on_demand == true)
        {
            $return_data['price_on_demand'] = true;
        }
        if (isset($property->property->currentprice) && isset($property->property->sale) && $property->property->sale == true)
        {
            $return_data['currentprice'] = $property->property->currentprice;
        }
        if (isset($property->property->st_rental) && $property->property->st_rental == true && isset($property->property->rental_seasons))
        {
            $todaydate = strtotime(date("Y/m/d"));
            $gdprice = [];
            $zstprice = 0;
            
            foreach ($property->property->rental_seasons as $seasons)
            {
                if(isset($seasons->period_from) && ($todaydate >= $seasons->period_from) && ($todaydate <= $seasons->period_to))
                {
                    if(isset($seasons->gross_day_price))
                    $gdprice[] = $seasons->gross_day_price;
                }
            }
            if(count($gdprice) > 0)
            $zstprice = min($gdprice);
            $atprice = 0;
            if (isset($property->bookings_extras) && count((array)$property->bookings_extras) > 0) {
                foreach ($property->bookings_extras as $booking_extra) {
                    if (isset($booking_extra->add_to_price) && $booking_extra->add_to_price == true) {
                        $atprice = $atprice + (isset($booking_extra->price) ? $booking_extra->price : 0);
                    }
                }
            }
            $return_data['zariko_st_price'] = $zstprice + $atprice;
        }

        if ($price == 'rent')
        {
            if (isset($property->property->st_rental) && $property->property->st_rental == true && isset($property->property->rental_seasons))
            {
                $st_price = [];
                foreach ($property->property->rental_seasons as $seasons)
                {
                    $st_price[] = ['price' => isset($seasons->new_price) ? $seasons->new_price : '', 'period' => isset($seasons->period) ? $seasons->period : '', 'seasons' => isset($seasons->seasons) ? $seasons->seasons : ''];
                }
                $return_data['price'] = ($st_price[0]['price'] != 0) ? number_format((int) $st_price[0]['price'], 0, '', '.') . ' ' . Yii::t('app', str_replace('_', ' ', (isset($st_price[0]['period']) ? $st_price[0]['period'] : ''))) : '';
                $return_data['seasons'] = isset($st_price[0]['seasons']) ? $st_price[0]['seasons'] : '';
                $return_data['season_data'] = ArrayHelper::toArray($property->property->rental_seasons);
            }
            elseif (isset($property->property->lt_rental) && $property->property->lt_rental == true && isset($property->property->period_seasons))
            {
                $st_price = [];
                foreach ($property->property->period_seasons as $seasons)
                {
                    $st_price[] = ['price' => isset($seasons->new_price) ? $seasons->new_price : '', 'period' => isset($seasons->period) ? $seasons->period : '', 'seasons' => isset($seasons->seasons) ? $seasons->seasons : ''];
                }
                $return_data['price'] = ($st_price[0]['price'] != 0) ? number_format((int) $st_price[0]['price'], 0, '', '.') . ' ' . Yii::t('app', str_replace('_', ' ', (isset($st_price[0]['period']) ? $st_price[0]['period'] : ''))) : '';
                $return_data['seasons'] = isset($st_price[0]['seasons']) ? $st_price[0]['seasons'] : '';
                $return_data['season_data'] = ArrayHelper::toArray($property->property->period_seasons);
            }
        }
        else
        {
            if (isset($property->property->currentprice))
            {
                $return_data['price'] = ($property->property->currentprice != 0) ? number_format((int) $property->property->currentprice, 0, '', '.') : '';
            }
        }
        if (isset($property->property->lt_rental) && $property->property->lt_rental == true && isset($property->property->period_seasons) && is_array($property->property->period_seasons) && $property->property->period_seasons[0]->new_price)
        {
            $return_data['ltprice'] = ($property->property->period_seasons[0]->new_price != 0) ? number_format((int) $property->property->period_seasons[0]->new_price, 0, '', '.') . ' ' . Yii::t('app', 'per_month') : '';
            $return_data['lt_price'] = ($property->property->period_seasons[0]->new_price != 0) ? number_format((int) $property->property->period_seasons[0]->new_price, 0, '', '.') . ' € ' . Yii::t('app', 'per_month') : '';
        }
        if (isset($property->property->lt_rental) && $property->property->lt_rental == true && isset($property->property->period_seasons) && is_object($property->property->period_seasons) && $property->property->period_seasons->{0}->new_price)
        {
            $return_data['ltprice'] = ($property->property->period_seasons->{0}->new_price != 0) ? number_format((int) $property->property->period_seasons->{0}->new_price, 0, '', '.') . ' ' . Yii::t('app', 'per_month') : '';
            $return_data['lt_price'] = ($property->property->period_seasons->{0}->new_price != 0) ? number_format((int) $property->property->period_seasons->{0}->new_price, 0, '', '.') . ' € ' . Yii::t('app', 'per_month') : '';
        }
        if (isset($property->property->type_two))
        {
            $return_data['type_two'] = $property->property->type_two;
        }
        if (isset($property->property->security_deposit))
        {
            $return_data['security_deposit'] = $property->property->security_deposit;
        }
        if (isset($property->property->type_one))
        {
            $return_data['type'] = $property->property->type_one;
        }
        if (isset($property->property->type_one_key))
        {
            $return_data['type_key'] = $property->property->type_one_key;
        }
        if (isset($property->property->built))
        {
            $return_data['built'] = $property->property->built;
        }
        if (isset($property->property->plot))
        {
            $return_data['plot'] = $property->property->plot;
        }
        if (isset($property->property->year_built))
        {
            $return_data['year_built'] = $property->property->year_built;
        }
        if (isset($property->property->address_country))
        {
            $return_data['country'] = $property->property->address_country;
        }
        if (isset($property->property->$description->$contentLang))
        {
            $return_data['description'] = $property->property->$description->$contentLang;
        }
        else if (isset($property->property->$description->EN))
        {
            $return_data['description'] = $property->property->$description->EN;
        }
        if (isset($property->property->address_province))
        {
            $return_data['province'] = $property->property->address_province;
        }
        if (isset($property->property->address_city))
        {
            $return_data['city'] = $property->property->address_city;
        }
        if (isset($property->property->city))
        {
            $return_data['city_key'] = $property->property->city;
        }
        if (isset($property->property->location_group) && $property->property->location_group != 'N/A')
        {
            $return_data['location_group'] = $property->property->location_group;
        }
        if (isset($property->property->location) && isset($property->property->location_key))
        {
            $return_data['location'] = $property->property->location;
            $return_data['location_key'] = $property->property->location_key;
        }
        if (isset($property->property->energy_certificate) && $property->property->energy_certificate != '')
        {
            if ($property->property->energy_certificate == 'X' || $property->property->energy_certificate == 'x')
            {
                $return_data['energy_certificate'] = strtolower('In Progress');
            }
            else if ($property->property->energy_certificate == 'Not available')
            {
                $return_data['energy_certificate'] = strtolower('In Progress');
            }
            else if ($property->property->energy_certificate == 'In Process')
            {
                $return_data['energy_certificate'] = strtolower('In Progress');
            }
            else
                $return_data['energy_certificate'] = $property->property->energy_certificate;
        } else
        {
            $return_data['energy_certificate'] = strtolower('In Progress');
        }
        if (isset($property->property->sale) && $property->property->sale == 1)
        {
            $return_data['sale'] = $property->property->sale;
        }
        if (isset($property->property->rent) && $property->property->rent == 1)
        {
            $return_data['rent'] = $property->property->rent;
        }
        if (isset($property->property->st_rental) && $property->property->st_rental == 1)
        {
            $return_data['st_rental'] = $property->property->st_rental;
        }
        if (isset($property->property->lt_rental) && $property->property->lt_rental == 1)
        {
            $return_data['lt_rental'] = $property->property->lt_rental;
        }
        if (isset($property->property->new_construction) && $property->property->new_construction == 1)
        {
            $return_data['new_construction'] = $property->property->new_construction;
        }
        if (isset($property->property->sale) && isset($property->property->new_construction) && $property->property->sale == 1 && $property->property->new_construction == 1)
        {
            $return_data['new_construction'] = $property->property->new_construction;
        }
        if (isset($property->property->$seo_title->$contentLang) && $property->property->$seo_title->$contentLang != '')
        {
            $return_data['meta_title'] = $property->property->$seo_title->$contentLang;
        }
        if (isset($property->property->$seo_description->$contentLang) && $property->property->$seo_description->$contentLang != '')
        {
            $return_data['meta_desc'] = $property->property->$seo_description->$contentLang;
        }
        if (isset($property->property->$keywords->$contentLang) && $property->property->$keywords->$contentLang != '')
        {
            $return_data['meta_keywords'] = $property->property->$keywords->$contentLang;
        }
        if (isset($property->property->custom_categories))
        {
            $cats = self::Categories();
            $catsArr = [];
            foreach ($property->property->custom_categories as $catdata)
            {
                if (isset($cats[$catdata]))
                    $catsArr[] = $cats[$catdata];
            }
            $return_data['categories'] = $catsArr;
        }
        if (isset($property->property->value_of_custom->basic_info))
        {
            $return_data['basic_info'] = ArrayHelper::toArray($property->property->value_of_custom->basic_info);
        }
        if (isset($property->attachments) && count($property->attachments) > 0)
        {
            foreach ($property->attachments as $pic)
            {
                $url = Yii::$app->params['img_url'] . '/' . $pic->model_id . '/1200/' . $pic->file_md5_name;
                $attachments[] = $url;
                $attachment_descriptions[] = isset($pic->description->$contentLang) ? $pic->description->$contentLang : '';
                $attachment_alt_descriptions[] = isset($pic->alt_description->$contentLang) ? $pic->alt_description->$contentLang : '';
            }
            $return_data['attachments'] = $attachments;
            $return_data['attachment_desc'] = $attachment_descriptions;
            $return_data['attachment_alt_desc'] = $attachment_alt_descriptions;
        }

        if (isset($property->documents) && count($property->documents) > 0)
        {
            foreach ($property->documents as $pic)
            {
                if (isset($pic->identification_type) && $pic->identification_type == 'FP')
                {
                    if (isset(Yii::$app->params['floor_plans_url']))
                        $floor_plans[] = Yii::$app->params['floor_plans_url'] . '/' . $pic->model_id . '/' . $pic->file_md5_name;
                }
            }
            $return_data['floor_plans'] = $floor_plans;
        }
        if (isset($property->documents) && count($property->documents) > 0)
        {
            foreach ($property->documents as $pic)
            {
                if (isset($pic->identification_type) && $pic->identification_type == 'FP')
                {
                    if (isset(Yii::$app->params['floor_plans_url']))
                    {
                        $url_fp = Yii::$app->params['floor_plans_url'] . '/' . $pic->model_id . '/' . $pic->file_md5_name;
                    }
                    if(isset($pic->description->$lang))
                    {
                        $desc_fp = $pic->description->$lang;
                    }
                    $floor_plans_with_description[] = ['url'=> isset($url_fp) ? $url_fp : '', 'description'=> isset($desc_fp) ? $desc_fp : ''];
                }
            }
            $return_data['floor_plans_with_description'] = $floor_plans_with_description;
        }
        if (isset($property->bookings_extras) && count($property->bookings_extras) > 0)
        {
            $return_data['booking_extras'] = ArrayHelper::toArray($property->bookings_extras);
        }
        if (isset($property->bookings_cleaning) && count($property->bookings_cleaning) > 0)
        {
            $return_data['booking_cleaning'] = ArrayHelper::toArray($property->bookings_cleaning);
        }
        if (isset($property->property->security_deposit) && $property->property->security_deposit != '')
        {
            $return_data['security_deposit'] = $property->property->security_deposit;
        }
        if (isset($property->property->vt_ids[0]))
        {
            $return_data['vt'] = 'https://my.optima-crm.com/yiiapp/frontend/web/index.php?r=virtualtours&user=' . Yii::$app->params['user'] . '&id=' . $property->property->vt_ids[0];
        }
        if (isset($property->property->license_number))
        {
            $return_data['license_number'] = $property->property->license_number;
        }
        if (isset($property->bookings) && count($property->bookings) > 0)
        {
            $group_booked = [];

            foreach ($property->bookings as $key => $booking)
            {
                if (isset($booking->date_from) && $booking->date_from != '' && isset($booking->date_until) && $booking->date_until != '')
                {
                    for ($i = $booking->date_from; $i <= $booking->date_until; $i += 86400)
                    {
                        $booked_dates[] = date(isset(Yii::$app->params['date_fromate']) ? Yii::$app->params['date_fromate'] : "m-d-Y", $i);
                    }
                    // booking dates for costa - last day available - OPT-3533
                    // Revert above-booking dates for costa - CA search calendar update (OPT-3561)
//                    for ($i = $booking->date_from; $i < $booking->date_until; $i += 86400)
//                    {
//                        $booked_dates_costa[] = date(isset(Yii::$app->params['date_fromate']) ? Yii::$app->params['date_fromate'] : "m-d-Y", $i);
//                    }
                    /*
                     * grouping logic dates
                     */
                    $group_booked[$key] = [];
                    for ($i = $booking->date_from; $i <= $booking->date_until; $i += 86400)
                    {
                        $booked_dates_costa[] = date(isset(Yii::$app->params['date_fromate']) ? Yii::$app->params['date_fromate'] : "m-d-Y", $i);
                        $group_booked[$key][] = date(isset(Yii::$app->params['date_fromate']) ? Yii::$app->params['date_fromate'] : "m-d-Y", $i);
                    }
                }
            }
            $return_data['group_booked'] = $group_booked;
            $return_data['booked_dates'] = $booked_dates;
            $return_data['booked_dates_costa'] = $booked_dates_costa;
        }



        if (isset($property->property->videos) && (is_array($property->property->videos) || is_object($property->property->videos)))
        {
            $videosArr = [];
            $videosArr_gogo = [];
            foreach ($property->property->videos as $video)
            {
                if (isset($video->status) && $video->status == 1 && isset($video->url->$contentLang) && $video->url->$contentLang != '')
                {
                    $videosArr[] = $video->url->$contentLang;
                }
            }
            $return_data['videos'] = $videosArr;
        }

        if (isset($property->property->videos) && (is_array($property->property->videos) || is_object($property->property->videos)))
        {
            $videosArrDesc = [];
            $videosArr_gogo = [];
            foreach ($property->property->videos as $video)
            {
                $url_vid = '';
                $desc_vid = '';
                if (isset($video->status) && $video->status == 1 && isset($video->url->$contentLang) && $video->url->$contentLang != '')
                {
                    $url_vid = $video->url->$contentLang;
                }
                if (isset($video->status) && $video->status == 1 && isset($video->description->$contentLang) && $video->description->$contentLang != '')
                {
                    $desc_vid = $video->description->$contentLang;
                }
                $videosArrDesc[] = ['url'=>$url_vid, 'description'=>$desc_vid];
            }
            $return_data['videos_with_description'] = $videosArrDesc;
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
        $rooms = [];
        $living_rooms = [];
        $beds = [];
        $baths = [];
        if(isset($property->property->rent) && $property->property->rent == true)
        {
            $rental_features = [];
            $rental_features['beds'] = [];
            $rental_features['baths'] = [];
            $rental_features['rooms'] = [];
            $rental_features['living_rooms'] = [];
            if(isset($property->property->feet_living_room) && count((array)$property->property->feet_living_room) > 0)
            {
                foreach($property->property->feet_living_room as $key=>$value)
                {
                    if(isset($value) && $value == true)
                    {
                        $living_rooms[] = Yii::t('app', $key);
                    }
                }
                $rental_features['living_rooms'] = $living_rooms;
            }
            if(isset($property->property->rooms) && count($property->property->rooms) > 0)
            {
                foreach($property->property->rooms as $value)
                {
                    $type = isset($value) && isset($value->type) && isset($value->type->$contentLang) ? $value->type->$contentLang : (isset($value) && isset($value->type) && isset($value->type->EN) ? $value->type->EN : '');
                    $name = isset($value) && isset($value->name) && isset($value->name->$contentLang) ? $value->name->$contentLang : (isset($value) && isset($value->name) && isset($value->name->EN) ? $value->name->EN : '');
                    $description = isset($value) && isset($value->description) && isset($value->description->$contentLang) ? $value->description->$contentLang : (isset($value) && isset($value->description) && isset($value->description->EN) ? $value->description->EN : '');
                    $rooms[] = ['type'=>$type, 'name'=>$name, 'description'=>$description];
                }
                $rental_features['rooms'] = $rooms;
            }
            if(isset($property->property->double_bed) && count($property->property->double_bed) > 0)
            {
                $double_bed = [];
                foreach($property->property->double_bed as $value)
                {
                    if(isset($value->x) && $value->x > 0 && isset($value->y) && $value->y > 0)
                    {
                        $double_bed[] = ['x'=>$value->x, 'y'=>$value->y];
                    }
                }
                if(count($double_bed) > 0)
                {
                    $beds['double_bed'] = $double_bed;
                }
            }
            if(isset($property->property->single_bed) && count($property->property->single_bed) > 0)
            {
                $single_bed = [];
                foreach($property->property->single_bed as $value)
                {
                    if(isset($value->x) && $value->x > 0 && (isset($value->y) && $value->y > 0))
                    {
                        $single_bed[] = ['x'=>$value->x, 'y'=>$value->y];
                    }
                }
                if(count($single_bed) > 0)
                {
                    $beds['single_bed'] = $single_bed;
                }
            }
            if(isset($property->property->sofa_bed) && count($property->property->sofa_bed) > 0)
            {
                $sofa_bed = [];
                foreach($property->property->sofa_bed as $value)
                {
                    if(isset($value->x) && $value->x > 0 && isset($value->y) && $value->y > 0)
                    {
                        $sofa_bed[] = ['x'=>$value->x, 'y'=>$value->y];
                    }
                }
                if(count($sofa_bed) > 0)
                {
                    $beds['sofa_bed'] = $sofa_bed;
                }
            }
            if(isset($property->property->bunk_beds) && count($property->property->bunk_beds) > 0)
            {
                $bunk_beds = [];
                foreach($property->property->bunk_beds as $value)
                {
                    if(isset($value->x) && $value->x > 0 && isset($value->y) && $value->y > 0)
                    {
                        $bunk_beds[] = ['x'=>$value->x, 'y'=>$value->y];
                    }
                }
                if(count($bunk_beds) > 0)
                {
                    $beds['bunk_beds'] = $bunk_beds;
                }
            }
            if(count($beds) > 0)
            {
                $rental_features['beds'] = $beds;
            }
            if(isset($property->property->bath_tubs) && $property->property->bath_tubs > 0)
            {
                $baths['bath_tubs'] = $property->property->bath_tubs;
            }
            if(isset($property->property->jaccuzi_bath) && $property->property->jaccuzi_bath > 0)
            {
                $baths['jaccuzi_bath'] = $property->property->jaccuzi_bath;
            }
            if(isset($property->property->bidet) && $property->property->bidet > 0)
            {
                $baths['bidet'] = $property->property->bidet;
            }
            if(isset($property->property->toilets) && $property->property->toilets > 0)
            {
                $baths['toilets'] = $property->property->toilets;
            }
            if(isset($property->property->corner_shower) && $property->property->corner_shower > 0)
            {
                $baths['corner_shower'] = $property->property->corner_shower;
            }
            if(isset($property->property->sink) && $property->property->sink > 0)
            {
                $baths['sink'] = $property->property->sink;
            }
            if(isset($property->property->double_sink) && $property->property->double_sink > 0)
            {
                $baths['double_sink'] = $property->property->double_sink;
            }
            if(isset($property->property->walk_in_shower) && $property->property->walk_in_shower > 0)
            {
                $baths['walk_in_shower'] = $property->property->walk_in_shower;
            }
            if(isset($property->property->en_suite) && $property->property->en_suite > 0)
            {
                $baths['en_suite'] = $property->property->en_suite;
            }
            if(isset($property->property->wheelchair_accesible_shower) && $property->property->wheelchair_accesible_shower > 0)
            {
                $baths['wheelchair_accesible_shower'] = $property->property->wheelchair_accesible_shower;
            }
            if(isset($property->property->hairdryer) && $property->property->hairdryer > 0)
            {
                $baths['hairdryer'] = $property->property->hairdryer;
            }
            if(count($baths) > 0)
            {
                $rental_features['baths'] = $baths;
            }
            $return_data['rental_features'] = $rental_features;
        }
        if (isset($property->property->feet_categories))
        {
            foreach ($property->property->feet_categories as $key => $value)
            {
                if ($value == true)
                {
                    $categories[] = $key;
                }
            }
        }
        if (isset($property->property->feet_features))
        {
            foreach ($property->property->feet_features as $key => $value)
            {
                if ($value == true)
                {
                    $features[] = $key;
                }
            }
        }
        if (isset($property->property->feet_climate_control))
        {
            foreach ($property->property->feet_climate_control as $key => $value)
            {
                if ($value == true)
                {
                    $climate_control[] = $key;
                }
            }
        }
        if (isset($property->property->feet_kitchen))
        {
            foreach ($property->property->feet_kitchen as $key => $value)
            {
                if ($value == true && $key != 'quantity')
                {
                    $kitchen[] = $key;
                }
            }
        }
        if (isset($property->property->feet_setting))
        {
            foreach ($property->property->feet_setting as $key => $value)
            {
                if ($value == true)
                {
                    $setting[] = $key;
                }
            }
        }
        if (isset($property->property->feet_orientation))
        {
            foreach ($property->property->feet_orientation as $key => $value)
            {
                if ($value == true)
                {
                    $orientation[] = $key;
                }
            }
        }
        if (isset($property->property->feet_views))
        {
            foreach ($property->property->feet_views as $key => $value)
            {
                if ($value == true)
                {
                    $views[] = $key;
                }
            }
        }
        if (isset($property->property->feet_utilities))
        {
            foreach ($property->property->feet_utilities as $key => $value)
            {
                if ($value == true)
                {
                    $utilities[] = $key;
                }
            }
        }
        if (isset($property->property->feet_security))
        {
            foreach ($property->property->feet_security as $key => $value)
            {
                if ($value == true)
                {
                    $security[] = $key;
                }
            }
        }
        if (isset($property->property->feet_furniture))
        {
            foreach ($property->property->feet_furniture as $key => $value)
            {
                if ($value == true)
                {
                    $furniture[] = $key;
                }
            }
        }
        if (isset($property->property->feet_parking))
        {
            foreach ($property->property->feet_parking as $key => $value)
            {
                if ($value == true)
                {
                    $parking[] = $key;
                }
            }
        }
        if (isset($property->property->feet_garden))
        {
            foreach ($property->property->feet_garden as $key => $value)
            {
                if ($value == true)
                {
                    $garden[] = $key;
                }
            }
        }
        if (isset($property->property->feet_pool))
        {
            foreach ($property->property->feet_pool as $key => $value)
            {
                if ($value == true)
                {
                    $pool[] = $key;
                }
            }
        }
        if (isset($property->property->feet_condition))
        {
            foreach ($property->property->feet_condition as $key => $value)
            {
                if ($value == true)
                {
                    $condition[] = $key;
                }
            }
        }
        if (isset($property->property->distance_airport) && count((array) $property->property->distance_airport) > 0 && isset($property->property->distance_airport->value) && $property->property->distance_airport->value > 0)
        {
            $distances['distance_airport'] = $property->property->distance_airport->value . ' ' . (isset($property->property->distance_airport->unit) ? $property->property->distance_airport->unit : 'km');
        }
        if (isset($property->property->distance_beach) && count((array) $property->property->distance_beach) > 0 && isset($property->property->distance_beach->value) && $property->property->distance_beach->value > 0)
        {
            $distances['distance_beach'] = $property->property->distance_beach->value . ' ' . (isset($property->property->distance_beach->unit) ? $property->property->distance_beach->unit : 'km');
        }
        if (isset($property->property->distance_golf) && count((array) $property->property->distance_golf) > 0 && isset($property->property->distance_golf->value) && $property->property->distance_golf->value > 0)
        {
            $distances['distance_golf'] = $property->property->distance_golf->value . ' ' . (isset($property->property->distance_golf->unit) ? $property->property->distance_golf->unit : 'km');
        }
        if (isset($property->property->distance_restaurant) && count((array) $property->property->distance_restaurant) > 0 && isset($property->property->distance_restaurant->value) && $property->property->distance_restaurant->value > 0)
        {
            $distances['distance_restaurant'] = $property->property->distance_restaurant->value . ' ' . (isset($property->property->distance_restaurant->unit) ? $property->property->distance_restaurant->unit : 'km');
        }
        if (isset($property->property->distance_sea) && count((array) $property->property->distance_sea) > 0 && isset($property->property->distance_sea->value) && $property->property->distance_sea->value > 0)
        {
            $distances['distance_sea'] = $property->property->distance_sea->value . ' ' . (isset($property->property->distance_sea->unit) ? $property->property->distance_sea->unit : 'km');
        }
        if (isset($property->property->distance_supermarket) && count((array) $property->property->distance_supermarket) > 0 && isset($property->property->distance_supermarket->value) && $property->property->distance_supermarket->value > 0)
        {
            $distances['distance_supermarket'] = $property->property->distance_supermarket->value . ' ' . (isset($property->property->distance_supermarket->unit) ? $property->property->distance_supermarket->unit : 'km');
        }
        if (isset($property->property->distance_next_town) && count((array) $property->property->distance_next_town) > 0 && isset($property->property->distance_next_town->value) && $property->property->distance_next_town->value > 0)
        {
            $distances['distance_next_town'] = $property->property->distance_next_town->value . ' ' . (isset($property->property->distance_next_town->unit) ? $property->property->distance_next_town->unit : 'km');
        }
        if (isset($property->construction) && count((array) $property->construction) > 0)
        {
            $obj = $property->construction;
            if (isset($obj->bedrooms_from) && $obj->bedrooms_from != '')
            {
                $construction['bedrooms_from'] = $obj->bedrooms_from;
            }
            if (isset($obj->bedrooms_to) && $obj->bedrooms_to != '')
            {
                $construction['bedrooms_to'] = $obj->bedrooms_to;
            }
            if (isset($obj->bathrooms_from) && $obj->bathrooms_from != '')
            {
                $construction['bathrooms_from'] = $obj->bathrooms_from;
            }
            if (isset($obj->bathrooms_to) && $obj->bathrooms_to != '')
            {
                $construction['bathrooms_to'] = $obj->bathrooms_to;
            }
            if (isset($obj->built_size_from) && $obj->built_size_from != '')
            {
                $construction['built_size_from'] = $obj->built_size_from;
            }
            if (isset($obj->built_size_to) && $obj->built_size_to != '')
            {
                $construction['built_size_to'] = $obj->built_size_to;
            }
            if (isset($obj->phase) && count($obj->phase) > 0)
            {
                $phases = [];
                foreach ($obj->phase as $phase)
                {
                    $arr = [];
                    if (isset($phase->phase_name) && $phase->phase_name != '')
                    {
                        $arr['phase_name'] = $phase->phase_name;
                    }
                    if (isset($phase->price_from) && $phase->price_from != '')
                    {
                        $arr['price_from'] = $phase->price_from;
                    }
                    if (isset($phase->price_to) && $phase->price_to != '')
                    {
                        $arr['price_to'] = $phase->price_to;
                    }
                    if (isset($phase->tq) && count($phase->tq) > 0)
                    {
                        $all_types = Dropdowns::types();
                        $types = [];
                        foreach ($phase->tq as $tq)
                        {
                            if (isset($tq->type) && $tq->type != '')
                            {
                                foreach ($all_types as $type)
                                {
                                    if ($type['key'] == $tq->type)
                                        $types[] = isset($type['value'][strtolower($contentLang)]) ? $type['value'][strtolower($contentLang)] : (isset($type['value']['en']) ? $type['value']['en'] : '');
                                }
                            }
                        }
                        $arr['types'] = $types;
                    }
                    $phases[] = $arr;
                }
                $construction['phases'] = $phases;
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
        $return_data['property_features']['distances'] = $distances;
        $return_data['construction_data'] = $construction;
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
         * transaction 5 = short term rental
         * transaction 6 = long term rental
         * transaction 7 = Resale in Categories
         */
        if (isset($get["transaction"]) && $get["transaction"] != "")
        {
            if ($get["transaction"] == '1')
            {
                $query .= '&rent=1';
            }
            if ($get["transaction"] == '5')
            {
                $query .= '&rent=1&st_rental=1';
            }
            if ($get["transaction"] == '6')
            {
                $query .= '&rent=1&lt_rental=1';
            }
            if ($get["transaction"] == '2')
            {
                $query .= '&categories[]=repossession';
            }

            if ($get["transaction"] == '3')
            {
                $query .= '&new_construction=1';
            }
            if ($get["transaction"] == '4')
            {
                $query .= '&sale=1';
            }
            if ($get["transaction"] == '7')
            {
                $query .= '&sale=1&not_new_construction=1';
            }
        }

        if (isset($get['orientations']) && $get['orientations'] != '')
        {
            if (is_array($get['orientations']) && count($get['orientations']) > 0)
            {
                foreach ($get["orientations"] as $value)
                {
                    if ($value != '')
                    {
                        $query .= '&orientation[]=' . $value;
                    }
                }
            }
        }

        if (isset($get['sea_views']) && $get['sea_views'] != '')
        {
            if (is_array($get['sea_views']) && count($get['sea_views']) > 0)
            {
                foreach ($get["sea_views"] as $value)
                {
                    if ($value != '')
                    {
                        $query .= '&views[]=' . $value;
                    }
                }
            }
        }

        if (isset($get['swimming_pools']) && $get['swimming_pools'] != '')
        {
            if (is_array($get['swimming_pools']) && count($get['swimming_pools']) > 0)
            {
                foreach ($get["swimming_pools"] as $value)
                {
                    if ($value != '')
                    {
                        $query .= '&pool[]=' . $value;
                    }
                }
            }
        }

        if (isset($get['furnitures']) && $get['furnitures'] != '')
        {
            if (is_array($get['furnitures']) && count($get['furnitures']) > 0)
            {
                foreach ($get["furnitures"] as $value)
                {
                    if ($value != '')
                    {
                        $query .= '&furniture[]=' . $value;
                    }
                }
            }
        }
        if (isset($get['pets']) && $get['pets'] == 'true')
        {
            $query .= '&categories[]=pets_allowed';
        }

        if (isset($get['parkings']) && $get['parkings'] != '')
        {
            if (is_array($get['parkings']) && count($get['parkings']) > 0)
            {
                foreach ($get["parkings"] as $value)
                {
                    if ($value != '')
                    {
                        $query .= '&parking[]=' . $value;
                    }
                }
            }
        }

        if (isset($get['province']) && $get['province'] != '')
        {
            if (is_array($get["province"]) && count($get["province"]))
            {
                foreach ($get["province"] as $value)
                {
                    if ($value != '')
                    {
                        $query .= '&address_province[]=' . $value;
                    }
                }
            }
            else
            {
                $query .= '&address_province[]=' . $get['province'];
            }
        }
        if (isset($get["location"]) && $get["location"] != "")
        {
            if (is_array($get["location"]) && count($get["location"]))
            {
                foreach ($get["location"] as $value)
                {
                    if ($value != '')
                    {
                        $query .= '&location[]=' . $value;
                    }
                }
            }
        }
        if (isset($get["city"]) && $get["city"] != "")
        {
            if (is_array($get["city"]) && count($get["city"]))
            {
                foreach ($get["city"] as $value)
                {
                    if ($value != '')
                    {
                        $query .= '&address_city[]=' . $value;
                    }
                }
            }
        }
        if (isset($get["type"]) && is_array($get["type"]) && $get["type"] != "")
        {
            foreach ($get["type"] as $key => $value)
            {
                if ($value != '')
                {
                    $query .= '&type_one[]=' . $value;
                }
            }
        }
        if (isset($get["type2"]) && is_array($get["type2"]) && $get["type2"] != "")
        {
            foreach ($get["type2"] as $key => $value)
            {
                if ($value != '')
                {
                    $query .= '&type_two[]=' . $value;
                }
            }
        }

        if (isset($get["location_group"]) && is_string($get["location_group"]) && $get["location_group"] != '')
        {
            $query .= '&location_group[]=' . $get["location_group"];
        }
        if (isset($get["location_group"]) && is_array($get["location_group"]) && count($get["location_group"]) > 0)
        {
            foreach ($get["location_group"] as $key => $value)
            {
                $query .= '&location_group[]=' . $value;
            }
        }
        if (isset($get["lg_by_key"]) && is_string($get["lg_by_key"]) && $get["lg_by_key"] != '')
        {
            $query .= '&lg_by_key[]=' . $get["lg_by_key"];
        }
        if (isset($get["lg_by_key"]) && is_array($get["lg_by_key"]) && count($get["lg_by_key"]) > 0)
        {
            foreach ($get["lg_by_key"] as $key => $value)
            {
                $query .= '&lg_by_key[]=' . $value;
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
        if (isset($get["booking_data"]) && $get["booking_data"] != "")
        {
            $query .= '&booking_data=' . $get["booking_data"];
        }
        if (isset($get["st_date_from"]) && $get["st_date_from"] != "" && $get["st_date_from"] != "Arrival" && isset($get["st_date_from_submit"]) && $get["st_date_from_submit"] != "")
        {
            $stdf = new \DateTime($get["st_date_from_submit"]);
            $query .= '&booking_from=' . $stdf->getTimestamp();
        }
        if (isset($get["st_date_to"]) && $get["st_date_to"] != "" && $get["st_date_to"] != "Return" && isset($get["st_date_to_submit"]) && $get["st_date_to_submit"] != "")
        {
            $stdt = new \DateTime($get["st_date_to_submit"]);
            $query .= '&booking_to=' . $stdt->getTimestamp();
        }
        if (isset($get["st_from"]) && $get["st_from"] != "")
        {
            $query .= '&st_new_price[]=' . $get["st_from"];
        }
        if (isset($get["st_from"]) && $get["st_from"] == "")
        {
            $query .= '&st_new_price[]=0';
        }
        if (isset($get["st_to"]) && $get["st_to"] != "")
        {
            $query .= '&st_new_price[]=' . $get["st_to"];
        }
        if (isset($get["st_to"]) && $get["st_to"] == "")
        {
            $query .= '&st_new_price[]=100000000';
        }
        if (isset($get["sleeps"]) && $get["sleeps"] != "")
        {
            $query .= '&sleeps=' . $get["sleeps"];
        }
        if (isset($get["transaction"]) && $get["transaction"] != '' && $get["transaction"] == '1')
        {
            if (isset($get["price_from"]) && $get["price_from"] != "")
            {
                $query .= '&lt_new_price[]=' . $get["price_from"];
            }
            if (isset($get["price_from"]) && $get["price_from"] == "" && $get["price_to"] != "")
            {
                $query .= '&lt_new_price[]=0';
            }
            if (isset($get["price_to"]) && $get["price_to"] != "")
            {
                $query .= '&lt_new_price[]=' . $get["price_to"];
            }
            if (isset($get["price_to"]) && $get["price_to"] == "" && $get["price_from"] != "")
            {
                $query .= '&lt_new_price[]=100000000';
            }
        }
        else
        {
            if (isset($get["price_range"]) && $get["price_range"] != "")
            {
                $from = substr($get["price_range"], 0, strrpos($get["price_range"], '-'));
                $to = substr($get["price_range"], strrpos($get["price_range"], '-') + 1);

                $query .= '&currentprice[]=' . $from;
                $query .= '&currentprice[]=' . $to;
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
        }
        if (isset($get["orientation"]) && $get["orientation"] != "")
        {
            $query .= '&orientation[]=' . $get['orientation'];
        }
        if (isset($get["usefull_area"]) && $get["usefull_area"] != "")
        {
            $query .= '&usefull_area=' . $get['usefull_area'];
        }
        if (isset($get["min_useful_area"]) && $get["min_useful_area"] != "")
        {
            $query .= '&min_useful_area=' . $get['min_useful_area'];
        }
        if (isset($get["plot"]) && $get["plot"] != "")
        {
            $query .= '&plot=' . $get['plot'];
        }
        if (isset($get["min_plot"]) && $get["min_plot"] != "")
        {
            $query .= '&min_plot=' . $get['min_plot'];
        }
        if (isset($get["communal_pool"]) && $get["communal_pool"] != "" && $get["communal_pool"])
        {
            $query .= '&pool[]=pool_communal';
        }
        if (isset($get["pool"]) && $get["pool"] != '')
        {
            $query .= '&pool[]=' . $get["pool"];
        }
        if (isset($get["private_pool"]) && $get["private_pool"] != "" && $get["private_pool"])
        {
            $query .= '&pool[]=pool_private';
        }
        if (isset($get["sold"]) && $get["sold"] != '' && $get["sold"])
        {
            $query .= '&sale=true';
        }
        if (isset($get["rented"]) && $get["rented"] != '' && $get["rented"])
        {
            $query .= '&rent=true';
        }
        if (isset($get["distressed"]) && $get["distressed"] != '' && $get["distressed"])
        {
            $query .= '&categories[]=distressed';
        }
        if (isset($get["exclusive"]) && $get["exclusive"] != '' && $get["exclusive"])
        {
            $query .= '&exclusive=true';
        }
        if (isset($get["first_line_beach"]) && $get["first_line_beach"] != '' && $get["first_line_beach"])
        {
            $query .= '&categories[]=beachfront';
        }
        if (isset($get["price_reduced"]) && $get["price_reduced"] != '' && $get["price_reduced"])
        {
            $query .= '&categories[]=reduced';
        }
        if (isset($get["golf"]) && $get["golf"] != '' && $get["golf"])
        {
            $query .= '&categories[]=golf';
        }
        if (isset($get["luxury"]) && $get["luxury"] != '' && $get["luxury"])
        {
            $query .= '&categories[]=luxury';
        }
        if (isset($get["close_to_sea"]) && $get["close_to_sea"] != '' && $get["close_to_sea"])
        {
            $query .= '&settings[]=close_to_sea';
        }
        if (isset($get["sea_view"]) && $get["sea_view"] != '' && $get["sea_view"])
        {
            $query .= '&views[]=sea';
        }
        if (isset($get["panoramic"]) && $get["panoramic"] != '' && $get["panoramic"])
        {
            $query .= '&views[]=panoramic';
        }
        if (isset($get["pool"]) && $get["pool"] != '' && $get["pool"])
        {
            $query .= '&pool[]=pool_private';
        }
        if (isset($get["storage_room"]) && $get["storage_room"] != '' && $get["storage_room"])
        {
            $query .= '&features[]=storage_room';
        }
        if (isset($get["garage"]) && $get["garage"] != '' && $get["garage"])
        {
            $query .= '&parking[]=garage';
        }
        if (isset($get["parking"]) && $get["parking"] != '' && $get["parking"])
        {
            $query .= '&parking[]=private';
        }
        if (isset($get["urbanisation"]) && $get["urbanisation"] != '')
        {
            $query .= '&urbanisation=' . $get['urbanisation'];
        }
        if (isset($get["new_property"]) && $get["new_property"] != "" && $get["new_property"])
        {
            $query .= '&conditions[]=never_lived';
        }
        if (isset($get["conditions"]) && is_array($get["conditions"]) && $get["conditions"] != "")
        {
            foreach ($get["conditions"] as $condition)
            {
                $query .= '&conditions[]=' . $condition;
            }
        }
        if (isset($get["features"]) && is_array($get["features"]) && $get["features"] != "")
        {
            foreach ($get["features"] as $feature)
            {
                $query .= '&features[]=' . $feature;
            }
        }
        if (isset($get["climate_control"]) && is_array($get["climate_control"]) && $get["climate_control"] != "")
        {
            foreach ($get["climate_control"] as $climate_control)
            {
                $query .= '&climate_control[]=' . $climate_control;
            }
        }
        if (isset($get["reference"]) && $get["reference"] != "")
        {
            $query .= '&reference=' . $get['reference'];
        }
        if (isset($get["agency_reference"]) && $get["agency_reference"] != "")
        {
            $query .= '&agency_reference=' . $get['agency_reference'];
        }
        if (isset($get["sale"]) && $get["sale"] != "")
        {
            $query .= '&sale=1';
        }
        if (isset($get["rent"]) && $get["rent"] != "")
        {
            $query .= '&rent=1';
        }
        if (isset($get["st_rental"]) && $get["st_rental"] != "")
        {
            $query .= '&st_rental=1';
        }
        if (isset($get["lt_rental"]) && $get["lt_rental"] != "")
        {
            $query .= '&lt_rental=1';
        }
        if (isset($get["ids"]) && $get["ids"] != "")
        {
            $query .= '&favourite_ids=' . $get["ids"];
        }
        if (isset($get["keywords"]) && $get["keywords"] != "")
        {
            $query .= '&keywords=' . $get["keywords"];
        }
        if (isset($get['orderby']) && !empty($get['orderby']))
        {
            if ($get['orderby'] == 'dateASC')
            {
                $query .= '&orderby[]=created_at&orderby[]=ASC';
            }
            elseif ($get['orderby'] == 'dateDESC')
            {
                $query .= '&orderby[]=created_at&orderby[]=DESC';
            }
            elseif ($get['orderby'] == 'priceASC')
            {
                $query .= '&orderby[]=currentprice&orderby[]=ASC';
            }
            elseif ($get['orderby'] == 'priceDESC')
            {
                $query .= '&orderby[]=currentprice&orderby[]=DESC';
            }
            elseif ($get['orderby'] == 'bedsDESC')
            {
                $query .= '&orderby[]=bedrooms&orderby[]=DESC';
            }
            elseif ($get['orderby'] == 'bedsASC')
            {
                $query .= '&orderby[]=bedrooms&orderby[]=ASC';
            }
            elseif ($get['orderby'] == 'statusDESC')
            {
                $query .= '&orderby[]=status&orderby[]=DESC';
            }
            elseif ($get['orderby'] == 'statusASC')
            {
                $query .= '&orderby[]=status&orderby[]=ASC';
            }
        }
        return $query;
    }

    public static function findAllWithLatLang()
    {
        $webroot = Yii::getAlias('@webroot');
        $url = Yii::$app->params['apiUrl'] . 'properties/properties-with-latlang&user=' . Yii::$app->params['user'];
        if (!is_dir($webroot . '/uploads/'))
        {
            mkdir($webroot . '/uploads/');
        }
        if (!is_dir($webroot . '/uploads/temp/'))
        {
            mkdir($webroot . '/uploads/temp/');
        }
        $file = $webroot . '/uploads/temp/properties-latlong.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = file_get_contents($url);
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, true);
    }

    public static function getAgency()
    {
        $lang = strtoupper(\Yii::$app->language);
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
            mkdir($webroot . '/uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        $file = $webroot . '/uploads/temp/agency.json';
        $url = Yii::$app->params['apiUrl'] . 'properties/agency&user=' . Yii::$app->params['user'];
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = file_get_contents($url);
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
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
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/get-listing-agent&user=' . Yii::$app->params['user'] . '&listing_agent=' . $id);
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function Categories()
    {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
            mkdir($webroot . '/uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        $file = $webroot . '/uploads/temp/property_categories.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/categories&user=' . Yii::$app->params['user']);
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        $Arr = json_decode($file_data, TRUE);
        $return_data = [];
        foreach ($Arr as $data)
        {
            if (isset($data['value']['en']))
                $return_data[$data['key']] = $data['value']['en'];
        }
        return $return_data;
    }

}
