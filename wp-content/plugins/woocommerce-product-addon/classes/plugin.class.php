<?php
/*
 * The base plugin class.
 */


class NM_PersonalizedProduct {
	
	static $tbl_productmeta = 'nm_personalized';
	
	
	/**
	 * this holds all input objects
	 */
	var $inputs;
	
	/**
	 * the static object instace
	 */
	private static $ins = null;
	
	
	public static function get_instance()
	{
		// create a new object if it doesn't exist.
		is_null(self::$ins) && self::$ins = new self;
		return self::$ins;
	}
	
	
	function __construct() {
		
		$this -> plugin_meta = ppom_get_plugin_meta ();
		
		// getting saved settings
		$this -> plugin_settings = get_option ( $this -> plugin_meta['shortname'] . '_settings' );
		
		// file upload dir name
		$this -> product_files = 'product_files';
		
		// this will hold form productmeta_id
		$this -> productmeta_id = '';
		
		// populating $inputs with NM_Inputs object
		$this -> inputs = self::get_all_inputs();
		//ppom_pa($this->inputs);
		
		
		/** ============ NEW Hooks ==================== */
		
		add_action( 'wp_enqueue_scripts', 'ppom_hooks_scripts' );
		
		// Rendering fields on product page
		add_action ( 'woocommerce_before_add_to_cart_button', 'ppom_woocommerce_show_fields', 15);
		if( apply_filters('ppom_remove_duplicate_fields', true) ) {
			add_action ( 'woocommerce_single_variation', 'ppom_woocommerce_show_fields', 15);
		}
		
		// Validating before add to cart
		add_filter ( 'woocommerce_add_to_cart_validation', 'ppom_woocommerce_validate_product', 10, 3 );
		
		// Adding meta to cart form product page
		add_filter ( 'woocommerce_add_cart_item_data', 'ppom_woocommerce_add_cart_item_data', 10, 2);
		
		add_action( 'wp', array($this, 'wp_loaded') );
		
		
		/*
		 * 4- now loading all meta on cart/checkout page from session confirmed that it is loading for cart and checkout
		 */
		// Filter/Upate cart/checkout prices using this hook
		add_filter ( 'woocommerce_get_cart_item_from_session', 'ppom_woocommerce_update_cart_fees', 10, 2 );
		add_action( 'woocommerce_cart_loaded_from_session', 'ppom_calculate_totals_from_session');
		
		// Add fixed extra fee in cart
		add_action( 'woocommerce_cart_calculate_fees', 'ppom_woocommerce_add_fixed_fee' );
		// Mini/Cart Widget fixed fee
		add_action( 'woocommerce_widget_shopping_cart_before_buttons', 'ppom_woocommerce_mini_cart_fixed_fee');
		
		// Changing price displa on loop for price matrix
	    add_filter('woocommerce_get_price_html', 'ppom_woocommerce_alter_price', 10, 2);
	    // Hiding variation price if dynamic price is enable
	    add_filter( 'woocommerce_show_variation_price', 'ppom_hide_variation_price_html', 99, 3);
	    
	    // Product Max quantity control for matrix
	    add_filter( 'woocommerce_quantity_input_max', 'ppom_woocommerce_set_max_quantity', 10, 2);
	    // Product Step quantity control for matrix
	    add_filter( 'woocommerce_quantity_input_step', 'ppom_woocommerce_set_quantity_step', 10, 2);
		
		// Show item meta data on cart/checkout pages.
		add_filter ( 'woocommerce_get_item_data', 'ppom_woocommerce_add_item_meta', 10, 2 );
		
		// Control quantity on cart when quantities used
		add_filter( 'woocommerce_add_to_cart_quantity', 'ppom_woocommerce_add_to_cart_quantity', 10, 2);
		add_filter( 'woocommerce_cart_item_quantity', 'ppom_woocommerce_control_cart_quantity', 10, 2);
		// add_filter( 'woocommerce_cart_item_subtotal', 'ppom_woocommerce_item_subtotal', 10, 3);
		add_filter( 'woocommerce_checkout_cart_item_quantity', 'ppom_woocommerce_control_checkout_quantity', 10, 3);
		add_filter( 'woocommerce_order_item_quantity_html', 'ppom_woocommerce_control_oder_item_quantity', 10, 2);
		add_filter( 'woocommerce_email_order_item_quantity', 'ppom_woocommerce_control_email_item_quantity', 10, 2);
		add_filter( 'woocommerce_order_item_get_quantity', 'ppom_woocommerce_control_order_item_quantity', 10, 2);
		
		// Cart update function
		add_filter( 'woocommerce_update_cart_validation', 'ppom_woocommerce_cart_update_validate', 10, 4);
		
		/**
		 * Adding item_meta to orders 2.0 it is in classes/class-wc-checkout function:
		** create_order() 
		**
		*/
		add_action ( 'woocommerce_add_order_item_meta', 'ppom_woocommerce_order_item_meta', 10, 3);
		
		// Changing display to label in orders
		add_filter( 'woocommerce_order_item_display_meta_key', 'ppom_woocommerce_order_key', 10, 3);
		// Few inputs like file/crop/image need to show meta value in tags
		add_filter( 'woocommerce_order_item_display_meta_value', 'ppom_woocommerce_order_value', 10, 3);
		// Hiding some additional field like ppom_has_quantities
		add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'ppom_woocommerce_hide_order_meta', 10, 2);
		
		/*
		 * 7- movnig confirmed/paid orders into another directory
		 * dir_name: confirmed
		*/
		add_action ( 'woocommerce_checkout_order_processed', 'ppom_woocommerce_rename_files', 10, 3);
		
		/** ============ LOCAL Hooks ==================== */
		 add_filter('ppom_field_attributes', 'ppom_hooks_set_attributes', 10, 2);
		 // if jQuery datepicker is selected
		 add_filter('ppom_field_setting', 'ppom_hooks_input_args', 10, 2);
		 // Checkbox validation hook
		 add_filter('ppom_has_posted_field_value', 'ppom_hooks_checkbox_valided', 10, 3);
		 // Change color type to text for rendering
		 add_filter('nmform_attribute_value', 'ppom_hooks_color_to_text_type', 10, 3);
		 // Option show for Pricematrix
		 add_filter('ppom_show_option_price', 'ppom_hooks_show_option_price_pricematrix', 10, 2);
		 // Translating meta settings being saved via admin
		 add_filter('ppom_meta_data_saving', 'ppom_hooks_register_wpml');
		 // add a wrapper class in each input e.g: ppom-input-{data_name}
		 add_filter('ppom_input_wrapper_class', 'ppom_hooks_input_wrapper_class', 10, 2);
		 // Saving cropped image
		 add_filter('ppom_add_cart_item_data', 'ppom_hooks_save_cropped_image', 10, 2);
		 // Formatting the order meta with options price and id
		 add_filter('ppom_order_display_value', 'ppom_hooks_format_order_value', 999, 3);
		 
		 // Compatible with currency switcher for option prices
		 add_filter('ppom_option_price', 'ppom_hooks_convert_price', 10, 4);
		 add_filter('ppom_cart_line_total', 'ppom_hooks_convert_price_back');
		 add_filter('ppom_cart_fixed_fee', 'ppom_hooks_convert_price_back');
		 
		 // Shortcode
		 add_shortcode('ppom', 'ppom_hooks_render_shortcode');
		 
		 /** ============ Ajax callbacks ==================== */
		 add_action('wp_ajax_nopriv_ppom_upload_file', 'ppom_upload_file');
		 add_action('wp_ajax_ppom_upload_file', 'ppom_upload_file');
		 add_action('wp_ajax_nopriv_ppom_delete_file', 'ppom_delete_file');
		 add_action('wp_ajax_ppom_delete_file', 'ppom_delete_file');
		 add_action('wp_ajax_nopriv_ppom_save_edited_photo', 'save_files_edited_photo');
		 add_action('wp_ajax_ppom_save_edited_photo', 'save_files_edited_photo');
		 /*add_action('wp_ajax_nopriv_ppom_get_cart_fragment', 'ppom_hooks_get_cart_fragment');
		 add_action('wp_ajax_ppom_get_cart_fragment', 'ppom_hooks_get_cart_fragment');*/
		 add_action('wp_ajax_ppom_ajax_validation', 'ppom_woocommerce_ajax_validate');
		 add_action('wp_ajax_nopriv_ppom_ajax_validation', 'ppom_woocommerce_ajax_validate');
		 
		 
		 
		 /** ============ Admin hooks ===================== **/		/*
		 * adding a panel on product single page in admin
		 */
		add_action ( 'add_meta_boxes', 'ppom_admin_product_meta_metabox');
		add_action( 'admin_notices', 'ppom_admin_show_notices' );
		
		// Saving settings and fields
		add_action('wp_ajax_ppom_save_settings', 'ppom_admin_save_settings');
		add_action('wp_ajax_ppom_save_form_meta', 'ppom_admin_save_form_meta');
		add_action('wp_ajax_ppom_update_form_meta', 'ppom_admin_update_form_meta');
		add_action('wp_ajax_ppom_delete_meta', 'ppom_admin_delete_meta');
		add_action('wp_ajax_ppom_delete_selected_meta', 'ppom_admin_delete_selected_meta');
		
		/*
		 * saving product meta in admin/product signel page
		 */
		add_action ( 'woocommerce_process_product_meta', 'ppom_admin_process_product_meta');
		
		
		/*
		 * plugin localization being initiated here
		 */
		add_action ( 'init', array (
				$this,
				'wpp_textdomain' 
		) );
		
		/**
		 * change add to cart text on shop page
		 */
		 //add_filter('woocommerce_loop_add_to_cart_link', array($this, 'change_add_to_cart_text'), 10, 2);
		add_filter( 'woocommerce_product_add_to_cart_url', array($this, 'loop_add_to_cart_url'), 10, 2);
 		add_filter( 'woocommerce_product_add_to_cart_text', array($this, 'loop_add_to_cart_text'), 10, 2);
 		add_filter( 'woocommerce_product_supports', array($this, 'product_supports'), 10, 3);
 		add_action( 'woocommerce_product_duplicate', array($this, 'duplicate_product_meta'), 10, 2 );
		
		
		/*
		 * 8- cron job (shedualed hourly)
		 * to remove un-paid images
		 */
		add_action('do_action_remove_images', 'ppom_files_removed_unused_images');
		
		

		add_action('admin_footer-edit.php', array($this, 'nm_add_bulk_meta'));
		
		add_action('load-edit.php', array(&$this, 'nm_meta_bulk_action'));
		
		add_action('admin_notices', array(&$this, 'nm_add_meta_notices'));
		
		// Export & Import Meta
		add_action( 'admin_post_ppom_import_meta', array($this, 'ppom_import_meta') );
		add_action( 'admin_post_ppom_export_meta', array($this, 'ppom_export_meta') );
		
		/**
		 * adding extra column in products list for meta show
		 * @since 8.0
		 **/
		add_filter('manage_product_posts_columns' , 'ppom_admin_show_product_meta', 999);
		add_action( 'manage_product_posts_custom_column' , 'ppom_admin_product_meta_column', 999, 2 );
		
		add_action('ppom_after_files_moved', 'ppom_send_files_in_email', 10, 2 );
		
		/**
		 * re-calculate price matrix prices if 
		 * variation options included from settings of price matrix
		 * @since 8.5
		 **/
		add_filter('ppom_price_matrix_post', 'ppom_adjust_price_matrix_for_option_price', 10, 3);
		
		// WooCommece Advance Order Plugin
		add_filter("woe_fetch_order", 'ppom_hooks_convert_option_json_to_string', 10, 2);
		
		// Remmove form-control class for certain input types like
		// image
		add_filter('ppom_input_classes', array($this, 'input_classes'), 99, 2);
	}
	
	/*
	 * ============================================================== All about Admin -> Single Product page ==============================================================
	 */
	 
	 /**
  	 * add to cart button url change
  	 */
  	function loop_add_to_cart_url($url, $product){
  	
  	
  		if( ! $product -> is_in_stock() ) 
  		return $url;
  			
  		$product_id = ppom_get_product_id($product);
  		$ppom		= new PPOM_Meta( $product_id );
	  	if( ! $ppom->is_exists ){
			return $url;
		}

  		if (!in_array($product->get_type(), array('variable', 'grouped', 'external'))) {
  			// only if can be purchased
  			if ($ppom->is_exists) {
  				return get_permalink($product_id);
  			}
  		}
  		return $url;
  	}
	  
  	/**
  	 * add to cart button text change
  	 */
  	function loop_add_to_cart_text($text, $product){
  		
  		if( ! $product -> is_in_stock() ) 
  			return $text;
  			
  		$product_id = ppom_get_product_id($product);
  		$ppom		= new PPOM_Meta( $product_id );
  		
	  	if( ! $ppom->is_exists ) {
			return $text;
		}
		

  		if (!in_array($product->get_type(), array('variable', 'grouped', 'external'))) {
  			// only if can be purchased
  			if ($ppom->is_exists) {
  				return __('Select options', 'woocommerce');
  			}
  		}
  		return $text;
  	}
	
  	/**
  	 * Filter woocommerce_product_supports in order to remove support for ajax_add_to_cart for personalized product
  	 */
  	function product_supports($support, $feature, $product) {
  	
  		if ( $feature != "ajax_add_to_cart" )
  			return $support;
		
	  	$product_id = ppom_get_product_id($product);
  		$ppom		= new PPOM_Meta( $product_id );
  		if( ! $ppom->is_exists ) {
			return $support;
		}

  		if (!in_array($product->get_type(), array('variable', 'grouped', 'external'))) {
  			// only if can be purchased
  			if ($ppom->is_exists) {
  				return false;
  			}
  		}
  		return $support;
  	}
  	
  	
  	/** This function is called when a product is duplicated by an admin.
  	 * It checks it the product got nm-woocommerce-personalized-product meta, and if yes,
  	 * copy the meta to the duplicated product. */
  	function duplicate_product_meta($duplicate, $product) {
  		
  		$product_id = ppom_get_product_id($product);
  		$ppom		= new PPOM_Meta( $product_id );
  		
  		if( $ppom->is_exists ) {
  			update_post_meta($duplicate->get_id(), PPOM_PRODUCT_META_KEY, $ppom->meta_id);
  		}	
  	}
	  
	  
	
	// i18n and l10n support here
	// plugin localization
	function wpp_textdomain() {
		
		$locale_dir = dirname( plugin_basename( dirname(__FILE__) ) ) . '/languages';
		
		load_plugin_textdomain('ppom', false, $locale_dir);
		
	}
	
	
	function wp_loaded() {
		
		// var_dump(DOING_AJAX);
		if( is_cart() || is_checkout() || (defined('DOING_AJAX') && DOING_AJAX && isset($_REQUEST['wc-ajax'])) ) {
			global $WOOCS;
			remove_filter('woocommerce_product_get_price', array($WOOCS, 'raw_woocommerce_price'), 9999, 2);
		}
	}
	
	/**
	 * Adds meta groups in admin dropdown to apply on products.
	 *
	 */
	function nm_add_bulk_meta() {
		global $post_type;
			
		if($post_type == 'product' and $all_meta = $this -> get_product_meta_all ()) {
			foreach ( $all_meta as $meta ) {
				?>
<script type="text/javascript">
						jQuery(document).ready(function() {
							jQuery('<option>').val('<?php printf(__("nm_action_%d", 'ppom'), $meta->productmeta_id)?>', 'ppom').text('<?php _e($meta->productmeta_name)?>').appendTo("select[name='action']");
							jQuery('<option>').val('<?php printf(__("nm_action_%d", 'ppom'), $meta->productmeta_id)?>').text('<?php _e($meta->productmeta_name)?>').appendTo("select[name='action2']");
						});
					</script>
<?php
			}
			?>
<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery('<option>').val('nm_delete_meta').text('<?php _e('Remove Meta', 'ppom')?>').appendTo("select[name='action']");
						jQuery('<option>').val('nm_delete_meta').text('<?php _e('Remove Meta', 'ppom')?>').appendTo("select[name='action2']");
					});
				</script>
<?php
	    }
	}

	function nm_meta_bulk_action() {
		global $typenow;
		$post_type = $typenow;
			
		if($post_type == 'product') {
				
			// get the action
			$wp_list_table = _get_list_table('WP_Posts_List_Table');  // depending on your resource type this could be WP_Users_List_Table, WP_Comments_List_Table, etc
			$action = $wp_list_table->current_action();
			
			// make sure ids are submitted.  depending on the resource type, this may be 'media' or 'ids'
			if(isset($_REQUEST['post']) && is_array($_REQUEST['post'])){
				$post_ids = array_map('intval', $_REQUEST['post']);
			}
			
			if(empty($post_ids)) return;
			
			// this is based on wp-admin/edit.php
			$sendback = remove_query_arg( array('nm_updated', 'nm_removed', 'untrashed', 'deleted', 'ids'), wp_get_referer() );
			if ( ! $sendback )
				$sendback = admin_url( "edit.php?post_type=$post_type" );
				
			$pagenum = $wp_list_table->get_pagenum();
			$sendback = add_query_arg( 'paged', $pagenum, $sendback );
			
			
			$nm_do_action = ($action == 'nm_delete_meta') ? $action : substr($action, 0, 10);
				
			switch($nm_do_action) {
				case 'nm_action_':
				$nm_updated = 0;
				foreach( $post_ids as $post_id ) {
							
					update_post_meta ( $post_id, '_product_meta_id', substr($action, 10) );
			
					$nm_updated++;
				}
				$sendback = add_query_arg( array('nm_updated' => $nm_updated, 'ids' => join(',', $post_ids)), $sendback );
				break;
				
				case 'nm_delete_meta':
				$nm_removed = 0;
				foreach( $post_ids as $post_id ) {
							
					delete_post_meta ( $post_id, '_product_meta_id' );
			
					$nm_removed++;
				}
				$sendback = add_query_arg( array('nm_removed' => $nm_removed, 'ids' => join(',', $post_ids)), $sendback );
				break;
				
				default: return;
			}
			
			wp_redirect($sendback);
			
			exit();
		}
	}
	/**
	 * display an admin notice on the Products page after updating meta
	 */
	function nm_add_meta_notices() {
		global $post_type, $pagenow;
			
		if($pagenow == 'edit.php' && $post_type == 'product' && isset($_REQUEST['nm_updated']) && (int) $_REQUEST['nm_updated']) {
			$message = sprintf( _n( 'Product meta updated.', '%s Products meta updated.', $_REQUEST['nm_updated'] ), number_format_i18n( $_REQUEST['nm_updated'] ) );
			echo "<div class=\"updated\"><p>{$message}</p></div>";
		}
		elseif($pagenow == 'edit.php' && $post_type == 'product' && isset($_REQUEST['nm_removed']) && (int) $_REQUEST['nm_removed']){
			$message = sprintf( _n( 'Product meta removed.', '%s Products meta removed.', $_REQUEST['nm_removed'] ), number_format_i18n( $_REQUEST['nm_removed'] ) );
			echo "<div class=\"updated\"><p>{$message}</p></div>";	
		}
	}
	
	
	function input_classes( $classes, $meta ) {
		
		$type 			= ( isset($meta['type']) ? $meta ['type'] : '');
		
		$no_form_control = array('image');
	
		// removing form-control class	
		if( ! in_array($type, $no_form_control) ) return $classes;
		
		if (($key = array_search('form-control', $classes)) !== false) {
			unset($classes[$key]);
		}
		
		return $classes;
	}
	
	
	function get_product_meta_all() {
		
		global $wpdb;
		
		$qry = "SELECT * FROM " . $wpdb->prefix . PPOM_TABLE_META;
		$res = $wpdb->get_results ( $qry );
		
		return $res;
	}
	
	function get_product_meta($meta_id) {
		
		if( !$meta_id )
			return ;
			
		if ($meta_id == 'None')
			return;
			
		global $wpdb;
		
		$qry = "SELECT * FROM " . $wpdb->prefix . PPOM_TABLE_META . " WHERE productmeta_id = $meta_id";
		$res = $wpdb->get_row ( $qry );
		
		return $res;
	}
	

	/*
	 * 9- adding files link in order email
	 */
	function add_files_link_in_email($order, $is_admin){
		
		if (sizeof ( $order->get_items () ) > 0) {
			foreach ( $order->get_items () as $item ) {
		
				// ppom_pa($item);
		
				$selected_meta_id = get_post_meta ( $item ['product_id'], '_product_meta_id', true );
				
				$single_meta = $this -> get_product_meta ( $selected_meta_id);
					
				if ( $single_meta == NULL )
					continue;
				$product_meta = json_decode ( $single_meta->the_meta );
		
				if($product_meta){
						
					foreach ( $product_meta as $meta => $data ) {
							
						if ($data -> type == 'file' || $data -> type == 'facebook') {
							
							$product_files = '';
							if(isset($item['product_attached_files'])) {
								
								$product_files =  is_array($item['product_attached_files'])  ? $item['product_attached_files'] : unserialize( $item['product_attached_files'] );	//explode(',', $item[$data -> title]);	
							}
							
							$product_files = $product_files[$data -> title];
							$product_id = $item ['product_id'];
								
							// ppom_pa($product_files); exit;
							
							if ($product_files) {
								
								echo '<strong>';
								printf(__('File attached %s', 'ppom'), $data->title);
								echo '</strong>';
									
								$files_found = 0;
								foreach ( $product_files as $file ) {
										
									$files_found++;
									$ext = strtolower ( substr ( strrchr ( $file, '.' ), 1 ) );
										
									if ($ext == 'png' || $ext == 'jpg' || $ext == 'gif' || $ext == 'jpeg')
										$src_thumb = ppom_get_dir_url( true ) . $file;
									else
										$src_thumb = PPOM_URL . '/images/file.png';
										
									$src_file = ppom_get_dir_url() . $file;
										
									if(!file_exists($src_file)){
										$file_name = nm_get_order_id($order) . '-' . $product_id . '-' . $file;		// from version 3.4
										$src_file = ppom_get_dir_url() . 'confirmed/' . $file_name;
									}else{
										$file_name = $file;
										$src_file = ppom_get_dir_url() . '/' . $file_name;
									}
										
										
									echo '<table>';
									echo '<tr><td width="100"><img src="' . esc_url($src_thumb) . '"><td><td><a href="' . esc_url($src_file) . '">' . sprintf(__ ( "Download %s", 'ppom'), $file).'</a> ' . ppom_get_filesize_in_kb ( $file_name ) . '</td>';
										
									$edited_path = ppom_get_dir_path() . 'edits/' . $file;
									if (file_exists($edited_path)) {
										$file_url_edit = ppom_get_dir_url() .  'edits/' . $file;
										echo '<td><a href="' . esc_url($file_url_edit) . '" target="_blank">' . __ ( 'Download edited image', 'ppom') . '</a></td>';
									}
										
									echo '</tr>';
									echo '</table>';
								}
							}
		
							
						}
					}
				}
		
			}
		}
	}


	
	public static function activate_plugin() {
		global $wpdb;
	
		/*
		 * meta_for: this is to make this table to contact more then one metas for NM plugins in future in this plugin it will be populated with: forms
		 */
		$forms_table_name = $wpdb->prefix . PPOM_TABLE_META;
		
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE $forms_table_name (
		productmeta_id INT(5) NOT NULL AUTO_INCREMENT,
		productmeta_name VARCHAR(50) NOT NULL,
		productmeta_validation VARCHAR(3),
        dynamic_price_display VARCHAR(10),
        send_file_attachment VARCHAR(3) NOT NULL,
        show_cart_thumb VARCHAR(3),
		aviary_api_key VARCHAR(40),
		productmeta_style MEDIUMTEXT,
		productmeta_categories MEDIUMTEXT,
		the_meta MEDIUMTEXT NOT NULL,
		productmeta_created DATETIME NOT NULL,
		PRIMARY KEY  (productmeta_id)
		) $charset_collate;";
		
		require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta ( $sql );
		
		update_option ( "personalizedproduct_db_version", PPOM_DB_VERSION );
		
		// this is to remove un-confirmed files daily
		if ( ! wp_next_scheduled( 'do_action_remove_images' ) ) {
			wp_schedule_event( time(), 'daily', 'do_action_remove_images');
		}
		
		if ( ! wp_next_scheduled( 'setup_styles_and_scripts_wooproduct' ) ) {
			wp_schedule_event( time(), 'daily', 'setup_styles_and_scripts_wooproduct');
		}
		
	}
	
	public static function deactivate_plugin() {
		
		// do nothing so far.
		wp_clear_scheduled_hook( 'do_action_remove_images' );
		
		wp_clear_scheduled_hook( 'setup_styles_and_scripts_wooproduct' );
		
	}
	
	
	/*
	 * cloning product meta for admin
	 * being called from: templates/admin/create-form.php
	 */
	function clone_product_meta($meta_id){
		
		global $wpdb;
		
		$forms_table_name = $wpdb->prefix . PPOM_TABLE_META;
		
		$sql = "INSERT INTO $forms_table_name
		(productmeta_name, aviary_api_key, productmeta_style,productmeta_categories, the_meta, productmeta_created) 
		SELECT productmeta_name, aviary_api_key, productmeta_style,productmeta_categories, the_meta, productmeta_created 
		FROM $forms_table_name 
		WHERE productmeta_id = %d;";
		
		$result = $wpdb -> query($wpdb -> prepare($sql, array($meta_id)));
		
		/* var_dump($result);
		
		$wpdb->show_errors();
		$wpdb->print_error(); */
		
	}
	
	/*
	 * returning NM_Inputs object
	*/
	function get_all_inputs() {
	
		$nm_inputs = PPOM_Inputs();
		// webcontact_pa($this->plugin_meta);
	
		// registering all inputs here
	
		$all_inputs = array (
				
				'text' 		=> $nm_inputs->get_input ( 'text' ),
				'textarea' 	=> $nm_inputs->get_input ( 'textarea' ),
				'select' 	=> $nm_inputs->get_input ( 'select' ),
				'radio' 	=> $nm_inputs->get_input ( 'radio' ),
				'checkbox' 	=> $nm_inputs->get_input ( 'checkbox' ),
				'email' 	=> $nm_inputs->get_input ( 'email' ),
				'date' 		=> $nm_inputs->get_input ( 'date' ),
				'number' 	=> $nm_inputs->get_input ( 'number' ),
				'hidden' 	=> $nm_inputs->get_input ( 'hidden' ),
				// 'masked' 	=> $nm_inputs->get_input ( 'masked' ),
		);
		
		return apply_filters('ppom_all_inputs', $all_inputs, $nm_inputs);
	}


	function ppom_export_meta(){
		
		
		if( !empty($_POST['ppom_meta']) ){
			
			global $wpdb;
		
			$meta_in = implode(",", $_POST['ppom_meta']);
			$qry = "SELECT * FROM ". $wpdb->prefix . PPOM_TABLE_META." WHERE productmeta_id IN (".$meta_in.");";
			$all_meta = $wpdb->get_results ( $qry, ARRAY_A );
			// ppom_pa($all_meta); exit;
			
			if( ! $all_meta){
				die( __("No meta found, make sure you selected meta and then export", "ppom") );
			}
			
			$all_meta = $this -> add_slashes_array($all_meta);
			// ppom_pa($all_meta); exit;
			$filename = 'ppom-export.csv';
			$delimiter = '|';
			
			 // tell the browser it's going to be a csv file
		    header('Content-Type: application/csv');
		    // tell the browser we want to save it instead of displaying it
		    header('Content-Disposition: attachement; filename="'.$filename.'";');
		    
			// open raw memory as file so no temp files needed, you might run out of memory though
		    $f = fopen('php://output', 'w'); 
		    // loop over the input array
		    foreach ($all_meta as $line) { 
		        // generate csv lines from the inner arrays
		        fputcsv($f, $line, $delimiter); 
		    }
		    // rewrind the "file" with the csv lines
		    @fseek($f, 0);
		   
		    // make php send the generated csv lines to the browser
		    fpassthru($f);
		    
			die(0);
		} else {
			
			wp_die( __("No meta found, make sure you selected meta and then export", "ppom") );
		}
	}
	
	function add_slashes_array($arr){
		asort($arr);
		foreach ($arr as $k => $v)
	        $ReturnArray[$k] = (is_array($v)) ? $this->add_slashes_array($v) : addslashes($v);
	    return $ReturnArray;
	}
	
	function ppom_import_meta(){
		
		global $wpdb;
		//get the csv file
		// ppom_pa($_FILES);
	    $file = $_FILES['ppom_csv']['tmp_name'];
	    $handle = fopen($file,"r");
	    
	    $qry = "INSERT INTO ".$wpdb->prefix . PPOM_TABLE_META;
	    $qry .= " (
	    			aviary_api_key, 
	    			productmeta_style,
	    			productmeta_categories,
	    			send_file_attachment,
	    			show_cart_thumb,
	    			productmeta_validation,
	    			productmeta_created,
	    			productmeta_name,
	    			the_meta,
	    			dynamic_price_display
	    			) VALUES";
	    	
	    
	    $cols = array();
	    $meta_count = 0;
	    //loop through the csv file and insert into database
	    do {
	        				
            // ppom_pa($cols);
            if($cols){
	            foreach( $cols as $key => $val ) {
		            $cols[$key] = trim( $cols[$key] );
		            //$cols[$key] = iconv('UCS-2', 'UTF-8', $cols[$key]."\0") ;
		            $cols[$key] = str_replace('""', '"', $cols[$key]);
		            $cols[$key] = preg_replace("/^\"(.*)\"$/sim", "$1", $cols[$key]);
	        	}
            }
        	
        	 if ( isset($cols[0]) ) {
        	 	
        	 	$meta_count++;
	        	$qry .= "(	
	        				'".$cols[0]."',
	        				'".$cols[1]."',
	        				'".$cols[2]."',
	        				'".$cols[3]."',
	        				'".$cols[4]."',
	        				'".$cols[5]."',
	        				'".$cols[7]."',
	        				'".$cols[8]."',
	        				'".$cols[9]."',
	        				'".$cols[10]."'
	        				),";
	        				
        	// ppom_pa($cols); 
	        }
	    } while ($cols = fgetcsv($handle,5000,"|"));
	    
	    $qry = substr($qry, 0, -1);
	    
	    // print $qry; exit;
	    $res = $wpdb->query( $qry );
	    
	    // $wpdb->show_errors();
	    // $wpdb->print_error();
	    // exit;
	    
	    $response = array('class'=>'updated', 'message'=> sprintf(__("%d meta(s) imported successfully.", "ppom"),$meta_count));
	    set_transient("ppom_meta_imported", $response, 30);
	    wp_redirect(  admin_url( 'admin.php?page=ppom' ) );
   		exit;
	}
	
	
}