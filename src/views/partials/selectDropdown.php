<?php $select_html .= '<select name="'.( isset($options['name'])? $options['name'] :'').'" 
          class="'.( isset($options['class'])? $options['class'] :'' ).'" 
          id="'.( isset($options['id'])? $options['id'] :'' ).'" 
          onchange="'.( isset($options['func'])? $options['func'] :'' ).'" 
          placeholder="'.Yii::$app->translate->t( isset($options['placeholder'])? $options['placeholder'] :'' ).'"
          '.( isset($options['multiple'])? $options['multiple'] :'' ) .'>';
          
          foreach ($data as $value) {
            $select_html .= '<option '.(Yii::$app->request->get( isset($options['name'])? $options['name'] :'') == $value['option_key']?'selected':'').' value="' . $value['option_key'] . '">' . Yii::$app->translate->t($value['option_value']) . '</option>';
         }
    ?>

<?php $select_html .= '</select>'; ?>
