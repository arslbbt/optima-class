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
class Dropdowns extends Model
{

    public static function provinces()
    {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
        {
            mkdir($webroot . '/uploads/');
        }
        if (!is_dir($webroot . '/uploads/temp/'))
        {
            mkdir($webroot . '/uploads/temp/');
        }
        $file = $webroot . '/uploads/temp/provinces.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/provinces&user=' . Yii::$app->params['user']);
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function locations($provinces = [], $to_json = false)
    {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
        {
            mkdir($webroot . '/uploads/');
        }
        if (!is_dir($webroot . '/uploads/temp/'))
        {
            mkdir($webroot . '/uploads/temp/');
        }
        $file = $webroot . '/uploads/temp/locations.json';
        if (is_array($provinces) && count($provinces))
        {
            $p_q = '';
            foreach ($provinces as $province)
            {
                $p_q .= '&province[]=' . $province;
            }
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/locations&user=' . Yii::$app->params['user'] . $p_q);
        }
        elseif (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/locations&user=' . Yii::$app->params['user']);
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        return $to_json ? json_encode(json_decode($file_data, TRUE)) : json_decode($file_data, TRUE);
    }

    public static function types()
    {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
        {
            mkdir($webroot . '/uploads/');
        }
        if (!is_dir($webroot . '/uploads/temp/'))
        {
            mkdir($webroot . '/uploads/temp/');
        }
        $file = $webroot . '/uploads/temp/types.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'properties/types&user=' . Yii::$app->params['user']);
            file_put_contents($file, $file_data);
        }
        else
        {
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
        foreach ($range as $value)
        {
            if ($value <= 2000 && $value % 200 == 0)
            {
                $data[] = [
                    'key' => $value,
                    'value' => str_replace(',', '.', (number_format((int) $value))) . ' €'
                ];
            }
            if ($value > 25000 && $value % 25000 == 0)
            {
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

}
