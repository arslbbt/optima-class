
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

$options['skip_options'] = isset($options['skip_options']) ? $options['skip_options'] : [];

/* OLD Code with recursive ternary which isnt supported in php 7.4 */
// foreach ($data as $value) {
//   if (!in_array($value['option_key'], $options['skip_options'])) {
//     $select_html .= '<option ' . (is_array(Yii::$app->request->get(str_replace("[]", "", $options['name']))) ? in_array($value['option_key'], Yii::$app->request->get(str_replace("[]", "", $options['name']))) ? 'selected' : '' : Yii::$app->request->get(str_replace("[]", "", $options['name'])) == $value['option_key'] ? 'selected' : '') . ' value="' . $value['option_key'] . '">' . (isset($options['noValueTranslation']) ? $value['option_value'] : Yii::$app->translate->t($value['option_value'])) . '</option>';
//   }
// }

foreach ($data as $value) {
  if (!in_array($value['option_key'], $options['skip_options'])) {
    $select_html .= '<option';
    /* [NEW]:Pass `get_name` Option to get selected values from GET params */
    $option_name = isset($options['get_name']) ? $options['get_name'] : str_replace("[]", "", $options['name']);
    if (is_array(Yii::$app->request->get($option_name))) {
      if (in_array($value['option_key'], Yii::$app->request->get($option_name))) {
        $select_html .= ' selected';
      }
    } else {
      if (Yii::$app->request->get($option_name) == $value['option_key']) {
        $select_html .= ' selected';
      }
    }
    $select_html .= ' value="' . $value['option_key'] . '">';
    $select_html .= isset($options['noValueTranslation']) ? $value['option_value'] : Yii::$app->translate->t($value['option_value']);
    $select_html .= '</option>';
  }
}

$select_html .= '</select>';
?>
