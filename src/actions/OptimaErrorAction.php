<?php

namespace optima\actions;

use Yii;
use yii\web\ErrorAction;

class OptimaErrorAction extends ErrorAction
{
    /**
     * Runs the action.
     *
     * @return string result content
     */
    public function run()
    {
        if (!YII_DEBUG) {
            return $this->controller->redirect(array('language' => strtolower(Yii::$app->language), '/404'));
        }

        if ($this->layout !== null) {
            $this->controller->layout = $this->layout;
        }

        Yii::$app->getResponse()->setStatusCodeByException($this->exception);

        if (Yii::$app->getRequest()->getIsAjax()) {
            return $this->renderAjaxResponse();
        }

        return $this->renderHtmlResponse();
    }
}
