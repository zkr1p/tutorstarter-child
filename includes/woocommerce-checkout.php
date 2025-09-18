<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Personalizaciones completas de la página de pago.
 */

// 1) Cambiar etiquetas de Estado y Ciudad para EE.UU.
add_filter('woocommerce_get_country_locale', function($locale) {
    $locale['US']['state']['label'] = __('Estado', 'woocommerce');
    $locale['US']['city']['label'] = __('Ciudad', 'woocommerce');
    return $locale;
});

// 2) Mostrar/ocultar campos de checkout según si el carrito necesita envío.
add_filter('woocommerce_checkout_fields', function($fields) {
    $needs_shipping = WC()->cart && WC()->cart->needs_shipping();

    $billing_fields_to_keep = [
        'billing_first_name', 'billing_last_name', 'billing_email', 'billing_phone'
    ];
    
    if ($needs_shipping) {
        $shipping_fields = ['billing_country', 'billing_state', 'billing_city', 'billing_address_1', 'billing_postcode'];
        $billing_fields_to_keep = array_merge($billing_fields_to_keep, $shipping_fields);
    }

    // Filtramos el array para mantener solo los campos deseados.
    $fields['billing'] = array_intersect_key($fields['billing'], array_flip($billing_fields_to_keep));

    // Elimina el campo de "Notas del pedido"
    unset($fields['order']['order_comments']);

    return $fields;
}, 20);

// 3) Limitar países a solo EE.UU.
add_filter('woocommerce_countries_allowed_countries', 'crf_only_us_country');
add_filter('woocommerce_countries_shipping_countries', 'crf_only_us_country');
function crf_only_us_country($countries) {
    return ['US' => __('United States', 'woocommerce')];
}

// 4) Preseleccionar EE.UU. por defecto.
add_filter('woocommerce_default_address_fields', function($address_fields) {
    $address_fields['country']['default'] = 'US';
    return $address_fields;
});

// 5) Oculta "Enviar a una dirección diferente" y los campos de envío.
add_filter('woocommerce_cart_needs_shipping_address', '__return_false');