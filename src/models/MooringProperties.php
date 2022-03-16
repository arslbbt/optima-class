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
class MooringProperties extends Model
{

    public static function findAll($page =1, $page_size=10 , $query = '',$sort = ['current_price' => '-1'], $options = [] )
    {        
        $query_options = [
            "page" => (int)$page,
            "limit" => (int)$page_size,
            "sort" => ['current_price' => (int)'-1'],
        ];     
        if (Yii::$app->request->get('orderby') && is_array(Yii::$app->request->get('orderby')) && count(Yii::$app->request->get('orderby') == 2)) {
            $sort = [Yii::$app->request->get('orderby')[0] => Yii::$app->request->get('orderby')[1]];
        }
        $query_array=[];
        if(isset($query) && !empty($query)){
            $query = self::setQuery();
        }
        if(!empty($query)){
            $query_array['query']  =  $query;
        }
        $query_array['query']['show_on'] =  ["All Websites" , "Our Website"];
        
        $query_array['options'] = $query_options;

        $node_url = Yii::$app->params['node_url'] . '/api/mooring_properties/search?user_apikey=' . Yii::$app->params['api_key'];

        $curl = new curl\Curl();
        $response = $curl->setRequestBody(json_encode($query_array))
                    ->setHeaders([
                'Content-Type' => 'application/json',
                'Content-Length' => strlen(json_encode($query_array))
            ])
            ->post($node_url);   
 
            $response = json_decode($response, TRUE);

        $properties = [];

        if(isset($response) && isset($response['docs']))
        foreach ($response['docs'] as $property) {       
            $property['total_properties'] = isset($response['total']) ? $response['total'] : '';
            $properties[] = self::formateProperty($property, $options);
        }
        $response = $properties;

        return $response;
    }
    public static function findOne($id)
    {
        $curl = new curl\Curl();
        $node_url = Yii::$app->params['node_url'] . 'api/mooring_properties/view/' . $id . '?user_apikey=' . Yii::$app->params['api_key'];
        $JsonData = Functions::getCRMData($node_url, false);
        $response = json_decode($JsonData,True);
        $property = self::formateProperty($response, $options=[]);        
        return $property;
    }

    public static function formateProperty($property,$options)
{
        $url_to_use_without_watermark = 'https://images.optima-crm.com/resize/properties_images/';
        $agency_data = Properties::getAgency();
        $langugesSystem = Cms::SystemLanguages();
        $lang = strtoupper(\Yii::$app->language);
        $contentLang = $lang;
        foreach ($langugesSystem as $sysLang) {
            if ((isset($sysLang['internal_key']) && $sysLang['internal_key'] != '') && $lang == $sysLang['internal_key']) {
                $contentLang = $sysLang['key'];
            }
        }

        $title = 'rental_title';
        $description = 'rental_description';
        $price = 'rent';
        $seo_title = 'rental_seo_title';
        $seo_description = 'rental_seo_description';
        $keywords = 'rental_keywords';
        $perma_link = 'rental_perma_link';
        if (isset($property)) {

            if ((isset($property['sale']) && $property['sale'] == 1)) {
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
            if (isset($property['total_properties'])) {
                $data['total_properties'] = $property['total_properties'];
            }
            if (isset($property['_id'])) {
                $data['_id'] = $property['_id'];
            }
            if (isset($property['reference'])) {
                $data['id'] = $property['reference'];
            }
            if (isset($property['external_reference'])) {
                $data['reference'] = $property['external_reference'];
            }

            if (isset($property['sale']) && $property['sale'] == true && isset($property['title'][$contentLang]) && $property['title'][$contentLang] != '') {
                $data['title'] = $property['title'][$contentLang];
            } elseif (isset($property['sale']) && $property['sale'] == true && isset($property['seo_title'][$contentLang]) && $property['seo_title'][$contentLang] != '') {
                $data['title'] = $property['seo_title'][$contentLang];
            }
            if (isset($property['rent']) && $property['rent'] == true && isset($property['rental_seo_title'][$contentLang]) && $property['rental_seo_title'][$contentLang] != '') {
                $data['title'] = $property['rental_seo_title'][$contentLang];
            }  elseif ($property['rent'] && $property['rent'] == true && isset($property['title'][$contentLang]) && $property['title'][$contentLang] != '') {
                $data['title'] = isset($property['rental_title'][$contentLang]) && !empty($property['rental_title'][$contentLang]) ?  $property['rental_title'][$contentLang] : '';
            }

            if (isset($property['status'])) {
                $data['status'] = $property['status'];
            }
            if (isset($property['type_one'])) {
                $data['type_one'] = $property['type_one'];
            }
            if (isset($property['type'])) {
                $data['type'] = $property['type'];
            }
            if (isset($property['perma_link'][$lang])) {
                $data['perma_link'] = $property['perma_link'][$lang];
            }
            $agency = Yii::$app->params['agency'];
            if (isset($property['latitude']) && $property['latitude'] != '') {
                $data['lat'] = $property['latitude'];
            }

            if (isset($property['longitude']) && $property['longitude'] != '') {
                $data['lng'] = $property['longitude'];
            }
            if (isset($property['offices']) && $property['offices'] != '') {
                $data['offices'] = $property['offices'];
            }
            if (isset($property['property_references']) && $property['property_references'] != '') {
                $data['property_references'] = $property['property_references'];
            }
            if (isset($property['sale']) && $property['sale'] == true && isset($property['description'][$contentLang]) && $property['description'][$contentLang] != '') {
                $data['description'] = $property['description'][$contentLang];
            }elseif (isset($property['sale']) && $property['sale'] == true && isset($property['seo_description'][$contentLang]) && $property['seo_description'][$contentLang] != '') {
                $data['description'] = $property['seo_description'][$contentLang];
            }
            if (isset($property['rent']) && $property['rent'] == true && isset($property['rental_description'][$contentLang]) && $property['rental_description'][$contentLang] != '') {
                $data['description'] = $property['rental_description'][$contentLang];
            }elseif (isset($property['rent']) && $property['rent'] == true && isset($property['rental_seo_description'][$contentLang]) && $property['rental_seo_description'][$contentLang] != '') {
                $data['description'] = $property['rental_seo_description'][$contentLang];
            }
            if (isset($property['description'][$contentLang]) && !empty($property['description'][$contentLang])) {
                $data['description'] = $property['description'][$contentLang];
            }
            if (isset($property['property_region']['value']) && !empty($property['property_region']['value'])) {
                $data['region'] = (isset(Yii::$app->language) && strtolower(Yii::$app->language) == 'es') ? $property['property_region']['value']['es_AR'] : $property['property_region']['value']['en'] ;
            }
            if (isset($property['property_country']['value']) && !empty($property['property_country']['value'])) {
                $data['country'] = (isset(Yii::$app->language) && strtolower(Yii::$app->language) == 'es') ? $property['property_country']['value']['es_AR'] : $property['property_country']['value']['en'] ;
            }
            if (isset($property['property_city']['value']) && !empty($property['property_city']['value'])) {
                $data['city'] = (isset(Yii::$app->language) && strtolower(Yii::$app->language) == 'es') ? $property['property_city']['value']['es_AR'] : $property['property_city']['value']['en'] ;
            }
            if (isset($property['street_number']) && !empty($property['street_number'])) {
                $data['street_number'] = $property['street_number'];
            }
            if (isset($property['sale']) && $property['sale'] == 1) {
                $data['sale'] = $property['sale'];
            }
            if (isset($property['rent']) && $property['rent'] == 1) {
                $data['rent'] = $property['rent'];
                if (isset($property['st_rental']) && $property['st_rental'] == 1) {
                    $data['st_rental'] = $property['st_rental'];
                }
                if (isset($property['lt_rental']) && $property['lt_rental'] == 1) {
                    $data['lt_rental'] = $property['lt_rental'];
                }
            }
            if (isset($property['currency']) && $property['currency'] != '') {
                $data['currency'] = $property['currency'];
            }
            if (isset($property['own']) && $property['own'] == true) {
                $data['own'] = true;
            }
            if (isset($property['bedrooms']) && $property['bedrooms'] > 0) {
                $data['bedrooms'] = $property['bedrooms'];
            }
            if (isset($property['bathrooms']) && $property['bathrooms'] > 0) {
                $data['bathrooms'] = $property['bathrooms'];
            }
            if (isset($property['occupancy_status'])) {
                $data['occupancy_status'] = $property['occupancy_status'];
            }
            if (isset($property['sleeps']) && $property['sleeps'] > 0) {
                $data['sleeps'] = $property['sleeps'];
            }
            if (isset($property['living_rooms']) && $property['living_rooms'] > 0) {
                $data['living_rooms'] = $property['living_rooms'];
            }
            if (isset($property['address_street']) && $property['address_street'] != '') {
                $data['address_street'] = $property['address_street'];
            }
            if (isset($property['vt_ids']) && !empty($property['vt_ids'])) {
                $data['vt'] = $property['vt_ids'];
            }
            if (isset($property['address_street_number']) && $property['address_street_number'] != '') {
                $data['address_street_number'] = $property['address_street_number'];
            }
            if (isset($property['current_price'])) {
                $data['price'] = ($property['current_price']!= 0) ? number_format((int) $property['current_price'], 0, '', '.') : '';
            }
            if (isset($property['current_price']) && $property['current_price'] > 0) {
                $data['current_price'] = str_replace(',', '.', (number_format((int) ($property['current_price']))));
            }
            if (isset($property['old_price']) && $property['old_price'] > 0) {
                $data['old_price'] = str_replace(',', '.', (number_format((int) ($property['old_price']))));
            }
            if (isset($property['date_built']) && $property['date_built'] > 0) {
                $data['built'] = $property['date_built'];
            }
            if (isset($property['price_per_built']) && !empty($property['price_per_built'])) {
                $data['price_per_built'] = $property['price_per_built'];
            }
            if (isset($property['exclusive']) && $property['exclusive'] == true) {
                $data['exclusive'] = true;
            }
            if (isset($property['features']) && !empty($property['features'])) {
                foreach($property['features'] as $key => $value ){
                    if($value == 1){
                    $data['features'][$key] = $value ;
                }
            }
            }
            if (isset($property['depth']) && $property['depth'] != '') {
                $data['features']['depth'] = $property['depth'];
            }
            if (isset($property['beam']) && $property['beam'] != '') {
                $data['features']['beam'] = $property['beam'];
            }
            if (isset($property['length']) && $property['length'] != '') {
                $data['features']['length'] = $property['length'];
            }
            if (isset($property['length']) && $property['length'] != '') {
                $data['features']['length'] = $property['length'];
            }
            if (isset($property['car_parking']) && $property['car_parking'] != '') {
                $data['features']['car_parking'] = $property['car_parking'];
            }
            if (isset($property['dimensions']) && $property['dimensions'] != '') {
                $data['features']['dimensions'] = $property['dimensions'];
            }
            if (isset($property['height_above_water']) && $property['height_above_water'] != '') {
                $data['features']['height_above_water'] = $property['height_above_water'];
            }
           
            $slugs = [];
            foreach ($langugesSystem as $lang_sys) {
                $lang_sys_key = $lang_sys['key'];
                $lang_sys_internal_key = isset($lang_sys['internal_key']) && $lang_sys['internal_key'] != '' ? $lang_sys['internal_key'] : '';
                if (isset($property['perma_link'][$lang_sys_key]) && $property['perma_link'][$lang_sys_key] != '') {
                    $slugs[$lang_sys_internal_key] = $property['perma_link'][$lang_sys_key];
                } elseif (isset($property['title'][$lang_sys_key]) && $property['title'][$lang_sys_key] != '') {
                    $slugs[$lang_sys_internal_key] = $property['title'][$lang_sys_key];
                } else {
                    if (isset($property['type_one']) && $property['type_one'] != '' && isset($slugs[$lang_sys_internal_key])) {
                        $slugs[$lang_sys_internal_key] = $property['type_one'] . ' ' . 'in' . ' ';
                    }
                }
            }
            //        end slug_all
            $data['slug_all'] = $slugs;

       
            if (isset($property['property_attachments']) && count($property['property_attachments']) > 0) {
                $attachments = [];  
                $attachments_size = isset($options['images_size']) && !empty($options['images_size']) ? $options['images_size'] . '/' : '1200/';     
                $attachment_alt_descriptions = [];         
                    if($property['property_attachments'] && count($property['property_attachments']) > 0){
                    foreach ($property['property_attachments'] as $pic) {
                        $attachments[] = Yii::$app->params['mooring_img_url'].'/'. $pic['model_id'] . '/' . $attachments_size . $pic['file_md5_name'];
                    }
                }
                $data['attachments'] = $attachments;
            }

            $videos = [];        
            if (isset($property['videos']) && !empty($property['videos'])) {
                foreach ($property['videos'] as $key => $value) {
                    $videos[] = $value;
                }
            }

            if(isset($property['agency_data']) && !empty($property['agency_data'])){
                $data['agency_logo'] = 'https://images.optima-crm.com/agencies/' . (isset($property['agency_data']['_id']) ? $property['agency_data']['_id'] : '') . '/' . (isset($property['agency_data']['agency_logo']) ? $property['agency_data']['agency_logo'] : '');
            }
            $features = [];
            if (isset($property->property->feet_features)) {
                foreach ($property->property->feet_features as $key => $value) {
                    if ($value == true) {
                        $features[] = $key;
                    }
                }
            }
            $data['property_features'] = [];
            $data['property_features']['videos'] = $videos;
            $return_data = $data;
        }
        return $return_data;
}

    public static function setQuery()
    {

        $get = Yii::$app->request->get();
        $query = [];

        if (isset($get['price_from']) && $get['price_from']) {
            $query['current_price_from'] = $get['price_from'];
        }
        if (isset($get['price_to']) && $get['price_to']) {
            $query['current_price_to'] = $get['price_to'];
        } 
        if (isset($get['length']) && $get['length']) {
            $query['length_from'] = $get['length'];
        }
        if (isset($get['width']) && $get['width']) {
            $query['beam_from'] = $get['width'];
        }
        if (isset($get['depth']) && $get['depth']) {
            $query['depth_from'] = $get['depth'];
        }
        if (isset($get['height']) && $get['height']) {
            $query['height_above_water_from'] = $get['height'];
        }
        if (isset($get['lg_by_key']) && $get['lg_by_key']) {
            $query['lg_by_key'] = array_map('intval', $get['lg_by_key']);
        }
        if (isset($get['location']) && $get['location']) {
            $query['location'] =  array_map('intval', $get['location']);
        }
        if (isset($get['p_types']) && $get['p_types']) {
            $query['p_types'] = $get['p_types'];
        }
        return $query;
    }
}