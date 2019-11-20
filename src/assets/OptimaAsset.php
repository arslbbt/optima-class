<?php

namespace optima\assets;


class OptimaAsset extends \yii\web\AssetBundle{

    public $sourcePath = '@vendor/arsl/optima-class/src/assets/';
    public $css = [
        'css/bootstrap-multiselect.css',
    ];
    public $js = [
        'js/bootstrap-multiselect.js',
    ];


    public $depends = [
        'yii\web\YiiAsset',
        'yii\web\JqueryAsset'
    ];


}