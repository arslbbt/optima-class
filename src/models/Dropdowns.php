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
            $file_data = Functions::getCRMData($url);
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
                "limit" => 1000,
                "sort" => ["accent_value.en" => 1]
            ];

            $post_data = ["query" => (object) $query, "options" => $options];

            $curl = new curl\Curl();
            $response = $curl->setRequestBody(json_encode($post_data, JSON_NUMERIC_CHECK))
                ->setHeaders([
                    'Content-Type' => 'application/json',
                    'Content-Length' => strlen(json_encode($post_data, JSON_NUMERIC_CHECK))
                ])
                ->post(Yii::$app->params['node_url'] . 'regions?user=' . Yii::$app->params['user']);

            $data = json_decode($response, TRUE);
            $return_data = isset($data['docs']) ? $data['docs'] : [];
            file_put_contents($file, json_encode($return_data));
        } else {
            $return_data = json_decode(file_get_contents($file), TRUE);
        }
        return $return_data;
    }

    // use Dropdowns::getProvinces() as it will provide more options to handle data in controller and works with countries and regions search too
    public static function provinces($country = '')
    {
        $country_query = $country == 'all' ? '&country=all' : '';
        $file = Functions::directory() . 'provinces.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $url = Yii::$app->params['apiUrl'] . 'properties/provinces&user_apikey=' . Yii::$app->params['api_key'] . $country_query;
            $file_data = Functions::getCRMData($url);
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
            $response = $curl->setRequestBody(json_encode($post_data, JSON_NUMERIC_CHECK))
                ->setHeaders([
                    'Content-Type' => 'application/json',
                    'Content-Length' => strlen(json_encode($post_data, JSON_NUMERIC_CHECK))
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

    // use Dropdowns::getCities() as it will provide more options to handle data in controller and works with countries search too
    public static function cities($country = '', $provinces = [], $to_json = false, $prop_count = 1)
    {
        $country_query = $country == 'all' ? '&country=all' : '';
        $file = Functions::directory() . 'cities_' . implode(',', $provinces) . '.json';
        
        if (is_array($provinces) && count($provinces) && !file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $p_q = '';
            foreach ($provinces as $province) {
                $p_q .= '&province[]=' . $province;
            }
            $url = Yii::$app->params['apiUrl'] . 'properties/all-cities' . $p_q . '&user_apikey=' . Yii::$app->params['api_key']. $country_query.'&check_prop_count='.$prop_count;
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
            $url = Yii::$app->params['apiUrl'] . 'properties/all-cities&user_apikey=' . Yii::$app->params['api_key']. $country_query.'&check_prop_count='.$prop_count;
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
        $provinces = isset($params['provinces']) ? is_array($params['provinces']) ? $params['provinces'] : explode(',', $params['provinces']) : [];
        $return_data = [];
        $file = Functions::directory() . 'cities_' . implode(',', $provinces) . '.json';

        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {

            $query = count($countries) ? array('country' => ['$in' => $countries]) : [];
            $query = count($provinces) ? array_merge($query, array('province' => ['$in' => $provinces])) : $query;
            $options = [
                "page" => 1,
                "limit" => 1000,
                "sort" => ["accent_value.en" => 1]
            ];

            $post_data = ["query" => (object) $query, "options" => $options];

            $curl = new curl\Curl();
            $response = $curl->setRequestBody(json_encode($post_data, JSON_NUMERIC_CHECK))
                ->setHeaders([
                    'Content-Type' => 'application/json',
                    'Content-Length' => strlen(json_encode($post_data, JSON_NUMERIC_CHECK))
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
            $file_data = Functions::getCRMData($url);

            file_put_contents($file, $file_data);
        } elseif (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $url = Yii::$app->params['apiUrl'] . 'properties/location-groups-key-value&user_apikey=' . Yii::$app->params['api_key'];
            $file_data = Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, true);
    }

    // use Dropdowns::getLocations() as it will provide more options to handle data in controller
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
            
            $file_data = Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        return $to_json ? json_encode(json_decode($file_data, TRUE)) : json_decode($file_data, TRUE);
    }

    public static function getLocations($params = [])
    {
        $countries = isset($params['countries']) ? is_array($params['countries']) ? $params['countries'] : explode(',', $params['countries']) : [];
        $cities = isset($params['cities']) ? is_array($params['cities']) ? $params['cities'] : explode(',', $params['cities']) : [];
        $return_data = [];
        $file = Functions::directory() . 'locations_' . implode(',', $cities) . '.json';

        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {

            $query = count($countries) ? array('country' => ['$in' => $countries]) : [];
            $query = count($cities) ? array_merge($query, array('city' => ['$in' => $cities])) : $query;
            $options = [
                "page" => 1,
                "limit" => 1000,
                "sort" => ["accent_value.en" => 1]
            ];

            $post_data = ["query" => (object) $query, "options" => $options];

            $curl = new curl\Curl();
            $response = $curl->setRequestBody(json_encode($post_data, JSON_NUMERIC_CHECK))
                ->setHeaders([
                    'Content-Type' => 'application/json',
                    'Content-Length' => strlen(json_encode($post_data, JSON_NUMERIC_CHECK))
                ])
                ->post(Yii::$app->params['node_url'] . 'locations?user=' . Yii::$app->params['user']);

            $data = json_decode($response, TRUE);
            $return_data = isset($data['docs']) ? $data['docs'] : [];
            file_put_contents($file, json_encode($return_data));
        } else {
            $return_data = json_decode(file_get_contents($file), TRUE);
        }
        return $return_data;
    }

    // use Dropdowns::getUrbanisations() as it will provide more options to handle data in controller
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
                "limit" => 1000,
                "sort" => ["value" => 1],
                "select" => "_id key value agency basic_info." . Yii::$app->params['agency']
            ];

            $post_data = ["query" => (object) $query, "options" => $options];

            $curl = new curl\Curl();
            $response = $curl->setRequestBody(json_encode($post_data, JSON_NUMERIC_CHECK))
                ->setHeaders([
                    'Content-Type' => 'application/json',
                    'Content-Length' => strlen(json_encode($post_data, JSON_NUMERIC_CHECK))
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

    public static function getCustomCategories($params = [])
    {
        $file = Functions::directory() . 'custom_categories.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $url = Yii::$app->params['apiUrl'] . 'properties/categories&user_apikey=' . Yii::$app->params['api_key'];
            $file_data = Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function mooringTypes($params = [])
    {
        $curl = new curl\Curl();
        $response = $curl->setRequestBody(json_encode([]))
            ->setHeaders([
                'Content-Type' => 'application/json',
                'Content-Length' => strlen(json_encode([]))
            ])
            ->post(Yii::$app->params['node_url'] . 'mooring_types/all?user_apikey=' . Yii::$app->params['api_key']);

        $return_data = json_decode($response, TRUE);

        if (isset($params['allData']))
            return $return_data;

        foreach ($return_data as $mooring_type) {
            $value = isset($mooring_type['value'][strtolower(Yii::$app->language) == 'es' ? 'es_AR' : strtolower(Yii::$app->language)]) ? $mooring_type['value'][strtolower(Yii::$app->language) == 'es' ? 'es_AR' : strtolower(Yii::$app->language)] : $mooring_type['value']['en'];
            $mooring_types[$mooring_type['key']] = $value;
        }
        return $mooring_types;
    }

    public static function types()
    {
        $file = Functions::directory() . 'types.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $url = Yii::$app->params['apiUrl'] . 'properties/types&user_apikey=' . Yii::$app->params['api_key'];
            $file_data = Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function CommercialType()
    {
        $query = [];
        $options = [
            "page" => 1,
            "limit" => 200,
        ];

        $post_data = ["query" => (object) $query, "options" => $options];
        $curl = new curl\Curl();
        $response = $curl->setRequestBody(json_encode($post_data))
            ->setHeaders([
                'Content-Type' => 'application/json',
                'Content-Length' => strlen(json_encode($post_data))
            ])
            ->post(Yii::$app->params['node_url'] . 'commercial_types?user_apikey=' . Yii::$app->params['api_key']);

        return json_decode($response, TRUE);
    }

    public static function typesByLanguage()
    {
        $types = [];

        $file = Functions::directory() . 'types.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $url = Yii::$app->params['apiUrl'] . 'properties/types&user_apikey=' . Yii::$app->params['api_key'];
            $file_data = Functions::getCRMData($url);
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
            $file_data = Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function offices()
    {
        $file = Functions::directory() . 'offices.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $url = Yii::$app->params['apiUrl'] . 'properties/get-offices&user_apikey=' . Yii::$app->params['api_key'] . '&agency_id=' . Yii::$app->params['agency'];
            $file_data = Functions::getCRMData($url);
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

    public static function settings()
    {
        return [
            ['key' => "beachfront", 'value' => \Yii::t('app', 'beachfront')],
            ['key' => "beachside", 'value' => \Yii::t('app', 'beachside')],
            ['key' => "close_to_airport", 'value' => \Yii::t('app', 'close_to_airport')],
            ['key' => "close_to_busstop", 'value' => \Yii::t('app', 'close_to_busstop')],
            ['key' => "close_to_church", 'value' => \Yii::t('app', 'close_to_church')],
            ['key' => "close_to_forest", 'value' => \Yii::t('app', 'close_to_forest')],
            ['key' => "close_to_golf", 'value' => \Yii::t('app', 'close_to_golf')],
            ['key' => "close_to_hotel", 'value' => \Yii::t('app', 'close_to_hotel')],
            ['key' => "close_to_marina", 'value' => \Yii::t('app', 'close_to_marina')],
            ['key' => "close_to_mosque", 'value' => \Yii::t('app', 'close_to_mosque')],
            ['key' => "close_to_port", 'value' => \Yii::t('app', 'close_to_port')],
            ['key' => "close_to_restaurant", 'value' => \Yii::t('app', 'close_to_restaurant')],
            ['key' => "close_to_schools", 'value' => \Yii::t('app', 'close_to_schools')],
            ['key' => "close_to_sea", 'value' => \Yii::t('app', 'close_to_sea')],
            ['key' => "close_to_shops", 'value' => \Yii::t('app', 'close_to_shops')],
            ['key' => "close_to_skiing", 'value' => \Yii::t('app', 'close_to_skiing')],
            ['key' => "close_to_supermarkets", 'value' => \Yii::t('app', 'close_to_supermarkets')],
            ['key' => "close_to_taxistand", 'value' => \Yii::t('app', 'close_to_taxistand')],
            ['key' => "close_to_town", 'value' => \Yii::t('app', 'close_to_town')],
            ['key' => "close_to_train", 'value' => \Yii::t('app', 'close_to_train')],
            ['key' => "commercial_area", 'value' => \Yii::t('app', 'commercial_area')],
            ['key' => "countryside", 'value' => \Yii::t('app', 'countryside')],
            ['key' => "easy_access", 'value' => \Yii::t('app', 'easy_access')],
            ['key' => "frontline_golf", 'value' => \Yii::t('app', 'frontline_golf')],
            ['key' => "marina", 'value' => \Yii::t('app', 'marina')],
            ['key' => "mountain_pueblo", 'value' => \Yii::t('app', 'mountain_pueblo')],
            ['key' => "no_nearby_neighbours", 'value' => \Yii::t('app', 'no_nearby_neighbours')],
            ['key' => "not_isolated", 'value' => \Yii::t('app', 'not_isolated')],
            ['key' => "port", 'value' => \Yii::t('app', 'port')],
            ['key' => "private", 'value' => \Yii::t('app', 'private')],
            ['key' => "suburban", 'value' => \Yii::t('app', 'suburban')],
            ['key' => "town", 'value' => \Yii::t('app', 'town')],
            ['key' => "tranquil", 'value' => \Yii::t('app', 'tranquil')],
            ['key' => "urbanisation", 'value' => \Yii::t('app', 'urbanisation')],
            ['key' => "village", 'value' => \Yii::t('app', 'village')],
        ];
    }

    public static function orientations()
    {
        return [
            ['key' => "north", 'value' => \Yii::t('app', 'north')],
            ['key' => "north_east", 'value' => \Yii::t('app', 'north_east')],
            ['key' => "east", 'value' => \Yii::t('app', 'east')],
            ['key' => "south_east", 'value' => \Yii::t('app', 'south_east')],
            ['key' => "south", 'value' => \Yii::t('app', 'south')],
            ['key' => "south_west", 'value' => \Yii::t('app', 'south_west')],
            ['key' => "west", 'value' => \Yii::t('app', 'west')],
            ['key' => "north_west", 'value' => \Yii::t('app', 'north_west')],
        ];
    }

    public static function views()
    {
        return [
            ['key' => "beach", 'value' => \Yii::t('app', 'beach')],
            ['key' => "countryside", 'value' => \Yii::t('app', 'countryside')],
            ['key' => "forest", 'value' => \Yii::t('app', 'forest')],
            ['key' => "garden", 'value' => \Yii::t('app', 'garden')],
            ['key' => "golf", 'value' => \Yii::t('app', 'golf')],
            ['key' => "lake", 'value' => \Yii::t('app', 'lake')],
            ['key' => "mountain", 'value' => \Yii::t('app', 'mountain')],
            ['key' => "panoramic", 'value' => \Yii::t('app', 'panoramic')],
            ['key' => "partial_seaviews", 'value' => \Yii::t('app', 'partial_seaviews')],
            ['key' => "pool", 'value' => \Yii::t('app', 'pool')],
            ['key' => "port", 'value' => \Yii::t('app', 'port')],
            ['key' => "sea", 'value' => \Yii::t('app', 'sea')],
            ['key' => "ski", 'value' => \Yii::t('app', 'ski')],
            ['key' => "street", 'value' => \Yii::t('app', 'street')],
            ['key' => "urban", 'value' => \Yii::t('app', 'urban')],
        ];
    }

    public static function conditions()
    {
        return [
            ['key' => "excellent", 'value' => \Yii::t('app', 'excellent')],
            ['key' => "fair", 'value' => \Yii::t('app', 'fair')],
            ['key' => "minor_updates_required", 'value' => \Yii::t('app', 'minor_updates_required')],
            ['key' => "good", 'value' => \Yii::t('app', 'good')],
            ['key' => "never_lived", 'value' => \Yii::t('app', 'never_lived')],
            ['key' => "renovation_required", 'value' => \Yii::t('app', 'renovation_required')],
            ['key' => "recently_renovated", 'value' => \Yii::t('app', 'recently_renovated')],
            ['key' => "recently_refurbished", 'value' => \Yii::t('app', 'recently_refurbished')],
            ['key' => "finishing_habitable_required", 'value' => \Yii::t('app', 'finishing_habitable_required')],
            ['key' => "basically_habitable", 'value' => \Yii::t('app', 'basically_habitable')],
        ];
    }

    public static function parkings()
    {
        return [
            ['key' => "communal_garage", 'value' => \Yii::t('app', 'communal_garage')],
            ['key' => "parking_communal", 'value' => \Yii::t('app', 'parking_communal')],
            ['key' => "covered", 'value' => \Yii::t('app', 'covered')],
            ['key' => "private", 'value' => \Yii::t('app', 'private')],
            ['key' => "more_than_one", 'value' => \Yii::t('app', 'more_than_one')],
        ];
        $propertyParkings = [
            'garage' => Yii::t('app', strtolower('garage')),
            'open' => Yii::t('app', strtolower('open')),
            'parking_optional' => Yii::t('app', strtolower('parking_optional')),
            'private' => Yii::t('app', strtolower('private')),
            'public_parking_nearby_against_a_fee' => Yii::t('app', strtolower('public_parking_nearby_against_a_fee')),
            'parking_street' => Yii::t('app', strtolower('parking_street')),
            'underground' => Yii::t('app', strtolower('underground'))
        ];
    }

    public static function pools()
    {
        return [
            ['key' => "pool_communal", 'value' => \Yii::t('app', 'pool_communal')],
            ['key' => "pool_indoor", 'value' => \Yii::t('app', 'pool_indoor')],
            ['key' => "pool_private", 'value' => \Yii::t('app', 'pool_private')],
        ];
        $propertyPools = [
            'childrens_pool' => Yii::t('app', strtolower('childrens_pool')),
            'covfenced_poolered' => Yii::t('app', strtolower('fenced_pool')),
            'freshwater' => Yii::t('app', strtolower('freshwater')),
            'pool_heated' => Yii::t('app', strtolower('pool_heated')),
            'ladder_access' => Yii::t('app', strtolower('ladder_access')),
            'outside_shower' => Yii::t('app', strtolower('outside_shower')),
            'outside_toilets' => Yii::t('app', strtolower('outside_toilets')),
            'roman_steps_into_pool' => Yii::t('app', strtolower('roman_steps_into_pool')),
            'soler_heated_pool' => Yii::t('app', strtolower('soler_heated_pool')),
            'room_for_pool' => Yii::t('app', strtolower('room_for_pool')),
            'sun_beds' => Yii::t('app', strtolower('sun_beds')),
            'whirlpool' => Yii::t('app', strtolower('whirlpool'))
        ];
    }

    public static function gardens()
    {
        return [
            ['key' => "almond_grove", 'value' => \Yii::t('app', 'almond_grove')],
            ['key' => "garden_communal", 'value' => \Yii::t('app', 'garden_communal')],
            ['key' => "easy_maintenance", 'value' => \Yii::t('app', 'easy_maintenance')],
            ['key' => "fenced", 'value' => \Yii::t('app', 'fenced_garden')],
            ['key' => "fruit_trees_citrus", 'value' => \Yii::t('app', 'fruit_trees_citrus')],
            ['key' => "fruit_trees_tropical", 'value' => \Yii::t('app', 'fruit_trees_tropical')],
            ['key' => "irrigation_rights", 'value' => \Yii::t('app', 'irrigation_rights')],
            ['key' => "landscaped", 'value' => \Yii::t('app', 'landscaped')],
            ['key' => "Lawn", 'value' => \Yii::t('app', 'Lawn')],
            ['key' => "olive_grove", 'value' => \Yii::t('app', 'olive_grove')],
            ['key' => "outdoor_dining", 'value' => \Yii::t('app', 'outdoor_dining')],
            ['key' => "playground", 'value' => \Yii::t('app', 'playground')],
            ['key' => "plenty_of_water", 'value' => \Yii::t('app', 'plenty_of_water')],
            ['key' => "pool_house", 'value' => \Yii::t('app', 'pool_house')],
            ['key' => "garden_private", 'value' => \Yii::t('app', 'garden_private')],
            ['key' => "shade_control", 'value' => \Yii::t('app', 'shade_control')],
            ['key' => "tropical_garden", 'value' => \Yii::t('app', 'tropical_garden')],
            ['key' => "vegetable", 'value' => \Yii::t('app', 'vegetable')],
            ['key' => "veranda", 'value' => \Yii::t('app', 'veranda')],
            ['key' => "vineyard", 'value' => \Yii::t('app', 'vineyard')],
        ];
    }

    public static function furnitures()
    {
        return [
            ['key' => "fully_furnished", 'value' => \Yii::t('app', 'fully_furnished')],
            ['key' => "part_furnished", 'value' => \Yii::t('app', 'part_furnished')],
            ['key' => "not_furnished", 'value' => \Yii::t('app', 'not_furnished')],
        ];
        $propertyFurnitures = [
            'optional' => Yii::t('app', strtolower('optional')),
        ];
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
        $loc = array_unique($loc, SORT_REGULAR);
        usort($loc, "self::sortedLocation");
        return self::html_select($loc, $options);
    }

    public static function sortedLocation($a, $b)
    {
        return strcmp($a["option_value"], $b["option_value"]);
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
            if (isset($value[$option_value_index])) {
                $finalFormatedSelectArray[$key]['option_value'] = (is_array($value[$option_value_index]) ? $value[$option_value_index]['en'] : $value[$option_value_index]);
            } else {
                $finalFormatedSelectArray[$key]['option_value'] = isset($value['en']) ? $value['en'] : '';
            }
        }
        return $finalFormatedSelectArray;
    }

    /**
     *
     * Get dropdown
     *
     * @param    array data array e.g for options return html 
     * @param    array options array e.g array('name' => 'ContactUs[provinces][]', 'class' => "multiselect", 'multiple' => 'multiple', 'onchange' => 'loadCities()', 'id' => 'provinces', 'placeholder' => 'Provinces', 'noValueTranslation' => true )
     * @return   html
     * @use      Dropdowns::dropdown($dataArray='Data to be formated', $options = ['name' => 'ContactUs[provinces][]'])
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
}
