<?php $select_html .= '<select name="' . (isset($options['name']) ? $options['name'] : '') . '" 
          class="' . (isset($options['class']) ? $options['class'] : '') . '" 
          id="' . (isset($options['id']) ? $options['id'] : '') . '" 
          onchange="' . (isset($options['func']) ? $options['func'] : '') . '" 
          placeholder="' . Yii::$app->translate->t(isset($options['placeholder']) ? $options['placeholder'] : '') . '"
          ' . (isset($options['multiple']) ? $options['multiple'] : '') . '>';

            foreach ($data as $value) {
                $select_html .= '<option ' . ((isset($_GET['lg_by_key']) && in_array($value['option_key'], $_GET['lg_by_key'])) || (isset($_GET['types']) && in_array($value['option_key'], $_GET['types'])) ? 'selected' : '') . ' value="' . $value['option_key'] . '">' . Yii::$app->translate->t($value['option_value']) . '</option>';
            }
?>

<?php $select_html .= '</select>'; ?>
