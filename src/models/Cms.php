<?php

namespace optima\models;

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

    public static function settings()
    {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
            mkdir($webroot . '/uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        $file = $webroot . '/uploads/temp/settings.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'cms/setting&user=' . Yii::$app->params['user'] . '&id=' . Yii::$app->params['template']);
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function getTranslations()
    {
        $lang = strtoupper(\Yii::$app->language);
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
            mkdir($webroot . '/uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        $file = $webroot . '/uploads/temp/translations_' . $lang . '.json';
        $url = Yii::$app->params['apiUrl'] . 'cms/get-translatons&user=' . Yii::$app->params['user'] . '&lang=' . $lang;
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = file_get_contents($url);
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function menu($name, $getUrlsFromPage = true, $getOtherSettings = false)
    {
        $lang = strtoupper(\Yii::$app->language);
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
            mkdir($webroot . '/uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        $file = $webroot . '/uploads/temp/menu_' . str_replace(' ', '_', strtolower($name)) . '.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'cms/menu-by-name&user=' . Yii::$app->params['user'] . '&name=' . $name);
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        $dataArr = json_decode($file_data, TRUE);
        $items = $dataArr['menu_items'];
        $finalData = [];
        if ($items)
        {
            foreach ($items as $data)
            {
                if (isset($data['item']['id']['oid']) && $getUrlsFromPage)
                {
                    $pageData = self::pageBySlug(null, 'EN', $data['item']['id']['oid']);
                    $data['item']['slug'] = $pageData['slug_all'];
                    if (isset($data['children']))
                    {
                        foreach ($data['children'] as $key => $ch)
                        {
                            if (isset($ch['item']['id']['oid']))
                            {
                                $pageData = self::pageBySlug(null, 'EN', $ch['item']['id']['oid']);
                                $data['children'][$key]['item']['slug'] = $pageData['slug_all'];
                                if ($getOtherSettings)
                                {
                                    $url = isset($pageData['featured_image'][$lang]['name']) ? Yii::$app->params['cms_img'] . '/' . $pageData['_id'] . '/' . $pageData['featured_image'][$lang]['name'] : '';
                                    $data['children'][$key]['item']['custom_settings'] = ((isset($pageData['custom_settings'][$lang]) && is_array($pageData['custom_settings'][$lang])) ? $pageData['custom_settings'][$lang] : []);
                                    $data['children'][$key]['item']['featured_image'] = isset($pageData['featured_image']) ? $pageData['featured_image'] : '';
                                }
                            }
                        }
                    }
                    $finalData[] = $data;
                }
                else
                {
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
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
            mkdir($webroot . '/uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        $file = $webroot . '/uploads/temp/menu.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'cms/menu&user=' . Yii::$app->params['user'] . '&id=' . $id);
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        $menu = json_decode($file_data, TRUE);
        $itemsArr = [];
        foreach ($menu['menu_items'] as $key => $value)
        {
            if (isset($value['children']) && count($value['children']) > 0)
            {
                $childArr = [];
                foreach ($value['children'] as $childkey => $child)
                {
                    if (isset($child['children']) && count($child['children']) > 0)
                    {
                        $childArrNested = [];
                        foreach ($child['children'] as $childkeyNested => $childNested)
                        {
                            $childArrNested[] = ['label' => (isset($childNested['item']['title'][$lang]) && $childNested['item']['title'][$lang] != '') ? $childNested['item']['title'][$lang] : 'please set menu label', 'url' => (isset($childNested['item']['slug'][$lang]) && $childNested['item']['slug'][$lang] != '') ? $childNested['item']['slug'][$lang] : 'slug-not-set'];
                        }
                        $childArr[] = ['label' => (isset($child['item']['title'][$lang]) && $child['item']['title'][$lang] != '') ? $child['item']['title'][$lang] : 'please set menu label', 'items' => $childArrNested];
                    }
                    else
                    {
                        $childArr[] = ['label' => (isset($child['item']['title'][$lang]) && $child['item']['title'][$lang] != '') ? $child['item']['title'][$lang] : 'please set menu label', 'url' => (isset($child['item']['slug'][$lang]) && $child['item']['slug'][$lang] != '') ? $child['item']['slug'][$lang] : 'slug-not-set'];
                    }
                }
                $itemsArr[] = ['label' => (isset($value['item']['title'][$lang]) && $value['item']['title'][$lang] != '') ? $value['item']['title'][$lang] : 'please set menu label', 'items' => $childArr];
            }
            else
            {
                $itemsArr[] = ['label' => (isset($value['item']['title'][$lang]) && $value['item']['title'][$lang] != '') ? $value['item']['title'][$lang] : 'please set menu label', 'url' => (isset($value['item']['slug'][$lang]) && $value['item']['slug'][$lang] != '') ? $value['item']['slug'][$lang] : 'slug-not-set'];
            }
        }
        return $itemsArr;
    }

    public static function languages()
    {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
            mkdir($webroot . '/uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        $file = $webroot . '/uploads/temp/languages.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'cms/languages&user=' . Yii::$app->params['user']);
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function SystemLanguages()
    {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
            mkdir($webroot . '/uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        $file = $webroot . '/uploads/temp/SystemLanguages.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'cms/system-languages&user=' . Yii::$app->params['user'] . '&name=gogo');
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function Categories()
    {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
            mkdir($webroot . '/uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        $file = $webroot . '/uploads/temp/categories.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'cms/categories&user=' . Yii::$app->params['user'] . '&name=gogo');
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function pageBySlug($slug, $lang_slug = 'EN', $id = null, $type = 'page')
    {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
            mkdir($webroot . '/uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        if ($id == null)
        {
            $file = $webroot . '/uploads/temp/' . str_replace('/', '_', $slug) . '-' . $type . '.json';
        }
        else
        {
            $file = $webroot . '/uploads/temp/' . $id . '.json';
        }
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            if ($id == null)
            {
                $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'cms/page-by-slug&user=' . Yii::$app->params['user'] . '&lang=' . $lang_slug . '&slug=' . $slug . '&type=' . $type);
            }
            else
            {
                $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'cms/page-view-by-id&user=' . Yii::$app->params['user'] . '&id=' . $id);
            }
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        $data = json_decode($file_data, TRUE);
        $lang = strtoupper(\Yii::$app->language);
        $url = isset($data['featured_image'][$lang]['name']) ? Yii::$app->params['cms_img'] . '/' . $data['_id'] . '/' . $data['featured_image'][$lang]['name'] : '';
        $name = isset($data['featured_image'][$lang]['name']) ? $data['featured_image'][$lang]['name'] : '';
        return [
            'featured_image' => isset($data['featured_image'][$lang]['name']) ? Cms::CacheImage($url, $name) : '',
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

    public static function Slugs($name)
    {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
            mkdir($webroot . '/uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        $file = $webroot . '/uploads/temp/slugs' . str_replace(' ', '_', strtolower($name)) . '.json';
        $url = Yii::$app->params['apiUrl'] . 'cms/get-slugs&user=' . Yii::$app->params['user'];
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = file_get_contents($url);
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        $dataEach = json_decode($file_data, TRUE);
        $lang = strtoupper(\Yii::$app->language);

        $retdata = [];
        $array = [];
        foreach ($dataEach as $key => $data)
        {
            $array['slug'] = isset($data['slug'][$lang]) ? $data['slug'][$lang] : '';
            $array['slug_all'] = isset($data['slug']) ? $data['slug'] : '';
            $retdata[] = $array;
        }
        return $retdata;
    }

    public static function postTypes($name, $category = null, $forRoutes = null)
    {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
            mkdir($webroot . '/uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        $file = $webroot . '/uploads/temp/' . str_replace(' ', '_', strtolower(self::clean($name))) . str_replace(' ', '_', strtolower(self::clean($category))) . '.json';
        $query = '&post_type=' . $name;
        if ($name == 'page')
            $query .= '&page-size=false';
        if ($category != null)
        {
            $query .= '&category=' . $category;
        }
        $url = Yii::$app->params['apiUrl'] . 'cms/posts&user=' . Yii::$app->params['user'] . $query;
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = file_get_contents($url);
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        $dataEach = json_decode($file_data, TRUE);
        $lang = strtoupper(\Yii::$app->language);

        $retdata = [];
        $array = [];
        foreach ($dataEach as $key => $data)
        {
            $url = isset($data['featured_image'][$lang]['name']) ? Yii::$app->params['cms_img'] . '/' . $data['_id'] . '/' . $data['featured_image'][$lang]['name'] : '';
            $name = isset($data['featured_image'][$lang]['name']) ? $data['featured_image'][$lang]['name'] : '';
            if ($forRoutes == true)
            {
                $array['featured_image'] = $url;
            }
            else
            {
                $array['featured_image'] = isset($data['featured_image'][$lang]['name']) ? Cms::CacheImage($url, $name) : '';
            }
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

    public static function CacheImage($url, $name)
    {
        $settings = self::settings();
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
            mkdir($webroot . '/uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        $filesaved = $webroot . '/uploads/temp/' . $name;
        if (!file_exists($filesaved) || (file_exists($filesaved) && time() - filemtime($filesaved) > 360 * 3600))
        {
            $handle = @fopen($url, 'r');
            if (!$handle)
            {
                $url = 'https://images.optima-crm.com/resize/cms_medias/' . $settings['site_id'] . '/1200/' . $name;
            }
            $file_data = @file_get_contents($url);
            file_put_contents($filesaved, $file_data);
        }
        return '/uploads/temp/' . $name;
    }

    public static function iconLogo($name)
    {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . 'Uploads/'))
            mkdir($webroot . 'Uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        $file = $webroot . '/uploads/temp/' . $name . '.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = Yii::$app->params['rootUrl'] . 'uploads/cms_settings/' . Yii::$app->params['template'] . '/' . $name;
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        return $file_data;
    }

    public static function clean($string)
    {
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

        return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
    }

    public static function getUsers()
    {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . 'Uploads/'))
            mkdir($webroot . 'Uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        $file = $webroot . '/uploads/temp/users_data.json';
        $url = Yii::$app->params['apiUrl'] . 'cms/users&user=' . Yii::$app->params['user'];
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = file_get_contents($url);
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }

//        $url = Yii::$app->params['cms_img'] . '/' . $data['_id'] . '/' . $data['featured_image'][$lang]['name'];
//        $name = isset($data['featured_image'][$lang]['name']) ? $data['featured_image'][$lang]['name'] : '';
//        return [
//            'featured_image' => isset($data['featured_image'][$lang]['name']) ? Cms::CacheImage($url, $name) : '',
//            'content' => isset($data['content'][$lang]) ? $data['content'][$lang] : '',
//            'title' => isset($data['title'][$lang]) ? $data['title'][$lang] : '',
//            'slug' => isset($data['slug'][$lang]) ? $data['slug'][$lang] : '',
//            'slug_all' => isset($data['slug']) ? $data['slug'] : '',
//            'meta_title' => isset($data['meta_title'][$lang]) ? $data['meta_title'][$lang] : '',
//            'meta_desc' => isset($data['meta_desc'][$lang]) ? $data['meta_desc'][$lang] : '',
//            'meta_keywords' => isset($data['meta_keywords'][$lang]) ? $data['meta_keywords'][$lang] : '',
//            'custom_settings' => isset($data['custom_settings']) ? $data['custom_settings'] : '',
//            'created_at' => isset($data['created_at']) ? $data['created_at'] : '',
//        ];
        $data = json_decode($file_data, true);
        $users = [];
        foreach ($data['users'] as $user)
        {
            $users[] = [
                'name' => (isset($user['firstname']) ? $user['firstname'] : '') . ' ' . (isset($user['lastname']) ? $user['lastname'] : ''),
                'dp' => (isset($user['dp']) && $user['dp']) ? Cms::CacheImage(Yii::$app->params['cms_img'] . '/' . $user['_id'] . '/' . $user['dp'], $user['dp']) : '',
            ];
        }
        return $users;
    }

}
