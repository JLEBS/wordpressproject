<?php
/*
 * all WooCommerce Hooks will be called here
 *
 *
 */

ppom_direct_access_not_allowed();

function ppom_woocommerce_show_fields() {
    
   global $product;
    
    
    // @Reason: variable product render fields twice. To stop this we add following code.
    if( apply_filters('ppom_remove_duplicate_fields', true) &&  $product->get_type() == 'variable' && current_filter() == 'woocommerce_before_add_to_cart_button') {
    	return;
    }
    
    $product_id = ppom_get_product_id( $product ); 
	$ppom		= new PPOM_Meta( $product_id );
	
	if( ! $ppom->fields ) return '';
	 
    // Loading all required scripts/css for inputs like datepicker, fileupload etc
    ppom_hooks_load_input_scripts( $product );
    
    // main css
    wp_enqueue_style( 'ppom-main', PPOM_URL.'/css/ppom-style.css');
    if ( $ppom->inline_css != '') {
		wp_add_inline_style( 'ppom-main', $ppom->inline_css );
    }
    
    // If Bootstrap is enabled
    if( ppom_load_bootstrap_css() ) {
        
        // Boostrap 4.0
        $ppom_bs_css = PPOM_URL.'/css/bootstrap/bootstrap.css';
        $ppom_bs_js  = '//stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js';
        
        // Boostrap 3.1
        // $ppom_bs_css = PPOM_URL.'/css/bootstrap/customized/css/bootstrap.css';
        // $ppom_bs_js  = PPOM_URL.'/css/bootstrap/customized/js/bootstrap.js';
        
        $ppom_bs_modal_css = PPOM_URL.'/css/bootstrap/bootstrap.modal.css';
        // $ppom_bs_css = '//stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css';
        $ppom_popper_cdn = '//cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js';
        
        
        wp_enqueue_style( 'ppom-bootstrap', $ppom_bs_css);
        wp_enqueue_style( 'ppom-bootstrap-modal', $ppom_bs_modal_css);
        
        
        wp_enqueue_script( 'ppom-popper', $ppom_popper_cdn, array('jquery'));
        wp_enqueue_script( 'bootstrap-js', $ppom_bs_js, array('jquery','ppom-popper'));
    }
    
    do_action('ppom_after_scripts_loaded', PPOM() -> productmeta_id, $product);
    
    
    $ppom_html = '<div id="ppom-box-'.esc_attr(PPOM()->productmeta_id).'" class="ppom-wrapper">';
    
    $template_vars = array('ppom_settings'  => $ppom->settings,
    						'product'	=> $product);
    ob_start();
    ppom_load_template ( 'render-fields.php', $template_vars );
    $ppom_html .= ob_get_clean();
    
    // Price container
	$ppom_html .= '<div id="ppom-price-container"></div>';
	
	// Clear fix
	$ppom_html .= '<div style="clear:both"></div>';   // Clear fix
	$ppom_html .= '</div>';   // Ends ppom-wrappper
	
	echo apply_filters('ppom_fields_html', $ppom_html, $product);
}


function ppom_woocommerce_validate_product($passed, $product_id, $qty) {
    
	$ppom		= new PPOM_Meta( $product_id );
	
	if( $ppom->ajax_validation_enabled ) {
		return $passed;
	}
	
	return ppom_check_validation($product_id, $_POST);
}

function ppom_woocommerce_ajax_validate() {
	
	// ppom_pa($_POST);
	$errors_found = array();
	
	$product_id = $_POST['ppom_product_id'];
	$passed =  ppom_check_validation($product_id, $_POST);
	
	$all_notices = wc_get_notices();
	wc_clear_notices();
	
	if( ! $passed ) {
		$errors_found = $all_notices['error'];
	}
	
	wp_send_json( array_unique($errors_found) );
}

function ppom_check_validation($product_id, $post_data, $passed=true) {
	
	$ppom		= new PPOM_Meta( $product_id );
	if( ! $ppom->fields ) return $passed;
	
	$ppom_posted_fields = isset($post_data['ppom']['fields']) ? $post_data['ppom']['fields'] : null;
	if( ! $ppom_posted_fields ) return $passed;
	
	foreach($ppom->fields as $field) {
		
		// ppom_pa($field);
		
		if( empty($field['data_name']) || empty($field['required']) 
		&& (empty($field['min_checked']) && empty($field['max_checked']) )
		) continue;
		
		$data_name	= sanitize_key($field['data_name']);
		$title		= isset($field['title']) ? $field['title'] : '';
		$type		= isset($field['type']) ? $field['type'] : '';
		
		// Check if field is required by hidden by condition
		if( ppom_is_field_hidden_by_condition($data_name) ) continue;
		
		if( ! ppom_has_posted_field_value($ppom_posted_fields, $field) ) {
			
			// Note: Checkbox is being validate by hook: ppom_has_posted_field_value
			
			$error_message = ($field['error_message'] != '') ? $title.": ".$field['error_message'] : "{$title} is a required field";
			$error_message = sprintf ( __ ( '%s', 'ppom' ), $error_message );
			$error_message = stripslashes ($error_message);
			ppom_wc_add_notice( $error_message );
			$passed = false;
		}
	}
		
	/*var_dump($passed);
	ppom_pa($post_data); exit;*/
	
	return $passed;
}


function ppom_woocommerce_add_cart_item_data($cart, $product_id) {
	
	if( ! isset($_POST['ppom']) ) return $cart;
	
	$ppom		= new PPOM_Meta( $product_id );
	if( ! $ppom->settings ) return $cart;
	
	// ADDED WC BUNDLES COMPATIBILITY
	if ( class_exists('WC_Bundles') && wc_pb_is_bundled_cart_item( $product_id )) {
		return $cart;
	}
	
	// PPOM also saving cropped images under this filter.
	$ppom_posted_fields = apply_filters('ppom_add_cart_item_data', $_POST['ppom'], $_POST);
	$cart['ppom'] = $ppom_posted_fields;
	
	// ppom_pa($_POST); exit;
	return $cart;
	
}

function ppom_woocommerce_update_cart_fees($cart_items, $values) {
	
	if( empty($cart_items) ) return $cart_items;

	if( ! isset( $values['ppom'] ) ) return $cart_items;
	
	$wc_product = $cart_items['data'];
	$product_id = ppom_get_product_id($wc_product);
	
	$ppom_item_org_price	= floatval(ppom_get_product_price($wc_product));
	$ppom_item_order_qty	= floatval($cart_items['quantity']);
	
	// Getting option price
	$option_prices = json_decode( stripslashes($values['ppom']['ppom_option_price']), true);
	// ppom_pa($option_prices);
	$total_option_price = 0;
	$ppom_matrix_price = 0;
	$ppom_quantities_price = 0;
	$ppom_total_quantities = 0;
	$ppom_quantities_include_base = false;
	$ppom_total_discount = 0;
	$ppon_onetime_cost = 0;
	$ppomm_measures = 0;
	
	
	// If quantities field found then we need to get total quantity to get correct matrix price
	// if matrix is also used
	
	if($option_prices) {
		foreach($option_prices as $option){
			if( $option['apply'] == 'quantities' ) {
				$ppom_total_quantities += $option['quantity'];
				$ppom_item_order_qty = $ppom_total_quantities;
			}

		}
	}
	

	// Check if price is set by matrix
	$matrix_found = ppom_get_price_matrix_chunk($wc_product, $option_prices, $ppom_item_order_qty);
	
	// Calculating option prices
	if($option_prices) {
		foreach($option_prices as $option){
			
			// Do not add if option is fixed/onetime
			// if( $option['apply'] != 'variable' ) continue;
			
			// ppom_get_field_option_price
			
			switch ($option['apply']) {

				case 'variable':
					
					$option_price = $option['price'];
					// verify prices from server due to security
					if( isset($option['data_name']) && isset($option['option_id'])) {
						
						$option_price = ppom_get_field_option_price_by_id($option['data_name'], $option, $wc_product);
					}
					$total_option_price += wc_format_decimal( $option_price, wc_get_price_decimals());
					break;
				
				case 'onetime':
					
					$option_price = $option['price'];
					// verify prices from server due to security
					if( isset($option['data_name']) && isset($option['option_id'])) {
						
						$option_price = ppom_get_field_option_price_by_id($option['data_name'], $option, $wc_product);
					}
					$ppon_onetime_cost += wc_format_decimal( $option_price, wc_get_price_decimals());
					break;
				
				case 'quantities':
		
					$ppom_quantities_use_option_price = apply_filters('ppom_quantities_use_option_price', true, $option_prices);
					if( $ppom_quantities_use_option_price ) { 
						
						$quantity_price = $option['price'];
						
						// If matrix found now product org price will be set to matrix
						if( !empty($matrix_found) && !isset($matrix_found['discount']) ) {
							
							$quantity_price = $matrix_found['price'];
							
						}
						
						$ppom_quantities_price += wc_format_decimal(( $quantity_price * $option['quantity'] ), wc_get_price_decimals());
						// $ppom_total_quantities += $option['quantity'];
					}
					
					if( !empty($option['include']) && $option['include'] == 'on') {
						$ppom_quantities_include_base = true;
					}
					break;
					
				case 'bulkquantity':
					
					
					// Note: May need to add matrix price like in quantites (above)
					
					$ppom_quantities_price += wc_format_decimal(($option['price'] * $option['quantity']), wc_get_price_decimals());
		
					$ppom_quantities_price += isset($option['base']) ? $option['base'] : 0;
					break;
					
				// Fixed price addon
				case 'fixedprice':
					
					$ppom_item_org_price = $option['unitprice'];
					
					// Well, it should NOT be like this but have to do this. will see later.
					$ppom_item_order_qty = 1;
					break;
					
				case 'measure':
					
					$measer_qty = isset($option['qty']) ? $option['qty'] : 0;
					$option_price = $option['price'];
					
					
					if( $option['use_units'] != 'no' ) {
					
						// verify prices from server due to security
						if( isset($option['data_name']) && isset($option['option_id'])) {
							
							$option_price = ppom_get_field_option_price_by_id($option['data_name'], $option, $wc_product);
						}
						$total_option_price += $option_price * $measer_qty;
						$total_option_price	= wc_format_decimal( $total_option_price, wc_get_price_decimals());
					} else {
					
						$ppom_item_org_price	= $option_price;
						$ppomm_measures			+= $measer_qty;
					}
					
					break;
					
			}
		}
	}
	
	
	if( $ppom_quantities_price > 0 ) {
		
		// $total_option_price = $ppom_quantities_price;
		/*$ppom_item_org_price = $ppom_quantities_price;*/
		$total_option_price = ($total_option_price * $ppom_total_quantities);
		
		
		if( ! $ppom_quantities_include_base ) {
			// $ppom_item_org_price = ($ppom_item_org_price * $ppom_total_quantities);
			$ppom_item_org_price = 0;
		}
	}
		
	// ppom_pa($matrix_found);
	if( !empty($matrix_found) ) {
		
		// Check that it's not a discount matrix
		if( ! isset($matrix_found['discount']) ) {
			$ppom_item_org_price = $matrix_found['price'];
		} else {
			
			// Discount matrix found
			if( !empty($matrix_found['percent']) ) {
						
				$total_with_options	= $ppom_item_org_price + $total_option_price + $ppon_onetime_cost;
				
				// Check wheather to apply on Both (Base+Options) or only Base
				if( $matrix_found['discount'] == 'both' ) {
					
					// Also adding quantities price if used
					$total_price_to_be_discount = $total_with_options+$ppom_quantities_price;
					
					$price_after_precent = ppom_get_amount_after_percentage($total_price_to_be_discount, $matrix_found['percent']);
				} elseif( $matrix_found['discount'] == 'base' ) {
					
					$total_price_to_be_discount = $ppom_item_org_price+$ppom_quantities_price;
					$price_after_precent = ppom_get_amount_after_percentage($total_price_to_be_discount, $matrix_found['percent']);
				}
				
				$ppom_total_discount += $price_after_precent;
			} else {
				/**
				 * when discount is in PRICE not Percent then applied to whole price Base+Option)
				 * so need to get per unit discount
				 **/
				$discount_per_unit = $matrix_found['price'] / $ppom_item_order_qty;
				$ppom_total_discount += $discount_per_unit;
			}
		}
	}
	
	// If measures found, Multiply it with options
	if( $ppomm_measures > 0 ) {
		
		$total_option_price = $total_option_price * $ppomm_measures;
		$ppom_item_org_price = $ppom_item_org_price * $ppomm_measures;
	}
	
	
	// var_dump($ppom_total_discount);
	// var_dump($ppom_item_org_price);
	// var_dump($total_option_price);
	// var_dump($ppom_quantities_price);
	
	
	$cart_line_total = ($ppom_item_org_price + $total_option_price + $ppom_quantities_price - $ppom_total_discount);
	
	$cart_line_total	= apply_filters('ppom_cart_line_total', $cart_line_total);
	
	// var_dump($cart_line_total);
	
	$wc_product -> set_price($cart_line_total);
	
	return $cart_items;
}

function ppom_calculate_totals_from_session( $cart ) {
	$cart->calculate_totals();
	// ppom_pa(WC()->session->cart);
	
}

function ppom_woocommerce_add_fixed_fee( $cart ) {
	
	$fee_no = 1;
	foreach( $cart->get_cart() as $item ){
	
		if( empty($item['ppom']['ppom_option_price']) ) continue;
		
		// Getting option price
		$option_prices = json_decode( stripslashes($item['ppom']['ppom_option_price']), true);
		
		if( $option_prices ) {
			foreach( $option_prices as $fee ) {
				
				if( $fee['apply'] != 'onetime' ) continue;
				
				
				$label = $fee_no.'-'.$fee['product_title'].': '.$fee['label'];
				$label = apply_filters('ppom_fixed_fee_label', $label, $fee, $item);
				
				$taxable = (isset($fee['taxable']) && $fee['taxable'] == 'on') ? true : false;
				$fee_price = $fee['price'];
				
				if( !empty($fee['without_tax']) ) {
					$fee_price = $fee['without_tax'];
				}
				
				// if(  'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
				// 	$taxable = false;
				// }
				
				$fee_price	= apply_filters('ppom_cart_fixed_fee', $fee_price);
				
				$cart -> add_fee( sprintf(__( "%s", 'ppom'), esc_html($label)), $fee_price, $taxable );
				$fee_no++;
			} 
		}
	}
}

// Show fixed fee in mini cart
function ppom_woocommerce_mini_cart_fixed_fee() {
	
	if( ! WC()->cart->get_fees() ) return '';
	
	$fixed_fee_html = '<table>';
	foreach ( WC()->cart->get_fees() as $fee ) {
		
		$item_fee = $fee->amount;
		if(  WC()->cart->display_prices_including_tax() && $fee->taxable ) {
			
			$item_fee = $fee->total + $fee->tax;
		}
		// var_dump($fee);
		$fixed_fee_html .= '<tr>';
			$fixed_fee_html .= '<td class="subtotal-text">'. esc_html( $fee->name );'</td>';
			$fixed_fee_html .= '<td class="subtotal-price">'. wc_price( $item_fee ).'</td>';
		$fixed_fee_html .= '</tr>';
	}
	
	$fixed_fee_html .= '<tr><td colspan="2">'.__("Total will be calcuated in Cart", "ppom").'</td></tr>';
	$fixed_fee_html .= '</table>';
	
	echo apply_filters('ppom_mini_cart_fixed_fee', $fixed_fee_html);
}

function ppom_woocommerce_add_item_meta($item_meta, $cart_item) {

	if( ! isset($cart_item['ppom']['fields']) ) return $item_meta;
	
	// ppom_pa($cart_item['ppom']);
	
	// ADDED WC BUNDLES COMPATIBILITY
	if ( class_exists('WC_Bundles') && wc_pb_is_bundled_cart_item( $cart_item )) {
		return $item_meta;
	}
	
	$ppom_meta = ppom_make_meta_data( $cart_item );
	// ppom_pa($ppom_meta);
	
	foreach( $ppom_meta as $key => $meta ) {
		
		$hidden = isset($meta['hidden']) ? $meta['hidden'] : false;
		$display = isset($meta['display']) ? $meta['display'] : $meta['value'];
		
		if( $key == 'ppom_has_quantities' ) $hidden = true;
		// if( $key == 'ppom_has_files' ) $hidden = true;
		
		if( isset( $meta['name']) ) {
		
			$meta_val = $meta['value'];
			if( apply_filters('ppom_show_option_price_cart', false) && isset($meta['price']) ) {
				$meta_val .=' ('.wc_price($meta['price']).')';
			}
			
			$meta_key = stripslashes($meta['name']);
			
			// WPML
			$meta_key = ppom_wpml_translate($meta_key, 'PPOM');
			
			$item_meta[] = array('name'	=> wp_strip_all_tags($meta_key), 'value' => $meta_val, 'hidden' => $hidden, 'display'=>$display);
		} else {
			$item_meta[] = array('name'	=> ($key), 'value' => $meta, 'hidden' => $hidden, 'display'=>$display);
		}
		
	}
	
	return $item_meta;
}

// alter price on shop page if price matrix found
function ppom_woocommerce_alter_price($price, $product) {
	
	$product_id = ppom_get_product_id($product);
	/*$ppom		= new PPOM_Meta( $product_id );
	if( ! $ppom->fields ) return $price;*/
	
	$price_matrix_found = ppom_has_field_by_type( $product_id, 'pricematrix' );
	if( empty($price_matrix_found) ) return $price;
	
	$from_pice = '';
	$to_price = '';
	
	if (!in_array($product->get_type(), array('variable', 'grouped', 'external'))) {
			
		$price_range = array();
		
		foreach($price_matrix_found as $meta){
			
			// ppom_pa($meta);
			
			if( ! ppom_is_field_visible( $meta ) ) continue;
			
			if($meta['type'] == 'pricematrix'){
				
				$options = $meta['options'];
				$ranges	 = ppom_convert_options_to_key_val($options, $meta, $product);
				// ppom_pa($ranges);	
				
				if( isset($meta['discount']) && $meta['discount'] == 'on' ) {
					
					$last_discount	= end($ranges);
					$least_price	= $last_discount['price'];
					
					if( !empty($last_discount['percent']) ) {
						$max_discount	= $last_discount['percent'];
						$least_price	= ppom_get_amount_after_percentage($product->get_price(), $max_discount);
					}
					
					$least_price	= $product->get_price() - $least_price;
					$least_price	= wc_format_decimal( $least_price, wc_get_price_decimals());
					// var_dump($least_price);
					$price = wc_price($least_price).'-'.$price;
				} else {
					
					foreach($ranges as $range){
						$price_range[] = $range['price'];
					}
					
					if( !empty($price_range) ){
					
						$from_pice = min($price_range);
						$to_price  = max($price_range);
						$price = wc_format_price_range($from_pice, $to_price);
					}
				}
			}
		}
		
	}
	
	return apply_filters('ppom_loop_matrix_price', $price, $from_pice, $to_price);
}

function ppom_hide_variation_price_html($show, $parent, $variation) {
	
	if( ! $selected_meta_id = ppom_has_product_meta( $parent->get_id()) ) return $show;
		
	
	$ppom_settings = PPOM() -> get_product_meta ( $selected_meta_id );
	if( empty($ppom_settings) ) return $show;
	
	if( $ppom_settings -> dynamic_price_display != 'hide' ) {
		$show = false;
	}
	
	return $show;
	
}

// Set max quantity for price matrix
function ppom_woocommerce_set_max_quantity( $max_quantity, $product ) {
	
	$product_id = ppom_get_product_id($product);
	$ppom		= new PPOM_Meta( $product_id );
	if( ! $ppom->is_exists ) return $max_quantity;
	
	$last_range = array();
	
	$ppom_quantities_found = ppom_has_field_by_type( $product_id, 'quantities' );
	if($ppom_quantities_found){
		foreach($ppom_quantities_found as $meta){
			
			$options = $meta['options'];
			$ranges	 = ppom_convert_options_to_key_val($options, $meta, $product);
			$last_range = end($ranges);
			$qty_ranges = explode('-', $last_range['raw']);
			$max_quantity	= $qty_ranges[1];
		}
	}
	
	return $max_quantity;
}

// Set quantity step for price matrix
function ppom_woocommerce_set_quantity_step( $quantity_step, $product ) {
	
	$product_id = ppom_get_product_id($product);
	$ppom		= new PPOM_Meta( $product_id );
	if( ! $ppom->is_exists ) return $quantity_step;
		
	$last_range = array();
	
	$ppom_quantities_found = ppom_has_field_by_type( $product_id, 'quantities' );
	if($ppom_quantities_found){
		foreach($ppom_quantities_found as $meta){
			
			$quantity_step = empty($meta['qty_step']) ? 1 : $meta['qty_step'];
		}
	}
	
	return $quantity_step;
}

// When quantities is used then reset quantity to 1
function ppom_woocommerce_add_to_cart_quantity( $quantity, $product_id ) {
	
	$ppom_quantities_found = ppom_has_field_by_type( $product_id, 'quantities' );
	
	// ppom_pa($ppom_quantities_found);
	if( ! empty($ppom_quantities_found) ) {
		
		// Found quantities field then reset quantity to 1
		$quantity = 1;
	}
	
	return $quantity;
}

// It is change cart quantity label
function ppom_woocommerce_control_cart_quantity($quantity, $cart_item_key) {
	
	$cart_item = WC()->cart->get_cart_item( $cart_item_key );
	
	// ppom_pa($cart_item)
	if( !isset($cart_item['ppom']['ppom_option_price']) &&
		!isset($cart_item['ppom']['ppom_pricematrix']) ) return $quantity;
	
	// Getting option price
	$option_prices = json_decode( stripslashes($cart_item['ppom']['ppom_option_price']), true);
	$ppom_has_quantities = 0;
	// ppom_pa($option_prices);
	
	if( empty($option_prices) ) return $quantity;
	
	foreach($option_prices as $option) {
		
		if( isset($option['include']) && $option['include'] == '') {
			if( isset($option['quantity']) ) {
				$ppom_has_quantities += intval( $option['quantity'] );
			}
		} elseif(isset($option['include']) && $option['include'] == 'on') {
			$ppom_has_quantities = 1;
		}
	}
	
	// var_dump($ppom_has_quantities);
	// If no quantity updated then return default
	$ppom_quantitiles_allow_update_cart = apply_filters('ppom_quantities_allow_cart_update', false, $option_prices);
	if( $ppom_has_quantities != 0 && !$ppom_quantitiles_allow_update_cart) {
		$quantity = '<span class="ppom-cart-quantity">'.$ppom_has_quantities.'</span>';
	}
	
	return $quantity;
}

// Control subtotal when quantities input used
function ppom_woocommerce_item_subtotal( $item_subtotal, $cart_item, $cart_item_key) {
	
	if( !isset($cart_item['ppom']['ppom_option_price']) ) return $item_subtotal;
	
	// Getting option price
	$option_prices = json_decode( stripslashes($cart_item['ppom']['ppom_option_price']), true);
	if( empty($option_prices) ) return $item_subtotal;
	
	$ppom_has_quantities = 0;
	foreach($option_prices as $option) {
		
		if( isset($option['quantity']) ) {
			$ppom_has_quantities += intval( $option['quantity'] );
		}
	}
	
	// If no quantity updated then return default
	if( $ppom_has_quantities == 0 ) return $item_subtotal;
	
	$_product = $cart_item['data'];
	$item_quantity = 1;
	return WC()->cart->get_product_subtotal( $_product,  $item_quantity);
	
}

function ppom_woocommerce_control_checkout_quantity($quantity, $cart_item, $cart_item_key) {
	
	if( !isset($cart_item['ppom']['ppom_option_price']) ) return $quantity;
	
	// Getting option price
	$option_prices = json_decode( stripslashes($cart_item['ppom']['ppom_option_price']), true);
	if( empty($option_prices) ) return $quantity;
	
	$ppom_has_quantities = 0;
	foreach($option_prices as $option) {
		
		if( isset($option['quantity']) ) {
			$ppom_has_quantities += intval( $option['quantity'] );
		}
	}
	
	// If no quantity updated then return default
	if( $ppom_has_quantities == 0 ) return $quantity;
	
	$quantity = '<strong class="product-quantity">' . sprintf( "&times; %s", $ppom_has_quantities ) . '</strong>';
	
	return $quantity;
}

function ppom_woocommerce_control_oder_item_quantity($quantity, $item) {
	
	$ppom_has_quantities = 0;
	
	foreach( $item->get_meta_data() as $meta ) {
		if( $meta -> key == 'ppom_has_quantities') {
			$ppom_has_quantities = $meta->value;
		}
	}
	
	if( $ppom_has_quantities == 0 ) return $quantity;
	
	$quantity = '<strong class="product-quantity">' . sprintf( "&times; %s", $ppom_has_quantities ) . '</strong>';
	
	return $quantity;
}

function ppom_woocommerce_control_email_item_quantity($quantity, $item) {
	
	$ppom_has_quantities = 0;
	
	foreach( $item->get_meta_data() as $meta ) {
		if( $meta -> key == 'ppom_has_quantities') {
			$ppom_has_quantities = $meta->value;
		}
	}
	
	if( $ppom_has_quantities == 0 ) return $quantity;
	
	$quantity = '<strong class="product-quantity">' . sprintf( "%s", $ppom_has_quantities ) . '</strong>';
	
	return $quantity;
}

function ppom_woocommerce_control_order_item_quantity($quantity, $item) {
	
	$ppom_has_quantities = 0;
	
	foreach( $item->get_meta_data() as $meta ) {
		if( $meta -> key == 'ppom_has_quantities') {
			$ppom_has_quantities = $meta->value;
		}
	}
	
	if( $ppom_has_quantities == 0 ) return $quantity;
	
	$quantity = $ppom_has_quantities;
	
	return $quantity;
}

function ppom_woocommerce_cart_update_validate( $cart_validated, $cart_item_key, $values, $quantity ) {
	
	$max_quantity = ppom_get_cart_item_max_quantity( $values );
	
	if( ! is_null($max_quantity) && $quantity > intval($max_quantity) ) {
		
		$cart_validated = false;
		wc_add_notice( sprintf( __( 'Sorry, maximum quantity is %d.', 'ppom' ), $max_quantity ), 'error' );
	}
	
	return $cart_validated;
}


function ppom_woocommerce_order_item_meta($item_id, $cart_item, $order_id) {
	

	if ( ! isset ( $cart_item ['ppom']['fields'] )) {
		return;
	}
	
	// ADDED WC BUNDLES COMPATIBILITY
	if ( class_exists('WC_Bundles') && wc_pb_is_bundled_cart_item( $cart_item )) {
		return;
	}
	
	
	$ppom_meta = ppom_make_meta_data( $cart_item, 'order' );
	// var_dump($ppom_meta); exit;
	foreach( $ppom_meta as $key => $meta ) {
		
		ppom_add_order_item_meta($item_id, $key, $meta['value']);
	}
	
}

// Changing order item meta key to label
function ppom_woocommerce_order_key( $display_key, $meta, $item ) {
	
	if ($item->get_type() != 'line_item') return $display_key;
	
	$field_meta = ppom_get_field_meta_by_dataname( $item->get_product_id(), $display_key );
	if( isset($field_meta['title']) && $field_meta['title'] != '' ) {
		$display_key = stripslashes( $field_meta['title'] );
	}
	
	return $display_key;
}

function ppom_woocommerce_order_value( $display_value, $meta=null, $item=null ) {
	
	if( is_null($item) ) return $display_value;
	
	
	if ($item->get_type() != 'line_item') return $display_value;
	
	$field_meta = ppom_get_field_meta_by_dataname( $item->get_product_id(), $meta->key );
	
	// if( ! isset($field_meta['type']) ) return $display_value;
	
	$input_type = isset($field_meta['type']) ? $field_meta['type'] : '';
	
	switch( $input_type ) {
		
		case 'file':
		case 'cropper':
			
			/**
			 * File upload and croppers now save only filename in meta
			 * seperated by commas, now here we will build it's html to show thumbs in item orde
			 * @since: 10.10
			 **/
			 $display_value = ppom_generate_html_for_files($meta->value, $input_type, $item);
			 break;
			 
		case 'image':
			$display_value = $meta->value;
			break;
			
		default:
			
			// Important hook: changing order value format using local hooks
			// Also being used for export order lite
			$display_value = apply_filters('ppom_order_display_value', $display_value, $meta, $item);
			break;
	 
	}
	
	return $display_value;
}


// Hiding some ppom meta like ppom_has_quantities
function ppom_woocommerce_hide_order_meta($formatted_meta, $order_item) {
	
	if( empty($formatted_meta) ) return $formatted_meta;
	
	$ppom_meta_searching = $formatted_meta;
	// ppom_has_quantities
	foreach( $ppom_meta_searching as $meta_id => $meta_data ) {
		
		if( $meta_data->key == 'ppom_has_quantities' ) {
			unset( $formatted_meta[$meta_id] );
		}
	}
	
	return $formatted_meta;
}

// When order paid update filename with order number
function ppom_woocommerce_rename_files( $order_id, $posted_data, $order ){
	
	global $woocommerce;

	// getting product id in cart
	$cart = WC()->cart->get_cart();
	
	// ppom_pa($cart); exit;
	
	
	
	// since 8.1, files will be send to email as attachment
	
	//ppom_pa($cart); exit;
	foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item){
		
		// ppom_pa($cart_item); exit;
		if( !isset($cart_item['ppom']['fields']) ) continue;
		
		$product_id = $cart_item['product_id'];
		$all_moved_files = array();
		
		foreach( $cart_item['ppom']['fields'] as $key => $values) {
			
			if( $key == 'id' ) continue;
			
			$field_meta = ppom_get_field_meta_by_dataname( $product_id, $key );
			if( ! $field_meta ) continue;
			
			$field_type = $field_meta['type'];
			$field_label= isset($field_meta['title']) ? $field_meta['title'] : $field_meta['data_name'];
			$moved_files = array();
			
			if( $field_type == 'file' ||  $field_type == 'cropper') {
				
				$base_dir_path 		= ppom_get_dir_path();
				$confirm_dir		= 'confirmed/'.$order_id;
				$confirmed_dir_path = ppom_get_dir_path($confirm_dir);
				$edits_dir_path 	= ppom_get_dir_path('edits');
				
				foreach($values as $file_id => $file_data) {
					
					$file_name		= $file_data['org'];
					$file_cropped	= isset($file_data['cropped']) ? true : false;
					
					$new_filename	= ppom_file_get_name($file_name, $product_id, $cart_item);
					$source_file	= $base_dir_path . $file_name;
					$destination_path	= $confirmed_dir_path . $new_filename;
					
					
					if (file_exists ( $destination_path )) {
						break;
					}
					
					/*$moved_files[] = array('path' => $destination_path,
											'file_name' => $file_name,
											'product_id' => $product_id);*/
																		
					if (file_exists ( $source_file )) {
						
						if (! rename ( $source_file, $destination_path )) {
							die ( 'Error while re-naming order image ' . $source_file );
						}
					}
					
					//renaming edited files
					$source_file_edit = $edits_dir_path . $file_name;
					$destination_path_edit = '';
					
					$file_edited = false;
					if (file_exists ( $source_file_edit )) {
						
						$destination_path_edit = $edits_dir_path . $new_filename;	
						if (! rename ( $source_file_edit, $destination_path_edit )){
							die ( 'Error while re-naming order image ' . $source_file_edit );
						}else{
							$file_edited = true;
						}
					}
					
					$moved_files[] = array(
											'path'		=> $destination_path,
											'file_name' => $file_name,
											'file_label'=> $field_label,
											'file_cropped'=> $file_cropped,
											'file_edited'=>$file_edited,
											'file_edit_path'=>$destination_path_edit,
											'product_id'=> $product_id,
											'field_name'	=> $key);
							
					// $moved_files['file_edited'] = $file_edited;
				}
				
				$all_moved_files[$key] = $moved_files;
			}
		}
		
		do_action('ppom_after_files_moved', $all_moved_files, $order_id, $order);
	}
}