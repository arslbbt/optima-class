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
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/provinces&user=' . Yii::$app->params['user']);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
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
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/locations&user=' . Yii::$app->params['user'] . '&count=true' . $p_q . $c_q);
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
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/all-cities&user=' . Yii::$app->params['user'] . $p_q);
            file_put_contents($file, $file_data);
        } elseif (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/all-cities&user=' . Yii::$app->params['user']);
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
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/types&user=' . Yii::$app->params['user']);
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
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/types&user=' . Yii::$app->params['user']);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }

        foreach (json_decode($file_data) as $file) {
            $sub_types=[];
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

    public static function locationGroups() {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/')) {
            mkdir($webroot . '/uploads/');
        }
        if (!is_dir($webroot . '/uploads/temp/')) {
            mkdir($webroot . '/uploads/temp/');
        }
        $file = $webroot . '/uploads/temp/locationGroups.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/location-groups-key-value&user=' . Yii::$app->params['user']);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function orientations() {
        return [['key' => "north", 'value' => \Yii::t('app', 'north')], ['key' => "north_east", 'value' => \Yii::t('app', 'north_east')], ['key' => "east", 'value' => \Yii::t('app', 'east')], ['key' => "south_east", 'value' => \Yii::t('app', 'south_east')], ['key' => "south", 'value' => \Yii::t('app', 'south')], ['key' => "south_west", 'value' => \Yii::t('app', 'south_west')], ['key' => "west", 'value' => \Yii::t('app', 'west')], ['key' => "north_west", 'value' => \Yii::t('app', 'north_west')],];
    }

}
