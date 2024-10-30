<?php
if (!defined('ABSPATH'))
    die('No direct access allowed');
        if (class_exists('WOOCS')){
            global $WOOCS;
            $currencies=$WOOCS->get_currencies();
			$currencies=array_slice($currencies,0,2);
        }
        
        if(!is_array($value)){
            $temp=$value;
            $value=array($temp);
        }
        if(!empty($currencies)){
                  ?><div class="woohp_item">
                    <label for="<?php echo $field_name ?>">
                      <?php  if($reverse=='LIKE'){
                        _e( 'Select country  to show this product:', 'woo_hide_by_currency' );   
                      }else{
                        _e( 'Select country  to hide this product:', 'woo_hide_by_currency' ); 
                      }
					  ?>  
                    </label>

                    <select id="<?php echo $field_name ?>" name="<?php echo $field_name ?>[]" multiple="" class="wooph-chosen-select" data-placeholder="<?php _e("Select currency", 'woo_hide_by_currency' ); ?>" >
                        
                        <?php foreach ($currencies as $key => $curr) { 
                            $selected="";
                            
                            if(in_array($key,$value)){
                              $selected="selected='selected'";  
                            }
                            ?>            
                        <option value="<?php echo $key ?>" <?php echo $selected ?> ><?php  echo $key; ?></option>    
                        <?php } ?>
                    </select><br>
					<i style="color:red;font-size:10px; font-weight:normal;"><?php _e('You can use only two items','hide_product_by_geoip') ?></i>
                       </div>
                    <?php
        }