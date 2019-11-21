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
                        $query_array['archived']['$ne'] = true;
                    }else{
                        $post_data[$k[0]] = $k[1];
                        $post_data['archived']['$ne'] = true;
                    }
                }
            }
        }
        if(isset($query) && $query != '' && is_array($query)){
            if (!count($query)) {
                $query = self::setQuery();
                $query['archived']['$ne'] = true;
            }
            if (count($query)){
                $query_array = $query;
                $query_array['archived']['$ne'] = true;
            }
        }




        
        $post_data = ["options" => $options];
        //[ '$gt' =>  100000, '$lt' => 6]
        if(!empty($query_array))
            $post_data["query"] =  $query_array;
        
        // echo '<pre>--------';
        // print_r($post_data);

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
        // echo '<pre>---';
        // print_r($response['kitchen']);
        // die;

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
        if (isset($get['price_from'])) {
            $query['current_price']['$gt'] = (int) $get['price_from'];
            $query['current_price']['$lt'] = (int) 100000000000000000;
        }

        $query['archived']['$ne'] = true;




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
        //Yii::$app->language = 'en';
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
               if(isset($pic['document']) && $pic['document'] != 1){
                $attachments[] = Yii::$app->params['com_img'] . '/' . $pic['model_id'] . '/' .  urldecode($pic['file_md5_name']);
               }
            }
            $f_property['attachments'] = $attachments;
        }
        if (isset($property['buildings']) && $property['buildings'] !='') {
            $f_property['buildings'] = $property['buildings'];
        }
        if (isset($property['sleeps']) && $property['sleeps'] !='') {
            $f_property['sleeps'] = $property['sleeps'];
        }
        if (isset($property['bedrooms']) && $property['bedrooms'] !='') {
            $f_property['bedrooms'] = $property['bedrooms'];
        }
        if (isset($property['bathrooms']) && $property['bathrooms'] !='') {
            $f_property['bathrooms'] = $property['bathrooms'];
        }
        if (isset($property['toilets']) && $property['toilets'] !='') {
            $f_property['toilets'] = $property['toilets'];
        }
        if (isset($property['living_rooms']) && $property['living_rooms'] !='') {
            $f_property['living_rooms'] = $property['living_rooms'];
        }
        if (isset($property['energy_certificate_one']) && $property['energy_certificate_one'] !='') {
            $f_property['energy_certificate_one'] = $property['energy_certificate_one'];
        }
        if (isset($property['energy_certificate_two']) && $property['energy_certificate_two'] !='') {
            $f_property['energy_certificate_two'] = $property['energy_certificate_two'];
        }
        if (isset($property['kilowatt']) && $property['kilowatt'] !='') {
            $f_property['kilowatt'] = $property['kilowatt'];
        }
        if (isset($property['miscellaneous_tax']) && $property['miscellaneous_tax'] !='') {
            $f_property['miscellaneous_tax'] = $property['miscellaneous_tax'];
        }
        if (isset($property['rubbish']) && $property['rubbish'] !='') {
            $f_property['rubbish'] = $property['rubbish'];
        }
        if (isset($property['parking_license']) && $property['parking_license'] !='') {
            $f_property['parking_license'] = $property['parking_license'];
        }
        if (isset($property['community_fees']) && $property['community_fees'] !='') {
            $f_property['community_fees'] = $property['community_fees'];
        }
        if (isset($property['real_estate_tax']) && $property['real_estate_tax'] !='') {
            $f_property['real_estate_tax '] = $property['real_estate_tax'];
        }
        if (isset($property['dimensions']) && $property['dimensions'] !='') {
            $f_property['dimensions'] = $property['dimensions'];
        }
        if (isset($property['plot']) && $property['plot'] !='') {
            $f_property['plot'] = $property['plot'];
        }
        if (isset($property['built']) && $property['built'] !='') {
            $f_property['built'] = $property['built'];
        }
        if (isset($property['usefull_area']) && $property['usefull_area'] !='') {
            $f_property['usefull_area'] = $property['usefull_area'];
        }
        if (isset($property['terrace']) && $property['terrace'] !='') {
            $f_property['terrace'] = $property['terrace'];
        }
        if (isset($property['cee']) && $property['cee'] !='') {
            $f_property['cee'] = $property['cee'];
        }
        if (isset($property['facade_size']) && $property['facade_size'] !='') {
            $f_property['facade_size'] = $property['facade_size'];
        }
        if (isset($property['display_window']) && $property['display_window'] !='') {
            $f_property['display_window'] = $property['display_window'];
        }
        if (isset($property['office_size']) && $property['office_size'] !='') {
            $f_property['office_size'] = $property['office_size'];
        }
        if (isset($property['ground_floor']) && $property['ground_floor'] !='') {
            $f_property['ground_floor'] = $property['ground_floor'];
        }
        if (isset($property['stories_total']) && $property['stories_total'] !='') {
            $f_property['stories_total'] = $property['stories_total'];
        }
        if (isset($property['height']) && $property['height'] !='') {
            $f_property['height'] = $property['height'];
        }
        if (isset($property['storage_size']) && $property['storage_size'] !='') {
            $f_property['storage_size'] = $property['storage_size'];
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
        $f_property['property_features']['kitchen'] = (isset($property['kitchen']))?$property['kitchen']:'';
        $f_property['property_features']['security'] = (isset($property['security']))?$property['security']:'';
        $f_property['property_features']['utility'] = (isset($property['utility']))?$property['utility']:'';
        $f_property['property_features']['furniture'] = (isset($property['furniture']))?$property['furniture']:'';
        $f_property['property_features']['climate_control'] = (isset($property['climate_control']))?$property['climate_control']:'';
        $f_property['property_features']['parking'] = (isset($property['parking']))?$property['parking']:'';
        $f_property['property_features']['garden'] = (isset($property['garden']))?$property['garden']:'';
        $f_property['property_features']['pool'] = (isset($property['pool']))?$property['pool']:'';
        $f_property['property_features']['leisure'] = (isset($property['leisure']))?$property['leisure']:'';
        $f_property['property_features']['features'] = (isset($property['features']))?$property['features']:'';
        $f_property['property_features']['rooms'] = (isset($property['rooms']))?$property['rooms']:'';


        return $f_property;
    }
}
