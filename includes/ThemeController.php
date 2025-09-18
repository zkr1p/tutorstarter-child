<?php
/**
 * Orquesta los eventos del tema y la invalidación de caché.
 */
namespace TutorstarterChild;

class ThemeController {
    public function init() {
        // Hooks para invalidar la caché cuando el estado de un usuario cambia.
        add_action('woocommerce_order_status_completed', [$this, 'clear_cache_on_order_change']);
        add_action('woocommerce_order_status_refunded', [$this, 'clear_cache_on_order_change']);
        add_action('woocommerce_subscription_status_updated', [$this, 'clear_cache_on_subscription_change'], 10, 1);
    }

    /**
     * Limpia la caché de un usuario para productos específicos cuando una orden cambia de estado.
     * @param int $order_id
     */
    public function clear_cache_on_order_change($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $user_id = $order->get_user_id();
        if (!$user_id) return;

        foreach ($order->get_items() as $item) {
            // Nos aseguramos de obtener el ID de la variación si existe.
            $product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
            $transient_key = "ebook_status_{$user_id}_prod_{$product_id}";
            
            if (false !== get_transient($transient_key)) {
                delete_transient($transient_key);
                ThemeLogger::log("Cache INVALIDATED (Order #$order_id) para la clave: $transient_key", 'INFO');
            }
        }
    }

    /**
     * Limpia TODAS las cachés de e-book de un usuario cuando el estado de su suscripción cambia.
     * VERSIÓN OPTIMIZADA: No carga todos los productos del sitio.
     * En su lugar, borra la caché usando un comodín en la base de datos, lo cual es mucho más eficiente.
     * @param \WC_Subscription $subscription
     */
    public function clear_cache_on_subscription_change($subscription) {
        $user_id = $subscription->get_user_id();
        if (!$user_id) {
            ThemeLogger::log("Evento de suscripción sin user_id. No se puede invalidar caché.", 'WARNING');
            return;
        }

        ThemeLogger::log("Evento de suscripción detectado para user_id: $user_id. Iniciando invalidación de caché optimizada.", 'INFO');

        global $wpdb;
        $transient_prefix = '_transient_ebook_status_' . $user_id . '_prod_';
        
        // Consulta SQL para borrar todos los transients que coincidan con el patrón del usuario.
        // Es mucho más rápido que cargar todos los productos con PHP.
        $query = $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $wpdb->esc_like($transient_prefix) . '%'
        );
        
        $deleted_rows = $wpdb->query($query);

        if ($deleted_rows > 0) {
            ThemeLogger::log("Se invalidaron un total de $deleted_rows cachés de e-book para user_id: $user_id.", 'INFO');
        } else {
            ThemeLogger::log("No se encontraron cachés activas para invalidar para user_id: $user_id.", 'INFO');
        }
    }
}