<?php

namespace optima\models;

use Yii;
use yii\base\Model;
use optima\models\Cms;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for Properties.
 *
 */
class Properties extends Model
{

    public $type_one;
    public $type_two;
    public $bedrooms;
    public $bathrooms;
    public $currentprice;
    public $description;

    public function rules()
    {
        return [[['type_one', 'type_two', 'bedrooms', 'bathrooms', 'currentprice','description'], 'safe']];

    }

    /**
     * LoginForm is the model behind the login form.
     *
     * @property string $query    query for properties search.
     * @property bool   $wm       send true for wm server.
     * @property bool   $cache    send true for cache response.
     * @property array  $options  options for data response e.g. 
     *                            'images_size' => 1200
     *                            'watermark_size' => 100   range is 100 - 500
     *
     */
    public static function findAll($query, $wm = false, $cache = false, $options = [])
    {
        $langugesSystem = Cms::SystemLanguages();
        $lang = strtoupper(\Yii::$app->language);
        $contentLang = $lang;
        foreach ($langugesSystem as $sysLang) {
            if ((isset($sysLang['internal_key']) && $sysLang['internal_key'] != '') && $lang == $sysLang['internal_key']) {
                $contentLang = $sysLang['key'];
            }
        }
        if (isset($options['set_query']) && $options['set_query'] == false) {
            $query .= $query;
        } else {
            $query .= self::setQuery();
        }
        $url = Yii::$app->params['apiUrl'] . 'properties&user_apikey=' . Yii::$app->params['api_key'] . $query;
        //  echo $url;
        //  die;

        if ($cache == true) {
            $JsonData = self::DoCache($query, $url);
        } else {
            $JsonData = Functions::getCRMData($url, false);
        }
        if (strpos($query, "&latlng=true")) {
            return json_decode($JsonData, true);
        }
        if (strpos($query, "&just_external_reference=true") || strpos($query, "&just_agency_reference=true") || strpos($query, "&just_reference=true")) {
            return json_decode($JsonData, true);
        }
        $apiData = json_decode($JsonData);

        $settings = Cms::settings();

        try {
            $get = Yii::$app->request->get();
        } catch (\Throwable $th) {
            $get = [];
        }
        /* to set the display price
         * transaction 1 = Rental
         * transaction 4 = Resale
         */
        $rent = false;
        $strent = false;
        $ltrent = false;
        $sale = true;
        $mixed = false;
        if (isset($get["transaction"]) && $get["transaction"] != "") {
            if ($get["transaction"] == '1') {
                $rent = true;
            } elseif ($get["transaction"] == '5') {
                $rent = true;
                $strent = true;
            } elseif ($get["transaction"] == '6') {
                $rent = true;
                $ltrent = true;
            } elseif ($get["transaction"] == '8') { // Mixed for Featued properties
                $mixed = true;
                $sale = true;
            } else {
                $sale = true;
            }
        }
        $query_components = explode("&", $query);
        if (count($query_components) > 0) {
            if (in_array('rent=1', $query_components) || in_array('st_rental=1', $query_components)) {
                $rent = true;
            }
        }
        if (isset($get["rent"]) && $get['rent'] != "") {
            $rent = true;
        }
        if (isset($get["st_rental"]) && $get['st_rental'] != "") {
            $rent = true;
            $strent = true;
        }
        $rent_check = isset($options['rent']) ? $options['rent'] : false;

        $return_data = [];
        if ($apiData != '') {
            foreach ($apiData as $property) {
                $title = 'rental_title';
                $description = 'rental_description';
                $price = 'rent';
                $seo_title = 'rental_seo_title';
                $seo_description = 'rental_seo_description';
                $keywords = 'rental_keywords';
                $perma_link = 'rental_perma_link';
                if (isset($property->property)) {
                    if (((isset($property->property->sale) && $property->property->sale == true) || (isset($property->property->transfer) && $property->property->transfer == true)) && !$rent_check) {
                        $title = 'title';
                        $description = 'description';
                        $price = 'sale';
                        $seo_title = 'seo_title';
                        $seo_description = 'seo_description';
                        $keywords = 'keywords';
                        $perma_link = 'perma_link';
                    }
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
                        if (isset($property->property->$ref)) {
                            $data['reference'] = $property->property->$ref;
                        } else {
                            $data['reference'] = $property->agency_code . '-' . $property->property->reference;
                        }
                    } else {
                        $data['reference'] = $property->agency_code . '-' . $property->property->reference;
                    }
                    if (isset($property->property->new_construction) && $property->property->new_construction == 1) {
                        $data['new_construction'] = $property->property->new_construction;
                    }
                    if (isset($property->property->sale) && $property->property->sale == true && isset($property->property->title->$contentLang) && $property->property->title->$contentLang != '') {
                        $data['sale_rent_title'] = $property->property->title->$contentLang;
                    } elseif (isset($property->property->rent) && $property->property->rent == true && isset($property->property->rental_title->$contentLang) && $property->property->rental_title->$contentLang != '') {
                        $data['sale_rent_title'] = $property->property->rental_title->$contentLang;
                    } elseif (isset($property->property->location) && isset($property->property->type_one)) {
                        $data['sale_rent_title'] = (isset($property->property->type_one) ? \Yii::t('app', strtolower($property->property->type_one)) : \Yii::t('app', 'N/A')) . ' ' . \Yii::t('app', 'in') . ' ' . \Yii::t('app', $property->property->location);
                    }

                    if (isset($property->property->$title->$contentLang) && $property->property->$title->$contentLang != '') {
                        $data['title'] = $property->property->$title->$contentLang;
                    } elseif (isset($property->property->location) && isset($property->property->type_one)) {
                        $data['title'] = (isset($property->property->type_one) ? \Yii::t('app', strtolower($property->property->type_one)) : \Yii::t('app', 'N/A')) . ' ' . \Yii::t('app', 'in') . ' ' . \Yii::t('app', $property->property->location);
                    }
                    if (isset($property->property->$seo_title->$contentLang) && $property->property->$seo_title->$contentLang != '') {
                        $data['meta_title'] = $property->property->$seo_title->$contentLang;
                    }


                    if (isset($property->property->property_name)) {
                        $data['property_name'] = $property->property->property_name;
                    }

                    if (isset($property->property->status)) {
                        $data['status'] = $property->property->status;
                    }
                    if (isset($property->property->type_one)) {
                        $data['type'] = $property->property->type_one;
                    }
                    if (isset($property->property->perma_link->$lang)) {
                        $data['perma_link'] = $property->property->perma_link->$lang;
                    }
                    if (isset($property->property->type_two)) {
                        $data['type_two'] = $property->property->type_two;
                    }
                    $agency = Yii::$app->params['agency'];
                    if (isset($property->property->latitude) && $property->property->latitude != '') {
                        $data['lat'] = $property->property->latitude;
                    } elseif (isset($property->property->private_info_object->$agency->latitude)) {
                        $data['lat'] = $property->property->private_info_object->$agency->latitude;
                    }

                    if (isset($property->property->longitude) && $property->property->longitude != '') {
                        $data['lng'] = $property->property->longitude;
                    } elseif (isset($property->property->private_info_object->$agency->longitude)) {
                        $data['lng'] = $property->property->private_info_object->$agency->longitude;
                    }
                    // if (isset($property->property->sale) && $property->property->sale == true && isset($property->property->description->$contentLang) && $property->property->description->$contentLang != '') {
                    //     $data['sale_rent_description'] = $property->property->description->$contentLang;
                    // } elseif (isset($property->property->rent) && $property->property->rent == true && isset($property->property->rental_description->$contentLang) && $property->property->rental_description->$contentLang != '') {
                    //     $data['sale_rent_description'] = $property->property->rental_description->$contentLang;
                    // }
                    if (isset($property->property->$description->$contentLang)) {
                        $data['sale_rent_description'] = $property->property->$description->$contentLang;
                    }
                    if (isset($property->property->$description->$contentLang)) {
                        $data['description'] = $property->property->$description->$contentLang;
                    }
                    if (isset($property->property->location)) {
                        $data['location'] = $property->property->location;
                    }
                    if (isset($property->property->p_style)) {
                        $data['p_style'] = $property->property->p_style;
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
                        if (isset($property->property->st_rental) && $property->property->st_rental == 1) {
                            $data['st_rental'] = $property->property->st_rental;
                        }
                        if (isset($property->property->lt_rental) && $property->property->lt_rental == 1) {
                            $data['lt_rental'] = $property->property->lt_rental;
                        }
                    }
                    if (isset($property->property->currency) && $property->property->currency != '') {
                        $data['currency'] = $property->property->currency;
                    }
                    if (isset($property->property->own) && $property->property->own == true) {
                        $data['own'] = true;
                    }
                    if (isset($property->property->bedrooms) && $property->property->bedrooms > 0) {
                        $data['bedrooms'] = $property->property->bedrooms;
                    }
                    if (isset($property->property->bathrooms) && $property->property->bathrooms > 0) {
                        $data['bathrooms'] = $property->property->bathrooms;
                    }
                    if (isset($property->property->occupancy_status)) {
                        $data['occupancy_status'] = $property->property->occupancy_status;
                    }
                    if (isset($property->property->sleeps) && $property->property->sleeps > 0) {
                        $data['sleeps'] = $property->property->sleeps;
                    }
                    if (isset($property->property->living_rooms) && $property->property->living_rooms > 0) {
                        $data['living_rooms'] = $property->property->living_rooms;
                    }
                    if (isset($property->property->address_street) && $property->property->address_street != '') {
                        $data['address_street'] = $property->property->address_street;
                    } elseif (isset($property->property->private_info_object->$agency->address_street)) {
                        $data['address_street'] = $property->property->private_info_object->$agency->address_street;
                    }
                    if (isset($property->property->vt_ids) && !empty($property->property->vt_ids)) {
                        $data['vt'] = $property->property->vt_ids;
                    }
                    if (isset($property->property->address_street_number) && $property->property->address_street_number != '') {
                        $data['address_street_number'] = $property->property->address_street_number;
                    } elseif (isset($property->property->private_info_object->$agency->address_street_number)) {
                        $data['address_street_number'] = $property->property->private_info_object->$agency->address_street_number;
                    }
                    if ($rent) {
                        if ($ltrent && isset($property->property->lt_rental) && $property->property->lt_rental == true && isset($property->property->period_seasons->{'0'}->new_price)) {
                            $data['price'] = ($property->property->period_seasons->{'0'}->new_price != 0) ? number_format((int) $property->property->period_seasons->{'0'}->new_price, 0, '', '.') . ' ' . Yii::t('app', 'per_month') : '';
                        } elseif ($strent && isset($property->property->st_rental) && $property->property->st_rental == true && isset($property->property->rental_seasons)) {
                            $st_price = [];
                            foreach ($property->property->rental_seasons as $seasons) {
                                $st_price[] = ['price' => isset($seasons->new_price) ? $seasons->new_price : '', 'period' => isset($seasons->period) ? $seasons->period : '', 'seasons' => isset($seasons->seasons) ? $seasons->seasons : ''];
                            }
                            if (isset(Yii::$app->params['rental_logic']) && Yii::$app->params['rental_logic']) {
                                $gdprice = [];
                                $st_price = 0;
                                foreach ($property->property->rental_seasons as $seasons) {
                                    /* For price per week */
                                    if (isset(Yii::$app->params['rental_logic_week']) && Yii::$app->params['rental_logic_week']) {
                                        if (isset($seasons->gross_price)) {
                                            $gdprice[] = $seasons->gross_price;
                                        }
                                    } else {
                                        if (isset($seasons->gross_day_price)) {
                                            if (isset($seasons->period_to) && $seasons->period_to >= time()) {
                                                //$gdprice[] = $seasons->gross_day_price;
                                                if (isset($seasons->discounts)) {
                                                    foreach ($seasons->discounts as $discount) {
                                                        if (isset($discount->discount_price) && $discount->discount_price != '') {
                                                            $gdprice[] = $discount->discount_price;
                                                        } else {
                                                            $gdprice[] = $seasons->gross_day_price;
                                                        }
                                                    }
                                                } else {
                                                    $gdprice[] = $seasons->gross_day_price;
                                                }
                                            }
                                        }
                                    }
                                }
                                if (count($gdprice) > 0) {
                                    $st_price = min($gdprice);
                                }
                                $b_price = 0;
                                if (isset($property->bookings_extras) && count((array) $property->bookings_extras) > 0) {
                                    foreach ($property->bookings_extras as $booking_extra) {
                                        $divider = 1;
                                        $multiplyer = 1;
                                        if (isset($booking_extra->type) && ($booking_extra->type == 'per_week' || $booking_extra->type == 'per_stay')) {
                                            $divider = 7;
                                        }
                                        if (isset(Yii::$app->params['exclude_per_stay_extras']) && isset($booking_extra->type) && $booking_extra->type == 'per_stay') {
                                            $multiplyer = 0;
                                        }
                                        if (isset($booking_extra->add_to_price) && $booking_extra->add_to_price == true) {
                                            $b_price = $b_price + (isset($booking_extra->price) ? ($booking_extra->price * 1 / $divider * $multiplyer) : 0);
                                        }
                                    }
                                }
                                if (isset($property->bookings_cleaning) && count((array) $property->bookings_cleaning) > 0) {
                                    foreach ($property->bookings_cleaning as $bookings_cleaning) {
                                        $divider = 1;
                                        $multiplyer = 1;
                                        if (isset($bookings_cleaning->type) && ($bookings_cleaning->type == 'per_week' || $bookings_cleaning->type == 'per_stay')) {
                                            $divider = 7;
                                        }
                                        if (isset(Yii::$app->params['exclude_per_stay_extras']) && $bookings_cleaning->type == 'per_stay') {
                                            $multiplyer = 0;
                                        }
                                        if (isset($bookings_cleaning->type) && $bookings_cleaning->type == 'per_hour') {
                                            $multiplyer = 24;
                                        }
                                        if (isset($bookings_cleaning->charge_to) && $bookings_cleaning->charge_to == 'client') {
                                            $b_price = $b_price + (isset($bookings_cleaning->price) ? ($bookings_cleaning->price * 1 * $multiplyer / $divider) : 0);
                                        }
                                    }
                                }

                                $data['price'] = number_format($st_price + $b_price, 2);
                            } else {
                                $data['price'] = (isset($st_price[0]['price']) && $st_price[0]['price'] != 0) ? number_format((int) $st_price[0]['price'], 0, '', '.') . ' ' . Yii::t('app', str_replace('_', ' ', (isset($st_price[0]['period']) ? $st_price[0]['period'] : ''))) : '';
                            }
                            $data['seasons'] = isset($st_price[0]['seasons']) ? $st_price[0]['seasons'] : '';
                            $data['season_data'] = ArrayHelper::toArray($property->property->rental_seasons);
                        }
                    } else {
                        if (isset($property->property->currentprice)) {
                            $data['price'] = ($property->property->currentprice != 0) ? number_format((int) $property->property->currentprice, 0, '', '.') : '';
                        }
                    }
                    if ($mixed) {
                        if (isset($property->property->rental_seasons)) {
                            $data['season_data'] = $property->property->rental_seasons ? ArrayHelper::toArray($property->property->rental_seasons) : "";
                        }
                    }

                    if ($rent) {
                        if (isset($property->property->st_rental) && $property->property->st_rental == true && isset($property->property->rental_seasons)) {
                            $st_price = [];
                            foreach ($property->property->rental_seasons as $seasons) {
                                $st_price[] = ['price' => isset($seasons->new_price) ? $seasons->new_price : '', 'period' => isset($seasons->period) ? $seasons->period : '', 'seasons' => isset($seasons->seasons) ? $seasons->seasons : ''];
                            }
                            $data['season_data'] = ArrayHelper::toArray($property->property->rental_seasons);
                        } elseif (isset($property->property->lt_rental) && $property->property->lt_rental == true && isset($property->property->period_seasons)) {
                            $st_price = [];
                            foreach ($property->property->period_seasons as $seasons) {
                                $st_price[] = ['price' => isset($seasons->new_price) ? $seasons->new_price : '', 'period' => isset($seasons->period) ? $seasons->period : '', 'seasons' => isset($seasons->seasons) ? $seasons->seasons : ''];
                            }
                            $data['season_data'] = ArrayHelper::toArray($property->property->period_seasons);
                        }
                    } else {
                        if (isset($property->property->currentprice)) {
                            $data['price'] = ($property->property->currentprice != 0) ? number_format((int) $property->property->currentprice, 0, '', '.') : '';
                        }
                    }
                    if (isset($property->property->currentprice) && $property->property->currentprice > 0) {
                        $data['currentprice'] = str_replace(',', '.', (number_format((int) ($property->property->currentprice))));
                    }
                    if (isset($property->property->oldprice->price) && $property->property->oldprice->price > 0) {
                        $data['oldprice'] = str_replace(',', '.', (number_format((int) ($property->property->oldprice->price))));
                    }
                    if (isset($property->property->oldprice->price_on_demand) && $property->property->oldprice->price_on_demand == true) {
                        $data['price_on_demand'] = true;
                    }
                    if (isset($property->property->sale) && $property->property->sale == 1 || isset($property->property->transfer) && $property->property->transfer == 1) {
                        if (isset($property->property->currentprice) && $property->property->currentprice > 0) {
                            $data['saleprice'] = str_replace(',', '.', (number_format((int) ($property->property->currentprice))));
                        }
                    }
                    if (isset($property->property->rent) && $property->property->rent == 1) {
                        if (isset($property->property->lt_rental) && $property->property->lt_rental == true && isset($property->property->period_seasons) && is_array($property->property->period_seasons) && isset($property->property->period_seasons[0]->new_price)) {
                            $data['ltprice'] = ($property->property->period_seasons[0]->new_price != 0) ? number_format((int) $property->property->period_seasons[0]->new_price, 0, '', '.') . ' ' . Yii::t('app', 'per_month') : '';
                            $data['lt_price'] = ($property->property->period_seasons[0]->new_price != 0) ? number_format((int) $property->property->period_seasons[0]->new_price, 0, '', '.') . ' € ' . Yii::t('app', 'per_month') : '';
                        }
                        if (isset($property->property->lt_rental) && $property->property->lt_rental == true && isset($property->property->period_seasons) && is_object($property->property->period_seasons) && $property->property->period_seasons->{0}->new_price) {
                            $data['ltprice'] = ($property->property->period_seasons->{0}->new_price != 0) ? number_format((int) $property->property->period_seasons->{0}->new_price, 0, '', '.') . ' ' . Yii::t('app', 'per_month') : '';
                            $data['lt_price'] = ($property->property->period_seasons->{0}->new_price != 0) ? number_format((int) $property->property->period_seasons->{0}->new_price, 0, '', '.') . ' € ' . Yii::t('app', 'per_month') : '';
                        }
                        if (isset($property->property->st_rental) && $property->property->st_rental == true && isset($property->property->rental_seasons)) {
                            $st_price = [];
                            $discount = false;
                            $data['discount'] = [];
                            foreach ($property->property->rental_seasons as $seasons) {
                                $st_price[] = ['price' => isset($seasons->new_price) ? $seasons->new_price : '', 'period' => isset($seasons->period) ? $seasons->period : '', 'seasons' => isset($seasons->seasons) ? $seasons->seasons : ''];
                                if (!$discount && isset($seasons->old_price) && $seasons->old_price && isset($seasons->new_price) && $seasons->new_price) {
                                    $discount = true;
                                    $data['discount'] = [
                                        'value' => (($seasons->old_price * 1 - $seasons->new_price * 1) * 100) / ($seasons->new_price * 1),
                                        'from' => $seasons->period_from,
                                        'to' => $seasons->period_to
                                    ];
                                }
                            }
                            $data['price_per_day'] = (isset($st_price[0]['price']) && $st_price[0]['price'] != 0) ? number_format((int) $st_price[0]['price'], 0, '', '.') : '';
                            $data['period'] = (isset($st_price[0]['period']) ? $st_price[0]['period'] : '');
                            $data['stprice'] = (isset($st_price[0]['price']) && $st_price[0]['price'] != 0) ? number_format((int) $st_price[0]['price'], 0, '', '.') . ' € ' . Yii::t('app', str_replace('_', ' ', (isset($st_price[0]['period']) ? $st_price[0]['period'] : ''))) : '';

                            $wgprice = [];
                            $wst_price = 0;
                            if (isset($property->property->st_rental) && isset($property->property->rental_seasons)) {
                                foreach ($property->property->rental_seasons as $seasons) {
                                    /* For price per week */
                                    if (isset(Yii::$app->params['rental_logic_week']) && Yii::$app->params['rental_logic_week']) {
                                        if (isset($seasons->gross_price)) {
                                            $wgprice[] = $seasons->gross_price;
                                        }
                                    }
                                }
                                if (count($wgprice) > 0) {
                                    $wst_price = min($wgprice);
                                }
                                $b_price = 0;
                                if (isset($property->bookings_extras) && count((array) $property->bookings_extras) > 0) {
                                    foreach ($property->bookings_extras as $booking_extra) {
                                        $divider = 1;
                                        if (isset($booking_extra->type) && ($booking_extra->type == 'per_week'))
                                            $divider = 7;
                                        if (isset($booking_extra->add_to_price) && $booking_extra->add_to_price == true) {
                                            $b_price = $b_price + (isset($booking_extra->price) ? ($booking_extra->price * 1 / $divider) : 0);
                                        }
                                    }
                                }
                                if (isset($property->bookings_cleaning) && count((array) $property->bookings_cleaning) > 0) {
                                    foreach ($property->bookings_cleaning as $bookings_cleaning) {
                                        $divider = 1;
                                        $multiplyer = 1;
                                        if (isset($bookings_cleaning->type) && ($bookings_cleaning->type == 'per_week'))
                                            $divider = 7;
                                        if (isset($bookings_cleaning->type) && $bookings_cleaning->type == 'per_hour')
                                            $multiplyer = 24;
                                        if (isset($bookings_cleaning->charge_to) && $bookings_cleaning->charge_to == 'client')
                                            $b_price = $b_price + (isset($bookings_cleaning->price) ? ($bookings_cleaning->price * 1 * $multiplyer / $divider) : 0);
                                    }
                                }
                                $data['gprice_week'] = number_format($wst_price + $b_price, 2);
                            }
                        }
                    }
                    if (isset($property->property->dimensions) && $property->property->dimensions != '') {
                        $data['dimensions'] = $property->property->dimensions;
                    }
                    if (isset($property->property->built) && $property->property->built > 0) {
                        $data['built'] = $property->property->built;
                    }
                    if (isset($property->property->plot) && $property->property->plot > 0) {
                        $data['plot'] = $property->property->plot;
                    }
                    if (isset($property->property->exclusive) && $property->property->exclusive == true) {
                        $data['exclusive'] = true;
                    }
                    if (isset($property->property->custom_categories) && is_array($property->property->custom_categories)) {
                        $cats = self::Categories();
                        $catsArr = [];
                        foreach ($property->property->custom_categories as $catdata) {
                            if (isset($cats[$catdata])) {
                                $catsArr[$catdata] = $cats[$catdata];
                            }
                        }
                        $data['categories'] = $catsArr;
                    }
                    if (isset($property->property->terrace)) {
                        if (is_object($property->property->terrace)) {
                            if (isset($property->property->terrace->{0}->terrace) && $property->property->terrace->{0}->terrace > 0) {
                                $data['terrace'] = $property->property->terrace->{0}->terrace;
                            }
                        }
                        if (is_array($property->property->terrace)) {
                            if (isset($property->property->terrace[0]->terrace) && $property->property->terrace[0]->terrace > 0) {
                                $data['terrace'] = $property->property->terrace[0]->terrace;
                            }
                        }
                    }
                    if (isset($property->property->created_at) && !empty($property->property->created_at)) {
                        $data['created_at'] = $property->property->created_at;
                    }
                    if (isset($property->featured) && !empty($property->featured)) {
                        $data['featured'] = $property->featured;
                    }
                    if (isset($property->property->updated_at) && $property->property->updated_at != '') {
                        $data['updated_at'] = $property->property->updated_at;
                    }
                    if (isset($property->bookings_extras) && count($property->bookings_extras) > 0) {
                        $data['booking_extras'] = ArrayHelper::toArray($property->bookings_extras);
                    }
                    if (isset($property->property->own) && $property->property->own == true && isset($property->agency_logo) && !empty($property->agency_logo)) {
                        $data['agency_logo'] = 'https://images.optima-crm.com/agencies/' . (isset($property->agency_logo->_id) ? $property->agency_logo->_id : '') . '/' . (isset($property->agency_logo->logo->name) ? $property->agency_logo->logo->name : '');
                    } elseif ((!isset($property->property->own) || !$property->property->own) && isset($property->agency_logo) && !empty($property->agency_logo)) {
                        $data['agency_logo'] = 'https://images.optima-crm.com/companies/' . (isset($property->agency_logo->_id) ? $property->agency_logo->_id : '') . '/' . (isset($property->agency_logo->logo->name) ? $property->agency_logo->logo->name : '');
                    }
                    if (isset($property->bookings_cleaning) && count($property->bookings_cleaning) > 0) {
                        $data['booking_cleaning'] = ArrayHelper::toArray($property->bookings_cleaning);
                    }
                    if (isset($property->property->location_group) && $property->property->location_group != 'N/A') {
                        $data['location_group'] = $property->property->location_group;
                    }
                    if (isset($property->property->address_province)) {
                        $data['province'] = $property->property->address_province;
                    }
                    if (isset($property->property->value_of_custom->feet_custom_categories)) {
                        // $data['feet_custom_categories'] = $property->property->value_of_custom->feet_custom_categories;
                        $data['feet_custom_categories'] = ArrayHelper::toArray($property->property->value_of_custom->feet_custom_categories);
                    }

                    $slugs = [];
                    foreach ($langugesSystem as $lang_sys) {
                        $lang_sys_key = $lang_sys['key'];
                        $lang_sys_internal_key = isset($lang_sys['internal_key']) && $lang_sys['internal_key'] != '' ? $lang_sys['internal_key'] : '';
                        if (isset($property->property->$perma_link->$lang_sys_key) && $property->property->$perma_link->$lang_sys_key != '') {
                            $slugs[$lang_sys_internal_key] = $property->property->$perma_link->$lang_sys_key;
                        } elseif (isset($property->property->$title->$lang_sys_key) && $property->property->$title->$lang_sys_key != '') {
                            $slugs[$lang_sys_internal_key] = $property->property->$title->$lang_sys_key;
                        } else {
                            if (isset($property->property->type_one) && $property->property->type_one != '' && isset($slugs[$lang_sys_internal_key])) {
                                $slugs[$lang_sys_internal_key] = $property->property->type_one . ' ' . 'in' . ' ';
                            }
                            if (isset($property->property->location) && $property->property->location != '' && isset($slugs[$lang_sys_internal_key])) {
                                $slugs[$lang_sys_internal_key] = $slugs[$lang_sys_internal_key] . $property->property->location;
                            }
                        }
                    }
                    //        end slug_all
                    $data['slug_all'] = $slugs;
                    if (isset($property->attachments) && count($property->attachments) > 0) {
                        $attachments = [];
                        $attachment_alt_descriptions = [];
                        $watermark_size = isset($options['watermark_size']) && !empty($options['watermark_size']) ? $options['watermark_size'] . '/' : '';
                        $attachments_size = isset($options['images_size']) && !empty($options['images_size']) ? $options['images_size'] . '/' : '1200/';
                        if ($wm == true && isset(Yii::$app->params['img_url_wm'])) {
                            foreach ($property->attachments as $pic) {
                                $attachments[] = Yii::$app->params['img_url_wm'] . '/' . $pic->model_id . '/' . $attachments_size . $pic->file_md5_name;
                            }
                        } elseif (!$wm && isset(Yii::$app->params['img_url__without_watermark'])) {
                            foreach ($property->attachments as $pic) {
                                $attachments[] = Yii::$app->params['img_url__without_watermark'] . '/' . $pic->model_id . '/' . $attachments_size . $pic->file_md5_name;
                                $attachment_alt_descriptions[] = isset($pic->alt_description->$contentLang) ? $pic->alt_description->$contentLang : '';
                            }
                        } else {
                            foreach ($property->attachments as $pic) {
                                $attachments[] = Yii::$app->params['img_url'] . '/' . $watermark_size . $pic->model_id . '/' . $attachments_size . $pic->file_md5_name;
                                $attachment_alt_descriptions[] = isset($pic->alt_description->$contentLang) ? $pic->alt_description->$contentLang : '';
                            }
                        }
                        $data['attachments'] = $attachments;
                        $data['attachment_alt_desc'] = $attachment_alt_descriptions;
                    }
                    $categories = [];
                    $features = [];
                    $climate_control = [];
                    $kitchen = [];
                    $setting = [];
                    $orientation = [];
                    $views = [];
                    $videos = [];
                    $utilities = [];
                    $security = [];
                    $furniture = [];
                    $parking = [];
                    $garden = [];
                    $pool = [];
                    $pool_size = "";
                    $condition = [];
                    $rental_investment_info = [];
                    if (isset($property->property->value_of_custom) && isset($property->property->value_of_custom->basic_info)) {
                        foreach ($property->property->value_of_custom->basic_info as $value) {
                            if (isset($value->field) && isset($value->value) && $value->field != '' && $value->value != '') {
                                $rental_investment_info[$value->field] = $value->value;
                            }
                        }
                    }
                    $data['rental_investment_info'] = $rental_investment_info;
                    if (isset($property->property->feet_categories)) {
                        foreach ($property->property->feet_categories as $key => $value) {
                            if ($value == true) {
                                $categories[] = $key;
                            }
                        }
                    }
                    if (isset($property->property->feet_features)) {
                        foreach ($property->property->feet_features as $key => $value) {
                            if ($value == true) {
                                $features[] = $key;
                            }
                        }
                    }
                    if (isset($property->property->feet_climate_control)) {
                        foreach ($property->property->feet_climate_control as $key => $value) {
                            if ($value == true) {
                                $climate_control[] = $key;
                            }
                        }
                    }
                    if (isset($property->property->feet_kitchen)) {
                        foreach ($property->property->feet_kitchen as $key => $value) {
                            if ($value == true) {
                                $kitchen[] = $key;
                            }
                        }
                    }
                    if (isset($property->property->videos) && !empty($property->property->videos)) {
                        foreach ($property->property->videos as $key => $value) {
                            $videos[] = $value;
                        }
                    }
                    if (isset($property->property->feet_setting)) {
                        foreach ($property->property->feet_setting as $key => $value) {
                            if ($value == true) {
                                $setting[] = $key;
                            }
                        }
                    }
                    if (isset($property->property->feet_orientation)) {
                        foreach ($property->property->feet_orientation as $key => $value) {
                            if ($value == true) {
                                $orientation[] = $key;
                            }
                        }
                    }
                    if (isset($property->property->feet_views)) {
                        foreach ($property->property->feet_views as $key => $value) {
                            if ($value == true) {
                                $views[] = $key;
                            }
                        }
                    }
                    if (isset($property->property->feet_utilities)) {
                        foreach ($property->property->feet_utilities as $key => $value) {
                            if ($value == true) {
                                $utilities[] = $key;
                            }
                        }
                    }
                    if (isset($property->property->feet_security)) {
                        foreach ($property->property->feet_security as $key => $value) {
                            if ($value == true) {
                                $security[] = $key;
                            }
                        }
                    }
                    if (isset($property->property->feet_furniture)) {
                        foreach ($property->property->feet_furniture as $key => $value) {
                            if ($value == true) {
                                $furniture[] = $key;
                            }
                        }
                    }
                    if (isset($property->property->feet_parking)) {
                        foreach ($property->property->feet_parking as $key => $value) {
                            if ($value == true) {
                                $parking[] = $key;
                            }
                        }
                    }
                    if (isset($property->property->feet_garden)) {
                        foreach ($property->property->feet_garden as $key => $value) {
                            if ($value == true) {
                                $garden[] = $key;
                            }
                        }
                    }

                    if (isset($property->property->feet_pool)) {
                        foreach ($property->property->feet_pool as $key => $value) {
                            if ($value == true) {
                                $pool[] = $key;
                            }
                            if ($key == 'pool_private_size') {
                                $pool_size = (array) $value;
                            }
                        }
                    }
                    if (isset($property->property->feet_condition)) {
                        foreach ($property->property->feet_condition as $key => $value) {
                            if ($value == true) {
                                $condition[] = $key;
                            }
                        }
                    }
                    $data['short_term_rental_price'] = isset($property->property->short_term_rental_price) ? $property->property->short_term_rental_price : 0;
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
                    $data['property_features']['pool_size'] = $pool_size;
                    $data['property_features']['condition'] = $condition;
                    $data['property_features']['videos'] = $videos;
                    $return_data[] = $data;
                }
            }
        }
        return $return_data;
    }

    public static function findOne($reference, $with_booking = false, $with_locationgroup = false, $rent = false, $with_construction = false, $with_listing_agency = false, $with_testimonials = false, $with_count = false, $image_size = '1200', $options = [])
    {
        $langugesSystem = Cms::SystemLanguages();
        $lang = strtoupper(\Yii::$app->language);
        $contentLang = $lang;
        foreach ($langugesSystem as $sysLang) {
            if ((isset($sysLang['internal_key']) && $sysLang['internal_key'] != '') && $lang == $sysLang['internal_key']) {
                $contentLang = $sysLang['key'];
            }
        }
        if (isset($reference) && !empty($reference)) {
            $url = Yii::$app->params['apiUrl'] . 'properties/view-by-ref&ref=' . $reference . '&ip=' . \Yii::$app->getRequest()->getUserIP() . '&user_apikey=' . Yii::$app->params['api_key'];
            // echo $url;die;
            if (isset($with_booking) && $with_booking == true) {
                $url .= '&with_booking=true';
            }
            if (isset($with_locationgroup) && $with_locationgroup == true) {
                $url .= '&with_locationgroup=true';
            }
            if (isset($with_construction) && $with_construction == true) {
                $url .= '&with_construction=true';
            }
            if (isset($with_listing_agency) && $with_listing_agency == true) {
                $url .= '&with_listing_agency=true';
            }
            if (isset($with_testimonials) && $with_testimonials == true) {
                $url .= '&with_testimonials=true';
            }
            if (isset($with_count) && $with_count == true) {
                $url .= '&view_count=true';
            }
            if (isset($options['exporter']) && $options['exporter'] == true) {
                $url .= '&exporter=true';
            }
            if(isset(Yii::$app->params['status']) && !empty(Yii::$app->params['status'])){
                foreach(Yii::$app->params['status'] as $status){
                    $url .='&status[]='.$status;
                }
            }
            // echo $url;die;
            $JsonData = Functions::getCRMData($url, false);
            $property = json_decode($JsonData);
            if (isset($property->property->reference)) {
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
                if (!isset($property->property)) {
                    throw new \yii\web\NotFoundHttpException();
                }
                if (isset($property->property->_id)) {
                    $return_data['_id'] = $property->property->_id;
                }
                if (isset($property->property->reference)) {
                    $return_data['id'] = $property->property->reference;
                }

                if (isset($settings['general_settings']['reference']) && $settings['general_settings']['reference'] != 'reference') {
                    $ref = $settings['general_settings']['reference'];
                    if (isset($property->property->$ref)) {
                        $return_data['reference'] = $property->property->$ref;
                    } else {
                        $return_data['reference'] = $property->agency_code . '-' . $property->property->reference;
                    }
                } else {
                    $return_data['reference'] = $property->agency_code . '-' . $property->property->reference;
                }
                if (isset($property->property->urbanisation) && $property->property->urbanisation != '') {
                    $data['urbanisation'] = $property->property->urbanisation;
                }

                $title = 'rental_title';
                $description = 'rental_description';
                $price = 'rent';
                $seo_title = 'rental_seo_title';
                $seo_description = 'rental_seo_description';
                $keywords = 'rental_keywords';
                $perma_link = 'rental_perma_link';
                if (((isset($property->property->sale) && $property->property->sale == true) || (isset($property->property->transfer) && $property->property->transfer == true)) && !$rent) {
                    $title = 'title';
                    $description = 'description';
                    $price = 'sale';
                    $seo_title = 'seo_title';
                    $seo_description = 'seo_description';
                    $keywords = 'keywords';
                    $perma_link = 'perma_link';
                }
                //    start slug_all
                $slugs = [];
                foreach ($langugesSystem as $lang_sys) {
                    $lang_sys_key = $lang_sys['key'];
                    $lang_sys_internal_key = isset($lang_sys['internal_key']) ? $lang_sys['internal_key'] : '';
                    if (isset($property->property->$perma_link->$lang_sys_key) && $property->property->$perma_link->$lang_sys_key != '') {
                        $slugs[$lang_sys_internal_key] = $property->property->$perma_link->$lang_sys_key;
                    } elseif (isset($property->property->$title->$lang_sys_key) && $property->property->$title->$lang_sys_key != '') {
                        $slugs[$lang_sys_internal_key] = $property->property->$title->$lang_sys_key;
                    } elseif (isset($slugs[$lang_sys_internal_key])) {
                        if (isset($property->property->type_one) && $property->property->type_one != '') {
                            $slugs[$lang_sys_internal_key] = $property->property->type_one . ' ' . 'in' . ' ';
                        }
                        if (isset($property->property->location) && $property->property->location != '') {
                            $slugs[$lang_sys_internal_key] = $slugs[$lang_sys_internal_key] . $property->property->location;
                        }
                    }
                }
                //        end slug_all
                $return_data['slug_all'] = $slugs;
                if (isset($property->property->sale) && $property->property->sale == true && isset($property->property->title->$contentLang) && $property->property->title->$contentLang != '') {
                    $return_data['sale_rent_title'] = $property->property->title->$contentLang;
                } elseif (isset($property->property->rent) && $property->property->rent == true && isset($property->property->rental_title->$contentLang) && $property->property->rental_title->$contentLang != '') {
                    $return_data['sale_rent_title'] = $property->property->rental_title->$contentLang;
                } elseif (isset($property->property->location) && isset($property->property->type_one)) {
                    $return_data['sale_rent_title'] = (isset($property->property->type_one) ? \Yii::t('app', strtolower($property->property->type_one)) : \Yii::t('app', 'N/A')) . ' ' . \Yii::t('app', 'in') . ' ' . \Yii::t('app', $property->property->location);
                }
                if (isset($property->property->$title->$contentLang) && $property->property->$title->$contentLang != '') {
                    $return_data['title'] = $property->property->$title->$contentLang;
                } else {
                    //if 'default_title' in params.php is set to 'EN'. in case current language dont have title it will show title in 'EN' 
                    if (isset(Yii::$app->params['default_title']) && Yii::$app->params['default_title'] == 'EN' && isset($property->property->$title)) {
                        $lang = 'EN';
                        $return_data['title'] = isset($property->property->$title->$lang) ? $property->property->$title->$lang : '';
                    } elseif (isset($property->property->locations) && $property->property->location != '') {
                        $return_data['title'] = \Yii::t('app', strtolower($property->property->type_one)) . ' ' . \Yii::t('app', 'in') . ' ' . \Yii::t('app', $property->property->location);
                    } else {
                        $return_data['title'] = \Yii::t('app', strtolower($property->property->type_one));
                    }
                }
                if (isset($property->property->$title->$contentLang) && $property->property->$title->$contentLang != '') {
                    $return_data['slug'] = $property->property->$title->$contentLang;
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
                if (isset($property->property->p_style)) {
                    $return_data['p_style'] = $property->property->p_style;
                }
                if (isset($property->property->bathrooms)) {
                    $return_data['bathrooms'] = $property->property->bathrooms;
                }
                if (isset($property->property->perma_link->$lang)) {
                    $return_data['perma_link'] = $property->property->perma_link->$lang;
                }
                if (isset($property->property->kilowatt)) {
                    $return_data['kilowatt'] = $property->property->kilowatt;
                }
                if (isset($property->property->co2)) {
                    $return_data['co2'] = $property->property->co2;
                }
                if (isset($property->property->basura)) {
                    $return_data['basura'] = $property->property->basura;
                }
                if (isset($property->property->status)) {
                    $return_data['status'] = $property->property->status;
                }
                if (isset($property->property->facade_width)) {
                    $return_data['facade_width'] = $property->property->facade_width;
                }
                if (isset($property->property->facade_width)) {
                    $return_data['facade_width'] = $property->property->facade_width;
                }
                if (isset($property->property->electricity_certificate)) {
                    $return_data['electricity_certificate'] = $property->property->electricity_certificate;
                }
                if (isset($property->property->allotment_permit)) {
                    $return_data['allotment_permit'] = $property->property->allotment_permit;
                }if (isset($property->property->soil_certificate)) {
                    $return_data['soil_certificate'] = $property->property->soil_certificate;
                }if (isset($property->property->summons)) {
                    $return_data['summons'] = $property->property->summons;
                }if (isset($property->property->right_to_sell)) {
                    $return_data['right_to_sell'] = $property->property->right_to_sell;
                }if (isset($property->property->protected_heritage)) {
                    $return_data['protected_heritage'] = $property->property->protected_heritage;
                }
                

                
                // Code for all terrace sizes

                // if (isset($property->property->terrace)) {
                //     if (is_object($property->property->terrace)) {
                //         foreach ($property->property->terrace as $terrace) {
                //             if (isset($terrace->terrace)) {
                //                 $return_data['terrace'][] = $terrace->terrace;
                //             }
                //         }
                //     }
                //     if (is_array($property->property->terrace)) {
                //         foreach ($property->property->terrace as $terrace) {
                //             if (isset($terrace->terrace)) {
                //                 $return_data['terrace'][] = $terrace->terrace;
                //             }
                //         }
                //     }
                // }

                // Code for first terrace size

                if (isset($property->property->terrace)) {
                    if (is_object($property->property->terrace)) {
                        if (isset($property->property->terrace->{0}->terrace) && $property->property->terrace->{0}->terrace > 0) {
                            $return_data['terrace'] = $property->property->terrace->{0}->terrace;
                        }
                    }
                    if (is_array($property->property->terrace)) {
                        if (isset($property->property->terrace[0]->terrace) && $property->property->terrace[0]->terrace > 0) {
                            $return_data['terrace'] = $property->property->terrace[0]->terrace;
                        }
                    }
                }
                if (isset($property->property->sleeps) && $property->property->sleeps > 0) {
                    $return_data['sleeps'] = $property->property->sleeps;
                }
                if (isset($property->property->living_rooms) && $property->property->living_rooms > 0) {
                    $return_data['living_rooms'] = $property->property->living_rooms;
                }

                if (isset($property->property->usefull_area) && $property->property->usefull_area > 0) {
                    $return_data['usefull_area'] = $property->property->usefull_area;
                }

                if (isset($property->property->oldprice->price) && $property->property->oldprice->price > 0) {
                    $return_data['oldprice'] = str_replace(',', '.', (number_format((int) ($property->property->oldprice->price))));
                }
                if (isset($property->property->oldprice->price_on_demand) && $property->property->oldprice->price_on_demand == true) {
                    $return_data['price_on_demand'] = true;
                }
                if (isset($property->property->currentprice) && isset($property->property->sale) && $property->property->sale == true) {
                    $return_data['currentprice'] = $property->property->currentprice;
                }
                if (isset($property->featured) && !empty($property->featured)) {
                    $return_data['featured'] = $property->featured;
                }
                if (isset($property->property->own) && $property->property->own == true) {
                    $return_data['own'] = true;
                }
                if (isset($property->property->exclusive) && $property->property->exclusive == true) {
                    $return_data['exclusive'] = true;
                }
                if (isset($property->property->created_at) && !empty($property->property->created_at)) {
                    $return_data['created_at'] = $property->property->created_at;
                }
                if (isset($property->property->floors) && !empty($property->property->floors)) {
                    $return_data['floors'] = ArrayHelper::toArray($property->property->floors);
                }
                if (isset($property->view_count) && !empty($property->view_count)) {
                    $return_data['view_count'] = $property->view_count;
                }
                if (isset($property->listing_agency_data) && count((array) $property->listing_agency_data) > 0) {
                    $listing_agency_data = [];
                    foreach ($property->listing_agency_data as $key => $value) {
                        if ($key == 'email' && $value != '') {
                            $listing_agency_data[$key] = $value;
                        }
                    }
                    $return_data['listing_agency_data'] = $listing_agency_data;
                }
                $agency = Yii::$app->params['agency'];
                if (isset($property->property->private_info_object->$agency->address->formatted_address)) {
                    $return_data['formatted_address'] = $property->property->private_info_object->$agency->address->formatted_address;
                }

                if ($price == 'rent') {
                    if (isset($property->property->st_rental) && $property->property->st_rental == true && isset($property->property->rental_seasons)) {
                        $st_price = [];
                        foreach ($property->property->rental_seasons as $seasons) {
                            if (isset($seasons->new_price) && $seasons->new_price > 0) {
                                $st_price[] = ['price' => isset($seasons->new_price) ? $seasons->new_price : '', 'period' => isset($seasons->period) ? $seasons->period : '', 'seasons' => isset($seasons->seasons) ? $seasons->seasons : ''];
                            }
                        }
                        if (isset(Yii::$app->params['rental_logic']) && Yii::$app->params['rental_logic']) {
                            $gdprice = [];
                            $st_price = 0;
                            $gwprice = [];
                            $stw_price = 0;
                            foreach ($property->property->rental_seasons as $seasons) {
                                /* For price per week */
                                if (isset(Yii::$app->params['rental_logic_week']) && Yii::$app->params['rental_logic_week']) {
                                    if (isset($seasons->gross_price)) {
                                        $gwprice[] = $seasons->gross_price;
                                    }
                                }
                                if (isset(Yii::$app->params['rental_logic_day']) && Yii::$app->params['rental_logic_day']) {
                                    if (isset($seasons->gross_day_price)) {
                                        $gdprice[] = $seasons->gross_day_price;
                                    }
                                } else {
                                    if (isset($seasons->gross_day_price)) {
                                        if (isset($seasons->period_to) && $seasons->period_to >= time()) {
                                            //$gdprice[] = $seasons->gross_day_price;
                                            if (isset($seasons->discounts)) {
                                                foreach ($seasons->discounts as $discount) {
                                                    if (isset($discount->discount_price) && $discount->discount_price != '') {
                                                        $gdprice[] = $discount->discount_price;
                                                    } else {
                                                        $gdprice[] = $seasons->gross_day_price;
                                                    }
                                                }
                                            } else {
                                                $gdprice[] = $seasons->gross_day_price;
                                            }
                                        }
                                    }
                                }
                            }
                            if (count($gwprice) > 0)
                                $stw_price = min($gwprice);
                            if (count($gdprice) > 0)
                                $st_price = min($gdprice);

                            $b_price = 0;
                            /* For price per week */
                            if (isset(Yii::$app->params['rental_logic_week']) && Yii::$app->params['rental_logic_week']) {
                                if (isset($property->bookings_extras) && count((array) $property->bookings_extras) > 0) {
                                    foreach ($property->bookings_extras as $booking_extra) {
                                        $divider = 1;
                                        if (isset($booking_extra->type) && ($booking_extra->type == 'per_week'))
                                            $divider = 7;
                                        if (isset($booking_extra->add_to_price) && $booking_extra->add_to_price == true) {
                                            $b_price = $b_price + (isset($booking_extra->price) ? ($booking_extra->price * 1 / $divider) : 0);
                                        }
                                    }
                                }
                                if (isset($property->bookings_cleaning) && count((array) $property->bookings_cleaning) > 0) {
                                    foreach ($property->bookings_cleaning as $bookings_cleaning) {
                                        $divider = 1;
                                        $multiplyer = 1;
                                        if (isset($bookings_cleaning->type) && ($bookings_cleaning->type == 'per_week'))
                                            $divider = 7;
                                        if (isset($bookings_cleaning->type) && $bookings_cleaning->type == 'per_hour')
                                            $multiplyer = 24;
                                        if (isset($bookings_cleaning->charge_to) && $bookings_cleaning->charge_to == 'client')
                                            $b_price = $b_price + (isset($bookings_cleaning->price) ? ($bookings_cleaning->price * 1 * $multiplyer / $divider) : 0);
                                    }
                                }
                            } else {
                                /* For price per day */
                                if (isset($property->bookings_extras) && count((array) $property->bookings_extras) > 0) {
                                    foreach ($property->bookings_extras as $booking_extra) {
                                        $divider = 1;
                                        $multiplyer = 1;
                                        if (isset($booking_extra->type) && ($booking_extra->type == 'per_week' || $booking_extra->type == 'per_stay'))
                                            $divider = 7;
                                        if (isset(Yii::$app->params['exclude_per_stay_extras']) && isset($booking_extra->type) && $booking_extra->type == 'per_stay') {
                                            $multiplyer = 0;
                                        }
                                        if (isset($booking_extra->add_to_price) && $booking_extra->add_to_price == true) {
                                            $b_price = $b_price + (isset($booking_extra->price) ? ($booking_extra->price * 1 / $divider * $multiplyer) : 0);
                                        }
                                    }
                                }
                                if (isset($property->bookings_cleaning) && count((array) $property->bookings_cleaning) > 0) {
                                    foreach ($property->bookings_cleaning as $bookings_cleaning) {
                                        $divider = 1;
                                        $multiplyer = 1;
                                        if (isset($bookings_cleaning->type) && ($bookings_cleaning->type == 'per_week' || $bookings_cleaning->type == 'per_stay'))
                                            $divider = 7;
                                        if (isset(Yii::$app->params['exclude_per_stay_extras']) && $bookings_cleaning->type == 'per_stay') {
                                            $multiplyer = 0;
                                        }
                                        if (isset($bookings_cleaning->type) && $bookings_cleaning->type == 'per_hour')
                                            $multiplyer = 24;
                                        if (isset($bookings_cleaning->charge_to) && $bookings_cleaning->charge_to == 'client')
                                            $b_price = $b_price + (isset($bookings_cleaning->price) ? ($bookings_cleaning->price * 1 * $multiplyer / $divider) : 0);
                                    }
                                }
                            }
                            if ($st_price  == '0') {
                                $return_data['price'] = number_format($st_price);
                            } else {
                                $return_data['price'] = number_format($st_price + $b_price, 2);
                            }
                            if ($stw_price == '0') {
                                $return_data['price_week'] = number_format($stw_price);
                            } else {
                                $return_data['price_week'] = number_format($stw_price + $b_price, 2);
                            }
                        } else {
                            $return_data['price'] = (isset($st_price[0]['price']) && $st_price[0]['price'] != 0) ? number_format((int) $st_price[0]['price'], 0, '', '.') . ' ' . Yii::t('app', str_replace('_', ' ', (isset($st_price[0]['period']) ? $st_price[0]['period'] : ''))) : '';
                            $return_data['price_week'] = (isset($st_price[0]['price']) && $st_price[0]['price'] != 0) ? number_format((int) $st_price[0]['price'], 0, '', '.') . ' ' . Yii::t('app', str_replace('_', ' ', (isset($st_price[0]['period']) ? $st_price[0]['period'] : ''))) : '';
                        }
                        $return_data['seasons'] = isset($st_price[0]['seasons']) ? $st_price[0]['seasons'] : '';
                        $return_data['season_data'] = ArrayHelper::toArray($property->property->rental_seasons);
                    } elseif (isset($property->property->lt_rental) && $property->property->lt_rental == true && isset($property->property->period_seasons)) {
                        $st_price = [];
                        foreach ($property->property->period_seasons as $seasons) {
                            $st_price[] = ['price' => isset($seasons->new_price) ? $seasons->new_price : '', 'period' => isset($seasons->period) ? $seasons->period : '', 'seasons' => isset($seasons->seasons) ? $seasons->seasons : ''];
                        }
                        if (isset($property->property->transfer) && $property->property->transfer == true) {
                            if (isset($property->property->currentprice)) {
                                $return_data['price'] = ($property->property->currentprice != 0) ? number_format((int) $property->property->currentprice, 0, '', '.') : '';
                            }
                        } else {
                            $return_data['price'] = (isset($st_price[0]['price']) && $st_price[0]['price'] != 0) ? number_format((int) $st_price[0]['price'], 0, '', '.') . ' ' . Yii::t('app', str_replace('_', ' ', (isset($st_price[0]['period']) ? $st_price[0]['period'] : ''))) : '';
                        }
                        $return_data['seasons'] = isset($st_price[0]['seasons']) ? $st_price[0]['seasons'] : '';
                        $return_data['season_data'] = ArrayHelper::toArray($property->property->period_seasons);
                    }
                } else {
                    if (isset($property->property->currentprice)) {
                        $return_data['price'] = ($property->property->currentprice != 0) ? number_format((int) $property->property->currentprice, 0, '', '.') : '';
                    }
                }
                if (isset($property->property->lt_rental) && $property->property->lt_rental == true && isset($property->property->period_seasons) && is_array($property->property->period_seasons) && isset($property->property->period_seasons[0]->new_price)) {
                    $return_data['ltprice'] = ($property->property->period_seasons[0]->new_price != 0) ? number_format((int) $property->property->period_seasons[0]->new_price, 0, '', '.') . ' ' . Yii::t('app', 'per_month') : '';
                    $return_data['lt_price'] = ($property->property->period_seasons[0]->new_price != 0) ? number_format((int) $property->property->period_seasons[0]->new_price, 0, '', '.') . ' € ' . Yii::t('app', 'per_month') : '';
                }
                if (isset($property->property->lt_rental) && $property->property->lt_rental == true && isset($property->property->period_seasons) && is_object($property->property->period_seasons) && $property->property->period_seasons->{0}->new_price) {
                    $return_data['ltprice'] = ($property->property->period_seasons->{0}->new_price != 0) ? number_format((int) $property->property->period_seasons->{0}->new_price, 0, '', '.') . ' ' . Yii::t('app', 'per_month') : '';
                    $return_data['lt_price'] = ($property->property->period_seasons->{0}->new_price != 0) ? number_format((int) $property->property->period_seasons->{0}->new_price, 0, '', '.') . ' € ' . Yii::t('app', 'per_month') : '';
                }
                if (isset($property->property->type_two)) {
                    $return_data['type_two'] = $property->property->type_two;
                }
                if (isset($property->property->security_deposit)) {
                    $return_data['security_deposit'] = $property->property->security_deposit;
                }
                if (isset($property->property->currency) && $property->property->currency != '') {
                    $return_data['currency'] = $property->property->currency;
                }
                if (isset($property->property->extras)) {
                    $return_data['extras'] = $property->property->extras;
                }
                if (isset($property->property->value_of_custom->distance)) {
                    $return_data['value_of_custom'] = $property->property->value_of_custom->distance;
                }
                if (isset($property->property->type_one)) {
                    $return_data['type'] = $property->property->type_one;
                }
                if (isset($property->property->type_one_key)) {
                    $return_data['type_key'] = $property->property->type_one_key;
                }
                if (isset($property->property->dimensions) && $property->property->dimensions != '') {
                    $return_data['dimensions'] = $property->property->dimensions;
                }
                if (isset($property->property->built)) {
                    $return_data['built'] = $property->property->built;
                }
                if (isset($property->property->plot)) {
                    $return_data['plot'] = $property->property->plot;
                }
                if (isset($property->property->community_fees)) {
                    $return_data['community_fees'] = $property->property->community_fees;
                }
                if (isset($property->property->ibi)) {
                    $return_data['real_estate_tax'] = $property->property->ibi;
                }
                if (isset($property->property->year_built)) {
                    $return_data['year_built'] = $property->property->year_built;
                }
                if (isset($property->property->address_country)) {
                    $return_data['country'] = $property->property->address_country;
                }
                if (isset($property->property->region) && !empty($property->property->region)) {
                    $return_data['region'] = $property->property->region;
                }
                // if (isset($property->property->sale) && $property->property->sale == true && isset($property->property->description->$contentLang) && $property->property->description->$contentLang != '') {
                //     $return_data['sale_rent_description'] = $property->property->description->$contentLang;
                // } elseif (isset($property->property->rent) && $property->property->rent == true && isset($property->property->rental_description->$contentLang) && $property->property->rental_description->$contentLang != '') {
                //     $return_data['sale_rent_description'] = $property->property->rental_description->$contentLang;
                // }
                if (isset($property->property->$description->$contentLang) && !empty($property->property->$description->$contentLang)) {
                    $return_data['sale_rent_description'] = $property->property->$description->$contentLang;
                }
                if (isset($property->property->$description->$contentLang) && !empty($property->property->$description->$contentLang)) {
                    $return_data['description'] = $property->property->$description->$contentLang;
                } elseif (isset($property->property->$description->EN) && !empty($property->property->$description->EN)) {
                    $return_data['description'] = $property->property->$description->EN;
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
                if (isset($property->property->location_group) && $property->property->location_group != 'N/A') {
                    $return_data['location_group'] = $property->property->location_group;
                }
                if (isset($property->property->location) && isset($property->property->location_key)) {
                    if (isset($property->property->location_name)) {
                        $return_data['location_name'] = (isset(\Yii::$app->language) && strtolower(\Yii::$app->language) == 'es') ? $property->property->location_name->es_AR : $property->property->location_name->en;
                    }
                    $return_data['location'] = $property->property->location;
                    $return_data['location_key'] = $property->property->location_key;
                }
                if (isset($property->property->energy_certificate) && $property->property->energy_certificate != '') {
                    if ($property->property->energy_certificate == 'X' || $property->property->energy_certificate == 'x') {
                        $return_data['energy_certificate'] = strtolower('In Progress');
                    } elseif ($property->property->energy_certificate == 'Not available') {
                        $return_data['energy_certificate'] = strtolower('In Progress');
                    } elseif ($property->property->energy_certificate == 'In Process') {
                        $return_data['energy_certificate'] = strtolower('In Progress');
                    } else {
                        $return_data['energy_certificate'] = $property->property->energy_certificate;
                    }
                } else {
                    $return_data['energy_certificate'] = strtolower('In Progress');
                }
                if (isset($property->property->energy_certificate_two) && $property->property->energy_certificate_two != '') {
                    if ($property->property->energy_certificate_two == 'X' || $property->property->energy_certificate_two == 'x') {
                        $return_data['energy_certificate_two'] = strtolower('In Progress');
                    } elseif ($property->property->energy_certificate_two == 'Not available') {
                        $return_data['energy_certificate_two'] = strtolower('In Progress');
                    } elseif ($property->property->energy_certificate_two == 'In Process') {
                        $return_data['energy_certificate_two'] = strtolower('In Progress');
                    } else {
                        $return_data['energy_certificate_two'] = $property->property->energy_certificate_two;
                    }
                } else {
                    $return_data['energy_certificate_two'] = strtolower('In Progress');
                }
                if (isset($property->property->co2)) {
                    $return_data['co2'] = $property->property->co2;
                }
                if (isset($property->property->kilowatt)) {
                    $return_data['kilowatt'] = $property->property->kilowatt;
                }
                if (isset($property->property->energy_certificate_two) && $property->property->energy_certificate_two != '') {
                    if ($property->property->energy_certificate_two == 'X' || $property->property->energy_certificate_two == 'x') {
                        $return_data['energy_certificate_two'] = strtolower('In Progress');
                    } elseif ($property->property->energy_certificate_two == 'Not available') {
                        $return_data['energy_certificate_two'] = strtolower('In Progress');
                    } elseif ($property->property->energy_certificate_two == 'In Process') {
                        $return_data['energy_certificate_two'] = strtolower('In Progress');
                    } else {
                        $return_data['energy_certificate_two'] = $property->property->energy_certificate_two;
                    }
                } else {
                    $return_data['energy_certificate_two'] = strtolower('In Progress');
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
                if (isset($property->property->$seo_title)) {
                    $return_data['meta_title_all'] = $property->property->$seo_title;
                }
                if (isset($property->property->$seo_title->$contentLang) && $property->property->$seo_title->$contentLang != '') {
                    $return_data['meta_title'] = $property->property->$seo_title->$contentLang;
                }
                if (isset($property->property->$seo_description->$contentLang) && $property->property->$seo_description->$contentLang != '') {
                    $return_data['meta_desc'] = $property->property->$seo_description->$contentLang;
                }
                if (isset($property->property->$keywords->$contentLang) && $property->property->$keywords->$contentLang != '') {
                    $return_data['meta_keywords'] = $property->property->$keywords->$contentLang;
                }
                if (isset($property->property->custom_categories) && !empty($property->property->custom_categories)) {
                    $cats = self::Categories();
                    $catsArr = [];
                    foreach ($property->property->custom_categories as $catdata) {
                        if (isset($cats[$catdata])) {
                            $catsArr[$catdata] = $cats[$catdata];
                        }
                    }
                    $return_data['categories'] = $catsArr;
                }
                if (isset($property->property->value_of_custom->basic_info)) {
                    $return_data['basic_info'] = ArrayHelper::toArray($property->property->value_of_custom->basic_info);
                }

                if (isset($property->attachments) && count($property->attachments) > 0) {
                    foreach ($property->attachments as $pic) {
                        if (isset(Yii::$app->params['img_url__without_watermark'])) {
                            $url = Yii::$app->params['img_url__without_watermark'] . '/' . $pic->model_id . '/' . 1800 . '/' . $pic->file_md5_name;
                        }else{
                            $url = Yii::$app->params['img_url'] . '/' . $pic->model_id . '/' . $image_size . '/' . $pic->file_md5_name;
                        }
                        $attachments[] = $url;
                        $attachment_descriptions[] = isset($pic->description->$contentLang) ? $pic->description->$contentLang : '';
                        $attachment_alt_descriptions[] = isset($pic->alt_description->$contentLang) ? $pic->alt_description->$contentLang : '';
                    }
                    $return_data['attachments'] = $attachments;
                    $return_data['attachment_desc'] = $attachment_descriptions;
                    $return_data['attachment_alt_desc'] = $attachment_alt_descriptions;
                }
                if (isset($property->documents) && count($property->documents) > 0) {
                    foreach ($property->documents as $pic) {
                        if (isset($pic->identification_type) && $pic->identification_type == 'FP') {
                            if (isset(Yii::$app->params['floor_plans_url'])) {
                                $floor_plans[] = Yii::$app->params['floor_plans_url'] . '/' . $pic->model_id . '/' . $pic->file_md5_name;
                            }
                        }
                    }
                    $return_data['floor_plans'] = $floor_plans;
                }
                if (isset($property->documents) && count($property->documents) > 0) {
                    foreach ($property->documents as $pic) {
                        if (isset($pic->identification_type) && $pic->identification_type == 'FP') {
                            if (isset(Yii::$app->params['floor_plans_url'])) {
                                $url_fp = Yii::$app->params['floor_plans_url'] . '/' . $pic->model_id . '/' . $pic->file_md5_name;
                            }
                            if (isset($pic->description->$lang)) {
                                $desc_fp = $pic->description->$lang;
                            }
                            $floor_plans_with_description[] = ['url' => isset($url_fp) ? $url_fp : '', 'description' => isset($desc_fp) ? $desc_fp : ''];
                        }
                    }
                    $return_data['floor_plans_with_description'] = $floor_plans_with_description;
                }
                if (isset($property->bookings_extras) && count($property->bookings_extras) > 0) {
                    $return_data['booking_extras'] = ArrayHelper::toArray($property->bookings_extras);
                }
                if (isset($property->bookings_cleaning) && count($property->bookings_cleaning) > 0) {
                    $return_data['booking_cleaning'] = ArrayHelper::toArray($property->bookings_cleaning);
                }
                if (isset($property->property->security_deposit) && $property->property->security_deposit != '') {
                    $return_data['security_deposit'] = $property->property->security_deposit;
                }

                if (isset($property->property->vt_ids->$lang)) {
                    $return_data['vt'] = Yii::$app->params['apiUrl'] . 'virtualtours&id=' . $property->property->vt_ids->$lang . '&user_apikey=' . Yii::$app->params['api_key'];
                } elseif (isset($property->property->vt_ids->{'EN'})) {
                    $return_data['vt'] = Yii::$app->params['apiUrl'] . 'virtualtours&id=' . $property->property->vt_ids->$lang . '&user_apikey=' . Yii::$app->params['api_key'];
                }
                if (isset($property->property->license_number)) {
                    $return_data['license_number'] = $property->property->license_number;
                }
                if (isset($property->property->minimum_stay)) {
                    $return_data['minimum_stay'] = ArrayHelper::toArray($property->property->minimum_stay);
                }

                if (isset($property->bookings) && count($property->bookings) > 0) {
                    $group_booked = [];
                    $booking_group_with_enquiry = [];
                    $booking_status = [];
                    $booked_dates_costa = [];
                    foreach ($property->bookings as $key => $booking) {
                        if (isset($booking->date_from) && $booking->date_from != '' && isset($booking->date_until) && $booking->date_until != '' && is_numeric($booking->date_from) && is_numeric($booking->date_until)) {
                            for ($i = $booking->date_from; $i <= $booking->date_until; $i += 86400) {
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
                            // $booking_status[] = $booking->status;
                            // $group_booked[$key] = [];
                            // for ($i = $booking->date_from; $i <= $booking->date_until; $i += 86400)
                            // {
                            //     $booked_dates_costa[] = date(isset(Yii::$app->params['date_fromate']) ? Yii::$app->params['date_fromate'] : "m-d-Y", $i);
                            //     $group_booked[$key][] = date(isset(Yii::$app->params['date_fromate']) ? Yii::$app->params['date_fromate'] : "m-d-Y", $i);
                            // }
                            $dates = [];
                            $period = new \DatePeriod(new \DateTime(date('Y-m-d', $booking->date_from)), new \DateInterval('P1D'), new \DateTime(date('Y-m-d', $booking->date_until) . ' +1 day'));
                            foreach ($period as $date) {
                                $dates[] = $date->format(isset(Yii::$app->params['date_fromate']) ? Yii::$app->params['date_fromate'] : "m-d-Y");
                            }
                            if (isset($booking->status) && $booking->status == 'enquiry') {
                                $booking_group_with_enquiry[$key] = [];
                                foreach ($dates as $date) {
                                    $booking_group_with_enquiry[$key][] = $date;
                                }
                            }
                            $booking_status[] = isset($booking->status) && !empty($booking->status) ? $booking->status : '';
                            $group_booked[$key] = [];
                            foreach ($dates as $date) {
                                $booked_dates_costa[] = $date;
                                $group_booked[$key][] = $date;
                            }
                        }
                    }
                    $return_data['booking_status'] = $booking_status;
                    $return_data['group_booked'] = $group_booked;
                    $return_data['booked_dates'] = $booked_dates;
                    $return_data['booking_group_with_enquiry'] = $booking_group_with_enquiry;
                    $return_data['booked_dates_costa'] = $booked_dates_costa;
                }

                if (isset($property->testimonials)) {
                    $testimonials = [];
                    foreach ($property->testimonials as $test) {
                        if (isset($test->status) && $test->status == 'approved') {
                            $test_array = [];
                            if (isset($test->date) && $test->date != '') {
                                $test_array['date'] = $test->date;
                            }
                            if (isset($test->name) && $test->name != '') {
                                $test_array['name'] = $test->name;
                            }
                            if (isset($test->testimonial) && $test->testimonial != '') {
                                $test_array['testimonial'] = $test->testimonial;
                            }
                            if (isset($test->rating)) {
                                $value_avg = 0;
                                foreach ($test->rating as $rating) {
                                    if ($rating != '')
                                        $value_avg = $value_avg + $rating;
                                }
                                $value_avg = ceil($value_avg / 10);
                            }
                            if (isset($value_avg) && $value_avg > 0) {
                                $test_array['rating'] = $value_avg;
                            }
                        }
                        $testimonials[] = $test_array;
                    }
                    if (count($testimonials) > 0) {
                        $return_data['testimonials'] = $testimonials;
                    }
                }
                if (isset($property->property->videos) && (is_array($property->property->videos) || is_object($property->property->videos))) {
                    $videosArr = [];
                    $videosArr_gogo = [];
                    foreach ($property->property->videos as $video) {
                        if (isset($video->status) && $video->status == 1 && isset($video->url->$contentLang) && $video->url->$contentLang != '') {
                            $videosArr[] = $video->url->$contentLang;
                        }
                    }

                    $return_data['videos'] = $videosArr;
                }

                if (isset($property->property->videos) && (is_array($property->property->videos) || is_object($property->property->videos))) {
                    $videosArrDesc = [];
                    $videosArr_gogo = [];
                    foreach ($property->property->videos as $video) {
                        $url_vid = '';
                        $desc_vid = '';
                        $type = '';
                        if (isset($video->status) && $video->status == 1 && isset($video->url->$contentLang) && $video->url->$contentLang != '') {
                            $url_vid = $video->url->$contentLang;
                        }
                        if (isset($video->status) && $video->status == 1 && isset($video->description->$contentLang) && $video->description->$contentLang != '') {
                            $desc_vid = $video->description->$contentLang;
                        }
                        if (isset($video->type)  && isset($video->status) && $video->status == 1) {
                            $type = $video->type;
                        }

                        $videosArrDesc[] = ['url' => $url_vid, 'description' => $desc_vid, 'type' => $type];
                    }
                    $return_data['videos_with_description'] = $videosArrDesc;
                }


                $custom_categories = [];
                $categories = [];
                $features = [];
                $climate_control = [];
                $kitchen = [];
                $setting = [];
                $leisure = [];
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
                $rental_investment_info = [];
                $mooring_type = [];
                $moorings = [];
                $communal_pool_size = [];
                $private_pool_size = [];
                $childrens_pool_size = [];
                $indoor_pool_size = [];
                if (isset($property->property->value_of_custom) && isset($property->property->value_of_custom->basic_info)) {
                    foreach ($property->property->value_of_custom->basic_info as $value) {
                        if (isset($value->field) && isset($value->value) && $value->field != '' && $value->value != '') {
                            $rental_investment_info[$value->field] = $value->value;
                        }
                    }
                }
                $return_data['rental_investment_info'] = $rental_investment_info;
                if (isset($property->property->rent) && $property->property->rent == true) {
                    $rental_features = [];
                    $rental_features['beds'] = [];
                    $rental_features['baths'] = [];
                    $rental_features['rooms'] = [];
                    $rental_features['living_rooms'] = [];
                    if (isset($property->property->feet_living_room) && count((array) $property->property->feet_living_room) > 0) {
                        foreach ($property->property->feet_living_room as $key => $value) {
                            if (isset($value) && $value == true) {
                                $living_rooms[] = Yii::t('app', $key);
                            }
                        }
                        $rental_features['living_rooms'] = $living_rooms;
                    }
                    if (isset($property->property->rooms) && count($property->property->rooms) > 0) {
                        foreach ($property->property->rooms as $value) {
                            $type = isset($value) && isset($value->type) && isset($value->type->$contentLang) ? $value->type->$contentLang : (isset($value) && isset($value->type) && isset($value->type->EN) ? $value->type->EN : '');
                            $name = isset($value) && isset($value->name) && isset($value->name->$contentLang) ? $value->name->$contentLang : (isset($value) && isset($value->name) && isset($value->name->EN) ? $value->name->EN : '');
                            $description = isset($value) && isset($value->description) && isset($value->description->$contentLang) ? $value->description->$contentLang : (isset($value) && isset($value->description) && isset($value->description->EN) ? $value->description->EN : '');
                            $rooms[] = ['type' => $type, 'name' => $name, 'description' => $description];
                        }
                        $rental_features['rooms'] = $rooms;
                    }
                    if (isset($property->property->double_bed) && count($property->property->double_bed) > 0) {
                        $double_bed = [];
                        foreach ($property->property->double_bed as $value) {
                            if (isset($value->x) && $value->x > 0 && isset($value->y) && $value->y > 0) {
                                $double_bed[] = ['x' => $value->x, 'y' => $value->y];
                            }
                        }
                        if (count($double_bed) > 0) {
                            $beds['double_bed'] = $double_bed;
                        }
                    }
                    if (isset($property->property->single_bed) && count($property->property->single_bed) > 0) {
                        $single_bed = [];
                        foreach ($property->property->single_bed as $value) {
                            if (isset($value->x) && $value->x > 0 && (isset($value->y) && $value->y > 0)) {
                                $single_bed[] = ['x' => $value->x, 'y' => $value->y];
                            }
                        }
                        if (count($single_bed) > 0) {
                            $beds['single_bed'] = $single_bed;
                        }
                    }
                    if (isset($property->property->sofa_bed) && count($property->property->sofa_bed) > 0) {
                        $sofa_bed = [];
                        foreach ($property->property->sofa_bed as $value) {
                            if (isset($value->x) && $value->x > 0 && isset($value->y) && $value->y > 0) {
                                $sofa_bed[] = ['x' => $value->x, 'y' => $value->y];
                            }
                        }
                        if (count($sofa_bed) > 0) {
                            $beds['sofa_bed'] = $sofa_bed;
                        }
                    }
                    if (isset($property->property->bunk_beds) && count($property->property->bunk_beds) > 0) {
                        $bunk_beds = [];
                        foreach ($property->property->bunk_beds as $value) {
                            if (isset($value->x) && $value->x > 0 && isset($value->y) && $value->y > 0) {
                                $bunk_beds[] = ['x' => $value->x, 'y' => $value->y];
                            }
                        }
                        if (count($bunk_beds) > 0) {
                            $beds['bunk_beds'] = $bunk_beds;
                        }
                    }
                    if (count($beds) > 0) {
                        $rental_features['beds'] = $beds;
                    }
                    if (isset($property->property->bath_tubs) && $property->property->bath_tubs > 0) {
                        $baths['bath_tubs'] = $property->property->bath_tubs;
                    }
                    if (isset($property->property->jaccuzi_bath) && $property->property->jaccuzi_bath > 0) {
                        $baths['jaccuzi_bath'] = $property->property->jaccuzi_bath;
                    }
                    if (isset($property->property->bidet) && $property->property->bidet > 0) {
                        $baths['bidet'] = $property->property->bidet;
                    }
                    if (isset($property->property->toilets) && $property->property->toilets > 0) {
                        $baths['toilets'] = $property->property->toilets;
                    }
                    if (isset($property->property->corner_shower) && $property->property->corner_shower > 0) {
                        $baths['corner_shower'] = $property->property->corner_shower;
                    }
                    if (isset($property->property->sink) && $property->property->sink > 0) {
                        $baths['sink'] = $property->property->sink;
                    }
                    if (isset($property->property->double_sink) && $property->property->double_sink > 0) {
                        $baths['double_sink'] = $property->property->double_sink;
                    }
                    if (isset($property->property->walk_in_shower) && $property->property->walk_in_shower > 0) {
                        $baths['walk_in_shower'] = $property->property->walk_in_shower;
                    }
                    if (isset($property->property->en_suite) && $property->property->en_suite > 0) {
                        $baths['en_suite'] = $property->property->en_suite;
                    }
                    if (isset($property->property->wheelchair_accesible_shower) && $property->property->wheelchair_accesible_shower > 0) {
                        $baths['wheelchair_accesible_shower'] = $property->property->wheelchair_accesible_shower;
                    }
                    if (isset($property->property->hairdryer) && $property->property->hairdryer > 0) {
                        $baths['hairdryer'] = $property->property->hairdryer;
                    }
                    if (count($baths) > 0) {
                        $rental_features['baths'] = $baths;
                    }
                    $return_data['rental_features'] = $rental_features;
                }
                if (isset($property->property->variant) && $property->property->variant && isset($property->property->variants)) {
                    $return_data['variants'] = $property->property->variants;
                }
                if (isset($property->property->feet_categories)) {
                    foreach ($property->property->feet_categories as $key => $value) {
                        if ($value == true) {
                            $categories[] = $key;
                        }
                    }
                }

                if (isset($property->property->mooring_type) && !empty($property->property->mooring_type) && count($property->property->mooring_type) > 0) {
                    foreach ($property->property->mooring_type as $key => $value) {
                        if ($value) {
                            $mooring_type[$key] = $value;
                        }
                    }
                }
                if (isset($property->property->feet_moorings) && !empty($property->property->feet_moorings) && count($property->property->feet_moorings) > 0) {
                    foreach ($property->property->feet_moorings as $mooring) {
                        foreach ($mooring as $key => $value) {
                            if ($value) {
                                $moorings[$key] = $value;
                            }
                        }
                    }
                }
                if (isset($property->property->value_of_custom) && isset($property->property->value_of_custom->feet_custom_categories) && count($property->property->value_of_custom->feet_custom_categories) > 0) {
                    foreach ($property->property->value_of_custom->feet_custom_categories as $value) {
                        if (isset($value->value) && $value->value == 1 && isset($value->key) && $value->key != '') {
                            $custom_categories[] = $value->key;
                        }
                    }
                }
                if (isset($property->property->custom_categories) && !empty($property->property->custom_categories)) {
                    $cats = self::Categories();
                    foreach ($property->property->custom_categories as $catdata) {
                        if (isset($cats[$catdata])) {
                            $custom_categories[] = $cats[$catdata];
                        }
                    }
                }
                if (isset($property->property->feet_features)) {
                    foreach ($property->property->feet_features as $key => $value) {
                        if ($value == true) {
                            $features[] = $key;
                        }
                    }
                }
                if (isset($property->property->value_of_custom) && isset($property->property->value_of_custom->leisures) && count($property->property->value_of_custom->leisures) > 0) {
                    foreach ($property->property->value_of_custom->leisures as $value) {
                        if (isset($value->value) && $value->value == 1 && isset($value->key) && $value->key != '') {
                            $features[] = $value->key;
                        }
                    }
                }
                if (isset($property->property->value_of_custom) && isset($property->property->value_of_custom->features) && count($property->property->value_of_custom->features) > 0) {
                    foreach ($property->property->value_of_custom->features as $value) {
                        if (isset($value->value) && $value->value == 1 && isset($value->key) && $value->key != '') {
                            $features[] = $value->key;
                        }
                    }
                }
                if (isset($property->property->feet_climate_control)) {
                    foreach ($property->property->feet_climate_control as $key => $value) {
                        if ($value == true) {
                            $climate_control[] = $key;
                        }
                    }
                }
                if (isset($property->property->feet_leisure)) {
                    foreach ($property->property->feet_leisure as $key => $value) {
                        if ($value == true) {
                            $leisure[] = $key;
                        }
                    }
                }
                if (isset($property->property->value_of_custom) && isset($property->property->value_of_custom->climate_control) && count($property->property->value_of_custom->climate_control) > 0) {
                    foreach ($property->property->value_of_custom->climate_control as $value) {
                        if (isset($value->value) && $value->value == 1 && isset($value->key) && $value->key != '') {
                            $climate_control[] = $value->key;
                        }
                    }
                }
                if (isset($property->property->feet_kitchen)) {
                    foreach ($property->property->feet_kitchen as $key => $value) {
                        if ($value == true && $key != 'quantity') {
                            $kitchen[] = $key;
                        }
                    }
                }
                if (isset($property->property->value_of_custom) && isset($property->property->value_of_custom->kitchen) && count($property->property->value_of_custom->kitchen) > 0) {
                    foreach ($property->property->value_of_custom->kitchen as $value) {
                        if (isset($value->value) && $value->value == 1 && isset($value->key) && $value->key != '') {
                            $kitchen[] = $value->key;
                        }
                    }
                }
                if (isset($property->property->feet_setting)) {
                    foreach ($property->property->feet_setting as $key => $value) {
                        if ($value == true) {
                            $setting[] = $key;
                        }
                    }
                }
                if (isset($property->property->feet_orientation)) {
                    foreach ($property->property->feet_orientation as $key => $value) {
                        if ($value == true) {
                            $orientation[] = $key;
                        }
                    }
                }
                if (isset($property->property->feet_views)) {
                    foreach ($property->property->feet_views as $key => $value) {
                        if ($value == true) {
                            $views[] = $key;
                        }
                    }
                }
                if (isset($property->property->value_of_custom) && isset($property->property->value_of_custom->feet_custom_view) && count($property->property->value_of_custom->feet_custom_view) > 0) {
                    foreach ($property->property->value_of_custom->feet_custom_view as $value) {
                        if (isset($value->value) && $value->value == 1 && isset($value->key) && $value->key != '') {
                            $views[] = $value->key;
                        }
                    }
                }
                if (isset($property->property->feet_utilities)) {
                    foreach ($property->property->feet_utilities as $key => $value) {
                        if ($value == true) {
                            $utilities[] = $key;
                        }
                    }
                }
                if (isset($property->property->value_of_custom) && isset($property->property->value_of_custom->utilities) && count($property->property->value_of_custom->utilities) > 0) {
                    foreach ($property->property->value_of_custom->utilities as $value) {
                        if (isset($value->value) && $value->value == 1 && isset($value->key) && $value->key != '') {
                            $utilities[] = $value->key;
                        }
                    }
                }
                if (isset($property->property->feet_security)) {
                    foreach ($property->property->feet_security as $key => $value) {
                        if ($value == true) {
                            $security[] = $key;
                        }
                    }
                }
                if (isset($property->property->value_of_custom) && isset($property->property->value_of_custom->security) && count($property->property->value_of_custom->security) > 0) {
                    foreach ($property->property->value_of_custom->security as $value) {
                        if (isset($value->value) && $value->value == 1 && isset($value->key) && $value->key != '') {
                            $security[] = $value->key;
                        }
                    }
                }
                if (isset($property->property->feet_furniture)) {
                    foreach ($property->property->feet_furniture as $key => $value) {
                        if ($value == true) {
                            $furniture[] = $key;
                        }
                    }
                }
                if (isset($property->property->value_of_custom) && isset($property->property->value_of_custom->furniture) && count($property->property->value_of_custom->furniture) > 0) {
                    foreach ($property->property->value_of_custom->furniture as $value) {
                        if (isset($value->value) && $value->value == 1 && isset($value->key) && $value->key != '') {
                            $furniture[] = $value->key;
                        }
                    }
                }
                if (isset($property->property->feet_parking)) {
                    foreach ($property->property->feet_parking as $key => $value) {
                        if ($value == true) {
                            if ($key == 'parking_quantity') {
                                $return_data['parking_quantity'] = $value;
                            }
                            $parking[] = $key;
                        }
                    }
                }
                if (isset($property->property->value_of_custom) && isset($property->property->value_of_custom->parking) && count($property->property->value_of_custom->parking) > 0) {
                    foreach ($property->property->value_of_custom->parking as $value) {
                        if (isset($value->value) && $value->value == 1 && isset($value->key) && $value->key != '') {
                            $parking[] = $value->key;
                        }
                    }
                }
                if (isset($property->property->feet_garden)) {
                    foreach ($property->property->feet_garden as $key => $value) {
                        if ($value == true) {
                            $garden[] = $key;
                        }
                    }
                }
                if (isset($property->property->value_of_custom) && isset($property->property->value_of_custom->garden) && count($property->property->value_of_custom->garden) > 0) {
                    foreach ($property->property->value_of_custom->garden as $value) {
                        if (isset($value->value) && $value->value == 1 && isset($value->key) && $value->key != '') {
                            $garden[] = $value->key;
                        }
                    }
                }
                if (isset($property->property->feet_pool)) {
                    foreach ($property->property->feet_pool as $key => $value) {
                        if ($value == true &&  $key != 'communal_pool_size' &&  $key != 'private_pool_size' &&  $key != 'childrens_pool_size' &&  $key != 'indoor_pool_size') {
                            $pool[] = $key;
                        }
                        if ($key == 'communal_pool_size') {
                            $communal_pool_size = (array) $value;
                        }
                        if ($key == 'private_pool_size') {
                            $private_pool_size = (array) $value;
                        }
                        if ($key == 'childrens_pool_size') {
                            $childrens_pool_size = (array) $value;
                        }
                        if ($key == 'indoor_pool_size') {
                            $indoor_pool_size = (array) $value;
                        }
                    }
                }
                if (isset($property->property->value_of_custom) && isset($property->property->value_of_custom->pool) && count($property->property->value_of_custom->pool) > 0) {
                    foreach ($property->property->value_of_custom->pool as $value) {
                        if (isset($value->value) && $value->value == 1 && isset($value->key) && $value->key != '') {
                            $pool[] = $value->key;
                        }
                    }
                }
                if (isset($property->property->feet_condition)) {
                    foreach ($property->property->feet_condition as $key => $value) {
                        if ($value == true) {
                            $condition[] = $key;
                        }
                    }
                }
                if (isset($property->property->value_of_custom) && isset($property->property->value_of_custom->feet_custom_condition) && count($property->property->value_of_custom->feet_custom_condition) > 0) {
                    foreach ($property->property->value_of_custom->feet_custom_condition as $value) {
                        if (isset($value->value) && $value->value == 1 && isset($value->key) && $value->key != '') {
                            $condition[] = $value->key;
                        }
                    }
                }
                if (isset($property->property->distance_airport) && count((array) $property->property->distance_airport) > 0 && isset($property->property->distance_airport->value) && $property->property->distance_airport->value > 0) {
                    $distances['distance_airport'] = $property->property->distance_airport->value . ' ' . (isset($property->property->distance_airport->unit) ? $property->property->distance_airport->unit : 'km');
                }
                if (isset($property->property->distance_beach) && count((array) $property->property->distance_beach) > 0 && isset($property->property->distance_beach->value) && $property->property->distance_beach->value > 0) {
                    $distances['distance_beach'] = $property->property->distance_beach->value . ' ' . (isset($property->property->distance_beach->unit) ? $property->property->distance_beach->unit : 'km');
                }
                if (isset($property->property->distance_golf) && count((array) $property->property->distance_golf) > 0 && isset($property->property->distance_golf->value) && $property->property->distance_golf->value > 0) {
                    $distances['distance_golf'] = $property->property->distance_golf->value . ' ' . (isset($property->property->distance_golf->unit) ? $property->property->distance_golf->unit : 'km');
                }
                if (isset($property->property->distance_restaurant) && count((array) $property->property->distance_restaurant) > 0 && isset($property->property->distance_restaurant->value) && $property->property->distance_restaurant->value > 0) {
                    $distances['distance_restaurant'] = $property->property->distance_restaurant->value . ' ' . (isset($property->property->distance_restaurant->unit) ? $property->property->distance_restaurant->unit : 'km');
                }
                if (isset($property->property->distance_sea) && count((array) $property->property->distance_sea) > 0 && isset($property->property->distance_sea->value) && $property->property->distance_sea->value > 0) {
                    $distances['distance_sea'] = $property->property->distance_sea->value . ' ' . (isset($property->property->distance_sea->unit) ? $property->property->distance_sea->unit : 'km');
                }
                if (isset($property->property->distance_supermarket) && count((array) $property->property->distance_supermarket) > 0 && isset($property->property->distance_supermarket->value) && $property->property->distance_supermarket->value > 0) {
                    $distances['distance_supermarket'] = $property->property->distance_supermarket->value . ' ' . (isset($property->property->distance_supermarket->unit) ? $property->property->distance_supermarket->unit : 'km');
                }
                if (isset($property->property->distance_next_town) && count((array) $property->property->distance_next_town) > 0 && isset($property->property->distance_next_town->value) && $property->property->distance_next_town->value > 0) {
                    $distances['distance_next_town'] = $property->property->distance_next_town->value . ' ' . (isset($property->property->distance_next_town->unit) ? $property->property->distance_next_town->unit : 'km');
                }
                if (isset($property->construction) && count((array) $property->construction) > 0) {
                    $obj = $property->construction;
                    if (isset($obj->bedrooms_from) && $obj->bedrooms_from != '') {
                        $construction['bedrooms_from'] = $obj->bedrooms_from;
                    }
                    if (isset($obj->bedrooms_to) && $obj->bedrooms_to != '') {
                        $construction['bedrooms_to'] = $obj->bedrooms_to;
                    }
                    if (isset($obj->bathrooms_from) && $obj->bathrooms_from != '') {
                        $construction['bathrooms_from'] = $obj->bathrooms_from;
                    }
                    if (isset($obj->bathrooms_to) && $obj->bathrooms_to != '') {
                        $construction['bathrooms_to'] = $obj->bathrooms_to;
                    }
                    if (isset($obj->built_size_from) && $obj->built_size_from != '') {
                        $construction['built_size_from'] = $obj->built_size_from;
                    }
                    if (isset($obj->built_size_to) && $obj->built_size_to != '') {
                        $construction['built_size_to'] = $obj->built_size_to;
                    }
                    if (isset($obj->phase) && count($obj->phase) > 0) {
                        $phases = [];
                        foreach ($obj->phase as $phase) {
                            $arr = [];
                            if (isset($phase->phase_name) && $phase->phase_name != '') {
                                $arr['phase_name'] = $phase->phase_name;
                            }
                            if (isset($phase->price_from) && $phase->price_from != '') {
                                $arr['price_from'] = $phase->price_from;
                            }
                            if (isset($phase->price_to) && $phase->price_to != '') {
                                $arr['price_to'] = $phase->price_to;
                            }
                            if (isset($phase->tq) && count($phase->tq) > 0) {
                                $all_types = Dropdowns::types();
                                $types = [];
                                foreach ($phase->tq as $tq) {
                                    if (isset($tq->type) && $tq->type != '') {
                                        foreach ($all_types as $type) {
                                            if ($type['key'] == $tq->type) {
                                                $types[] = isset($type['value'][strtolower($contentLang)]) ? $type['value'][strtolower($contentLang)] : (isset($type['value']['en']) ? $type['value']['en'] : '');
                                            }
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
                $return_data['property_features']['condition'] = $condition;
                $return_data['property_features']['categories'] = $categories;
                $return_data['property_features']['custom_categories'] = $custom_categories;
                $return_data['property_features']['mooring_type'] = $mooring_type;
                $return_data['property_features']['moorings'] = $moorings;
                $return_data['property_features']['setting'] = $setting;
                $return_data['property_features']['orientation'] = $orientation;
                $return_data['property_features']['views'] = $views;
                $return_data['property_features']['distances'] = $distances;
                $return_data['property_features']['kitchen'] = $kitchen;
                $return_data['property_features']['utilities'] = $utilities;
                $return_data['property_features']['security'] = $security;
                $return_data['property_features']['furniture'] = $furniture;
                $return_data['property_features']['climate_control'] = $climate_control;
                $return_data['property_features']['leisure'] = $leisure;
                $return_data['property_features']['parking'] = $parking;
                $return_data['property_features']['garden'] = $garden;
                $return_data['property_features']['pool'] = $pool;
                $return_data['property_features']['communal_pool_size'] = $communal_pool_size;
                $return_data['property_features']['private_pool_size'] = $private_pool_size;
                $return_data['property_features']['childrens_pool_size'] = $childrens_pool_size;
                $return_data['property_features']['indoor_pool_size'] = $indoor_pool_size;
                $return_data['property_features']['features'] = $features;
                $return_data['construction_data'] = $construction;
                return $return_data;
            } else {
                throw new \yii\web\NotFoundHttpException();
            }
        } else {
            throw new \yii\web\NotFoundHttpException();
        }
    }

    public static function setQuery()
    {
        $cms_settings = Cms::settings();
        $get = Yii::$app->request->get();
        $query = '';
        if(isset(Yii::$app->params['status']) && !empty(Yii::$app->params['status'])){
            foreach(Yii::$app->params['status'] as $status){
                $query .='&status[]='.$status;
            }
        }
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

            if ($get["transaction"] == '3') {
                $query .= '&new_construction=1';
            }
            if ($get["transaction"] == '4') {
                $query .= '&sale=1';
            }
            if ($get["transaction"] == '7') {
                $query .= '&sale=1&not_new_construction=1';
            }
        }


        if (isset($get['orientations']) && $get['orientations'] != '') {
            if (is_array($get['orientations']) && count($get['orientations']) > 0) {
                foreach ($get["orientations"] as $value) {
                    if ($value != '') {
                        $query .= '&orientation[]=' . $value;
                    }
                }
            }
        }

        if (isset($get['sea_views']) && $get['sea_views'] != '') {
            if (is_array($get['sea_views']) && count($get['sea_views']) > 0) {
                foreach ($get["sea_views"] as $value) {
                    if ($value != '') {
                        $query .= '&views[]=' . $value;
                    }
                }
            }
        }

        if (isset($get['swimming_pools']) && $get['swimming_pools'] != '') {
            if (is_array($get['swimming_pools']) && count($get['swimming_pools']) > 0) {
                foreach ($get["swimming_pools"] as $value) {
                    if ($value != '') {
                        $query .= '&pool[]=' . $value;
                    }
                }
            }
        }

        if (isset($get['furnitures']) && $get['furnitures'] != '') {
            if (is_array($get['furnitures']) && count($get['furnitures']) > 0) {
                foreach ($get["furnitures"] as $value) {
                    if ($value != '') {
                        $query .= '&furniture[]=' . $value;
                    }
                }
            }
        }
        if (isset($get['pets']) && $get['pets'] == 'true') {
            $query .= '&categories[]=pets_allowed';
        }

        if (isset($get['parkings']) && $get['parkings'] != '') {
            if (is_array($get['parkings']) && count($get['parkings']) > 0) {
                foreach ($get["parkings"] as $value) {
                    if ($value != '') {
                        $query .= '&parking[]=' . $value;
                    }
                }
            }
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
            } else {
                $query .= '&location[]=' . $get["location"];
            }
        }
        // for testing multiple bedrooms
        if (isset($_GET['bedrooms_in']) && is_array($_GET['bedrooms_in'])) {
            foreach ($_GET['bedrooms_in'] as $bed) {
                $query .= '&bedrooms_in[]=' . $bed;
            }
        }
        if (isset($get["city"]) && $get["city"] != "") {
            if (is_array($get["city"]) && count($get["city"])) {
                foreach ($get["city"] as $value) {
                    if ($value != '') {
                        $query .= '&address_city[]=' . $value;
                    }
                }
            } else {
                $query .= '&address_city[]=' . $get["city"];
            }
        }
        if (isset($get["type"]) && is_array($get["type"]) && $get["type"] != "") {
            foreach ($get["type"] as $key => $value) {
                if ($value != '') {
                    $query .= '&type_one[]=' . $value;
                }
            }
        }
        if (isset($get["type2"]) && is_array($get["type2"]) && $get["type2"] != "") {
            foreach ($get["type2"] as $key => $value) {
                if ($value != '') {
                    $query .= '&type_two[]=' . $value;
                }
            }
        }

        if (isset($get["location_group"]) && is_string($get["location_group"]) && $get["location_group"] != '') {
            $query .= '&location_group[]=' . $get["location_group"];
        }
        if (isset($get["location_group"]) && is_array($get["location_group"]) && count($get["location_group"]) > 0) {
            foreach ($get["location_group"] as $key => $value) {
                $query .= '&location_group[]=' . $value;
            }
        }
        if (isset($get["lg_by_key"]) && is_string($get["lg_by_key"]) && $get["lg_by_key"] != '') {
            $query .= '&lg_by_key[]=' . $get["lg_by_key"];
        }
        if (isset($get["lg_by_key"]) && is_array($get["lg_by_key"]) && count($get["lg_by_key"]) > 0 && !empty($get["lg_by_key"][0])) {
            foreach ($get["lg_by_key"] as $key => $value) {
                $query .= '&lg_by_key[]=' . $value;
            }
        }
        if (isset($get['bedrooms_from']) && !empty($get['bedrooms_from'])) {
            $query .= '&bedrooms[]=' . $get['bedrooms_from'];
            if (isset($get['bedrooms_to']) && !empty($get['bedrooms_to'])) {
                $query .= '&bedrooms[]=' . $get['bedrooms_to'];
            } else {
                $query .= '&bedrooms[]=50';
            }
        } elseif (isset($get['bedrooms_to']) && !empty($get['bedrooms_to'])) {
            $query .= '&bedrooms[]=1';
            $query .= '&bedrooms[]=' . $get['bedrooms_to'];
        } elseif (isset($get["bedrooms"]) && $get["bedrooms"] != "") {
            $query .= '&bedrooms[]=' . $get["bedrooms"] . '&bedrooms[]=50';
        }

        if (isset($get['built_from']) && !empty($get['built_from'])) {
            $query .= '&built[]=' . $get['built_from'];
            if (isset($get['built_to']) && !empty($get['built_to'])) {
                $query .= '&built[]=' . $get['built_to'];
            } else {
                $query .= '&built[]=50';
            }
        } elseif (isset($get['built_to']) && !empty($get['built_to'])) {
            $query .= '&built[]=1';
            $query .= '&built[]=' . $get['built_to'];
        }

        if (isset($get['bathrooms_from']) && !empty($get['bathrooms_from'])) {
            $query .= '&bathrooms[]=' . $get['bathrooms_from'];
            if (isset($get['bathrooms_to']) && !empty($get['bathrooms_to'])) {
                $query .= '&bathrooms[]=' . $get['bathrooms_to'];
            } else {
                $query .= '&bathrooms[]=50';
            }
        } elseif (isset($get['bathrooms_to']) && !empty($get['bathrooms_to'])) {
            $query .= '&bathrooms[]=1';
            $query .= '&bathrooms[]=' . $get['bathrooms_to'];
        } elseif (isset($get["bathrooms"]) && $get["bathrooms"] != "") {
            $query .= '&bathrooms[]=' . $get["bathrooms"] . '&bathrooms[]=50';
        }
        if (isset($get["booking_data"]) && $get["booking_data"] != "") {
            $query .= '&booking_data=' . $get["booking_data"];
        }
        // if (isset($get["st_date_from"]) && $get["st_date_from"] != "" && $get["st_date_from"] != "Arrival" && isset($get["st_date_from_submit"]) && $get["st_date_from_submit"] != "") {
        //     $stdf = new \DateTime($get["st_date_from_submit"]);
        //     $query .= '&booking_from=' . $stdf->getTimestamp();
        // }
        // if (isset($get["st_date_to"]) && $get["st_date_to"] != "" && $get["st_date_to"] != "Return" && isset($get["st_date_to_submit"]) && $get["st_date_to_submit"] != "") {
        //     $stdt = new \DateTime($get["st_date_to_submit"]);
        //     $query .= '&booking_to=' . $stdt->getTimestamp();
        // }
        if (isset($get["st_date_from_submit"]) && $get["st_date_from_submit"] != "") {
            $stdf = new \DateTime($get["st_date_from_submit"]);
            $query .= '&booking_from=' . $stdf->getTimestamp();
        }
        if (isset($get["st_date_to_submit"]) && $get["st_date_to_submit"] != "") {
            $stdt = new \DateTime($get["st_date_to_submit"]);
            $query .= '&booking_to=' . $stdt->getTimestamp();
        }
        if (isset($get["st_from"]) && $get["st_from"] != "") {
            $query .= '&st_new_price[]=' . $get["st_from"];
        }
        if (isset($get["st_from"]) && $get["st_from"] == "") {
            $query .= '&st_new_price[]=0';
        }
        if (isset($get["st_to"]) && $get["st_to"] != "") {
            $query .= '&st_new_price[]=' . $get["st_to"];
        }
        if (isset($get["st_to"]) && $get["st_to"] == "") {
            $query .= '&st_new_price[]=100000000';
        }
        if (isset($get["no_of_days"]) && $get["no_of_days"] != "" && isset($get["st_from"]) && $get["st_from"] != "" && isset($get["st_date_from"]) && $get["st_date_from"] != "" && isset($get["st_date_to"]) && $get["st_date_to"] != "") {
            $stdf = new \DateTime($get["st_date_from"]);
            $stdt = new \DateTime($get["st_date_to"]);
            $query .= '&st_new_price[]=' . $stdf->getTimestamp();
            $query .= '&st_new_price[]=' . $stdt->getTimestamp();
            $query .= '&st_new_price[]=' . $get["no_of_days"];
        }
        if (isset($get["sleeps"]) && $get["sleeps"] != "") {
            $query .= '&sleeps=' . $get["sleeps"];
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
            if (isset($get["price_range"]) && $get["price_range"] != "") {
                $from = substr($get["price_range"], 0, strrpos($get["price_range"], '-'));
                $to = substr($get["price_range"], strrpos($get["price_range"], '-') + 1);

                $query .= '&currentprice[]=' . $from;
                $query .= '&currentprice[]=' . $to;
            }
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
        if (isset($get["pool"]) && $get["pool"] != '') {
            $query .= '&pool[]=' . $get["pool"];
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
        if (isset($get["golf"]) && $get["golf"] != '' && $get["golf"]) {
            $query .= '&categories[]=golf';
        }
        if (isset($get["luxury"]) && $get["luxury"] != '' && $get["luxury"]) {
            $query .= '&categories[]=luxury';
        }
        if (isset($get["close_to_sea"]) && $get["close_to_sea"] != '' && $get["close_to_sea"]) {
            $query .= '&settings[]=close_to_sea';
        }
        if (isset($get["sea_view"]) && $get["sea_view"] != '' && $get["sea_view"]) {
            $query .= '&views[]=sea';
        }
        if (isset($get["panoramic"]) && $get["panoramic"] != '' && $get["panoramic"]) {
            $query .= '&views[]=panoramic';
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
            $query .= '&urbanisation=' . $get['urbanisation'];
        }
        if (isset($get["new_property"]) && $get["new_property"] != "" && $get["new_property"]) {
            $query .= '&conditions[]=never_lived';
        }
        if (isset($get["conditions"]) && is_array($get["conditions"]) && $get["conditions"] != "") {
            foreach ($get["conditions"] as $condition) {
                $query .= '&conditions[]=' . $condition;
            }
        }
        if (isset($get["features"]) && is_array($get["features"]) && $get["features"] != "") {
            foreach ($get["features"] as $feature) {
                $query .= '&features[]=' . $feature;
            }
        }
        if (isset($get["climate_control"]) && is_array($get["climate_control"]) && $get["climate_control"] != "") {
            foreach ($get["climate_control"] as $climate_control) {
                $query .= '&climate_control[]=' . $climate_control;
            }
        }
        if (isset($get["reference"]) && $get["reference"] != "") {
            $query .= '&' . (isset($cms_settings['general_settings']['reference']) ? $cms_settings['general_settings']['reference'] : 'reference') . '=' . $get['reference'];
        }
        if (isset($get["agency_reference"]) && $get["agency_reference"] != "") {
            $query .= '&agency_reference=' . $get['agency_reference'];
        }
        if (isset($get["building-style"]) && is_array($get["building-style"]) && $get["building-style"] != "") {
            foreach ($get["building-style"] as $style) {
                $query .= '&p_style[]=' . $style;
            }
        }


        if (isset($get["sale"]) && !isset($get["rent"]) && $get["sale"] != "") {
            $query .= '&sale=1';
        }
        if (!isset($get["sale"]) && isset($get["rent"]) && $get["rent"] != "") {
            $query .= '&rent=1';
        }
        if (isset($get["sale"]) && $get["sale"] && isset($get["rent"]) && $get["rent"]) {
            $query .= '&sale_rent=1';
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
        if (isset($get["keywords"]) && $get["keywords"] != "") {
            $query .= '&keywords=' . $get["keywords"];
        }
        if (isset($get["mooring_type"]) && is_array($get["mooring_type"]) && $get["mooring_type"] != "") {
            foreach ($get['mooring_type'] as $mooring_type) {
                $query .= '&mooring_type[]=' . $mooring_type;
            }
        }
        if (isset($get["listing_agent"]) && $get["listing_agent"]) {
            if (is_array($get["listing_agent"])) {
                foreach ($get['listing_agent'] as $agent) {
                    $query .= '&listing_agent[]=' . $agent;
                }
            } else {
                $query .= '&listing_agent=' . $get["listing_agent"];
            }
        }
        if (isset($get['orderby']) && !empty($get['orderby'])) {
            if ($get['orderby'] == 'dateASC') {
                $query .= '&orderby[]=created_at&orderby[]=ASC';
            } elseif ($get['orderby'] == 'dateDESC') {
                $query .= '&orderby[]=created_at&orderby[]=DESC';
            } elseif ($get['orderby'] == 'updateASC') {
                $query .= '&orderby[]=updated_at&orderby[]=ASC';
            } elseif ($get['orderby'] == 'updateDESC') {
                $query .= '&orderby[]=updated_at&orderby[]=DESC';
            } elseif ($get['orderby'] == 'priceASC') {
                $query .= '&orderby[]=currentprice&orderby[]=ASC';
            } elseif ($get['orderby'] == 'priceDESC') {
                $query .= '&orderby[]=currentprice&orderby[]=DESC';
            } elseif ($get['orderby'] == 'bedsDESC') {
                $query .= '&orderby[]=bedrooms&orderby[]=DESC';
            } elseif ($get['orderby'] == 'bedsASC') {
                $query .= '&orderby[]=bedrooms&orderby[]=ASC';
            } elseif ($get['orderby'] == 'statusDESC') {
                $query .= '&orderby[]=status&orderby[]=DESC';
            } elseif ($get['orderby'] == 'statusASC') {
                $query .= '&orderby[]=status&orderby[]=ASC';
            } elseif ($get['orderby'] == 'priceDESC_st_rental') {
                $query .= '&orderby[]=st_new_price&orderby[]=DESC';
            } elseif ($get['orderby'] == 'priceASC_st_rental') {
                $query .= '&orderby[]=st_new_price&orderby[]=ASC';
            } elseif ($get['orderby'] == 'priceDESC_lt_rental') {
                $query .= '&orderby[]=lt_new_price&orderby[]=DESC';
            } elseif ($get['orderby'] == 'priceASC_lt_rental') {
                $query .= '&orderby[]=lt_new_price&orderby[]=ASC';
            } elseif ($get['orderby'] == 'own') {
                $query .= '&orderby[]=own&orderby[]=DESC&orderby[]=exclusive&orderby[]=DESC';
            }
        }
        /**New Rental Query */
        if (isset($get["st_date_from_submit"]) && $get["st_date_from_submit"] != "") {
            $stdf = new \DateTime($get["st_date_from_submit"]);
            $query .= '&rental_period_from=' . $stdf->getTimestamp();
            $query =  Properties::removeParam($query, 'booking_from');
        }
        if (isset($get["st_date_to_submit"]) && $get["st_date_to_submit"] != "") {
            $stdt = new \DateTime($get["st_date_to_submit"]);
            $query .= '&rental_period_to=' . $stdt->getTimestamp();
            $query = Properties::removeParam($query, 'booking_to');
        }
        if (isset($get["st_from"]) && isset($get["st_to"])) {
            $query = Properties::removeParam($query, 'st_new_price[]');
            $query .= '&rental_new_price=';
            if ($get["st_from"] != "" && $get["st_from"] > 0) {
                $query .= $get["st_from"] . ',';
            } elseif ($get["st_from"] == "" || $get["st_from"] < 1) {
                $query .= '1,';
            }
            if ($get["st_to"] != "" && $get["st_to"] > 0) {
                $query .= $get["st_to"];
            } elseif ($get["st_to"] == "" || $get["st_to"] < 1) {
                $query .= '100000000000000';
            }
        }
        return $query;
    }
    public static function removeParam($url, $param)
    {
        $url = preg_replace('/(&|\?)' . preg_quote($param) . '=[^&]*$/', '', $url);
        $url = preg_replace('/(&|\?)' . preg_quote($param) . '=[^&]*&/', '$1', $url);
        $url = preg_replace('/(&|\?)' . preg_quote($param) . '=[^&]*&/', '$1', $url);
        $url = preg_replace('/(&|\?)' . preg_quote($param) . '=[^&]*&/', '$1', $url);
        return $url;
    }
    public static function findWithLatLang($query, $wm = false, $cache = false, $options = ['images_size' => 1200])
    {
        $webroot = Yii::getAlias('@webroot');
        $file = $webroot . '/uploads/temp/'.(isset($options['transaction_type']) ? $options['transaction_type'].'_' : '').'properties-all-latlang.json';
        $query .= '&latlng=true';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $data_array = self::findAll($query, $wm, $cache, $options);
            $json_data =  json_encode($data_array);

            file_put_contents($file, $json_data);
        } else {
            $json_data = file_get_contents($file);
        }
        return json_decode($json_data, true);
    }

    public static function findAllWithLatLang($type = "")
    {
        $webroot = Yii::getAlias('@webroot');
        if (!empty($type)) {
            $type = '&type=' . $type;
        }
        $url = Yii::$app->params['apiUrl'] . 'properties/properties-with-latlang&user_apikey=' . Yii::$app->params['api_key'] . $type;
        if (!is_dir($webroot . '/uploads/')) {
            mkdir($webroot . '/uploads/');
        }
        if (!is_dir($webroot . '/uploads/temp/')) {
            mkdir($webroot . '/uploads/temp/');
        }
        $file = $webroot . '/uploads/temp/properties-latlong.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $file_data =
                //file_get_contents($url);
                Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
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
        $url = Yii::$app->params['apiUrl'] . 'properties/agency&user_apikey=' . Yii::$app->params['api_key'];
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $file_data =
                //file_get_contents($url);
                Functions::getCRMData($url);
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
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $url = Yii::$app->params['apiUrl'] . 'properties/get-listing-agent&listing_agent=' . $id . '&user_apikey=' . Yii::$app->params['api_key'];
            $file_data =
                //file_get_contents($url);
                Functions::getCRMData($url);

            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, true);
    }

    public static function Categories()
    {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
            mkdir($webroot . '/uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        $file = $webroot . '/uploads/temp/property_categories.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $url = Yii::$app->params['apiUrl'] . 'properties/categories&user_apikey=' . Yii::$app->params['api_key'];
            $file_data =
                //file_get_contents();
                Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        $Arr = json_decode($file_data, true);
        $return_data = [];
        foreach ($Arr as $data) {
            if (isset($data['value']['en']))
                $return_data[$data['key']] = $data['value']['en'];
        }
        return $return_data;
    }

    public static function DoCache($query, $url)
    {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
            mkdir($webroot . '/uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        $file = $webroot . '/uploads/temp/' . sha1($query) . '.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $file_data =
                //file_get_contents($url);
                Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        return $file_data;
    }
    /**
     * calculations
     * @depricated don't use anywhere 
     */
    public static function calculations($pref, $date_from, $date_to, $nosleeps)
    {
        \Yii::$app->setTimeZone('Europe/Paris');
        $agency = Properties::getAgency();
        $rental_prices = [];
        $total_price = 0;
        $rental_bill = 0;
        $deposit = isset($agency['payment_options']) && count($agency['payment_options']) > 0 && isset($agency['payment_options']['deposit']) && $agency['payment_options']['deposit'] > 0 ? $agency['payment_options']['deposit'] : 0;
        $table = '';
        $return_data = [];
        $data = [];

        if (isset($pref)) {
            $property = Properties::findOne($pref, true, false, true);
        }

        $arrival = ($date_from / 1000);
        $departure = ($date_to / 1000);
        $number_sleeps = $nosleeps;
        $start_date = new \DateTime();
        $start_date->setTimestamp($arrival);

        $end_date = new \DateTime();
        $end_date->setTimestamp($departure);

        $number_of_days = $start_date->diff($end_date)->format('%a');
        $end_season_to = [];

        if (isset($property['season_data']) && count($property['season_data']) > 0) {
            $season_data_from = $property['season_data'][0]['period_to'];
            $season_data_to = $property['season_data'][count($property['season_data']) - 1]['period_to'];
            foreach ($property['season_data'] as $season) {
                if ($season['period_to']) {
                    $end_season_to[] = $season['period_to'];
                }
            }
            foreach ($property['season_data'] as $season) {
                // rental discount logic -----STRT-----
                $s_gross_perday_price = isset($season['gross_day_price']) && $season['gross_day_price'] !== '' ? $season['gross_day_price'] : '';
                if (isset($season['discounts']) && count($season['discounts']) && $s_gross_perday_price) {
                    asort($season['discounts']);
                    $discount = array_filter($season['discounts'], function ($var) use ($number_of_days) {
                        if (isset($var['number_days'])) {
                            return ($var['number_days'] <= $number_of_days);
                        }
                    });
                    $discount = end($discount);
                    if (!empty($discount)) {
                        $discount_percent = (isset($discount['discount_percent']) && $discount['discount_percent'] != '') ? $discount['discount_percent'] : 0;
                        $s_gross_perday_price = $s_gross_perday_price - ($s_gross_perday_price * ($discount_percent / 100));
                    }
                }
                // rental discount logic -----END-----

                $undefined_days = 0;
                $begin = new \DateTime(date('Y-m-d', $arrival));
                // $begin->modify('-1 day');
                $end = new \DateTime(date('Y-m-d', $departure));
                // $end->modify('+1 day');
                $season_data_to = $season['period_to'];

                $interval = \DateInterval::createFromDateString('1 day');
                $period = new \DatePeriod($begin, $interval, $end);

                foreach ($period as $dt) {
                    $tdt = $dt->getTimestamp();
                    $period_from = new \DateTime(date('Y-m-d', $season['period_from']));
                    $period_from = $period_from->getTimestamp();

                    $period_to = new \DateTime(date('Y-m-d', $season['period_to']));
                    $period_to = $period_to->getTimestamp();

                    if ($tdt > max($end_season_to)) {
                        $return_data['undefined_period'] = 1;
                        $return_data['undefined_days'] = $undefined_days++;
                        $return_data['number_of_days'] = $number_of_days;
                    } else {
                        if ($period_from <= $tdt && $period_to >= $tdt) {
                            // print_r($season);
                            if ($s_gross_perday_price !== '') {
                                $rental_bill = $rental_bill + $s_gross_perday_price;
                            } else {
                                $rental_bill = $rental_bill + $season['price_per_day'];
                            }
                            $rental_prices['rental_bill'] = $rental_bill;
                            $total_price = $rental_bill;
                        }
                        $rental_prices['rental_bill'] = $rental_bill;
                        $total_price = $rental_bill;
                    }
                }
            }
        }

        if (isset($property['booking_extras']) && count($property['booking_extras']) > 0) {
            foreach ($property['booking_extras'] as $booking_extra) {
                if (isset($booking_extra['type']) && $booking_extra['type'] == 'per_stay') {
                    if (isset($booking_extra['add_to_price']) && $booking_extra['add_to_price'] == 1) {
                        $rental_prices[$booking_extra['description']['en']] = $booking_extra['price'];
                        $total_price += $booking_extra['price'];
                    }
                } else if (isset($booking_extra['type']) && $booking_extra['type'] == 'per_person') {
                    if (isset($booking_extra['add_to_price']) && $booking_extra['add_to_price'] == 1) {
                        $rental_prices[$booking_extra['description']['en']] = ($booking_extra['price'] * $number_sleeps);
                        $total_price += ($booking_extra['price'] * $number_sleeps);
                    }
                }
            }
        }

        if (isset($property['booking_cleaning']) && count($property['booking_cleaning']) > 0) {
            foreach ($property['booking_cleaning'] as $booking_cleaning) {
                if (isset($booking_cleaning['type']) && $booking_cleaning['type'] == 'per_stay') {
                    $rental_prices[$booking_cleaning['description']['en']] = $booking_cleaning['price'];
                    if (isset($booking_cleaning['charge_to']) && $booking_cleaning['charge_to'] == 'client')
                        $total_price += $booking_cleaning['price'];
                } else if (isset($booking_cleaning['type']) && $booking_cleaning['type'] == 'per_person') {
                    $rental_prices[$booking_cleaning['description']['en']] = ($booking_cleaning['price'] * $number_sleeps);
                    if (isset($booking_cleaning['charge_to']) && $booking_cleaning['charge_to'] == 'client')
                        $total_price += ($booking_cleaning['price'] * $number_sleeps);
                } else if (isset($booking_cleaning['type']) && $booking_cleaning['type'] == 'per_week') {
                    $rental_prices[$booking_cleaning['description']['en']] = $booking_cleaning['price'];
                    if (isset($booking_cleaning['charge_to']) && $booking_cleaning['charge_to'] == 'client')
                        $total_price += $booking_cleaning['price'];
                }
            }
        }
        $return_data['rental_prices'] = $rental_prices;
        $return_data['total_price'] = $total_price;
        return $return_data;
    }

    public static function displayPrice($price)
    {
        $price_done = $price;
        //echo 'hellllllllooooo';
        $settings = Cms::settings();

        if (isset($settings['custom_settings'])) {
            foreach ($settings['custom_settings'] as $cs) {
                if (isset($cs['key']) && $cs['key'] == 'dollar')
                    $dollar = $cs['value'] ? $cs['value'] : '';
                if (isset($cs['key']) && $cs['key'] == 'tl')
                    $tl = $cs['value'] ? $cs['value'] : '';
                if (isset($cs['key']) && $cs['key'] == 'euro')
                    $euro = $cs['value'] ? $cs['value'] : '';
                if (isset($cs['key']) && $cs['key'] == 'gbp')
                    $gbp = $cs['value'] ? $cs['value'] : '';
            }
        }

        $price =  str_replace(".", "", $price);

        $rc = preg_match_all('/\b\d+\b/', $price, $matches);
        $result = preg_replace('/\b\d+\b/', '', $price);
        $result1 = preg_replace('/[^A-Za-z0-9\-]/', '', $result);


        if (isset($_SESSION["pricerate"])) {
            foreach ($matches as $val) {
                if ($_SESSION["pricerate"] == $tl) {
                    $price_done = number_format((float) $_SESSION["pricerate"] * $val[0]) . ' ' . '₺' . ' ' . $result1;
                } elseif ($_SESSION["pricerate"] == $dollar) {
                    $price_done = number_format((float) $_SESSION["pricerate"] * $val[0]) . ' ' . '$' . ' ' . $result1;
                } elseif ($_SESSION["pricerate"] == $gbp) {
                    $price_done = number_format((float) $_SESSION["pricerate"] * $val[0]) . ' ' . '£' . ' ' . $result1;
                } elseif ($_SESSION["pricerate"] == $euro)
                    $price_done = number_format((float) $_SESSION["pricerate"] * $val[0]) . ' ' . '€' . ' ' . $result1;
            }
        } else {
            $price_done = number_format($result1);
        }


        return $price_done;
    }


    public static function PriceRateCahange()
    {
        if (isset($_GET['pricerate'])) {
            $_SESSION["pricerate"] = $_GET['pricerate'];
        } elseif (isset($_GET['?pricerate'])) {
            $_SESSION["pricerate"] = $_GET['?pricerate'];
            if (isset($_GET['test'])) {
                echo $_SESSION["pricerate"];
            }
        }
        //die('1111');
        if (!isset($_SESSION["pricerate"])) {
            $_SESSION["pricerate"] = 1;
        }
    }
    public static function getPropertyRentalPrice($property, $arrival, $departure)
    {
        $url = Yii::$app->params['apiUrl'] . 'properties/calculate-rental-price&user_apikey=' . Yii::$app->params['api_key'] . '&property=' . $property . '&from=' . $arrival . '&to=' . $departure;
        $json = file_get_contents($url);
        return json_decode($json);
    }

    public function saveProperty(){
        $settings = Cms::settings();
        $url = Yii::$app->params['apiUrl'] . 'properties/create&user_apikey=' . Yii::$app->params['api_key'];
        
        $fields = array(
            'type_one' => (isset($this->type_one) ? $this->type_one : null),
            'type_two' => (isset($this->type_two) ? $this->type_two : null),
            'bedrooms' => (isset($this->bedrooms) ? $this->bedrooms : null),
            'bathrooms' => (isset($this->bathrooms) ? $this->bathrooms : null),
            'currentprice' => (isset($this->currentprice) ? $this->currentprice : null),
            'description' => (isset($this->description) ? $this->description : null),
        );
        $curl = new \linslin\yii2\curl\Curl();
        $response = $curl->setPostParams($fields)->post($url);
    }
}
