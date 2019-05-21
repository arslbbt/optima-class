<?php

namespace optima\models;

use Yii;
use yii\base\Model;
use linslin\yii2\curl;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class Dropdowns extends Model {

    public static function provinces() {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/')) {
            mkdir($webroot . '/uploads/');
        }
        if (!is_dir($webroot . '/uploads/temp/')) {
            mkdir($webroot . '/uploads/temp/');
        }
        $file = $webroot . '/uploads/temp/provinces.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/provinces&user_apikey=' . Yii::$app->params['api_key']);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function countries() {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/')) {
            mkdir($webroot . '/uploads/');
        }
        if (!is_dir($webroot . '/uploads/temp/')) {
            mkdir($webroot . '/uploads/temp/');
        }
        $file = $webroot . '/uploads/temp/countries.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/countries&user_apikey=' . Yii::$app->params['api_key']);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function urbanisations() {
        $return_data = [];
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/')) {
            mkdir($webroot . '/uploads/');
        }
        if (!is_dir($webroot . '/uploads/temp/')) {
            mkdir($webroot . '/uploads/temp/');
        }
        $file = $webroot . '/uploads/temp/urbanisations.json';
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
            if(isset($data['docs']) && count($data['docs']) > 0)
            {
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

    public static function locations($provinces = [], $to_json = false, $cities = []) {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/')) {
            mkdir($webroot . '/uploads/');
        }
        if (!is_dir($webroot . '/uploads/temp/')) {
            mkdir($webroot . '/uploads/temp/');
        }
        $file = $webroot . '/uploads/temp/locations_' . implode(',', $provinces) . implode(',', $cities) . '.json';


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
                        $url=Yii::$app->params['apiUrl'] . 'properties/locations&count=true' . $p_q . $c_q.'&user_apikey=' . Yii::$app->params['api_key'].'&lang='.((isset(\Yii::$app->language)&& strtolower(\Yii::$app->language)!='en')?'es_AR':'en');
            $file_data = file_get_contents($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        return $to_json ? json_encode(json_decode($file_data, TRUE)) : json_decode($file_data, TRUE);
    }

    public static function cities($provinces = [], $to_json = false) {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/')) {
            mkdir($webroot . '/uploads/');
        }
        if (!is_dir($webroot . '/uploads/temp/')) {
            mkdir($webroot . '/uploads/temp/');
        }
        $file = $webroot . '/uploads/temp/cities_' . implode(',', $provinces) . '.json';

        if (is_array($provinces) && count($provinces) && !file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $p_q = '';
            foreach ($provinces as $province) {
                $p_q .= '&province[]=' . $province;
            }
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/all-cities'.$p_q.'&user_apikey=' . Yii::$app->params['api_key']);
            $file_data = json_decode($file_data);
            usort($file_data, function ($item1, $item2) {
            return $item1->value <=> $item2->value;
        });
        $file_data = json_encode($file_data);
            file_put_contents($file, $file_data);
        } elseif (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/all-cities&user_apikey=' . Yii::$app->params['api_key']);
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

    public static function types() {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/')) {
            mkdir($webroot . '/uploads/');
        }
        if (!is_dir($webroot . '/uploads/temp/')) {
            mkdir($webroot . '/uploads/temp/');
        }
        $file = $webroot . '/uploads/temp/types.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/types&user_apikey=' . Yii::$app->params['api_key']);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }
    


    public static function typesByLanguage() {
        $types = [];
//        $types['subtypes'] = [];
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/')) {
            mkdir($webroot . '/uploads/');
        }
        if (!is_dir($webroot . '/uploads/temp/')) {
            mkdir($webroot . '/uploads/temp/');
        }
        $file = $webroot . '/uploads/temp/types.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/types&user_apikey=' . Yii::$app->params['api_key']);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
       $fdata=json_decode($file_data);
    //    echo"<pre>";
    //    print_r($fdata);
    //    die;
        
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

    public static function numbers($limit) {
        return range(1, $limit);
    }

    public static function prices($from, $to, $to_json = false) {
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

    public static function locationGroups($provinces = []) {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/')) {
            mkdir($webroot . '/uploads/');
        }
        if (!is_dir($webroot . '/uploads/temp/')) {
            mkdir($webroot . '/uploads/temp/');
        }
        $file = $webroot . '/uploads/temp/locationGroups_' . implode(',', $provinces) . '.json';
      
        if (is_array($provinces) && count($provinces) && !file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $p_q = '';
            foreach ($provinces as $province) {
                $p_q .= '&province[]=' . $province;
            }
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/location-groups-key-value'.$p_q.'&user_apikey=' . Yii::$app->params['api_key']);
           
            file_put_contents($file, $file_data);
        } elseif (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/location-groups-key-value&user_apikey=' . Yii::$app->params['api_key']);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        // echo $file_data;
        //    die;
        return json_decode($file_data, true);
    }

    public static function orientations() {
        return [['key' => "north", 'value' => \Yii::t('app', 'north')], ['key' => "north_east", 'value' => \Yii::t('app', 'north_east')], ['key' => "east", 'value' => \Yii::t('app', 'east')], ['key' => "south_east", 'value' => \Yii::t('app', 'south_east')], ['key' => "south", 'value' => \Yii::t('app', 'south')], ['key' => "south_west", 'value' => \Yii::t('app', 'south_west')], ['key' => "west", 'value' => \Yii::t('app', 'west')], ['key' => "north_west", 'value' => \Yii::t('app', 'north_west')],];
    }
    public static function buildingStyles() {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/')) {
            mkdir($webroot . '/uploads/');
        }
        if (!is_dir($webroot . '/uploads/temp/')) {
            mkdir($webroot . '/uploads/temp/');
        }
        $file = $webroot . '/uploads/temp/building-style.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/building-style&user_apikey=' . Yii::$app->params['api_key']);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }

    
    public static function file_get_contents_curl($url) {
        $ch = curl_init();
    
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);       
    
        $data = curl_exec($ch);
        curl_close($ch);
    
        return $data;
    }

}
