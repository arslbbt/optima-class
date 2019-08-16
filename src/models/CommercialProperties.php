<?php

namespace optima\models;

use Yii;
use yii\base\Model;
use optima\models\Cms;
use linslin\yii2\curl;
use phpDocumentor\Reflection\Location;
use function PHPSTORM_META\type;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class CommercialProperties extends Model
{

    public static function findAll($page = 1, $page_size = 10, $query = '', $sort = ['current_price' => '-1'])
    {
        $query_array=[];
        $options = ["page" => $page, "limit" => $page_size];
        $options['populate'] = [
            [
                'path' => 'property_attachments',
                'match' => ['document' => ['$ne' => true], 'publish_status' => ['$ne' => false]],
            ]
        ];
        //$query_array['favourite_ids'] = [0=>'5cd560fed314c86ba2258d85',1=>'5d4c321bc0fb225c974c89f2',2=>'5c5597bbd9973d0526203e0f'];
        //$query_array['favourite_ids'] = explode(',', "5cd560fed314c86ba2258d85,5d4c321bc0fb225c974c89f2,5c5597bbd9973d0526203e0f,5d4be8cb0de7717a1beb5ec5");
        // $query_array['price'] = '1221';

        if (Yii::$app->request->get('orderby') && is_array(Yii::$app->request->get('orderby')) && count(Yii::$app->request->get('orderby') == 2)) {
            $sort = [Yii::$app->request->get('orderby')[0] => Yii::$app->request->get('orderby')[1]];
        }
        $options['sort'] = $sort;

        
        if(isset($query) && $query != '' && !is_array($query)){
            $vars = explode('&', $query);
            foreach($vars as $var){
                $k = explode('=', $var);
                if(isset($k[0]) && isset($k[1])){
                    if($k[0] == 'favourite_ids'){
                        $query_array['favourite_ids'] = explode(',', $k[1]);
                    }else{
                        $post_data[$k[0]] = $k[1];
                    }
                }
            }
        }
        if(isset($query) && $query != '' && is_array($query)){
            //$post_data = ["options" => $options];	        
            // echo "<pre>";	        if(isset($query) && $query != ''){
            // print_r($options['populate']);	            $vars = explode('&', $query);
            // die;	            
            //foreach($vars as $var){
            if (!count($query)) {	                
                // $k = explode('=', $var);
                $query = self::setQuery();
            }
            if (count($query)){
                $query_array = $query;
            }
        }




        
        $post_data = ["options" => $options];
        if(!empty($query_array))
            $post_data["query"] =  $query_array;

        $node_url = Yii::$app->params['node_url'] . 'commercial_properties?user=' . Yii::$app->params['user'];
        
        $curl = new curl\Curl();
        $response = $curl->setRequestBody(json_encode($post_data))
            ->setHeaders([
                'Content-Type' => 'application/json',
                'Content-Length' => strlen(json_encode($post_data))
            ])
            ->post($node_url);
        $response = json_decode($response, TRUE);


        $properties = [];

        if(isset($response) && isset($response['docs']))
        foreach ($response['docs'] as $property) {
            $properties[] = self::formateProperty($property);
        }
        $response['docs'] = $properties;
        // echo '<pre>';
        // print_r($response);
        // print_r($post_data);
        return $response;
    }

    public static function findOne($id)
    {
        $options = [];
        $options['populate'] = [
            [
                'path' => 'property_attachments',
                'match' => ['document' => ['$ne' => true], 'publish_status' => ['$ne' => false]]
            ]
        ];
        $post_data = ['options' => $options];
        $curl = new curl\Curl();
        $response = $curl->setRequestBody(json_encode($post_data))
            ->setHeaders([
                'Content-Type' => 'application/json',
                'Content-Length' => strlen(json_encode($post_data))
            ])
            ->post(Yii::$app->params['node_url'] . 'commercial_properties/view/' . $id . '?user=' . Yii::$app->params['user']);
        // echo "<pre>";
        // print_r(Yii::$app->params['node_url'] . 'commercial_properties/view/' . $id . '?user=' . Yii::$app->params['user']);
        // die();

        $response = json_decode($response, TRUE);

        $property = self::formateProperty($response);
        // echo "<pre>";
        // print_r($property);
        // die();
        return $property;
    }

    public static function setQuery()
    {
        $get = Yii::$app->request->get();
        $query = [];
        if (isset($get['price_from']) && $get['price_from']) {
            $query['current_price'] = ['$gte' => (int) $get['price_from']];
        }
        if (isset($get['reference']) && $get['reference']) {
            $query['$or'] = [
                ["reference" => (int) $get['reference']],
                ["other_reference" => ['$regex' => ".*" . $get['reference'] . ".*", '$options' => "i"]],
                ["external_reference" => ['$regex' => ".*" . $get['reference'] . ".*", '$options' => "i"]]
            ];
        }

        if (isset($get['type']) && $get['type'] && is_array($get['type']) && count($get['type']) > 0 && $get['type'][0] != 0 && $get['type'][0] != '' && $get['type'][0] != '0') {
            $query['type_one'] = ['$in' => $get['type']];
        }

        if (isset($get['country']) && $get['country']) {
            $query['country'] = (int) $get['country'];
        }
        if (isset($get['city']) && $get['city']) {
            $query['city'] = (int) $get['city'];
        }
        if (isset($get['location']) && $get['location']) {
            $query['location'] = (int) $get['location'];
        }
        if (isset($get['province']) && $get['province']) {
            $query['province'] = (int) $get['province'];
        }
        if (isset($get['region']) && $get['region']) {
            $query['region'] = (int) $get['region'];
        }





        // if(isset($get['bedrooms']) && $get['bedrooms'] )
        // {
        //     $query['bedrooms']= ['$gte'=> 2, $lte: 3}
        // }
        if (isset($get['location']) && $get['location']) {

            $query['location'] = (int) $get['location'];
        }
        if (isset($get['featured']) && $get['featured']) {
            $query['featured'] = true;
        }



        return $query;
    }

    public static function formateProperty($property)
    {
        Yii::$app->language = 'en';
        $settings = Cms::settings();
        $lang = strtoupper(\Yii::$app->language);
        $f_property = [];
        if (isset($settings['general_settings']['reference']) && $settings['general_settings']['reference'] != 'reference') {
            $ref = $settings['general_settings']['reference'];
            $f_property['reference'] = $property[$ref];
        } else {

            $f_property['reference'] = $property['reference'];
        }
        if (isset($property['_id'])) {
            $f_property['_id'] = $property['_id'];
        }
        if (isset($property['reference'])) {
            $f_property['id'] = $property['reference'];
        }
        if (isset($property['title'][$lang]) && $property['title'][$lang] != '') {
            $f_property['title'] = $property['title'][$lang];
        } else {
            $f_property['title'] = (isset($property['type_one']) ? \Yii::t('app', $property['type_one']) : '') . ' ' . \Yii::t('app', 'in') . ' ' . (isset($property['property_location']['value']['en']) ? \Yii::t('app', $property['property_location']['value']['en']) : '');
        }
        if (isset($property['status'])) {
            $f_property['status'] = \Yii::t('app', $property['status']);
        }
        if (isset($property['description'][$lang])) {
            $f_property['description'] = $property['description'][$lang];
        }
        if (isset($property['type_one'])) {
            $f_property['type'] = \Yii::t('app', $property['type_one']);
        }
        if (isset($property['latitude_alt']) && isset($property['longitude_alt']) && $property['latitude_alt'] != '' && $property['longitude_alt'] != '') {
            $f_property['lat'] = $property['latitude_alt'];
            $f_property['lng'] = $property['longitude_alt'];
        } elseif (isset($property['latitude']) && isset($property['longitude']) && $property['latitude'] != '' && $property['longitude'] != '') {
            $f_property['lat'] = $property['latitude'];
            $f_property['lng'] = $property['longitude'];
        } elseif (isset($property['address']['lat']) && isset($property['address']['lng']) && $property['address']['lat'] != '' && $property['address']['lng'] != '') {
            $f_property['lat'] = $property['address']['lat'];
            $f_property['lng'] = $property['address']['lng'];
        }
        if (isset($property['sale']) && $property['sale']) {
            $f_property['sale'] = TRUE;
        }
        if (isset($property['rent']) && $property['rent']) {
            $f_property['rent'] = TRUE;
        }
        if (isset($property['bedrooms']) && $property['bedrooms'] > 0) {
            $f_property['bedrooms'] = $property['bedrooms'];
        }
        if (isset($property['bathrooms']) && $property['bathrooms'] > 0) {
            $f_property['bathrooms'] = $property['bathrooms'];
        }
        if (isset($property['city'])) {
            $f_property['city_key'] = $property['city'];
        }
        if (isset($property['location'])) {
            $f_property['location_key'] = $property['location'];
        }
        if (isset($property['type_one_key'])) {
            $f_property['type_key'] = $property['type_one_key'];
        }
        if (isset($property['current_price'])) {
            $f_property['price'] = $property['current_price'];
        }
        if (isset($property['property_attachments']) && count($property['property_attachments']) > 0) {
            $attachments = [];
            foreach ($property['property_attachments'] as $pic) {
                $attachments[] = Yii::$app->params['com_img'] . '/' . $pic['model_id'] . '/' .  urldecode($pic['file_md5_name']);
            }
            $f_property['attachments'] = $attachments;
        }
        $categories = [];
        $setting = [];
        $orientation = [];
        $views = [];
        $condition = [];
        if (isset($property['categories']) && count($property['categories']) > 0) {
            foreach ($property['categories'] as $key => $value) {
                if ($value == true) {
                    $categories[] = $key;
                }
            }
        }
        if (isset($property['settings']) && count($property['settings']) > 0) {
            foreach ($property['settings'] as $key => $value) {
                if ($value == true) {
                    $setting[] = $key;
                }
            }
        }
        if (isset($property['orientations']) && count($property['orientations']) > 0) {
            foreach ($property['orientations'] as $key => $value) {
                if ($value == true) {
                    $orientation[] = $key;
                }
            }
        }
        if (isset($property['views']) && count($property['views']) > 0) {
            foreach ($property['views'] as $key => $value) {
                if ($value == true) {
                    $views[] = $key;
                }
            }
        }
        if (isset($property['conditions']) && count($property['conditions']) > 0) {
            foreach ($property['conditions'] as $key => $value) {
                if ($value == true) {
                    $condition[] = $key;
                }
            }
        }
        $f_property['property_features'] = [];
        $f_property['property_features']['categories'] = $categories;
        $f_property['property_features']['setting'] = $setting;
        $f_property['property_features']['orientation'] = $orientation;
        $f_property['property_features']['views'] = $views;
        $f_property['property_features']['condition'] = $condition;

        return $f_property;
    }
}
