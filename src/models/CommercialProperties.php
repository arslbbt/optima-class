<?php

namespace optima\models;

use Yii;
use yii\base\Model;
use optima\models\Cms;
use linslin\yii2\curl;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class CommercialProperties extends Model
{

    public static function findAll($page = 1, $page_size = 10)
    {
        $options = ["page" => $page, "limit" => $page_size];
        if (Yii::$app->request->get('orderby') && is_array(Yii::$app->request->get('orderby')) && count(Yii::$app->request->get('orderby') == 2))
            $sort = [Yii::$app->request->get('orderby')[0] => Yii::$app->request->get('orderby')[1]];
        else
            $sort = ['current_price' => '-1'];
        $options['sort'] = $sort;
        $post_data = ["options" => $options];
        $query = self::setQuery();
        if (count($query))
            $post_data['query'] = $query;

        $curl = new curl\Curl();
        $response = $curl->setRequestBody(json_encode($post_data))
                ->setHeaders([
                    'Content-Type' => 'application/json',
                    'Content-Length' => strlen(json_encode($post_data))
                ])
                ->post(Yii::$app->params['node_url'] . 'commercial_properties?user=' . Yii::$app->params['user']);
        $response = json_decode($response, TRUE);
        $properties = [];
        foreach ($response['docs'] as $property)
        {
            $properties[] = self::formateProperty($property);
        }
        $response['docs'] = $properties;
        return $response;
    }

    public static function findOne()
    {
        
    }

    public static function setQuery()
    {
        $query = [];
        return $query;
    }

    public static function formateProperty($property)
    {
        Yii::$app->language = 'en';
        $settings = Cms::settings();
        $lang = strtoupper(\Yii::$app->language);
        $f_property = [];
        if (isset($settings['general_settings']['reference']) && $settings['general_settings']['reference'] != 'reference')
        {
            $ref = $settings['general_settings']['reference'];
            $f_property['reference'] = $property[$ref];
        }
        else
        {
            $f_property['reference'] = $property['reference'];
        }
        if (isset($property['title'][$lang]) && $property['title'][$lang] != '')
        {
            $f_property['title'] = $property['title'][$lang];
        }
        else
        {
            $f_property['title'] = (isset($property['type_one']) ? \Yii::t('app', $property['type_one']) : '') . ' ' . \Yii::t('app', 'in') . ' ' . (isset($property['property_location']['value']['en']) ? \Yii::t('app', $property['property_location']['value']['en']) : '');
        }
        if (isset($property['status']))
        {
            $f_property['status'] = \Yii::t('app', $property['status']);
        }
        if (isset($property['description'][$lang]))
        {
            $f_property['description'] = $property['description'][$lang];
        }
        if (isset($property['type_one']))
        {
            $f_property['type'] = \Yii::t('app', $property['type_one']);
        }
        if (isset($property['latitude_alt']) && isset($property['longitude_alt']) && $property['latitude_alt'] != '' && $property['longitude_alt'] != '')
        {
            $f_property['lat'] = $property['latitude_alt'];
            $f_property['lng'] = $property['longitude_alt'];
        }
        elseif (isset($property['latitude']) && isset($property['longitude']) && $property['latitude'] != '' && $property['longitude'] != '')
        {
            $f_property['lat'] = $property['latitude'];
            $f_property['lng'] = $property['longitude'];
        }
        elseif (isset($property['address']['lat']) && isset($property['address']['lng']) && $property['address']['lat'] != '' && $property['address']['lng'] != '')
        {
            $f_property['lat'] = $property['address']['lat'];
            $f_property['lng'] = $property['address']['lng'];
        }
        if (isset($property['sale']) && $property['sale'])
        {
            $f_property['sale'] = TRUE;
        }
        if (isset($property['rent']) && $property['rent'])
        {
            $f_property['rent'] = TRUE;
        }

        return $f_property;
    }

}
