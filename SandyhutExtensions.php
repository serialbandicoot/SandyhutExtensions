<?php
/**
* @package SandyhutExtensions
*/

/*
Plugin Name: SandyhutExtensions
Plugin URI: https://github.com/serialbandicoot/SandyhutExtensions
Description: Extensions for AppCart
Version: 1.0.0
Author: @serialbandicoot
Author URI: https://github.com/serialbandicoot/SandyhutExtensions
License: GPLv2 or later 
Text Domain: SandyhutExtensions
*/
	
defined( 'ABSPATH' ) or die('Silence is golden.');

class SandyhutExtensions 
{

	function __construct(){
		add_action( 'rest_api_init', array( $this , 'activate_table_rate_shipping_data' ) );
		add_filter( 'woocommerce_rest_prepare_product_object', array('SandyhutExtensions', 'get_product_media_images'), 10, 3 ); 
	}

	function activate(){
		$this->get_table_rate_shipping_data();
		flush_rewrite_rules();
	}

	function deactivate(){
		flush_rewrite_rules();
	}


	/*
		URL: http://lhost/wp-json/shipping/v1/table_rate_data
	*/

	function activate_table_rate_shipping_data(){
		register_rest_route( 'shipping/v1', '/table_rate_data', array(
				'methods' => 'GET',
				'callback' => 'SandyhutExtensions::get_table_rate_shipping_data',
			));
	}

	static function get_table_rate_shipping_data(){
		global $woocommerce;
		global $wpdb;

		$active_methods   = array();
		$shipping_methods = $woocommerce->shipping->load_shipping_methods();

		foreach ( $shipping_methods as $id => $shipping_method ) {

			$data_arr = array( 'title' => $shipping_method->title, 'tax_status' => $shipping_method->tax_status );  

			if( $id == 'table_rate'){ 
					$raw_zones = $wpdb->get_results("SELECT zone_id, zone_name, zone_order FROM {$wpdb->prefix}woocommerce_shipping_zones order by zone_order ASC;");


	 			$shipping = array();
	    		$shippingarr = array();


				foreach ($raw_zones as $raw_zone) {

					$zones = new WC_Shipping_Zone($raw_zone->zone_id);

			        $zone_id 		= $zones->zone_id; 
			        $zone_name 		= $zones->zone_name; 
			        $zone_enabled 	= $zones->zone_enabled; 
			        $zone_type 		= $zones->zone_type; 
			        $zone_order 	= $zones->zone_order; 

			        $shipping['zone_id']  		= $zone_id;
			        $shipping['zone_name'] 		= $zone_name;
			        $shipping['zone_enabled'] 	= $zone_enabled;
			        $shipping['zone_type'] 		= $zone_type;
			        $shipping['zone_order'] 	= $zone_order;

			        $shipping_methods = $zones->shipping_methods; 

					foreach($shipping_methods as $shipping_method){
					    $methodid = $shipping_method["number"];
					    $raw_rates[$methodid]['rates'] = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}woocommerce_shipping_table_rates WHERE shipping_method_id={$methodid};", ARRAY_A);
					}

					$shipping['shipping_methods'] = $raw_rates;
					$raw_country = $wpdb->get_results("SELECT location_code FROM {$wpdb->prefix}woocommerce_shipping_zone_locations WHERE zone_id={$zone_id};", ARRAY_N);
					$shipping['countries'] = $raw_country;
					$shippingarr[] = $shipping;
					
				}

			}

		}

	 
		return $shipping_methods;
	}

	static function get_product_media_images( $response, $product, $request ) {

		global $_wp_additional_image_sizes;

	    if (empty($response->data)) {
	        return $response;
	    }

	    foreach ($response->data['images'] as $key => $image) {
	        $image_urls = [];
	        foreach ($_wp_additional_image_sizes as $size => $value) {
	            $image_info = wp_get_attachment_image_src($image['id'], $size);
	            $response->data['images'][$key][$size] = $image_info[0];
	        }
	    }
	    return $response;

	}

}

if (class_exists('SandyhutExtensions')) {
	$sandyhut_extensions = new SandyhutExtensions();
}

//activate
register_activation_hook( __FILE__, array($sandyhut_extensions, 'activate') );


//deactivate
register_deactivation_hook( __FILE__, array($sandyhut_extensions, 'deactivate') );

