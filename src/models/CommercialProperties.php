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

        if (Yii::$app->request->get('orderby') && is_array(Yii::$app->request->get('orderby')) && count(Yii::$app->request->get('orderby')) == 2 ) {
            $sort = [Yii::$app->request->get('orderby')[0] => Yii::$app->request->get('orderby')[1]];
        }
        $options['sort'] = $sort;

        
        if(isset($query) && $query != '' && !is_array($query)){
            $vars = explode('&', $query);
            foreach($vars as $var){
                $k = explode('=', $var);
                if(isset($k[0]) && isset($k[1])){
                    if($k[0] == 'favourite_ids'){
                        $query_array['$and'][]['favourite_ids'] = explode(',', $k[1]);
                        $query_array['$and'][]['archived']['$ne'] = true;
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
            }
            if (count($query)){
                $query_array = $query;
                $query_array['$and'][]['status'] = ['$in' => (isset(Yii::$app->params['status']) && !empty(Yii::$app->params['status']) ? Yii::$app->params['status'] : ['Available', 'Under Offer'])];
            }
        }
        
        $post_data = ["options" => $options];
        if(!empty($query_array)){
            $post_data["query"] =  $query_array;
        }

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
;
       
        $response = json_decode($response, TRUE);
        $property = self::formateProperty($response);
        
        return $property;
    }

    public static function setQuery()
    {
        $get = Yii::$app->request->get();
        $query = [];
        if (isset($get['price_from']) && !empty($get['price_from'])) {
            $query['$and'][]['current_price'] = ['$gte' => (int) $get['price_from']];
        }
        if (isset($get['price_to']) && !empty($get['price_to'])) {
            $query['$and'][]['current_price'] = ['$lte' => (int) $get['price_to']];
        }
        if (isset($get['reference']) && !empty($get['reference'])) {
            $query['$and'][]['$or'] = [
                ["reference" => (int) $get['reference']],
                ["other_reference" => ['$regex' => ".*" . $get['reference'] . ".*", '$options' => "i"]],
                ["external_reference" => ['$regex' => ".*" . $get['reference'] . ".*", '$options' => "i"]]
            ];
        }

        if (isset($get['type']) && !empty($get['type']) && is_array($get['type']) && count($get['type']) > 0 && $get['type'][0] != 0 && $get['type'][0] != '' && $get['type'][0] != '0') {
            $intArray = array();
            foreach ($get['type'] as $int_val) {
                $intArray[] = (int) $int_val;
            }
            $query['$and'][]['type_one'] = ['$in' => $intArray];
        }
        if (isset($get['sub_type']) && !empty($get['sub_type']) && is_array($get['sub_type']) && count($get['sub_type']) > 0 && $get['sub_type'][0] != 0 && $get['sub_type'][0] != '' && $get['sub_type'][0] != '0') {
            $intArray = array();
            foreach ($get['sub_type'] as $int_val) {
                $intArray[] = (int) $int_val;
            }
            $query['$and'][]['type_two'] = ['$in' => $intArray];
        }

        if (isset($get['country']) && !empty($get['country'])) {
            $query['$and'][]['country'] = (int) $get['country'];
        }
        if (isset($get['city']) && $get['city']) {
            $intArray = array();
            foreach ($get['city'] as $int_val) {
                $intArray[] = (int) $int_val;
            }
            $query['$and'][]['city'] = ['$in' => $intArray];
        }
        if (isset($get['location']) && !empty($get['location'])) {
            $intArray = array();
            foreach ($get['location'] as $int_val) {
                $intArray[] = (int) $int_val;
            }
            $query['$and'][]['location'] = ['$in' => $intArray];
        }
        if (isset($get['province']) && !empty($get['province'])) {
            $intArray = array();
            foreach ($get['province'] as $int_val) {
                $intArray[] = (int) $int_val;
            }
            $query['$and'][]['province'] = ['$in' => $intArray];
        }
        if (isset($get['cp_features']) && !empty($get['cp_features'])) {
            foreach($get['cp_features'] as $features){
                $search_features = [];
                if(isset($features) && !empty($features)){
                    foreach($features as $key => $feature){
                        $search_features[$key] = $feature;
                    }
                }
                $query['$and'][]['$or'] = $search_features;
            }
        }
        if (isset($get['sale']) && !empty($get['sale'])) {
            $query['$and'][]['sale'] = true;
        }
        if (isset($get['rent']) && !empty($get['rent'])) {
            $query['$and'][]['rent'] = true;
        }
        if (isset($get['bedrooms']) && !empty($get['bedrooms'])) {
            $query['$and'][]['bedrooms'] = $get['bedrooms'];
        }
        if (isset($get['min_bed']) && !empty($get['min_bed'])) {
            $query['$and'][]['bedrooms'] = ['$lte' => (int)$get['min_bed']];
        } elseif (isset($get['max_bed']) && !empty($get['max_bed'])) {
            $query['$and'][]['bedrooms'] = ['$gte' => (int)$get['max_bed']];
        }
        if (isset($get['bathrooms']) && $get['bathrooms']) {
            $query['$and'][]['bathrooms'] = $get['bathrooms'];
        }
        if (isset($get['min_bath']) && !empty($get['min_bath'])) {
            $query['$and'][]['bathrooms'] = ['$lte' => (int)$get['min_bath']];
        } elseif (isset($get['max_bath']) && !empty($get['max_bath'])) {
            $query['$and'][]['bathrooms'] = ['$gte' => (int)$get['max_bath']];
        }
        if (isset($get['new_built']) && !empty($get['new_built'])) {
            $query['$and'][]['project'] = true;
        }
        if (isset($get['region']) && !empty($get['region'])) {
            $query['$and'][]['region'] = (int) $get['region'];
        }

        $query['$and'][]['archived']['$ne'] = true;

        if (isset($get['featured']) && !empty($get['featured'])) {
            $query['$and'][]['featured'] = true;
        }
        if (isset($get['office']) && !empty($get['office'])) {
            $query['$and'][]['offices'] =['$in' => $get['office']];
        }
        return $query;
    }

    public static function formateProperty($property)
    {
        $settings = Cms::settings();
        $lang = strtoupper(\Yii::$app->language);
        $contentLang = strtolower(\Yii::$app->language);
        if(strtolower(\Yii::$app->language) == 'es'){
            $contentLang = 'es_AR';
        }
        $f_property = [];
        if (isset($settings['general_settings']['reference']) && $settings['general_settings']['reference'] != 'reference') {
            $ref = $settings['general_settings']['reference'];
            $f_property['reference'] = $property[$ref];
        } else {
            $f_property['reference'] = isset($property['reference']) ? $property['reference'] : '';
        }
        if (isset($property['external_reference'])) {
            $f_property['external_reference'] = $property['external_reference'];
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
            $f_property['title'] = (isset($property['type_one_value']['en']) ? \Yii::t('app', $property['type_one_value']['en']) : '') . ' ' . (isset($property['property_location']['value']['en']) ? \Yii::t('app', 'in'). ' ' .\Yii::t('app', $property['property_location']['value']['en']) : '');
        }
        if (isset($property['status'])) {
            $f_property['status'] = \Yii::t('app', $property['status']);
        }
        if (isset($property['agency_data']['logo']['name']) && !empty($property['agency_data']['logo']['name']) ) {
            $f_property['agency_logo'] = 'https://images.optima-crm.com/agencies/' . (isset($property['agency_data']['_id']) ? $property['agency_data']['_id'] : '') . '/' . (isset($property['agency_data']['logo']['name']) ? $property['agency_data']['logo']['name'] : '');
        }
        if (isset($property['description'][$lang])) {
            $f_property['description'] = $property['description'][$lang];
        }
        if (isset($property['seo_title'][$lang]) && $property['seo_title'][$lang] != '') {
            $f_property['meta_title'] = $property['seo_title'][$lang];
        }
        if (isset($property['seo_description'][$lang]) && $property['seo_description'][$lang] != '') {
            $f_property['meta_desc'] = $property['seo_description'][$lang];
        }
        if (isset($property['keywords'][$lang]) && $property['seo_description'][$lang] != '') {
            $f_property['meta_keywords'] = $property['keywords'][$lang];
        }
        $urls = [];
        if (isset($property['agency_data']['property_url_website_cp']) && !empty($property['agency_data']['property_url_website_cp']) ) {
            if (isset($property['agency_data']['property_url_website_cp']['sale']) && $property['agency_data']['property_url_website_cp']['sale'] != '' && !empty($property['agency_data']['property_url_website_cp']['sale'])) {
                $urls['sale_urls'] = $property['agency_data']['property_url_website_cp']['sale'];
            } 
            if (isset($property['agency_data']['property_url_website_cp']['rent']) && $property['agency_data']['property_url_website_cp']['rent'] != '' && !empty($property['agency_data']['property_url_website_cp']['rent'])) {
                $urls['rent_urls'] = $property['agency_data']['property_url_website_cp']['rent'];
            }
        }
        $f_property['urls'] = $urls;
        if(isset($property['videos']) && !empty($property['videos'])){
            $videos = [];
            $virtual_tours = [];
            foreach($property['videos'] as $video){
                if(isset($video['type']) && $video['type'] == 'Video' && isset($video['status']) && $video['status'] == 1){
                    $videos[] = $video['url'][strtoupper(Yii::$app->language)];
                }
            }
            $f_property['videos'] = $videos;
            foreach($property['videos'] as $vt){
                if(isset($vt['type']) && $vt['type'] == '2' && isset($vt['status']) && $vt['status'] == 1){
                    $virtual_tours[] = $vt['url'][strtoupper(Yii::$app->language)];
                }
            }
            $f_property['vt'] = $virtual_tours;
        }
        if (isset($property['created_at']) && !empty($property['created_at'])) {
            $f_property['created_at'] = strtotime($property['created_at']);
        }
        
        if (isset($property['featured'])) {
            $f_property['featured'] = $property['featured'];
        }
        if (isset($property['type_one'])) {
            $f_property['type'] = \Yii::t('app', $property['type_one']);
        }
        if (isset($property['type_one_value'][$contentLang])) {
            $f_property['type_one'] = \Yii::t('app', $property['type_one_value'][$contentLang]);
        }
        if (isset($property['type_two'])) {
            $f_property['type_two_key'] = \Yii::t('app', $property['type_two']);
        }
        if (isset($property['type_two_value'][$contentLang])) {
            $f_property['type_two'] = \Yii::t('app', $property['type_two_value'][$contentLang]);
        }
        if (isset($property['address']['formatted_address'])) {
            $f_property['address'] = $property['address']['formatted_address'];
        }
        if (isset($property['street'])) {
            $f_property['street'] = $property['street'];
        }
        if (isset($property['street_number'])) {
            $f_property['street_number'] = $property['street_number'];
        }
        if (isset($property['postal_code'])) {
            $f_property['postal_code'] = $property['postal_code'];
        }
        if (isset($property['cadastral_numbers'])) {
            $f_property['cadastral_numbers'] = $property['cadastral_numbers'];
        }
        if (isset($property['project'])) {
            $f_property['project'] = $property['project'];
        }
        if (isset($property['country'])) {
            $f_property['country'] = $property['country'];
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
        if (isset($property['transfer']) && $property['transfer']) {
            $f_property['transfer'] = TRUE;
        }
        if (isset($property['leasehold']) && $property['leasehold']) {
            $f_property['leasehold'] = $property['leasehold'];
        }
        if (isset($property['leasehold_rental_price']) && $property['leasehold_rental_price']) {
            $f_property['leasehold_rental_price'] = $property['leasehold_rental_price'];
        }  
        if (isset($property['leasehold_rental_unit']) && $property['leasehold_rental_unit']) {
            $f_property['leasehold_rental_unit'] = $property['leasehold_rental_unit'];
        }
        if (isset($property['rental_seasons']) && !empty($property['rental_seasons']) && count($property['rental_seasons']) > 0) {
            $f_property['rental_season_data'] = [];
            foreach($property['rental_seasons'] as $season){
                $f_property['rental_season_data'][] = $season;
            }
        }
        if (isset($property['leasehold_unit']) && $property['leasehold_unit']) {
            $f_property['leasehold_unit'] = $property['leasehold_unit'];
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
        if (isset($property['city_value'][$contentLang])) {
            $f_property['city'] = $property['city_value'][$contentLang];
        }
        if (isset($property['province_value'][$contentLang])) {
            $f_property['province'] = $property['province_value'][$contentLang];
        }
        if (isset($property['location'])) {
            $f_property['location_key'] = $property['location'];
        }
        if (isset($property['location_value'][$contentLang])) {
            $f_property['location'] = $property['location_value'][$contentLang];
        }
        if (isset($property['type_one_key'])) {
            $f_property['type_key'] = $property['type_one_key'];
        }
        if (isset($property['current_price'])) {
            $f_property['price'] = $property['current_price'];
        }
        if (isset($property['old_price'])) {
            $f_property['old_price'] = $property['old_price'];
        }
        if (isset($property['currency'])) {
            $f_property['currency'] = $property['currency'];
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
        if (isset($property['show_on']) && $property['show_on'] !='') {
            $f_property['show_on '] = $property['show_on'];
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
        $offices = [];
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
        if (isset($property['offices']) && count($property['offices']) > 0) {
            foreach ($property['offices'] as $key => $value) {
                if ($value) {
                    $offices[] = $value;
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
        $f_property['offices'] = $offices;

        return $f_property;
    }

    public static function findAllWithLatLang($qry = 'true', $cache = false)
    {
        $webroot = Yii::getAlias('@webroot');
        $node_url = Yii::$app->params['node_url'] . 'commercial_properties/find-all?user=' . Yii::$app->params['user'].(isset($qry) && $qry == 'true' ? '&latLang=1' : '');
        $query = [];
        $sort = ['current_price' => '-1'];
        $query_array=[];
        $options = ["page" => 1, "limit" => 10];
        $options['populate'] = [
            [
                'path' => 'property_attachments',
                'match' => ['document' => ['$ne' => true], 'publish_status' => ['$ne' => false]],
            ]
        ];

        if (Yii::$app->request->get('orderby') && is_array(Yii::$app->request->get('orderby')) && count(Yii::$app->request->get('orderby')) == 2 ) {
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
            }
            if (count($query)){
                $query_array = $query;
                $query_array['status'] = ['$in' => (isset(Yii::$app->params['status']) && !empty(Yii::$app->params['status']) ? Yii::$app->params['status'] : ['Available', 'Under Offer'])];
            }
        }
        $post_data = ["options" => $options];
        if(!empty($query_array))
        {
            $post_data["query"] =  $query_array;
        }
       
        $curl = new curl\Curl();
        $response = $curl->setRequestBody(json_encode($post_data))
            ->setHeaders([
                'Content-Type' => 'application/json',
                'Content-Length' => strlen(json_encode($post_data))
            ])
            ->post($node_url);
        if (!is_dir($webroot . '/uploads/')) {
            mkdir($webroot . '/uploads/');
        }
        if (!is_dir($webroot . '/uploads/temp/')) {
            mkdir($webroot . '/uploads/temp/');
        }
        if($cache){
            return json_decode($response, true);
        }
        $file = $webroot . '/uploads/temp/commercial_properties-latlang.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $file_data = file_put_contents($file, $response);
        } else {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, true);
    }
    public static function getAgencyProperties($transaction_type = 'sale', $id, $options = ['page' => 1, 'limit' => 10]){
        $post_data['options'] = [
            'page' => $options['page'],
            'limit' => $options['limit'],
            'populate' => 'property_attachments'
        ];
        $post_data['query'] = [
            'id' => $id
        ];
        $node_url = Yii::$app->params['node_url'] . 'commercial_properties/get-properties-with-transaction-types/'. $transaction_type .'?user=' . Yii::$app->params['user'];
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

        return $response;
    }
    public static function getAgencies($query = [], $options = [])
    {
        $post_data['option'] = [
            'skipLimit' => (isset($options['skipLimit']) ? (int)$options['skipLimit'] : 0),
            'endLimit' => (isset($options['endLimit']) ? (int)$options['endLimit'] : 10),
        ];
        $post_data['query']['type'] = 'Agency';
        if (isset($query['country']) && !empty($query['country'])) {
            $post_data['query']['country'] = (int)$query['country'];
        }
        if (isset($query['cities']) && !empty($query['cities'])) {
            $intArray = array();
            foreach ($query['cities'] as $int_val) {
                $intArray[] = (int) $int_val;
            }
            $post_data['query']['city'] = ['$in' => $intArray];
        }
        if (isset($query['languages']) && !empty($query['languages'])) {
            $intArray = array();
            foreach ($query['languages'] as $int_val) {
                $intArray[] = (string)$int_val;
            }
            $post_data['query']['$or'][]['communication_language'] = ['$in' => $intArray];
            $post_data['query']['$or'][]['spoken_language'] = ['$in' => $intArray];
        }
        if (isset($query['transaction_type']) && !empty($query['transaction_type'])) {
            foreach ($query['transaction_type'] as $int_val) {
                $post_data['query'][$int_val] = (boolean)'true';
            }
        }
        if (isset($query['property_valuation']) && !empty($query['property_valuation'])) {
            $post_data['query']['property_valuation'] = $query['property_valuation'];
        }
        $node_url = Yii::$app->params['node_url'] . 'companies/search-company?user=' . Yii::$app->params['user'];
        $curl = new curl\Curl();
        $response = $curl->setRequestBody(json_encode($post_data))
            ->setHeaders([
                'Content-Type' => 'application/json',
                'Content-Length' => strlen(json_encode($post_data))
            ])
            ->post($node_url);
        return json_decode($response);
    }

    public static function findAnAgency($id){
        $post_data['option'] = [
            "skipLimit" => 0,
            "endLimit" => 3
        ];
        $post_data['query'] = [
            "type" => "Agency"
        ];
        $node_url = Yii::$app->params['node_url'] . 'companies/company-type-of-agency/' . $id;
        $curl = new curl\Curl();
        $response = $curl->setRequestBody(json_encode($post_data))
            ->setHeaders([
                'Content-Type' => 'application/json',
                'Content-Length' => strlen(json_encode($post_data))
            ])
            ->post($node_url);
        return json_decode($response);
    }

    public static function createProperty($data){
        $languages = Cms::siteLanguages();
        $fields = [
        'sale' => (isset($data['transaction_type']) && $data['transaction_type'] == 'sale' ? (Boolean)'1' : (Boolean)'0'),
        'rent' => (isset($data['transaction_type']) && $data['transaction_type'] == 'rent' ? (Boolean)'1' : (Boolean)'0'),
        'lt_rental' => (isset($data['transaction_type']) && $data['transaction_type'] == 'rent' ? (Boolean)'1' : (Boolean)'0'),
        'type_one' => (isset($data['type_one']) && !empty($data['type_one']) ? (int)$data['type_one'] : ''),
        'type_two' => (isset($data['type_two']) && !empty($data['type_two']) ? (int)$data['type_two'] : ''),
        'bedrooms' => (isset($data['bedrooms']) && !empty($data['bedrooms']) ? (int)$data['bedrooms'] : ''),
        'bathrooms' => (isset($data['bathrooms']) && !empty($data['bathrooms']) ? (int)$data['bathrooms'] : ''),
        'built'  => (isset($data['built']) && !empty($data['built']) ? (int)$data['built'] : ''),
        'plot'  => (isset($data['plot']) && !empty($data['plot']) ? (int)$data['plot'] : ''),
        'energy_certificate_one' => (isset($data['energy_certificate_one']) && !empty($data['energy_certificate_one']) ? (string)$data['energy_certificate_one'] : ''),
        'private_info_object' => [Yii::$app->params['agency'] => ['cadastral_numbers' => [0 => ['cadastral_number'=> (isset($data['cadastral_numbers']) && !empty($data['cadastral_numbers']) ? (int)$data['cadastral_numbers'] : '')]]]],
        'address' => ['formatted_address' => (isset($data['formatted_address']) && !empty($data['formatted_address']) ? (string)$data['formatted_address'] : '')],
        'country' => (isset($data['country']) && !empty($data['country']) ? (int)$data['country'] : ''),
        'region'  => (isset($data['region']) && !empty($data['region']) ? (int)$data['region'] : ''),
        'province'  => (isset($data['province']) && !empty($data['province']) ? (int)$data['province'] : ''),
        'city'  => (isset($data['city']) && !empty($data['city']) ? (int)$data['city'] : ''),
        'location' => (isset($data['location']) && !empty($data['location']) ? (int)$data['location'] : ''),
        'street' => (isset($data['street']) && !empty($data['street']) ? (string)$data['street'] : ''),
        'street_number' => (isset($data['street_number']) && !empty($data['street_number']) ? (string)$data['street_number'] : ''),
        'postal_code'  => (isset($data['postal_code']) && !empty($data['postal_code']) ? (string)$data['postal_code'] : ''),
        'currency' => (isset($data['currency']) && !empty($data['currency']) ? (string)$data['currency'] : ''),
        'latitude_alt' => (isset($data['lat']) && !empty($data['lat']) ? $data['lat'] : ''),
        'longitude_alt' => (isset($data['lng']) && !empty($data['lng']) ? $data['lng'] : ''),
        'status' => (isset($data['status']) && !empty($data['status']) ? $data['status'] : 'Valuation'),
        ];
        if(isset($data['transaction_type']) && $data['transaction_type'] == 'sale'){
            $fields['current_price'] = (isset($data['current_price']) && !empty($data['current_price']) ? (int)$data['current_price'] : '');
        } 
        elseif(isset($data['transaction_type']) && $data['transaction_type'] == 'rent'){
            $fields['period_seasons'][] = ['seasons' => (isset($data['seasons']) && !empty($data['seasons']) ? $data['seasons'] : 'All year'), 'new_price' => (isset($data['current_price']) && !empty($data['current_price']) ? ((int)$data['current_price'] * 12) : ''), 'total_per_month' => (isset($data['current_price']) && !empty($data['current_price']) ? (int)$data['current_price'] : '')];
        }
        $fields['project'] = false;
        $fields['features'] = ['lift_elevator' => false];
        $fields['security'] = ['gated_complex' => false];
        $fields['categories']['freehold'] = false;
        $fields['categories']['leasehold'] = false;
        $fields['parking']['private'] = false;
        $fields['parking']['parking_communal'] = false;
        $fields['garden']['garden_private'] = false;
        $fields['garden']['garden_communal'] = false;
        $fields['pool']['pool_private'] = false;
        $fields['pool']['pool_communal'] = false;
        if(isset($data['parking']) && !empty($data['parking'])){
            foreach($data['parking'] as $parking){
                $fields['parking'][$parking] = true;
            }
        }
        if(isset($data['garden']) && !empty($data['garden'])){
            foreach($data['garden'] as $garden){
                $fields['garden'][$garden] = true;
            }
        }
        if(isset($data['pool']) && !empty($data['pool'])){
            foreach($data['pool'] as $pool){
                $fields['pool'][$pool] = true;
            }
        }
        if(isset($data['features']) && !empty($data['features'])){
            foreach($data['features'] as $feature){
                if($feature == 'project'){
                    $fields[$feature] = true;
                }
                elseif($feature == 'lift_elevator'){
                    $fields['features'] = [$feature => true];
                }
                elseif($feature == 'gated_complex'){
                    $fields['security'] = [$feature => true];
                }
                else {
                    $fields['categories'][$feature] = true;
                }
            }
        }
        if(isset($languages) && !empty($languages)){
            foreach($languages as $lang){
                if(isset($data['transaction_type']) && $data['transaction_type'] == 'sale'){
                    $fields['title'][strtoupper($lang)] = (isset($data['title'][strtoupper($lang)]) && !empty($data['title'][strtoupper($lang)]) ? $data['title'][strtoupper($lang)] : $data['title']['EN']);
                    $fields['description'][strtoupper($lang)] =(isset($data['description'][strtoupper($lang)]) && !empty($data['description'][strtoupper($lang)]) ? $data['description'][strtoupper($lang)] : $data['description']['EN']);
                } else {
                    $fields['rental_title'][strtoupper($lang)] = (isset($data['title'][strtoupper($lang)]) && !empty($data['title'][strtoupper($lang)]) ? $data['title'][strtoupper($lang)] : $data['title']['EN']);
                    $fields['rental_description'][strtoupper($lang)] =(isset($data['description'][strtoupper($lang)]) && !empty($data['description'][strtoupper($lang)]) ? $data['description'][strtoupper($lang)] : $data['description']['EN']);
                }
            }
        }
        $curl = new curl\Curl();
        if(isset($data['prop_id']) && !empty($data['prop_id'])){
            $node_url = Yii::$app->params['node_url'] . 'commercial_properties/update/'.$data['prop_id'].'?user=' . $data['user_id'];
            $response = $curl->setRequestBody(json_encode($fields))
            ->setHeaders([
                'Content-Type' => 'application/json',
                'Content-Length' => strlen(json_encode($fields))
                ])
                ->put($node_url);
        }else{
            $node_url = Yii::$app->params['node_url'] . 'commercial_properties/create?user=' . $data['user_id'];
            $response = $curl->setRequestBody(json_encode($fields))
            ->setHeaders([
                'Content-Type' => 'application/json',
                'Content-Length' => strlen(json_encode($fields))
                ])
                ->post($node_url);
        }
        return json_decode($response);
    }

    public static function savePropertyAttachments($id, $images){

        $node_url = Yii::$app->params['apiUrl'] . 'commercial-properties/upload-images&user_apikey=' . Yii::$app->params['api_key'];

        $fields = [
            'id' => $id,
            'modelName' => "commercial_images",   // model name should never be changed               // depend on you to send or send empty value
            'files' => $images,
        ];
        $curl = new curl\Curl();
        $response = $curl->setRequestBody(json_encode($fields, JSON_UNESCAPED_SLASHES))
        ->setHeaders([
            'Content-Type' => 'application/json',
            'Content-Length' => strlen(json_encode($fields, JSON_UNESCAPED_SLASHES))
            ])
            ->post($node_url);
        return json_decode($response);

    }

    public static function savePropertyOfInterest($data){

        $node_url = Yii::$app->params['node_url'] . 'accounts/update-with-email/?user_apikey=' . Yii::$app->params['api_key'];

        $fields['query'] = [
            'email' => $data['email'],
            'data' => [
                'commercials_interested' => [(int)$data['id']],
                'communication_language' => strtoupper(Yii::$app->language),
                'language' => [strtoupper(Yii::$app->language)],
                'title' => 'update account',
            ],
        ];
        $curl = new curl\Curl();
        $response = $curl->setRequestBody(json_encode($fields))
        ->setHeaders([
            'Content-Type' => 'application/json',
            'Content-Length' => strlen(json_encode($fields))
            ])
            ->post($node_url);
        return json_decode($response);

    }

    public static function getAllUserProperties($query, $options = ['page' => 1, 'limit' => 10], $sort = ['current_price' => '-1']){

        $node_url = Yii::$app->params['node_url'] . 'commercial_properties/get-all-properties-of-user/?user=' . $query['_id'];
        $post_data['options'] = [
            'page' => isset($options['page']) ? (int)$options['page'] : 1,
            'limit' => isset($options['limit']) ? (int)$options['limit'] : 10,
            "populate" => ["property_attachments", "property_type_one", "property_type_two"],
            'sort' => $sort,
        ];
        $post_data['query'] = [
            "userId" => $query['_id'],
            "type" => $query['property_type'],
        ];
        $curl = new curl\Curl();
        $response = $curl->setRequestBody(json_encode($post_data))
        ->setHeaders([
            'Content-Type' => 'application/json',
            'Content-Length' => strlen(json_encode($post_data))
            ])
            ->post($node_url);
        return json_decode($response);

    }
}
