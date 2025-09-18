<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Añade un nuevo tab "Reservas" a la página "Mi Cuenta" de WooCommerce.
 */
add_filter('woocommerce_account_menu_items', function($items) {
    if (current_user_can('wpamelia-customer')) {
        $new_items = [];
        $count = 0;
        foreach ($items as $key => $value) {
            if (++$count === 2) { // Insertar en segunda posición
                $new_items['reservas'] = 'Reservas';
            }
            $new_items[$key] = $value;
        }
        return $new_items;
    }
    return $items;
}, 10, 1);

/**
 * Agrega el endpoint para que WordPress reconozca la URL /reservas/.
 */
add_action('init', function() {
    add_rewrite_endpoint('reservas', EP_ROOT | EP_PAGES);
});

/**
 * Muestra el contenido del shortcode en el endpoint del nuevo tab.
 */
add_action('woocommerce_account_reservas_endpoint', function() {
    echo do_shortcode('[ameliacustomerpanel]');
});

// IMPORTANTE: Después de guardar este archivo, ve a WordPress Admin > Ajustes >
// Enlaces Permanentes y haz clic en "Guardar Cambios" para que el nuevo
// endpoint "reservas" sea reconocido por el sistema.