
<?php 
  $select_html .= '<select 
          name="' . (isset($options['name']) ? $options['name'] : '') . '" 
          class="' . (isset($options['class']) ? $options['class'] : '') . '" 
          id="' . (isset($options['id']) ? $options['id'] : '') . '" 
          onchange="' . (isset($options['onchange']) ? $options['onchange'] : '') . '" 
          data-placeholder="' . Yii::$app->translate->t(isset($options['placeholder']) ? $options['placeholder'] : '') . '"
          ' . (isset($options['disabled']) ? $options['disabled'] : '') . '
          ' . (isset($options['multiple']) ? $options['multiple'] : '') . '
          >';
          
  foreach ($data as $value) {
      $select_html .= '<option ' . (is_array(Yii::$app->request->get(str_replace("[]","",$options['name'])))? in_array($value['option_key'], Yii::$app->request->get(str_replace("[]","",$options['name'])))? 'selected' : '' : Yii::$app->request->get(str_replace("[]","",$options['name'])) == $value['option_key']? 'selected' : '' ) . ' value="' . $value['option_key'] . '">' . Yii::$app->translate->t($value['option_value']) . '</option>';
  }
  $select_html .= '</select>'; 
?>
