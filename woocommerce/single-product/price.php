<?php
/**
 * Single Product Price
 * Sobrescribe la plantilla de WooCommerce para mostrar precios dinámicos a suscriptores.
 */

defined( 'ABSPATH' ) || exit;

global $product;

$user_id = get_current_user_id();
$override_price_to_zero = false;

// Solo aplicamos la lógica si tenemos un usuario con sesión iniciada.
if ( $user_id > 0 && $product ) {
    // Verificamos si la clase y el método del plugin existen.
    if ( class_exists('\boctulus\TutorNewCourses\libs\WCSubscriptionsExtended') && method_exists('\boctulus\TutorNewCourses\libs\WCSubscriptionsExtended', 'isForSubscription') ) {
        $subs_checker = new \boctulus\TutorNewCourses\libs\WCSubscriptionsExtended();

        // 1. ¿El usuario tiene una suscripción activa?
        // 2. ¿Este producto es elegible para ser gratuito con la suscripción?
        if ( $subs_checker->hasActive($user_id) && \boctulus\TutorNewCourses\libs\WCSubscriptionsExtended::isForSubscription($product->get_id()) ) {

            // Comprobamos si las descargas están agotadas usando el EbookStatusManager del tema.
            $is_exhausted = false;
            if (class_exists('\TutorstarterChild\EbookStatusManager')) {
                // Usamos el holder estático que ya calcula el estado para productos variables.
                // Para productos simples, podríamos necesitar una comprobación más directa si el holder no se llena.
                $is_exhausted = \TutorstarterChild\EbookStatusManager::$ebook_status_holder === 'exhausted';
            }
            
            // Solo mostramos el precio a cero si las descargas NO están agotadas.
            if (!$is_exhausted) {
                $override_price_to_zero = true;
            }
        }
    }
}

// Renderizamos el precio final.
if ( $override_price_to_zero ) {
    echo '<p class="price">' . wc_price(0) . '</p>';
} else {
    echo '<p class="price">' . $product->get_price_html() . '</p>';
}