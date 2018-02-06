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
        if (!fileExists($file))
        {
            
        }
//        $filename = '../uploads/temp/home_areas.json';
//        if (!file_exists($filename))
//        {
//            $jsonDatahomeAreas = file_get_contents('https://my.optima-crm.com/yiiapp/frontend/web/index.php?r=cms/posts&user=' . Yii::$app->params['user'] . '&post_type=Home_Area');
//            file_put_contents($filename, $jsonDatahomeAreas);
//        }
//        if (time() - filemtime($filename) > 2 * 3600)
//        {
//            // file older than 2 hours
//            $jsonDatahomeAreas = file_get_contents('https://my.optima-crm.com/yiiapp/frontend/web/index.php?r=cms/posts&user=' . Yii::$app->params['user'] . '&post_type=Home_Area');
//            file_put_contents($filename, $jsonDatahomeAreas);
//        }
//        else
//        {
//            // file younger than 2 hours
//            $jsonDatahomeAreas = file_get_contents($filename);
//        }
//        $home_areas = json_decode($jsonDatahomeAreas, TRUE);
//        if (!$session->has('home_area'))
//        {
//            $session->set('home_area', isset($home_areas[0]) ? $home_areas[0] : []);
//        }
//        $home_area = $this->view->params['home_area'] = $session->get('home_area');
        return [];
    }

}
