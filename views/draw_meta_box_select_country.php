<?php
if (!defined('ABSPATH'))
    die('No direct access allowed');
    //+++
    $pd = array();
    $countries = array();
    if (class_exists('WC_Geolocation')) {
        $c = new WC_Countries();
        $countries = $c->get_countries();
        $pd = WC_Geolocation::geolocate_ip();
    }
    //+++
        
        if(!is_array($value)){
            $temp=$value;
            $value=array($temp);
        }
        if(!empty( $countries)){
                  ?>
                    <div class="woohp_item">
                    <label for="<?php echo $field_name ?>">
                      <?php  _e( 'Select country  to hide this product:', 'woo_hide_by_currency' ); ?>  
                    </label>

                     <select id="<?php echo $field_name ?>" name="<?php echo $field_name ?>[]" multiple="" class="wooph-chosen-select" data-placeholder="<?php _e("Choose a country", 'woo_hide_by_currency' ); ?>" >
                        
                        <?php foreach ( $countries as $key => $country) { 
                            $selected="";
                            
                            if(in_array($key,$value)){
                              $selected="selected='selected'";  
                            }

                            ?>            
                        <option value="<?php echo $key ?>" <?php echo $selected ?> ><?php  echo $country; ?></option>    
                        <?php } ?>
                    </select>
                    <?php
                    if (!empty($pd) AND ! empty($countries) AND isset($countries[$pd['country']])) {
                            echo '<i style="font-size:11px; font-weight:normal;">' . sprintf(__('Your country is: %s', 'hide_product_by_geoip'), $countries[$pd['country']]) . '</i>';
                        } else {
                            echo '<i style="color:red;font-size:11px; font-weight:normal;">' . __('Your country is not defined! Troubles with internet connection or GeoIp service.', 'hide_product_by_geoip') . '</i>';
                        }
           ?>           <i style="color:red;font-size:10px; font-weight:normal;"><?php _e('You can use only two items','hide_product_by_geoip') ?></i>
					</div>           <?php 
        }
               
                    