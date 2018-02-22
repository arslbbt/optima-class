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
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'cms/get-translatons&user=' . Yii::$app->params['user'] . '&lang=' . $lang);
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function menu($name)
    {
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
        return json_decode($file_data, TRUE);
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
                    $childArr[] = ['label' => (isset($child['item']['title'][$lang]) && $child['item']['title'][$lang] != '') ? $child['item']['title'][$lang] : 'please set menu label', 'url' => (isset($child['item']['slug'][$lang]) && $child['item']['slug'][$lang] != '') ? $child['item']['slug'][$lang] : 'slug-not-set'];
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
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'cms/languages&user=' . Yii::$app->params['user'] . '&name=gogo');
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function pageBySlug($slug)
    {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
            mkdir($webroot . '/uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        $file = $webroot . '/uploads/temp/' . str_replace('/', '_', $slug) . '.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'cms/page-by-slug&user=' . Yii::$app->params['user'] . '&slug=' . $slug);
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        $data = json_decode($file_data, TRUE);
        $lang = strtoupper(\Yii::$app->language);
        $url = isset($data['featured_image'][$lang]['name']) ? 'https://my.optima-crm.com/uploads/cms_pages/' . $data['_id'] . '/' . $data['featured_image'][$lang]['name'] : '';
        $name = isset($data['featured_image'][$lang]['name']) ? $data['featured_image'][$lang]['name'] : '';
        return [
            'featured_image' => isset($data['featured_image'][$lang]['name']) ? Cms::CacheImage($url, $name) : '',
            'content' => isset($data['content'][$lang]) ? $data['content'][$lang] : '',
            'title' => isset($data['title'][$lang]) ? $data['title'][$lang] : '',
            'slug' => isset($data['slug'][$lang]) ? $data['slug'][$lang] : '',
            'meta_title' => isset($data['meta_title'][$lang]) ? $data['meta_title'][$lang] : '',
            'meta_desc' => isset($data['meta_desc'][$lang]) ? $data['meta_desc'][$lang] : '',
            'meta_keywords' => isset($data['meta_keywords'][$lang]) ? $data['meta_keywords'][$lang] : '',
            'custom_settings' => isset($data['custom_settings']) ? $data['custom_settings'] : '',
        ];
    }

    public static function postTypes($name)
    {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
            mkdir($webroot . '/uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        $file = $webroot . '/uploads/temp/' . str_replace(' ', '_', strtolower($name)) . '.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'cms/posts&user=' . Yii::$app->params['user'] . '&post_type=' . $name);
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
            $url = isset($data['featured_image'][$lang]['name']) ? 'https://my.optima-crm.com/uploads/cms_pages/' . $data['_id'] . '/' . $data['featured_image'][$lang]['name'] : '';
            $name = isset($data['featured_image'][$lang]['name']) ? $data['featured_image'][$lang]['name'] : '';
            $array['featured_image'] = isset($data['featured_image'][$lang]['name']) ? Cms::CacheImage($url, $name) : '';
            $array['content'] = isset($data['content'][$lang]) ? $data['content'][$lang] : '';
            $array['title'] = isset($data['title'][$lang]) ? $data['title'][$lang] : '';
            $array['slug'] = isset($data['slug'][$lang]) ? $data['slug'][$lang] : '';
            $array['meta_title'] = isset($data['meta_title'][$lang]) ? $data['meta_title'][$lang] : '';
            $array['meta_desc'] = isset($data['meta_desc'][$lang]) ? $data['meta_desc'][$lang] : '';
            $array['meta_keywords'] = isset($data['meta_keywords'][$lang]) ? $data['meta_keywords'][$lang] : '';
            $array['custom_settings'] = isset($data['custom_settings']) ? $data['custom_settings'] : '';
            $retdata[] = $array;
        }
        return $retdata;
    }

    public static function CacheImage($url, $name)
    {

        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
            mkdir($webroot . '/uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        $filesaved = $webroot . '/uploads/temp/' . $name;
        if (!file_exists($filesaved) || (file_exists($filesaved) && time() - filemtime($filesaved) > 360 * 3600))
        {
            $file_data = file_get_contents($url);
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

}
