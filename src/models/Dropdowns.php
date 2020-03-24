<?php

namespace optima\models;

use Yii;
use yii\base\Model;
use linslin\yii2\curl;
use optima\assets\OptimaAsset;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class Dropdowns extends Model
{

    public static function countries()
    {

        $file = Functions::directory() . 'countries.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $url = Yii::$app->params['apiUrl'] . 'properties/countries&user_apikey=' . Yii::$app->params['api_key'];
            $file_data =
                //file_get_contents();
                Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function getRegions($params = [])
    {
        $countries = isset($params['countries']) ? is_array($params['countries']) ? $params['countries'] : explode(',', $params['countries']) : [];
        $return_data = [];

        $file = Functions::directory() . 'regions' . implode(',', $countries) . '.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {

            $query = count($countries) ? array('country' => ['$in' => $countries]) : [];
            $options = [
                "page" => 1,
                "limit" => 50,
                "sort" => ["accent_value.en" => 1]
            ];

            $post_data = ["query" => (object) $query, "options" => $options];

            $curl = new curl\Curl();
            $response = $curl->setRequestBody(json_encode($post_data))
                ->setHeaders([
                    'Content-Type' => 'application/json',
                    'Content-Length' => strlen(json_encode($post_data))
                ])
                ->post(Yii::$app->params['node_url'] . 'regions?user=' . Yii::$app->params['user']);
            $data = json_decode($response, TRUE);
            if (isset($data['docs']) && count($data['docs']) > 0) {
                foreach ($data['docs'] as $doc) {
                    $return_data[$doc['key']] = $doc['value'][strtolower(\Yii::$app->language)];
                }
            }

            file_put_contents($file, json_encode($return_data));
        } else {
            $return_data = json_decode(file_get_contents($file), TRUE);
        }
        return $return_data;
    }

    public static function provinces()
    {

        $file = Functions::directory() . 'provinces.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $url = Yii::$app->params['apiUrl'] . 'properties/provinces&user_apikey=' . Yii::$app->params['api_key'];
            $file_data =
                //file_get_contents();
                Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function getProvinces($params = [])
    {
        $countries = isset($params['countries']) ? is_array($params['countries']) ? $params['countries'] : explode(',', $params['countries']) : [];
        $regions = isset($params['regions']) ? is_array($params['regions']) ? $params['regions'] : explode(',', $params['regions']) : [];
        $return_data = [];
        $file = Functions::directory() . 'provinces_' . implode(',', $regions) . '.json';

        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {

            $query = count($countries) ? array('country' => ['$in' => $countries]) : [];
            $query = count($regions) ? array_merge($query, array('region' => ['$in' => $regions])) : $query;
            $options = [
                "page" => 1,
                "limit" => 50,
                "sort" => ["accent_value.en" => 1]
            ];

            $post_data = ["query" => (object) $query, "options" => $options];

            $curl = new curl\Curl();
            $response = $curl->setRequestBody(json_encode($post_data))
                ->setHeaders([
                    'Content-Type' => 'application/json',
                    'Content-Length' => strlen(json_encode($post_data))
                ])
                ->post(Yii::$app->params['node_url'] . 'provinces?user=' . Yii::$app->params['user']);

            $data = json_decode($response, TRUE);
            $return_data = isset($data['docs']) ? $data['docs'] : [];
            file_put_contents($file, json_encode($return_data));
        } else {
            $return_data = json_decode(file_get_contents($file), TRUE);
        }
        return $return_data;
    }

    public static function cities($provinces = [], $to_json = false)
    {

        $file = Functions::directory() . 'cities_' . implode(',', $provinces) . '.json';

        if (is_array($provinces) && count($provinces) && !file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $p_q = '';
            foreach ($provinces as $province) {
                $p_q .= '&province[]=' . $province;
            }
            $url = Yii::$app->params['apiUrl'] . 'properties/all-cities' . $p_q . '&user_apikey=' . Yii::$app->params['api_key'];
            $file_data =
                //file_get_contents($url);
                Functions::getCRMData($url);
            $file_data = json_decode($file_data);
            usort($file_data, function ($item1, $item2) {
                return $item1->value <=> $item2->value;
            });
            $file_data = json_encode($file_data);
            file_put_contents($file, $file_data);
        } elseif (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $url = Yii::$app->params['apiUrl'] . 'properties/all-cities&user_apikey=' . Yii::$app->params['api_key'];
            $file_data =
                //file_get_contents($url);
                Functions::getCRMData($url);
            $file_data = json_decode($file_data);
            usort($file_data, function ($item1, $item2) {
                return $item1->value <=> $item2->value;
            });
            $file_data = json_encode($file_data);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        return $to_json ? json_encode(json_decode($file_data, TRUE)) : json_decode($file_data, TRUE);
    }

    public static function getCities($params = [])
    {
        $countries = isset($params['countries']) ? is_array($params['countries']) ? $params['countries'] : explode(',', $params['countries']) : [];
        $provinces = isset($params['provinces']) ? is_array($params['provinces']) ? $params['provinces'] : explode(',', intval($params['provinces'])) : [];
        $return_data = [];
        $file = Functions::directory() . 'cities_' . implode(',', $provinces) . '.json';

        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {

            $query = count($countries) ? array('country' => ['$in' => $countries]) : [];
            $query = count($provinces) ? array_merge($query, array('province' => ['$in' => $provinces])) : $query;
            $options = [
                "page" => 1,
                "limit" => 50,
                "sort" => ["accent_value.en" => 1]
            ];

            $post_data = ["query" => (object) $query, "options" => $options];

            $curl = new curl\Curl();
            $response = $curl->setRequestBody(json_encode($post_data))
                ->setHeaders([
                    'Content-Type' => 'application/json',
                    'Content-Length' => strlen(json_encode($post_data))
                ])
                ->post(Yii::$app->params['node_url'] . 'cities?user=' . Yii::$app->params['user']);

            $data = json_decode($response, TRUE);
            $return_data = isset($data['docs']) ? $data['docs'] : [];
            file_put_contents($file, json_encode($return_data));
        } else {
            $return_data = json_decode(file_get_contents($file), TRUE);
        }
        return $return_data;
    }

    public static function locationGroups($provinces = [])
    {
        $file = Functions::directory() . 'locationGroups_' . implode(',', $provinces) . '.json';

        if (is_array($provinces) && count($provinces) && !file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $p_q = '';
            foreach ($provinces as $province) {
                $p_q .= '&province[]=' . $province;
            }
            $url = Yii::$app->params['apiUrl'] . 'properties/location-groups-key-value' . $p_q . '&user_apikey=' . Yii::$app->params['api_key'];
            $file_data =
                //file_get_contents($url);
                Functions::getCRMData($url);

            file_put_contents($file, $file_data);
        } elseif (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $url = Yii::$app->params['apiUrl'] . 'properties/location-groups-key-value&user_apikey=' . Yii::$app->params['api_key'];
            $file_data =
                //file_get_contents($url);
                Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        // echo $file_data;
        //    die;
        return json_decode($file_data, true);
    }

    public static function locations($provinces = [], $to_json = false, $cities = [], $country = '')
    {

        $file = Functions::directory() . 'locations_' . implode(',', $provinces) . implode(',', $cities) . '.json';


        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $p_q = '';
            $c_q = '';
            if (is_array($provinces) && count($provinces)) {
                foreach ($provinces as $province) {
                    $p_q .= '&province[]=' . $province;
                }
            }
            if (is_array($cities) && count($cities)) {
                foreach ($cities as $city) {
                    $c_q .= '&city[]=' . $city;
                }
            }
            $country_check = '';
            if ($country) {
                $country_check = '&country=' . $country;
            }
            $url = Yii::$app->params['apiUrl'] . 'properties/locations&count=true' . $p_q . $c_q . '&user_apikey=' . Yii::$app->params['api_key'] . '&lang=' . ((isset(\Yii::$app->language) && strtolower(\Yii::$app->language) == 'es') ? 'es_AR' : 'en') . $country_check;


            $file_data =
                //file_get_contents($url);
                Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        return $to_json ? json_encode(json_decode($file_data, TRUE)) : json_decode($file_data, TRUE);
    }

    public static function urbanisations()
    {
        $return_data = [];

        $file = Functions::directory() . 'urbanisations.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $post_data = ["query" => (object) [], "options" => ["page" => 1, "limit" => 1000, "sort" => ["value" => 1], "select" => "_id key value agency basic_info." . Yii::$app->params['agency']]];
            $curl = new curl\Curl();
            $response = $curl->setRequestBody(json_encode($post_data))
                ->setHeaders([
                    'Content-Type' => 'application/json',
                    'Content-Length' => strlen(json_encode($post_data))
                ])
                ->post(Yii::$app->params['node_url'] . 'urbanisations/dropdown?user=' . Yii::$app->params['user']);
            $data = json_decode($response, TRUE);
            if (isset($data['docs']) && count($data['docs']) > 0) {
                foreach ($data['docs'] as $doc) {
                    if (isset($doc['basic_info'][Yii::$app->params['agency']]['status']) && $doc['basic_info'][Yii::$app->params['agency']]['status'] == 'Active' && isset($doc['key']))
                        $return_data[$doc['key']] = isset($doc['value']) ? $doc['value'] : '';
                }
            }
            file_put_contents($file, json_encode($return_data));
        } else {
            $return_data = json_decode(file_get_contents($file), TRUE);
        }
        return $return_data;
    }

    public static function getUrbanisations($params = [])
    {
        $return_data = [];
        $file = Functions::directory() . 'urbanisation.json';

        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $query = [];
            $options = [
                "page" => 1,
                "limit" => 50,
                "sort" => ["value" => 1],
                "select" => "_id key value agency basic_info." . Yii::$app->params['agency']
            ];

            $post_data = ["query" => (object) $query, "options" => $options];

            $curl = new curl\Curl();
            $response = $curl->setRequestBody(json_encode($post_data))
                ->setHeaders([
                    'Content-Type' => 'application/json',
                    'Content-Length' => strlen(json_encode($post_data))
                ])
                ->post(Yii::$app->params['node_url'] . 'urbanisations/dropdown?user=' . Yii::$app->params['user']);

            $data = json_decode($response, TRUE);
            $return_data = isset($data['docs']) ? $data['docs'] : [];
            file_put_contents($file, json_encode($return_data));
        } else {
            $return_data = json_decode(file_get_contents($file), TRUE);
        }
        return $return_data;
    }

    public static function mooringTypes()
    {
        $curl = new curl\Curl();
        $response = $curl->setRequestBody(json_encode([]))
            ->setHeaders([
                'Content-Type' => 'application/json',
                'Content-Length' => strlen(json_encode([]))
            ])
            ->post(Yii::$app->params['node_url'] . 'mooring_types/all?user_apikey=' . Yii::$app->params['api_key']);

        $data = json_decode($response, TRUE);
        foreach ($data as $mooring_type) {
            $value = isset($mooring_type['value'][strtolower(Yii::$app->language) == 'es' ? 'es_AR' : strtolower(Yii::$app->language)]) ? $mooring_type['value'][strtolower(Yii::$app->language) == 'es' ? 'es_AR' : strtolower(Yii::$app->language)] : '';
            $mooring_types[$mooring_type['key']] = $value;
        }
        return $mooring_types;
    }

    public static function types()
    {

        $file = Functions::directory() . 'types.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $url = Yii::$app->params['apiUrl'] . 'properties/types&user_apikey=' . Yii::$app->params['api_key'];
            $file_data =
                //file_get_contents($url);
                Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function CommercialType()
    {
        $options = ["page" => 1, "limit" => 200];
        $post_data = ["options" => $options];
        $curl = new curl\Curl();
        $response = $curl->setRequestBody(json_encode($post_data))
            ->setHeaders([
                'Content-Type' => 'application/json',
                'Content-Length' => strlen(json_encode($post_data))
            ])
            ->post(Yii::$app->params['node_url'] . 'commercial_types?user_apikey=' . Yii::$app->params['api_key']);

        // $webroot = Yii::getAlias('@webroot');
        // if (!is_dir($webroot . '/uploads/')) {
        //     mkdir($webroot . '/uploads/');
        // }
        // if (!is_dir(Functions::directory() . '')) {
        //     mkdir(Functions::directory() . '');
        // }
        // $file = Functions::directory() . 'types.json';
        // if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
        //     $url = Yii::$app->params['node_url'] . 'commercial_types?user_apikey=' . Yii::$app->params['api_key'];
        //     echo $url;
        //     die;
        //     $file_data =
        //         //file_get_contents($url);
        //         Functions::getCRMData($url);
        //     file_put_contents($file, $file_data);
        // } else {
        //     $file_data = file_get_contents($file);
        // }
        return json_decode($response, TRUE);
    }

    public static function typesByLanguage()
    {
        $types = [];
        //        $types['subtypes'] = [];

        $file = Functions::directory() . 'types.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $url = Yii::$app->params['apiUrl'] . 'properties/types&user_apikey=' . Yii::$app->params['api_key'];
            $file_data =
                //file_get_contents(Yii::$app->params['apiUrl'] . 'properties/types&user_apikey=' . Yii::$app->params['api_key']);
                Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        $fdata = json_decode($file_data);

        foreach ($fdata as $file) {
            $sub_types = [];
            if (isset($file->sub_type) && count($file->sub_type) > 0) {
                foreach ($file->sub_type as $subtype) {
                    $sub_types[] = ['key' => $subtype->key, 'value' => Yii::t('app', strtolower($subtype->value->en))];
                }
                usort($sub_types, function ($item1, $item2) {
                    return $item1['value'] <=> $item2['value'];
                });
            }
            $types[] = ['key' => $file->key, 'value' => Yii::t('app', strtolower($file->value->en)), 'sub_types' => $sub_types];
        }
        usort($types, function ($item1, $item2) {
            return $item1['value'] <=> $item2['value'];
        });
        return $types;
    }

    public static function buildingStyles()
    {

        $file = Functions::directory() . 'building-style.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $url = Yii::$app->params['apiUrl'] . 'properties/building-style&user_apikey=' . Yii::$app->params['api_key'];
            $file_data =
                //file_get_contents($url);
                Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function numbers($limit)
    {
        return range(1, $limit);
    }

    public static function prices($from, $to, $to_json = false)
    {
        $range = range($from, $to);
        $data = [];
        foreach ($range as $value) {
            if ($value <= 2000 && $value % 200 == 0) {
                $data[] = [
                    'key' => $value,
                    'value' => str_replace(',', '.', (number_format((int) $value))) . ' €'
                ];
            }
            if ($value > 25000 && $value % 25000 == 0) {
                $data[] = [
                    'key' => $value,
                    'value' => str_replace(',', '.', (number_format((int) $value))) . ' €'
                ];
            }
        }
        return $to_json ? json_encode($data) : $data;
    }

    public static function orientations()
    {
        return [['key' => "north", 'value' => \Yii::t('app', 'north')], ['key' => "north_east", 'value' => \Yii::t('app', 'north_east')], ['key' => "east", 'value' => \Yii::t('app', 'east')], ['key' => "south_east", 'value' => \Yii::t('app', 'south_east')], ['key' => "south", 'value' => \Yii::t('app', 'south')], ['key' => "south_west", 'value' => \Yii::t('app', 'south_west')], ['key' => "west", 'value' => \Yii::t('app', 'west')], ['key' => "north_west", 'value' => \Yii::t('app', 'north_west')],];
    }

    /**
     *
     * Get types html
     *
     * @param    array data array e.g for options return html 
     * @param    array options array e.g array('name'=>'test','id'=>'my_id',class='my_class')
     * @return   JSON OR html
     * @use      Dropdowns::typesHTML($data, $options = [name='test'])
     */
    public static function types_html($options)
    {
        $types = self::types();
        $types = self::prepare_select_data($types, 'key', 'value');
        return self::html_select($types, $options);
    }

    /**
     *
     * Get location groups html dropdown
     *
     * @param    array options array e.g array('name'=>'test','id'=>'my_id','class'=>'my_class')
     * @return   html
     * @use      Dropdowns::location_groups_html($options = [name='test'])
     */
    public static function location_groups_html($options = array('name' => 'lg_by_key[]'))
    {
        $locationGroups = self::locationGroups();
        $locationGroups = self::prepare_select_data($locationGroups, 'key_system', 'value');
        return self::html_select($locationGroups, $options);
    }

    /**
     *
     * Get locations html dropdown
     *
     * @param    array selected_locationGroups array e.g array('0'=>'712','1'=>'714')
     * @param    array options array e.g array('name'=>'test','id'=>'my_id','class'=>'my_class')
     * @return   html
     * @use      Dropdowns::locations_html($options = [name='test'])
     */
    public static function locations_html($selected_locationGroups, $options = array('name' => 'location[]'))
    {
        $locationGroups = self::locationGroups();
        $locations = [];
        $loc = [];

        foreach ($selected_locationGroups as $selected_locationGroup) {
            foreach ($locationGroups as $locationGroup) {
                if ($selected_locationGroup == $locationGroup['key_system']) {
                    $lGroups[] = $locationGroup;
                    if (isset($locationGroup['group_value'])) {
                        $locations = self::prepare_select_data($locationGroup['group_value'], 'key', strtolower(Yii::$app->language) == 'es' ? 'es_AR' : strtolower(Yii::$app->language));
                    }
                }
            }
            foreach ($locations as $value) {
                $loc[] = $value;
            }
        }
        return self::html_select($loc, $options);
    }

    /**
     *
     * Get prepared select data
     *
     * @param    array data array e.g for options return html 
     * @param    array options array e.g array('name'=>'test','id'=>'my_id','class'=>'my_class')
     * @return   html
     * @use      Dropdowns::prepare_select_data($dataArray='Data to be formated', $option_key_index='key', $option_value_index='value')
     */
    public static function prepare_select_data($dataArray, $option_key_index = 'key', $option_value_index = 'value')
    {
        $finalFormatedSelectArray = array();
        foreach ($dataArray as $key => $value) {
            $finalFormatedSelectArray[$key]['option_key'] = $value[$option_key_index];
            $finalFormatedSelectArray[$key]['option_value'] = (is_array($value[$option_value_index]) ? $value[$option_value_index]['en'] : $value[$option_value_index]);
        }
        return $finalFormatedSelectArray;
    }

    /**
     *
     * Get prepared select data
     *
     * @param    array data array e.g for options return html 
     * @param    array options array e.g array('name'=>'test','id'=>'my_id',class='my_class')
     * @return   html
     * @use      Dropdowns::dropdown($dataArray='Data to be formated', $options = [name='test'])
     */
    public static function dropdown($dataArray, $options)
    {
        $finalFormatedSelectArray = array();
        foreach ($dataArray as $key => $value) {
            $finalFormatedSelectArray[$key]['option_key'] = $key;
            $finalFormatedSelectArray[$key]['option_value'] = $value;
        }

        return self::html_select($finalFormatedSelectArray, $options);
    }

    /**
     *
     * Get html_select dropdown
     *
     * @param    array data array e.g for options return html 
     * @param    array options array e.g array('name'=>'test','id'=>'my_id',class='my_class')
     * @return   html
     * @use      Dropdowns::html_select($data, $options = [name='test'])
     */
    public static function html_select($data, $options = [])
    {
        $path =  dirname(dirname(__FILE__));
        $view = Yii::$app->controller->view;

        optimaAsset::register($view);

        $select_html = '';
        require($path . '/views/partials/selectDropdown.php');
        return $select_html;
    }

    public static function offices()
    {

        $file = Functions::directory() . 'offices.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $url = Yii::$app->params['apiUrl'] . 'properties/get-offices&user_apikey=' . Yii::$app->params['api_key'] . '&agency_id=' . Yii::$app->params['agency'];
            // echo $url;
            // die('---');
            $file_data =
                //file_get_contents();
                Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }
}
