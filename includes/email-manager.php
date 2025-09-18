<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Gestor de Correos Transaccionales Personalizados.
 */

/**
 * Función genérica para cargar y renderizar plantillas de correo desde /templates/emails/.
 *
 * @param string $template_name El nombre del archivo de la plantilla (ej: 'digital-purchase-es.html').
 * @param array $variables Un array de variables para reemplazar en la plantilla (ej: ['customer_name' => 'Juan']).
 * @return string El contenido HTML del correo, listo para enviar.
 */
function get_themed_email_template($template_name, $variables = []) {
    $template_path = get_stylesheet_directory() . "/templates/emails/{$template_name}";

    if (!file_exists($template_path)) {
        error_log("No se encontró la plantilla de correo: " . $template_path);
        return '';
    }

    $template_content = file_get_contents($template_path);

    foreach ($variables as $key => $value) {
        $template_content = str_replace('{{' . $key . '}}', $value, $template_content);
    }

    return $template_content;
}

// =========================================================================
// HOOKS DE CORREOS
// =========================================================================

/**
 * 1. Correo para productos digitales cuando la orden se completa.
 */
add_action('woocommerce_order_status_completed', 'send_digital_product_email', 10, 1);
function send_digital_product_email($order_id) {
    // (El código de esta función no cambia)
    $order = wc_get_order($order_id);
    if (!$order) return;
    $has_digital_product = false;
    $product_names = [];
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if ($product && $product->is_downloadable()) {
            $has_digital_product = true;
            $product_names[] = $product->get_name();
        }
    }
    if (!$has_digital_product) return;
    $language = apply_filters('wpml_element_language_code', 'es', ['element_id' => $order_id, 'element_type' => 'post_order']);
    $subject = $language === 'en' ? 'Your Digital Purchase is Ready' : 'Tu compra digital está lista';
    $template_file = $language === 'en' ? 'digital-purchase-en.html' : 'digital-purchase-es.html';
    $variables = [
        'customer_name' => esc_html($order->get_billing_first_name()),
        'product_list'  => esc_html(implode(', ', $product_names)),
        'download_url'  => $language === 'en' ? 'https://taxes4pros.com/en/account/downloads/' : 'https://taxes4pros.com/cuenta/downloads/',
        'site_url'      => $language === 'en' ? 'https://taxes4pros.com/en' : 'https://taxes4pros.com'
    ];
    $body = get_themed_email_template($template_file, $variables);
    if (empty($body)) return;
    wp_mail($order->get_billing_email(), $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
}

/**
 * 2. Correo de bienvenida para nuevas suscripciones. (VERSIÓN CORREGIDA)
 */
add_action('woocommerce_checkout_subscription_created', 'send_subscription_welcome_email', 10, 1);
function send_subscription_welcome_email($subscription) {
    // Mapeo de IDs de productos de suscripción.
    $product_ids_map = [
        'monthly' => ['es' => 17723, 'en' => 18148],
        'annual'  => ['es' => 17725, 'en' => 18147]
    ];

    foreach ($subscription->get_items() as $item) {
        $product_id = $item->get_product_id();
        
        foreach ($product_ids_map as $type => $languages) {
            // Buscamos si el ID del producto coincide con alguno de nuestros productos de suscripción.
            if ($language = array_search($product_id, $languages)) {
                
                $subject = $language === 'es' ? "¡Bienvenido a Taxes4Pros!" : "Welcome to Taxes4Pros!";
                $template_file = "subscription-{$type}-{$language}.html";
                
                // Preparamos las variables para la plantilla usando el formato {{...}}
                $variables = [
                    'customer_name' => esc_html($subscription->get_billing_first_name())
                ];

                // Usamos nuestra función centralizada para obtener el correo.
                $body = get_themed_email_template($template_file, $variables);
                
                if (!empty($body)) {
                    wp_mail($subscription->get_billing_email(), $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
                }
                
                // Salimos del bucle una vez que hemos encontrado el producto y enviado el correo.
                return;
            }
        }
    }
}


/**
 * 3. Correo de confirmación para Preorders cuando la orden se completa.
 */
add_action('woocommerce_order_status_completed', 'send_preorder_confirmation_email', 20, 1);
function send_preorder_confirmation_email($order_id) {
    // (El código de esta función no cambia)
    $order = wc_get_order($order_id);
    if (!$order) return;
    $preorder_items = [];
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if ($product && $product->backorders_allowed()) {
            $preorder_items[] = sprintf('%dx %s', $item->get_quantity(), $product->get_name());
        }
    }
    if (empty($preorder_items)) return;
    $language = apply_filters('wpml_element_language_code', 'es', ['element_id' => $order_id, 'element_type' => 'post_order']);
    $subject = $language === 'en' ? '🔖 Your Preorder Is Confirmed' : '🔖 Tu reserva se ha confirmado';
    $template_file = $language === 'en' ? 'preorder-en.html' : 'preorder-es.html';
    $variables = [
        'customer_name' => esc_html($order->get_billing_first_name()),
        'product_list'  => '<ul><li>' . implode('</li><li>', array_map('esc_html', $preorder_items)) . '</li></ul>',
        'order_url'     => esc_url($order->get_view_order_url()),
        'site_url'      => $language === 'en' ? 'https://taxes4pros.com/en' : 'https://taxes4pros.com'
    ];
    $body = get_themed_email_template($template_file, $variables);
    if (empty($body)) return;
    wp_mail($order->get_billing_email(), $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
}

/**
 * 4. Correo de bienvenida para el Tour Fiscal cuando la orden está en "processing".
 */
add_action('woocommerce_order_status_processing', 'send_tour_fiscal_email', 20, 1);
function send_tour_fiscal_email($order_id) {
    // (El código de esta función no cambia)
    $order = wc_get_order($order_id);
    if (!$order) return;
    $tour_product_ids = [25731, 25732, 25733, 25734, 25735];
    $has_tour_product = false;
    $tour_product_names = '';
    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        $variation_id = $item->get_variation_id();
        if (in_array($product_id, $tour_product_ids) || in_array($variation_id, $tour_product_ids)) {
            $has_tour_product = true;
            $tour_product_names .= '<li>' . esc_html($item->get_name()) . '</li>';
        }
    }
    if (!$has_tour_product) return;
    $subject = '¡Bienvenido/a al Tour de Reformas Fiscales!';
    $template_file = 'tour-fiscal-es.html';
    $variables = [
        'customer_name' => esc_html($order->get_billing_first_name()),
        'product_list'  => '<ul>' . $tour_product_names . '</ul>',
        'order_url'     => esc_url($order->get_view_order_url())
    ];
    $body = get_themed_email_template($template_file, $variables);
    if (empty($body)) return;
    wp_mail($order->get_billing_email(), $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
    $order->add_order_note('Correo de bienvenida al Tour de Reformas Fiscales enviado.', false);
    $order->save();
}