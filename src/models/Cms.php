<?php

namespace optima\models;

use optima\models\Functions;
use Yii;
use yii\base\Model;
use yii\helpers\Url;


/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class Cms extends Model
{
    private static $image_url = 'https://images.optima-crm.com/resize/cms_medias/'; // For resize image URLs
    private static $image_url_users = ' https://images.optima-crm.com/resize/users/'; // For getUsers image URLs

    public static function settings()
    {
        $file = Functions::directory() . 'settings.json';

        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $url = Yii::$app->params['apiUrl'] . 'cms/setting&user=' . Yii::$app->params['user'] . '&id=' . Yii::$app->params['template'];

            $file_data =
                //file_get_contents($url);
                Functions::getCRMData($url);

            file_put_contents($file, $file_data);
        } else {
            $file_data =
                file_get_contents($file);
            //Functions::getCRMData($file);
        }
        return json_decode($file_data, TRUE);
    }

    // For General settings no need to pass params
    // For Page settings pass page_data['custom_settings'] as param without language

    public static function custom_settings($custom_settings = "")
    {

        if (!$custom_settings) {
            $settings = self::settings();
            $custom_settings = $settings['custom_settings'];
        } else {
            $lang = strtoupper(\Yii::$app->language);

            if (isset($custom_settings[$lang])) {
                $custom_settings = $custom_settings[$lang];
            } else {
                return false;
            }
        }
        $func = function ($k, $v) {
            return [isset($v['key']) ? $v['key'] : '',  isset($v['value']) ? $v['value'] : ''];
        };

        return Functions::array_map_assoc($func, $custom_settings);
    }

    public static function getTranslations()
    {
        $lang = strtoupper(\Yii::$app->language);
        $file = Functions::directory() . 'translations_' . $lang . '.json';
        $site_id = isset(\Yii::$app->params['site_id']) ? '&site_id=' . \Yii::$app->params['site_id'] : '';
        $commercial = isset(\Yii::$app->params['commercial']) ? '&commercial=' . \Yii::$app->params['commercial'] : '';
        $url = Yii::$app->params['apiUrl'] . 'cms/get-translatons&user=' . Yii::$app->params['user'] . '&lang=' . $lang . $site_id . $commercial;
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $file_data =
                //file_get_contents($url);
                Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data =
                file_get_contents($file);
            //Functions::getCRMData($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function menu($name, $getUrlsFromPage = true, $getOtherSettings = true)
    {
        $lang = strtoupper(\Yii::$app->language);
        $file = Functions::directory() . 'menu_' . str_replace(' ', '_', strtolower($name)) . '.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $site_id = isset(\Yii::$app->params['site_id']) ? '&site_id=' . \Yii::$app->params['site_id'] : '';
            $url = Yii::$app->params['apiUrl'] . 'cms/menu-by-name&user=' . Yii::$app->params['user'] . '&name=' . $name . $site_id;
            $file_data =
                //file_get_contents(Yii::$app->params['apiUrl'] . 'cms/menu-by-name&user=' . Yii::$app->params['user'] . '&name=' . $name . $site_id);
                Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data =
                file_get_contents($file);
            //Functions::getCRMData($file);
        }
        $dataArr = json_decode($file_data, TRUE);
        $items = (isset($dataArr['menu_items'])) ? $dataArr['menu_items'] : '';
        $finalData = [];
        if ($items) {
            foreach ($items as $data) {
                if (isset($data['item']['id']['oid']) && $getUrlsFromPage) {
                    $pageData = self::pageBySlug(null, 'EN', $data['item']['id']['oid']);
                    $data['item']['slug'] = $pageData['slug_all'];
                    if (isset($data['children'])) {
                        foreach ($data['children'] as $key => $ch) {
                            if (isset($ch['item']['id']['oid'])) {
                                $pageData = self::pageBySlug(null, 'EN', $ch['item']['id']['oid']);
                                $data['children'][$key]['item']['slug'] = $pageData['slug_all'];
                                if ($getOtherSettings) {
                                    $url = isset($pageData['featured_image'][$lang]['name']) ? Yii::$app->params['cms_img'] . '/' . $pageData['_id'] . '/' . $pageData['featured_image'][$lang]['name'] : '';
                                    $data['children'][$key]['item']['custom_settings'] = ((isset($pageData['custom_settings'][$lang]) && is_array($pageData['custom_settings'][$lang])) ? $pageData['custom_settings'][$lang] : []);
                                    $data['children'][$key]['item']['featured_image'] = isset($pageData['featured_image']) ? $pageData['featured_image'] : '';
                                }
                            }
                        }
                    }
                    $finalData[] = $data;
                } else {
                    $finalData[] = $data;
                }
            }
        }
        $dataArr['menu_items'] = $finalData;
        return $dataArr;
    }

    public static function menuYII($id)
    {
        $lang = strtoupper(\Yii::$app->language);
        $file = Functions::directory() . 'menu.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $url = Yii::$app->params['apiUrl'] . 'cms/menu&user=' . Yii::$app->params['user'] . '&id=' . $id;
            $file_data =
                //file_get_contents(Yii::$app->params['apiUrl'] . 'cms/menu&user=' . Yii::$app->params['user'] . '&id=' . $id);
                Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data =
                file_get_contents($file);
            //Functions::getCRMData($file);
        }
        $menu = json_decode($file_data, TRUE);
        $itemsArr = [];
        foreach ($menu['menu_items'] as $key => $value) {
            if (isset($value['children']) && count($value['children']) > 0) {
                $childArr = [];
                foreach ($value['children'] as $childkey => $child) {
                    if (isset($child['children']) && count($child['children']) > 0) {
                        $childArrNested = [];
                        foreach ($child['children'] as $childkeyNested => $childNested) {
                            $childArrNested[] = ['label' => (isset($childNested['item']['title'][$lang]) && $childNested['item']['title'][$lang] != '') ? $childNested['item']['title'][$lang] : 'please set menu label', 'url' => (isset($childNested['item']['slug'][$lang]) && $childNested['item']['slug'][$lang] != '') ? '/' . $childNested['item']['slug'][$lang] : 'slug-not-set'];
                        }
                        $childArr[] = ['label' => (isset($child['item']['title'][$lang]) && $child['item']['title'][$lang] != '') ? $child['item']['title'][$lang] : 'please set menu label', 'items' => $childArrNested];
                    } else {
                        $childArr[] = ['label' => (isset($child['item']['title'][$lang]) && $child['item']['title'][$lang] != '') ? $child['item']['title'][$lang] : 'please set menu label', 'url' => (isset($child['item']['slug'][$lang]) && $child['item']['slug'][$lang] != '') ? str_replace('//', '/', ('/' . $child['item']['slug'][$lang])) : 'slug-not-set'];
                    }
                }
                $itemsArr[] = ['label' => (isset($value['item']['title'][$lang]) && $value['item']['title'][$lang] != '') ? $value['item']['title'][$lang] : 'please set menu label', 'items' => $childArr];
            } else {
                $itemsArr[] = ['label' => (isset($value['item']['title'][$lang]) && $value['item']['title'][$lang] != '') ? $value['item']['title'][$lang] : 'please set menu label', 'url' => (isset($value['item']['slug'][$lang]) && $value['item']['slug'][$lang] != '') ? str_replace('//', '/', ('/' . $value['item']['slug'][$lang])) : 'slug-not-set'];
            }
        }
        return $itemsArr;
    }

    public static function languages()
    {
        $file = Functions::directory() . 'languages.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $site_id = isset(\Yii::$app->params['site_id']) ? '&site_id=' . \Yii::$app->params['site_id'] : '';
            $url = Yii::$app->params['apiUrl'] . 'cms/languages&user=' . Yii::$app->params['user'] . $site_id;
            $file_data =
                //file_get_contents(Yii::$app->params['apiUrl'] . 'cms/languages&user=' . Yii::$app->params['user'] . $site_id);
                Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data =
                file_get_contents($file);
            //Functions::getCRMData($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function SystemLanguages()
    {
        $file = Functions::directory() . 'SystemLanguages.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $url = Yii::$app->params['apiUrl'] . 'cms/system-languages&user=' . Yii::$app->params['user'] . '&name=gogo';
            $file_data =
                //file_get_contents(Yii::$app->params['apiUrl'] . 'cms/system-languages&user=' . Yii::$app->params['user'] . '&name=gogo');
                Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data =
                file_get_contents($file);
            //Functions::getCRMData($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function UserLanguages()
    {
        $file = Functions::directory() . 'UserLanguages.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $url = Yii::$app->params['apiUrl'] . 'cms/user-languages&user=' . Yii::$app->params['user'] . '&name=gogo';
            $file_data =
                //file_get_contents(Yii::$app->params['apiUrl'] . 'cms/system-languages&user=' . Yii::$app->params['user'] . '&name=gogo');
                Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data =
                file_get_contents($file);
            //Functions::getCRMData($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function Categories()
    {
        $file = Functions::directory() . 'categories.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $site_id = isset(\Yii::$app->params['site_id']) ? '&site_id=' . \Yii::$app->params['site_id'] : '';
            $url = Yii::$app->params['apiUrl'] . 'cms/categories&user=' . Yii::$app->params['user'] . $site_id;
            $file_data =
                //file_get_contents(Yii::$app->params['apiUrl'] . 'cms/categories&user=' . Yii::$app->params['user'] . $site_id);
                Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data =
                file_get_contents($file);
            //Functions::getCRMData($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function pageBySlug($slug, $lang_slug = 'EN', $id = null, $type = 'page', $options = [])
    {
        if ($id == null) {
            $file = Functions::directory() . str_replace('/', '_', $slug) . '-' . $type . '.json';
        } else {
            $file = Functions::directory() . $id . '.json';
        }
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $query = isset($options['seoimage']) ? '&seoimage=yes' : '';
            $query .= isset($options['template']) ? '&expand=template' : '';
            if ($id == null) {
                $site_id = isset(\Yii::$app->params['site_id']) ? '&site_id=' . \Yii::$app->params['site_id'] : '';
                $url = Yii::$app->params['apiUrl'] . 'cms/page-by-slug&user=' . Yii::$app->params['user'] . '&lang=' . $lang_slug . '&slug=' . $slug . '&type=' . $type . $site_id . $query;

                //echo $file;

                $file_data =
                    //file_get_contents(Yii::$app->params['apiUrl'] . 'cms/page-by-slug&user=' . Yii::$app->params['user'] . '&lang=' . $lang_slug . '&slug=' . $slug . '&type=' . $type . $site_id);
                    Functions::getCRMData($url);
            } else {
                $url = Yii::$app->params['apiUrl'] . 'cms/page-view-by-id&user=' . Yii::$app->params['user'] . '&id=' . $id . $query;
                $file_data =
                    //file_get_contents(Yii::$app->params['apiUrl'] . 'cms/page-view-by-id&user=' . Yii::$app->params['user'] . '&id=' . $id);
                    Functions::getCRMData($url);
            }
            file_put_contents($file, $file_data);
        } else {
            $file_data =
                file_get_contents($file);
            //Functions::getCRMData($file);
        }
        $data = json_decode($file_data, TRUE);
        $lang = strtoupper(\Yii::$app->language);
        $attachment_url = isset($data['featured_image'][$lang]['name']) ? Yii::$app->params['cms_img'] . '/' . $data['_id'] . '/' . $data['featured_image'][$lang]['name'] : '';
        if (isset($options['seoimage'])) {
            $attachment_url = isset($data['featured_image'][$lang]['file_md5_name']) ? Yii::$app->params['cms_img'] . '/' . $data['_id'] . '/' . $data['featured_image'][$lang]['file_md5_name'] : $attachment_url;
        }
        // $name = isset($data['featured_image'][$lang]['name']) ? $data['featured_image'][$lang]['name'] : '';
        return [
            'featured_image' => isset($attachment_url) && !empty($attachment_url) ? Cms::ResizeImage($attachment_url) : '',
            'featured_image_200' => isset($attachment_url) && !empty($attachment_url) ? Cms::ResizeImage($attachment_url, 200) : '',
            'featured_image_seo_alt_desc' => isset($data['featured_image'][$lang]['seo_alt_desc']) ? $data['featured_image'][$lang]['seo_alt_desc'] : '',
            'featured_image_seo_meta_title' => isset($data['featured_image'][$lang]['seo_meta_title']) ? $data['featured_image'][$lang]['seo_meta_title'] : '',
            'content' => isset($data['content'][$lang]) ? $data['content'][$lang] : '',
            'title' => isset($data['title'][$lang]) ? $data['title'][$lang] : '',
            'slug' => isset($data['slug'][$lang]) ? $data['slug'][$lang] : '',
            'slug_all' => isset($data['slug']) ? $data['slug'] : '',
            'meta_title' => isset($data['meta_title'][$lang]) ? $data['meta_title'][$lang] : '',
            'meta_desc' => isset($data['meta_desc'][$lang]) ? $data['meta_desc'][$lang] : '',
            'meta_keywords' => isset($data['meta_keywords'][$lang]) ? $data['meta_keywords'][$lang] : '',
            'custom_settings' => isset($data['custom_settings']) ? $data['custom_settings'] : '',
            'created_at' => isset($data['created_at']) ? $data['created_at'] : '',
        ];
    }

    /**
     * Cms::getPage($options) For CMS page data by slug or id
     * 
     * $options =   [
     *                  ['slug'] => 'home',
     *                  ['id'] => '5eb3ebc9fe107a46744d2346',
     *                  ['lang'] => 'EN',
     *                  ['type'] => 'page',
     *                  ['seoimage'] => true,
     *                  ['template'] => true,
     *              ]
     * 
     */
    public static function getPage($options = [])
    {
        $slug = isset($options['slug']) ? $options['slug'] : null;
        $id = isset($options['id']) ? $options['id'] : null;

        if ($slug !== null || $id !== null) {
            $lang = isset($options['lang']) ? $options['lang'] : 'EN';
            $type = isset($options['type']) ? $options['type'] : 'page';
            $imagesSeo = isset($options['seoimage']) ? $options['seoimage'] : false;

            if ($id == null) {
                $file = Functions::directory() . str_replace('/', '_', $slug) . '-' . $type . '.json';
            } else {
                $file = Functions::directory() . $id . '.json';
            }
            if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
                $query = isset(\Yii::$app->params['user']) ? '&user=' . \Yii::$app->params['user'] : '';
                $query .= '&expand=template';

                if ($id == null) {
                    $query .= isset(\Yii::$app->params['site_id']) ? '&site_id=' . \Yii::$app->params['site_id'] : '';
                    $query .= '&lang=' . $lang;
                    $query .= '&slug=' . $slug;
                    $query .= '&type=' . $type;
                    $query .= $imagesSeo ? '&seoimage=yes' : '';

                    $url = Yii::$app->params['apiUrl'] . 'cms/page-by-slug' . $query;

                    $file_data = Functions::getCRMData($url);
                } else {
                    $query .= '&id=' . $id;

                    $url = Yii::$app->params['apiUrl'] . 'cms/page-view-by-id' . $query;

                    $file_data = Functions::getCRMData($url);
                }
                file_put_contents($file, $file_data);
            } else {
                $file_data = file_get_contents($file);
            }
            $data = json_decode($file_data, TRUE);

            $lang = strtoupper(\Yii::$app->language);

            $attachment_url = isset($data['featured_image'][$lang]['name']) ? Yii::$app->params['cms_img'] . '/' . $data['_id'] . '/' . $data['featured_image'][$lang]['name'] : '';
            if ($imagesSeo) {
                $attachment_url = isset($data['featured_image'][$lang]['file_md5_name']) ? Yii::$app->params['cms_img'] . '/' . $data['_id'] . '/' . $data['featured_image'][$lang]['file_md5_name'] : $attachment_url;
            }
            // $name = isset($data['featured_image'][$lang]['name']) ? $data['featured_image'][$lang]['name'] : '';
            return [
                'featured_image' => isset($attachment_url) && !empty($attachment_url) ? Cms::ResizeImage($attachment_url) : '',
                'featured_image_200' => isset($attachment_url) && !empty($attachment_url) ? Cms::ResizeImage($attachment_url, 200) : '',
                'featured_image_seo_alt_desc' => isset($data['featured_image'][$lang]['seo_alt_desc']) ? $data['featured_image'][$lang]['seo_alt_desc'] : '',
                'featured_image_seo_meta_title' => isset($data['featured_image'][$lang]['seo_meta_title']) ? $data['featured_image'][$lang]['seo_meta_title'] : '',
                'content' => isset($data['content'][$lang]) ? $data['content'][$lang] : '',
                'title' => isset($data['title'][$lang]) ? $data['title'][$lang] : '',
                'slug' => isset($data['slug'][$lang]) ? $data['slug'][$lang] : '',
                'slug_all' => isset($data['slug']) ? $data['slug'] : '',
                'meta_title' => isset($data['meta_title'][$lang]) ? $data['meta_title'][$lang] : '',
                'meta_desc' => isset($data['meta_desc'][$lang]) ? $data['meta_desc'][$lang] : '',
                'meta_keywords' => isset($data['meta_keywords'][$lang]) ? $data['meta_keywords'][$lang] : '',
                'custom_settings' => isset($data['custom_settings']) ? $data['custom_settings'] : '',
                'created_at' => isset($data['created_at']) ? $data['created_at'] : '',
                'view_path' => isset($data['template']['viewPath']) ? $data['template']['viewPath'] : '',
            ];
        }
    }

    public static function postById($id)
    {
        $file = Functions::directory() . $id . '.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $url = Yii::$app->params['apiUrl'] . 'cms/page-view-by-id&user=' . Yii::$app->params['user'] . '&id=' . $id;
            $file_data =
                //file_get_contents(Yii::$app->params['apiUrl'] . 'cms/page-view-by-id&user=' . Yii::$app->params['user'] . '&id=' . $id);
                Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data =
                file_get_contents($file);
            //Functions::getCRMData($file);
        }
        $data = json_decode($file_data, TRUE);
        $lang = strtoupper(\Yii::$app->language);
        $url = isset($data['featured_image'][$lang]['name']) ? Yii::$app->params['cms_img'] . '/' . $data['_id'] . '/' . $data['featured_image'][$lang]['name'] : '';
        // $name = isset($data['featured_image'][$lang]['name']) ? $data['featured_image'][$lang]['name'] : '';
        return [
            'featured_image' => isset($data['featured_image'][$lang]['name']) ? Cms::ResizeImage($url) : '',
            'featured_image_200' => isset($data['featured_image'][$lang]['name']) ? Cms::ResizeImage($url, 200) : '',
            'content' => isset($data['content'][$lang]) ? $data['content'][$lang] : '',
            'title' => isset($data['title'][$lang]) ? $data['title'][$lang] : '',
            'slug' => isset($data['slug'][$lang]) ? $data['slug'][$lang] : '',
            'slug_all' => isset($data['slug']) ? $data['slug'] : '',
            'meta_title' => isset($data['meta_title'][$lang]) ? $data['meta_title'][$lang] : '',
            'meta_desc' => isset($data['meta_desc'][$lang]) ? $data['meta_desc'][$lang] : '',
            'meta_keywords' => isset($data['meta_keywords'][$lang]) ? $data['meta_keywords'][$lang] : '',
            'custom_settings' => isset($data['custom_settings']) ? $data['custom_settings'] : '',
            'created_at' => isset($data['created_at']) ? $data['created_at'] : '',
        ];
    }

    public static function setParams()
    {
        $root =  realpath(dirname(__FILE__) . '/../../../../../');
        Yii::setAlias('@webroot', $root . '/web');
        $params = require $root . '/config/params.php';
        $url_array = explode('/', $_SERVER['REQUEST_URI']);
        \Yii::$app->params['user'] =    $params['user'];
        \Yii::$app->params['site_id'] = $params['site_id'];
        \Yii::$app->params['apiUrl'] =  $params['apiUrl'];
        \Yii::$app->language  = (isset($url_array[1]) ? $url_array[1] : 'en');
    }


    public static function Slugs($name)
    {
        $file = Functions::directory() . 'slugs' . str_replace(' ', '_', strtolower($name)) . '.json';
        $site_id = isset(\Yii::$app->params['site_id']) ? '&site_id=' . \Yii::$app->params['site_id'] : '';
        $url = Yii::$app->params['apiUrl'] . 'cms/get-slugs&user=' . \Yii::$app->params['user'] . $site_id;
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $file_data = Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else
            $file_data = file_get_contents($file);

        $dataEach = json_decode($file_data, true);
        $lang = strtoupper(\Yii::$app->language);
        $retdata = [];
        $array = [];

        if (!is_array($dataEach) || count($dataEach) <= 0) {
            die('Error Getting CMS Data');
        }

        foreach ($dataEach as $key => $data) {
            $array['type'] = isset($data['type']) ? $data['type'] : '';
            $array['slug'] = isset($data['slug'][$lang]) ? $data['slug'][$lang] : '';
            $array['slug_all'] = isset($data['slug']) ? $data['slug'] : '';
            $retdata[] = $array;
        }

        return $retdata;
    }

    public static function slugsWithTemplate($params = [])
    {
        $name = isset($params['name']) ? $params['name'] : 'all';
        $file = Functions::directory() . 'slugs_with_templates_for_' . str_replace(' ', '_', strtolower($name)) . '.json';
        $query = isset(\Yii::$app->params['user']) ? '&user=' . \Yii::$app->params['user'] : '';
        $query .= isset(\Yii::$app->params['site_id']) ? '&site_id=' . \Yii::$app->params['site_id'] : '';
        $query .= '&expand=template';

        $url = Yii::$app->params['apiUrl'] . 'cms/get-slugs-with-templates' . $query;
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $file_data = file_get_contents($url);
            file_put_contents($file, $file_data);
        } else
            $file_data = file_get_contents($file);
        $dataEach = json_decode($file_data, true);
        $retdata = [];

        if (!is_array($dataEach) || count($dataEach) <= 0)
            die('Error Getting CMS Data');
        foreach ($dataEach as $data) {
            $array['slug_all'] = isset($data['slug']) ? $data['slug'] : '';
            $array['type'] = isset($data['type']) ? $data['type'] : '';
            $array['template_action'] = isset($data['template']['template_action']) ? $data['template']['template_action'] : '';
            $retdata[] = $array;
        }
        return $retdata;
    }

    public static function cmsRules()
    {
        // $redirects = require __DIR__ . '/redirects.php';
        // $actual_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        // foreach ($redirects as $key => $val) {
        //     if ($key == $_SERVER['REQUEST_URI']) {
        //         header('Location:' . $val);
        //         die;
        //     }
        // }

        self::setParams(); // to set some config because config not loaded yet in from web

        $cmsModel = self::slugsWithTemplate();
        $routeArray = [];

        foreach ($cmsModel as $row) {
            if (!empty($row['template_action'])) {
                if (
                    strpos($row['template_action'], '/view')
                    || strpos($row['template_action'], '/blog-post')
                ) {
                    foreach ($row['slug_all'] as $key => $val) {
                        if ($val) {
                            $routeArray[] = [
                                'pattern'  => $val . '/<title>',
                                'route'    => $row['template_action'],
                                'defaults' => ['slug' => $val],
                            ];
                        }
                    }
                } else {
                    foreach ($row['slug_all'] as $key => $val) {
                        if ($val) {
                            $routeArray[] = [
                                'pattern'  => $val,
                                'route'    => $row['template_action'],
                                'defaults' => ['slug' => $val],
                            ];
                        }
                    }
                }
            }
        }

        /** Site controller */
        $routeArray['/<title>'] = 'site/page';
        // $routeArray['/<title>' . '/<title1>'] = 'site/page';
        // $routeArray['/<title>' . '/<title1>' . '/<title2>'] = 'site/page';
        // $routeArray['/<title>' . '/<title1>' . '/<title2>' . '/<title3>'] = 'site/page';

        // print_r($routeArray); die;
        return $routeArray;
    }

    /* Template Code started  */

    public function updateAgencyPageTemplates()
    {
        $files = (array) self::get_files('php', 1, true);

        foreach ($files as $file => $full_path) {
            if (!preg_match('|Template Name:(.*)$|mi', file_get_contents($full_path), $header)) {
                continue;
            }

            preg_match('|Action Path:(.*)$|mi', file_get_contents($full_path), $action);

            /*  For multiple types like page and post Code start */

            $types = array('page');
            if (preg_match('|Template Post Type:(.*)$|mi', file_get_contents($full_path), $type)) {
                $types = explode(',', self::_cleanup_header_comment($type[1]));
            }

            foreach ($types as $type) {
                $type = self::sanitize_key($type);
                // if ( ! isset( $post_templates[ $type ] ) ) {
                // 	$post_templates[ $type ] = array();
                // }

                $post_templates[$type][str_replace(".php", "", $file)] = array(
                    "label" => self::_cleanup_header_comment($header[1]),
                    "action" => isset($action[1]) ? self::_cleanup_header_comment($action[1]) : ''
                );
            }

            /*  Multiple types Code ends  */
        }

        echo '<pre>';
        print_r($post_templates);
        die;
    }

    public function get_files($type = null, $depth = 0, $search_parent = false)
    {
        $files = (array) self::scandir(self::getViewPath(), $type, $depth);
        return $files;
    }

    public function getViewPath()
    {
        return Yii::getAlias('../web/themes/optima_theme/views');
    }

    private static function scandir($path, $extensions = null, $depth = 0, $relative_path = '')
    {
        if (!is_dir($path)) {
            return false;
        }

        if ($extensions) {
            $extensions  = (array) $extensions;
            $_extensions = implode('|', $extensions);
        }

        $relative_path = self::trailingslashit($relative_path);
        if ('/' == $relative_path) {
            $relative_path = '';
        }

        $results = scandir($path);
        $files   = array();

        /**
         * Filters the array of excluded directories and files while scanning theme folder.
         *
         * @since 4.7.4
         *
         * @param string[] $exclusions Array of excluded directories and files.
         */
        $exclusions = [];

        foreach ($results as $result) {
            if ('.' == $result[0] || in_array($result, $exclusions, true)) {
                continue;
            }
            if (is_dir($path . '/' . $result)) {
                if (!$depth) {
                    continue;
                }
                $found = self::scandir($path . '/' . $result, $extensions, $depth - 1, $relative_path . $result);
                $files = array_merge_recursive($files, $found);
            } elseif (!$extensions || preg_match('~\.(' . $_extensions . ')$~', $result)) {
                $files[$relative_path . $result] = $path . '/' . $result;
            }
        }

        return $files;
    }

    private static function trailingslashit($string)
    {
        return rtrim($string, '/\\') . '/';
    }

    private static function sanitize_key($key)
    {
        $raw_key = $key;
        $key     = strtolower($key);
        $key     = preg_replace('/[^a-z0-9_\-]/', '', $key);
        return $key;
    }

    private static function _cleanup_header_comment($str)
    {
        return trim(preg_replace('/\s*(?:\*\/|\?>).*/', '', $str));
    }

    /* Template Code Ended  */

    public static function CacheImage($url, $name, $size = 1200)
    {
        $settings = self::settings();
        $filesaved = Functions::directory() . $size . '_' . $name;
        if (isset(Yii::$app->params['ImageFrom']) && Yii::$app->params['ImageFrom'] == 'remote') {
            return self::$image_url . $settings['site_id'] . '/' . $size . '/' . $name;
        } else {
            if (!file_exists($filesaved) || (file_exists($filesaved) && time() - filemtime($filesaved) > 360 * 3600)) {
                $handle = @fopen($url, 'r');
                if (!$handle) {
                    $url = self::$image_url . $settings['site_id'] . '/' . $size . '/' . $name;
                }
                $file_data =
                    //@file_get_contents($url);
                    Functions::getCRMData($url);
                file_put_contents($filesaved, $file_data);
            }
            return '/uploads/temp/' . $size . '_' . $name;
        }
    }

    public static function ResizeImage($url, $size = 1200, $type = 'cms_medias')
    {
        $settings = self::settings();

        $url_array = explode('/', $url);
        $name = end($url_array);

        if ($type == 'user') {
            return str_replace($name, $size . '/' . $name, $url);
        } else if ($type == 'property') {
            return str_replace(prev($url_array), $size, $url);
        }

        return self::$image_url . $settings['site_id'] . '/' . $size . '/' . $name;
    }

    public static function iconLogo($name)
    {
        $file = Functions::directory() . $name . '.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $file_data = Yii::$app->params['rootUrl'] . 'uploads/cms_settings/' . Yii::$app->params['template'] . '/' . $name;
            file_put_contents($file, $file_data);
        } else {
            $file_data =
                file_get_contents($file);
            //Functions::getCRMData($file);
        }
        return $file_data;
    }

    public static function postTypes($name, $category = null, $forRoutes = null, $pageSize = 10, $imageseo = false, $options = [])
    {
        $file_name = $name;
        if ($name != 'page' && $pageSize == false)
            $file_name = $name . '-all';
        $file = Functions::directory() . str_replace(' ', '_', strtolower(Functions::clean($file_name))) . str_replace(' ', '_', strtolower(Functions::clean($category))) . '.json';

        $query = isset(\Yii::$app->params['user']) ? '&user=' . \Yii::$app->params['user'] : '';
        $query .= isset(\Yii::$app->params['site_id']) ? '&site_id=' . \Yii::$app->params['site_id'] : '';
        $query .= is_numeric($name) ? '&post_type_id=' . $name : '&post_type=' . $name;

        if ($name == 'page' || $pageSize == false)
            $query .= '&page-size=false';
        if ($pageSize != false) {
            $query .= '&page-size=' . $pageSize;
            if (isset($options['page'])) {
                $query .= '&page=' . $options['page'];
            }
        }
        if ($category != null)
            $query .= '&category=' . $category;
        if ($imageseo)
            $query .= '&seoimage=yes';

        $cache = isset($options['cache']) ? $options['cache'] : true;

        $url = Yii::$app->params['apiUrl'] . 'cms/posts' . $query;

        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600) || !$cache) {
            $file_data = Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data = file_get_contents($file);
        }
        $header = get_headers($url, 1);
        $dataEach = json_decode($file_data, TRUE);
        $lang = strtoupper(\Yii::$app->language);
        $retdata = [];
        $array = [];
        foreach ($dataEach as $key => $data) {
            if (isset($header['X-Pagination-Total-Count'])) {
                $array['totalCount'] = $header['X-Pagination-Total-Count'];
            }
            $attachment_url = isset($data['featured_image'][$lang]['name']) ? Yii::$app->params['cms_img'] . '/' . $data['_id'] . '/' . $data['featured_image'][$lang]['name'] : '';
            if ($imageseo) {
                $attachment_url = isset($data['featured_image'][$lang]['file_md5_name']) ? Yii::$app->params['cms_img'] . '/' . $data['_id'] . '/' . $data['featured_image'][$lang]['file_md5_name'] : $attachment_url;
            }
            // $name = isset($data['featured_image'][$lang]['name']) ? $data['featured_image'][$lang]['name'] : '';
            if ($forRoutes == true) {
                $array['featured_image'] = $attachment_url;
            } else {
                $array['featured_image'] = isset($attachment_url) && !empty($attachment_url) ? Cms::ResizeImage($attachment_url) : '';
            }
            $array['featured_image_seo_alt_desc'] = isset($data['featured_image'][$lang]['seo_alt_desc']) ? $data['featured_image'][$lang]['seo_alt_desc'] : '';
            $array['featured_image_seo_meta_title'] = isset($data['featured_image'][$lang]['seo_meta_title']) ? $data['featured_image'][$lang]['seo_meta_title'] : '';
            $array['content'] = isset($data['content'][$lang]) ? $data['content'][$lang] : '';
            $array['created_at'] = isset($data['created_at']) ? $data['created_at'] : '';
            $array['title'] = isset($data['title'][$lang]) ? $data['title'][$lang] : '';
            $array['slug'] = isset($data['slug'][$lang]) ? $data['slug'][$lang] : '';
            $array['slug_all'] = isset($data['slug']) ? $data['slug'] : '';
            $array['meta_title'] = isset($data['meta_title'][$lang]) ? $data['meta_title'][$lang] : '';
            $array['meta_desc'] = isset($data['meta_desc'][$lang]) ? $data['meta_desc'][$lang] : '';
            $array['meta_keywords'] = isset($data['meta_keywords'][$lang]) ? $data['meta_keywords'][$lang] : '';
            $array['custom_settings'] = isset($data['custom_settings']) ? $data['custom_settings'] : '';
            $array['categories'] = isset($data['categories']) ? $data['categories'] : [];
            $retdata[] = $array;
        }
        return $retdata;
    }

    public static function getUsers()
    {
        $file = Functions::directory() . 'users_data.json';
        $url = Yii::$app->params['apiUrl'] . 'cms/users&user=' . Yii::$app->params['user'];
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600)) {
            $file_data =
                //file_get_contents($url);
                Functions::getCRMData($url);
            file_put_contents($file, $file_data);
        } else {
            $file_data =
                file_get_contents($file);
            //Functions::getCRMData($file);
        }

        //  $url = Yii::$app->params['cms_img'] . '/' . $data['_id'] . '/' . $data['featured_image'][$lang]['name'];
        //  $name = isset($data['featured_image'][$lang]['name']) ? $data['featured_image'][$lang]['name'] : '';
        //  return [
        //      'featured_image' => isset($data['featured_image'][$lang]['name']) ? Cms::CacheImage($url, $name) : '',
        //      'content' => isset($data['content'][$lang]) ? $data['content'][$lang] : '',
        //      'title' => isset($data['title'][$lang]) ? $data['title'][$lang] : '',
        //      'slug' => isset($data['slug'][$lang]) ? $data['slug'][$lang] : '',
        //      'slug_all' => isset($data['slug']) ? $data['slug'] : '',
        //      'meta_title' => isset($data['meta_title'][$lang]) ? $data['meta_title'][$lang] : '',
        //      'meta_desc' => isset($data['meta_desc'][$lang]) ? $data['meta_desc'][$lang] : '',
        //      'meta_keywords' => isset($data['meta_keywords'][$lang]) ? $data['meta_keywords'][$lang] : '',
        //      'custom_settings' => isset($data['custom_settings']) ? $data['custom_settings'] : '',
        //      'created_at' => isset($data['created_at']) ? $data['created_at'] : '',
        //  ];
        $data = json_decode($file_data, true);
        $users = [];
        foreach ($data['users'] as $user) {
            $users[] = [
                'name' => (isset($user['firstname']) ? $user['firstname'] : '') . ' ' . (isset($user['lastname']) ? $user['lastname'] : ''),
                'dp' => (isset($user['dp']) && $user['dp']) ? Cms::ResizeImage(self::$image_url_users . $user['_id'] . '/' . $user['dp'], 300, 'user') : '',
                'number_of_listing' => (isset($user['number_of_listing']) ? $user['number_of_listing'] : ''),
                'number_of_rent' => (isset($user['number_of_rent']) ? $user['number_of_rent'] : ''),
                'number_of_sales' => (isset($user['number_of_sales']) ? $user['number_of_sales'] : ''),
            ];
        }
        return $users;
    }


    public static function clean($string)
    {
        trigger_error('Method CMS::' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);

        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

        return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
    }
}
