<?php

namespace optima\components;

use Yii;
use yii\base\Component;
use Cocur\Slugify\Slugify;
use optima\models\Cms;
use yii\helpers\Url;

class Urlhelper extends Component
{

    public static function slug($tag)
    {
        return Cms::getSlugByTagName($tag);
    }

    public static function propertiesListingUrl()
    {
        return Url::to(self::slug('propertyList'));
    }

    public static function propertyDetailsSlug()
    {
        return self::slug('propertyDetails');
    }

    public static function developmentsListingUrl()
    {
        return Url::to(self::slug('developmentsListing'));
    }

    public static function developmentDetailsSlug()
    {
        return self::slug('developmentDetails');
    }

    public static function blogListingUrl()
    {
        return Url::to(self::slug('blogListing'));
    }

    public static function blogDetailsSlug()
    {
        return self::slug('blogDetails');
    }

    /**
     * Urlhelper::getPropertyTitle($property, $language)
     * 
     * @param mixed $property
     * @param mixed $language
     * 
     * @return string
     */
    public static function getPropertyTitle($property, $language = '')
    {
        $lang = empty($language) ? strtoupper(Yii::$app->language) : strtoupper($language);
        $slugify = new Slugify();
        $permaLink = isset($property['slug_all'][$lang]) ? $property['slug_all'][$lang] : 'property';

        return $slugify->slugify($permaLink) . '_' . $property['id'];
    }

    /**
     * Urlhelper::getPropertyUrl($property)
     * 
     * @param mixed $property
     * 
     * @return url
     */
    public static function getPropertyUrl($property)
    {
        return Url::to('/' . self::propertyDetailsSlug() . '/' . self::getPropertyTitle($property));
    }

    /**
     * Urlhelper::getDevelopmentTitle($development, $language)
     * 
     * @param mixed $development
     * @param mixed $language
     * 
     * @return string
     */
    public static function getDevelopmentTitle($development, $language = '')
    {
        $lang = empty($language) ? strtoupper(Yii::$app->language) : strtoupper($language);
        $slugify = new Slugify();
        $permaLink = isset($development['slug_all'][$lang]) ? $development['slug_all'][$lang] : 'property';

        return $slugify->slugify($permaLink) . '_' . $development['id'];
    }

    /**
     * Urlhelper::getDevelopmentUrl($development, $language)
     * 
     * @param mixed $development
     * 
     * @return url
     */
    public static function getDevelopmentUrl($development)
    {
        return Url::to('/' . self::developmentDetailsSlug() . '/' . self::getDevelopmentTitle($development));
    }

    /**
     * Urlhelper::getPostTitle($post, $language)
     * 
     * @param mixed $post
     * @param mixed $language
     * 
     * @return string
     */
    public static function getPostTitle($post, $language = '')
    {
        $lang = empty($language) ? strtoupper(Yii::$app->language) : strtoupper($language);
        $slugify = new Slugify();
        $postSlug = isset($post['slug_all'][$lang]) ? $post['slug_all'][$lang] : 'post-not-found';

        return $slugify->slugify($postSlug);
    }

    /**
     * Urlhelper::getBlogUrl($development, $language)
     * 
     * @param mixed $post
     * @param string $language
     * 
     * @return url
     */
    public static function getBlogUrl($post)
    {
        return Url::to('/' . self::blogDetailsSlug() . '/' . self::getPostTitle($post));
    }
}
