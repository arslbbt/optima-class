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
class Cms extends Model
{

    public $username;
    public $password;
    public $rememberMe = true;
    private $_user = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
        ];
    }

    public static function settings()
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

    public static function menu()
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
        $file = $webroot . '/uploads/temp/menu.json';
        if (!file_exists($file) || (file_exists($file) && time() - filemtime($file) > 2 * 3600))
        {
            $file_data = file_get_contents(Yii::$app->params['apiUrl'] . 'cms/menu-by-name&user=' . Yii::$app->params['user'] . '&name=gogo');
            file_put_contents($file, $file_data);
        }
        else
        {
            $file_data = file_get_contents($file);
        }
        return json_decode($file_data, TRUE);
    }

    public static function languages()
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

}
