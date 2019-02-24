<?php
/*
Plugin Name: Paczkomaty InPost
Plugin URI: https://bogaczek.com
Description: Klient ma możliwość wyboru paczkomatu z geowidgetu. Nazwa wraz z lokalizacją Paczkomatu jest zapisywana w zamówieniu. Należy ręcznie ustawić ID metody wysyłki, przy której ma wyświetlać się opcja wyboru paczkomatu w linijce 39 kodu.
Version: 0.7
Author: Black Sun
Author URI: https://bogaczek.com
Text Domain: paczkomaty-inpost
*/ 
if ( ! defined( 'WPINC' ) ) {
	die;
}

// enqueuing styles for plugin
function geowidget_styles() {
	if (is_checkout()) {
		wp_enqueue_style( 'geowidget-style', 'https://geowidget.easypack24.net/css/easypack.css' );
	}
}
add_action('wp_enqueue_scripts', 'geowidget_styles', 666 );

// enqueuing scripts for plugin
function geowidget_scripts() {
	if (is_checkout()) {
    	//wp_enqueue_script( 'geowidget-settings', plugin_dir_url(__FILE__).'/assets/js/scripts.js' );
		wp_enqueue_script( 'geowidget-sdk', 'https://geowidget.easypack24.net/js/sdk-for-javascript.js' );
	}
}
add_action('wp_enqueue_scripts', 'geowidget_scripts', 666);


/**
* Display paczkomat field in WooCommerce Checkout
*/
add_action( 'woocommerce_after_shipping_rate', 'checkout_shipping_parcel_locker_field', 20, 2 );
function checkout_shipping_parcel_locker_field( $method, $index )
{
    if( $method->get_id() == 'flat_rate:11' ){
?>
<div class="easypack-parcel-machine-select">
	<a id="popup-btn"></a>
	<button class="button" name="geowidget_show_map" id="geowidget_show_map" value="Wybierz paczkomat" data-value="Wybierz paczkomat">Wybierz paczkomat</button>

	<div id="selected-parcel-machine" class="hidden">
			<span id="selected-parcel-machine-id"></span>
	</div>
	<input type="hidden" id="parcel_machine_id" name="parcel_machine_id">
	<input type="hidden" id="parcel_machine_desc" name="parcel_machine_desc">
</div>

<script>
    	var initiated = false;
		window.easyPackAsyncInit = (function () {
        	easyPack.init({});
        });
                
		jQuery('#geowidget_show_map').click(function (e) {
			e.preventDefault();
            easyPack.init({
            	apiEndpoint: 'https://api-pl-points.easypack24.net/v1',
                defaultLocale: 'pl',
                closeTooltip: false,
                points: {
                	types: ['parcel_locker']
                        },
                map: {
                	useGeolocation: true
                     }
                    });
            easyPack.modalMap(function (point) {
            	this.close();   // Close modal with map, must be called from inside modalMap() callback.
                var parcelMachineAddressDesc = getAddressByPoint(point);
                jQuery('#parcel_machine_id').val(point.name);
                jQuery('#parcel_machine_desc').val(parcelMachineAddressDesc);
                jQuery('#selected-parcel-machine').removeClass('hidden');
                jQuery('#selected-parcel-machine-id').html(parcelMachineAddressDesc);
            }, 
			{width: 500, height: 600});
				setTimeout(function () {
                	jQuery("html, body").animate({
						scrollTop: jQuery('#widget-modal').offset().top
					}, 500);
                }, 0);
        });

        jQuery(document).ready(function () {
        	if (false === initiated) {
				if (typeof(easyPack) !== 'undefined') {
                	easyPack.init({
                    	apiEndpoint: '',
                        defaultLocale: 'pl',
                        closeTooltip: false,
                        points: {
                        	types: ['parcel_locker']
                        },
                        map: {
                        	useGeolocation: true
                        }
                    });
                }
                initiated = true;
           	}
        });

		//getting data from geowidget
		jQuery(document).ready(function(){
    		jQuery( '#parcel_machine_id' ).bind( 'change', function() {
        		jQuery('.easypack-widget').hide();
    		});
		});

		function getAddressByPoint($pointObject) {
    		var $output = '<p><strong>';
    		$output  += $pointObject.name;
    		$output  += '</strong>, <br/>';
    		$output  += $pointObject.address.line1;
    		$output  += ', <br/>';
    		$output  += $pointObject.address.line2;
    		$output  += ', <br/>';
    		$output  += $pointObject.location_description;
    		$output += '.</p>';
			return $output;
		}
			/*
			jQuery(document).ready(function($){ 
				$("input[id='shipping_method_0_flat_rate11']").click(function() {
					var test = $(this).val();
					$(".easypack-parcel-machine-select").hide();
					$("#"+test).show();
				}); 
			});
			*/
</script><?php
    }
}


/**
* Save Paczkomat field in the order meta
*/
function dexter_checkout_parcel_machine_id_update_order_meta( $order_id ) {
    if ( ! empty( $_POST['parcel_machine_id'] ) ) {
        update_post_meta( $order_id, '_parcel_machine_id', sanitize_text_field( $_POST['parcel_machine_id'] ) );
    }
	if ( ! empty( $_POST['parcel_machine_desc'] ) ) {
        update_post_meta( $order_id, '_parcel_machine_desc', sanitize_text_field( $_POST['parcel_machine_desc'] ) );
    }	
}
add_action( 'woocommerce_checkout_update_order_meta', 'dexter_checkout_parcel_machine_id_update_order_meta' );


/**
 * Display Paczkomat field in order edit screen
 */
function dexter_parcel_machine_id_display_admin_order_meta( $order ) {
    echo '<p><strong>' . __( 'Paczkomat', 'woocommerce' ) . ':</strong> ' . get_post_meta( $order->id, '_parcel_machine_id', true ) . '</p>';
	echo '<p><strong>' . __( 'Lokalizacja Paczkomatu', 'woocommerce' ) . ':</strong> ' . get_post_meta( $order->id, '_parcel_machine_desc', true ) . '</p>';
}
add_action( 'woocommerce_admin_order_data_after_billing_address', 'dexter_parcel_machine_id_display_admin_order_meta', 10, 1 );
?>