
<?php
$select_html .= '<select 
          name="' . (isset($options['name']) ? $options['name'] : '') . '" 
          class="' . (isset($options['class']) ? $options['class'] : '') . '" 
          id="' . (isset($options['id']) ? $options['id'] : '') . '" 
          onchange="' . (isset($options['onchange']) ? $options['onchange'] : '') . '" 
          data-placeholder="' . Yii::$app->translate->t(isset($options['placeholder']) ? $options['placeholder'] : '') . '"
          data-nselectedtext="' . Yii::$app->translate->t('selected') . '"
          data-allselectedtext="' . Yii::$app->translate->t('All selected') . '"
          ' . (isset($options['disabled']) ? $options['disabled'] : '') . '
          ' . (isset($options['multiple']) ? $options['multiple'] : '') . '
          ' . (isset($options['required']) ? $options['required'] : '') . '
          >';

foreach ($data as $value) {
  if (!in_array($value['option_key'], $options['skip_options'])) {
    $select_html .= '<option ' . (is_array(Yii::$app->request->get(str_replace("[]", "", $options['name']))) ? in_array($value['option_key'], Yii::$app->request->get(str_replace("[]", "", $options['name']))) ? 'selected' : '' : Yii::$app->request->get(str_replace("[]", "", $options['name'])) == $value['option_key'] ? 'selected' : '') . ' value="' . $value['option_key'] . '">' . (isset($options['noValueTranslation']) ? $value['option_value'] : Yii::$app->translate->t($value['option_value'])) . '</option>';
  }
}
$select_html .= '</select>';
?>
