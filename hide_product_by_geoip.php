<?php
/*
  Plugin Name: Hide product by GeoIP
  Plugin URI: http://#
  Description: Symply hide  products by GeoIP + by currency(WOOCS)
  Author: pavloborysenko
  Version: 1.0.1
  Requires at least: WP 4.6.0
  Tested up to: WP 4.7.3
  Text Domain: hide_product_by_geoip
  Domain Path: /languages
  Author URI: http://pavloborysenko.h1n.ru/
 */


define('WOOHP_PATH', plugin_dir_path(__FILE__));
define('WOOHP_LINK', plugin_dir_url(__FILE__));
define('WOOHP_PLUGIN_NAME', plugin_basename(__FILE__));
define('WOOHP_VERSION', '1.0.1');

class WOOHP_BY_GEOIP{
    public $meta_fields=array();
    public $enable_by_country=false;
    public $enable_by_currency=false;
    public $reverse_query="NOT LIKE";
    public function __construct(){
        if('yes' == get_option( 'woohp_hide_by_country', 'yes' )){
            $this->enable_by_country=true;
        }
        if('yes' == get_option( 'woohp_hide_by_currency', 'yes' )){
            $this->enable_by_currency=true;
        }
        if('yes' == get_option( 'woohp_reverse_query', 'no' )){
            $this->reverse_query="LIKE";
        }
        
    }
    public function init(){
        $this->meta_fields=array(
           'hide_by_currency'=>array(
           'field_name' =>'woohp_by_currency',
           'value'=>-1,
           ), 
           'hide_by_country'=>array(
           'field_name' =>'woohp_by_country',
           'value'=>-1,
           ),  
        );
        // Add plugin settings     
        add_filter( 'woocommerce_product_settings', array( $this, 'add_settings' ) );
        // Add link   to setting of the plugin
        global $pagenow;
	if ( 'plugins.php' == $pagenow ) :
            // Plugins page
            add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_plugin_action_links' ), 10, 2 );
	endif;
        // include  JS and CSS
        add_action( 'admin_init',  array( $this,'init_admin_styles_scripts') );
        // add mrta box
        if(is_admin()){
               add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
	       add_action( 'save_post', array( $this, 'save' ) );
        }
        
        //Interaction with the wp_query
        //add_filter('woocommerce_product_query', array($this, "woocommerce_product_query"), 9999);
        add_filter('woocommerce_product_query_meta_query', array($this, "woocommerce_product_query_meta_query"), 9999,2);
        add_action('template_redirect',array($this,'reirect_to_homepage'));
        
    }
    /**
    * Works with template
    *
    * Auxiliary function for working with the template. It display HTML
    *
    *
    * @param	string	$pagepath	Path to the themplaate.
    * @param	array	$data	Data to fill in the template
    * @return   string  HTML of the templete
    */    
    public function render_html($pagepath, $data = array()) {
	$pagepath = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $pagepath);
	if (is_array($data) AND ! empty($data)) {
            extract($data);
	}
	ob_start();
	include($pagepath);
	return ob_get_clean();
    }
    public function init_admin_styles_scripts(){
            wp_enqueue_script('chosen-drop-down', WOOHP_LINK . 'js/chosen/chosen.jquery.min.js', array('jquery'));
	    wp_enqueue_style('chosen-drop-down', WOOHP_LINK. 'js/chosen/chosen.min.css');
            wp_enqueue_script('wooph-script', WOOHP_LINK . 'js/admin.js', array('jquery','chosen-drop-down'));
    }
    public function add_meta_box( $post_type ) {
            $post_types = array('product');
            if ( in_array( $post_type, $post_types )) {
		add_meta_box(
			'woo_hide_by_currency'
			,__( 'Hide product by country + by currency(WOOCS)', 'woo_hide_by_currency' )
			,array( $this, 'render_meta_box_content' )
			,$post_type
			,'side'
			,'low'
		       );
		}
	}
        
    public function save( $post_id ) {

		if ( ! isset( $_POST['woohp_by_currency_box_nonce'] ) )
			return $post_id;
		$nonce = $_POST['woohp_by_currency_box_nonce'];

		if ( ! wp_verify_nonce( $nonce, 'woohp_by_currency_box' ) )
			return $post_id;

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;

		if ( 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;

		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}

                foreach ($this->meta_fields as $item){                 
                    $value=$this->sanitaz_array_r($_POST[$item['field_name']]);
                    if(empty($value)){
                        $value=-1;
                    }
                    update_post_meta( $post_id, $item['field_name'], $value );
                }
	}
     /**
	 * HTML  of the meta box
	 *
	 * @param WP_Post $post object.
	 */
	public function render_meta_box_content( $post ) {

		wp_nonce_field( 'woohp_by_currency_box', 'woohp_by_currency_box_nonce' );
                
                foreach ($this->meta_fields as $type=>$arg){
                    
                   $arg['post_id']= $post->ID;
                   $value=get_post_meta( $post->ID, $arg['field_name'], true );
                   if($value){
                     $arg['value']= $value; 
                   }
                   switch ($type){
                       case "hide_by_currency":
                           echo $this->render_html(WOOHP_PATH . 'views/draw_meta_box_select_currency.php', $arg);
                           break;
                       case "hide_by_country":
					        $arg['reverse']=$this->reverse_query;
                            echo $this->render_html(WOOHP_PATH . 'views/draw_meta_box_select_country.php', $arg);
                            break;
                        default :
                            break;
                           
                   }
                }
               
	}
    public function woocommerce_product_query($q) {
            //http://docs.woothemes.com/wc-apidocs/class-WC_Query.html
            //wp-content\plugins\woocommerce\includes\class-wc-query.php -> public function product_query( $q )
            //$meta_query = $q->get('meta_query');
           // $q->set('meta_query',$meta_query);
            
        return $q;
    }
    public function  woocommerce_product_query_meta_query($meta_query, $obj){
         $current_currency="";
         $current_coutry="";
         $array_by_country=array();
         $array_by_currency=array();
         $like=array();
         if (class_exists('WC_Geolocation') AND $this->enable_by_country) {
            if($this->reverse_query=="LIKE"){//if reverse
                $like=array(
                        'key'     => 'woohp_by_country',
                        'value'   => -1,
                        'compare' => 'LIKE' 
                );
            }
            $current_coutry=WC_Geolocation::geolocate_ip();
            $array_by_country[]=array(
                'relation' => 'OR',
                array(
                    'key'     => 'woohp_by_country',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key'     => 'woohp_by_country',
                    'value'   => $current_coutry['country'],
                    'compare' => $this->reverse_query,
                ),
                $like   
            ); 
         }

        if (class_exists('WOOCS') AND $this->enable_by_currency){
            global $WOOCS;
            $current_currency=$WOOCS->current_currency;
            $array_by_currency[]=array(
                'relation' => 'OR',
                 array(
                         'key'     => 'woohp_by_currency',
                         'compare' => 'NOT EXISTS'
                 ),
                 array(
                         'key'     => 'woohp_by_currency',
                         'value'   => $current_currency,
                         'compare' => 'NOT LIKE'
                    ),
                 );

            }
        if(!empty($array_by_country) AND !empty($array_by_currency)){
            $meta_query[]=array(
             'relation' => 'AND',
             $array_by_country,
             $array_by_currency,
            );
        }elseif(!empty($array_by_country) OR !empty($array_by_currency)){
            $meta_query[]=(!empty($array_by_country))?$array_by_country:$array_by_currency;
        }
        //var_dump($meta_query);  
        return $meta_query;
    }
    /**
    * Redirect  to home page
    * 
    * Redirects to the home page if the user is on the hidden product page.
    * Removes hidden products from the cart
    *
    */
    public function reirect_to_homepage(){
        if(is_product()){
                global $post;
                $id=$post->ID;
                if($this->check_product_by_id($id)){
                    if(wp_redirect( home_url() )){
                        exit();
                    }
                }                       
        }
        if( is_cart() || is_checkout() ) {
            foreach( WC()->cart->cart_contents as $key=> $prod_in_cart ) {              
                if( $this->check_product_by_id($prod_in_cart['product_id']) ) {
                    unset( WC()->cart->cart_contents[$key] );
                }               
                
            }
        }
    }
    /**
    * Check product
    * 
    * 
    * 
    * @param  int $post_id   Id of the current post
    * @return bool    true  if the post is hidden
    */   
    public function check_product_by_id($post_id){
       $current_currency=""; 
        if (class_exists('WOOCS') AND $this->enable_by_currency ){  
           global $WOOCS;
           $current_currency=$WOOCS->current_currency; 
        } 
        $current_country="";
        if (class_exists('WC_Geolocation') AND $this->enable_by_country){
           $tmp=WC_Geolocation::geolocate_ip();
           $current_country=$tmp['country'];
        }
        foreach($this->meta_fields as $type=>$item){
            $value=get_post_meta( $post_id,$item['field_name'] , true );
            if(!is_array($value)){
                $temp=$value;
                $value=array($temp);
            }
            
            switch ($type){
                case "hide_by_currency":
                    if($current_currency){
                        
                        if(in_array($current_currency, $value)){
                            return true;
                        }
                    }
                     break;
                case "hide_by_country":
                    if($current_country){ 
                        if($this->reverse_query=='LIKE'){//if reverse
                            $temp_array=array();
                            $temp_array=array_intersect(array($current_country,-1,""), $value);
                            if(count($temp_array)<1){
                                return true;
                            }
                        }else{
                            if(in_array($current_country, $value)){
                                return true;
                            }  
                        }

                    }
                     break;
                default :
                     return false;
                     break;
            }
            
        }
        return false;
    }
    protected function check_ability($type=""){
        switch ($type){
            case "hide_by_currency":
                if (class_exists('WOOCS')){
                    return true;
                }
                break;
            case "hide_by_country":
                if (class_exists('WC_Geolocation')) {
                    return true;
                }
                break;
            default :
                return false;   
        }
        return false;     
    }

    public function add_settings($settings){
         $new_settings = array(
			array(
				'type' 	=> 'sectionend',
				'id' 	=> 'woohp_start'
			),            
			array(
				'title' => sprintf(__('Hide product by GeoIP + by currency v.%s', 'hide_product_by_geoip'), WOOHP_VERSION),
				'type' 	=> 'title',
				'desc' 	=> '',
				'id' 	=> 'woohp_title'
			),
             );
        if($this->check_ability("hide_by_country")){
            $new_settings[]=array(
				'title'			=> __( 'Enable: Hide product by country', 'hide_product_by_geoip' ),
				'desc'			=> __( 'The ability to enable or disable hiding the product by GeoIP', 'hide_product_by_geoip' ),
				'id'			=> 'woohp_hide_by_country',
				'default'		=> 'yes',
				'type'			=> 'checkbox',
			);
        }
        if($this->check_ability("hide_by_currency")){
            $new_settings[]=array(
				'title'			=> __( 'Enable: Hide product by currency', 'hide_product_by_geoip' ),
				'desc'			=> __( 'The ability to enable or disable hiding the product by currency', 'hide_product_by_geoip' ),
				'id'			=> 'woohp_hide_by_currency',
				'default'		=> 'yes',
				'type'			=> 'checkbox',
			);
        }
        if($this->check_ability("hide_by_country") ){
            $new_settings[]=array(
				'title'			=> __( 'Reverse the query', 'hide_product_by_geoip' ),
				'desc'			=> __( 'This option makes the search query reverse. Now the products will be displayed by GeoIP', 'hide_product_by_geoip' ),
				'id'			=> 'woohp_reverse_query',
				'default'		=> 'no',
				'type'			=> 'checkbox',
			);
            
        }
        
        $new_settings[]= array(
				'type' 	=> 'sectionend',
				'id' 	=> 'woohp_options'
			);

        return array_merge( $settings, $new_settings );
        
    }
	/**
	 * Plugin action links.
	 *
	 * Add links to the plugins.php page below the plugin name
	 * and besides the 'activate', 'edit', 'delete' action links.
	 *
	 * @since 1.0.1
	 *
	 * @param	array	$links	List of existing links.
	 * @param	string	$file	Name of the current plugin being looped.
	 * @return	array			List of modified links.
	 */
    public function add_plugin_action_links( $links, $file ) {

	if ( $file == plugin_basename( __FILE__ ) ) :
            $links = array_merge( array(
			'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=products&section=display#woohp_hide_by_country' ) ) . '">' . __( 'Settings', 'hide_product_by_geoip' ) . '</a>'
			), $links );
	endif;

	return $links;

    }    
    public function sanitaz_array_r($arr) {
        if(is_array($arr)){
            $newArr = array();
            foreach ($arr as $key => $value) {
                $newArr[sanitize_text_field($key)] = ( is_array($value) ) ? $this->sanitaz_array_r($value) : sanitize_text_field($value);
            }
            return $newArr;
        }
        return sanitize_text_field($arr);
    }
}


         $woohp_by_geoip= new WOOHP_BY_GEOIP();
         
         add_action('init',  function (){
             global $woohp_by_geoip;
             $woohp_by_geoip->init(); 
         });
         
        