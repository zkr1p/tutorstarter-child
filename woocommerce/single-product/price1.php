<?php
/**
 * Single Product Price Override
 * Tema hijo: tutorstarter-child
 * Ruta: woocommerce/single-product/price.php
 * VERSIÓN MODIFICADA: Comprueba el estado de las descargas antes de mostrar el precio a cero.
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Obtiene ID de usuario y librería de suscripciones
$user_id = get_current_user_id();
$subs    = new \boctulus\TutorNewCourses\libs\WCSubscriptionsExtended();

$override_price_zero = false;

// Comprueba si el usuario tiene una suscripción activa
if ( $user_id && $subs->hasActive( $user_id ) ) {
    $product_id = $product->get_id();

    // Comprueba si el producto es elegible para la suscripción
    if ( \boctulus\TutorNewCourses\libs\WCSubscriptionsExtended::isForSubscription( $product_id ) ) {
        
        // --- INICIO DE LA NUEVA LÓGICA ---
        // Comprueba si el EbookStatusManager ha determinado que las descargas están agotadas.
        $is_exhausted = class_exists('\TutorstarterChild\EbookStatusManager') && \TutorstarterChild\EbookStatusManager::$ebook_status_holder === 'exhausted';

        // Solo se anula el precio a cero si las descargas NO están agotadas.
        if (!$is_exhausted) {
            $override_price_zero = true;
        }
        // --- FIN DE LA NUEVA LÓGICA ---
    }
}

// Muestra el precio
if ( $override_price_zero ) {
    // Muestra el precio a cero para suscriptores con descargas disponibles.
    echo '<p class="price">' . wc_price( 0 ) . '</p>';
} else {
    // Muestra el precio normal para no suscriptores O para suscriptores con descargas agotadas.
    echo '<p class="price">' . $product->get_price_html() . '</p>';
}