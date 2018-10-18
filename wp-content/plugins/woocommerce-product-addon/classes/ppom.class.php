<?php
/**
 * PPOM Meta Class
 * @since version 15.0
 * 
 * */
 
 
class PPOM_Meta {
    
        protected static $wc_product;
        var $product_id;
        var $meta_id;
        
        // $product_id can be null if get instance to get data by meta_id
        function __construct( $product_id=null ) {
            
            self::$wc_product = wc_get_product( $product_id );
            $this->meta_id    = null;
            $this->product_id = $product_id;
            
            //Now we are creating properties agains each methods in our Alpha class.
            $methods = get_class_methods( $this );
            $excluded_methods = array('__construct', 
                                        'get_settings_by_id');
                                        
            foreach ( $methods as $method ) {
                if ( ! in_array($method, $excluded_methods) ) {
                    $this->$method = $this->$method();
                }
            }
        }
        
        
        // Properties functions
        function is_exists() {
            
            $this->meta_id = get_post_meta ( $this->product_id, PPOM_PRODUCT_META_KEY, true );
            if( $this->meta_id == 0 || $this->meta_id == 'None' ) {
        		$this->meta_id = null;
        	}
            
            if( $this->meta_id == null ) {
        		if($meta_found = ppom_has_category_meta( $this->product_id ) ){
            		
            		/**
            		 * checking product against categories
            		 * @since 6.4
            		 */
            		$this->meta_id = $meta_found;
            	}
        	}
        	
        	return $this->meta_id == null ? false : true;
        }
        
        // getting settings
        function settings() {
            
            if( ! $this->is_exists() )
    			return null;
    			
    		global $wpdb;
    		$meta_id = $this->meta_id;
    		$qry = "SELECT * FROM " . $wpdb->prefix . PPOM_TABLE_META . " WHERE productmeta_id = {$meta_id}";
    		$meta_settings = $wpdb->get_row ( $qry );
    		
    		$meta_settings = empty($meta_settings) ? null : $meta_settings;
    		
    		return apply_filters('ppom_meta_settings', $meta_settings, $this);
        }
        
        // getting fields
        function fields() {
            
            if( ! $this->is_exists() )
    			return null;
    			
    		// Meta created without any fields
            if( ! $this->settings() ) return null;
    			
            $fields_meta = json_decode ( $this->settings()->the_meta, true );
            
            if( empty($fields_meta) ) return null;
            
            return apply_filters('ppom_meta_fields', $fields_meta, $this);
        }
        
        // check meta settings: ajax validation
        function ajax_validation_enabled() {
            
            $validation_enabled = false;
            
            if( ! $this->is_exists() )
    			return null;
    			
    		// Meta created without any fields
            if( ! $this->settings() ) return null;
            
            if( $this->settings()->productmeta_validation == 'yes' ) {
                $validation_enabled = true;
            }
    			
            return apply_filters('ppom_ajax_validation_enabled', $validation_enabled, $this);
        }
        
        // check meta settings: styels
        function inline_css() {
            
            $inline_css = '';
            
            if( ! $this->is_exists() )
    			return null;
    			
    		// Meta created without any fields
            if( ! $this->settings() ) return null;
            
            if( $this->settings()->productmeta_style != '' ) {
                $inline_css = stripslashes(strip_tags( $this->settings()->productmeta_style ));
            }
    			
            return apply_filters('ppom_inline_css', $inline_css, $this);
        }
        
        // check meta settings: styels
        function price_display() {
            
            $price_display = '';
            
            if( ! $this->is_exists() )
    			return null;
    			
    		// Meta created without any fields
            if( ! $this->settings() ) return null;
            
            $price_display = $this->settings()->dynamic_price_display;
    			
            return apply_filters('ppom_price_display', $price_display, $this);
        }
        
        // check meta settings: styels
        function meta_title() {
            
            $meta_title = '';
            
            if( ! $this->is_exists() )
    			return null;
    			
    		// Meta created without any fields
            if( ! $this->settings() ) return null;
            
            $meta_title = $this->settings()->productmeta_name;
    			
            return apply_filters('ppom_meta_title', $meta_title, $this);
        }
        
        
        /* ============== Get settings by metaid  ================= */
        function get_settings_by_id( $meta_id ) {
            
            global $wpdb;
            
    		$qry = "SELECT * FROM " . $wpdb->prefix . PPOM_TABLE_META . " WHERE productmeta_id = {$meta_id}";
    		$meta_settings = $wpdb->get_row ( $qry );
    		
    		$meta_settings = empty($meta_settings) ? null : $meta_settings;
    		
    		return apply_filters('ppom_get_settings_by_id', $meta_settings, $meta_id, $this);
        }
        
}
 
 