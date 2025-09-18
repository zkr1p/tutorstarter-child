<?php
/**
 * Gestiona la lógica de negocio y el estado de compra/descarga para los E-books.
 */
namespace TutorstarterChild;

class EbookStatusManager {
    /**
     * @var string|null Almacena el estado del e-book para que otros archivos puedan consultarlo.
     */
    public static $ebook_status_holder = null;

    /**
     * Obtiene el estado de compra de un e-book para un usuario.
     * Implementa un sistema de caché por usuario y maneja casos de preventa.
     *
     * @param int $user_id ID del usuario.
     * @param \WC_Product_Variable $product Objeto del producto de WooCommerce.
     * @return array Datos de estado para la UI.
     */
    public function get_status(int $user_id, \WC_Product_Variable $product): array {
        
        ThemeLogger::log("==============================================================");
        ThemeLogger::log("Iniciando análisis para Producto ID: " . $product->get_id() . " - Nombre: " . $product->get_name());
        
        $download_id = 0;
        $physical_id = 0;

        foreach ($product->get_children() as $child_id) {
            $variation = wc_get_product($child_id);
            if ($variation) {
                if ($variation->is_downloadable()) {
                    $download_id = $child_id;
                } else {
                    $physical_id = $child_id;
                }
            }
        }

        if (!$download_id || !$physical_id) {
            ThemeLogger::log("¡VALIDACIÓN FALLIDA! El producto no es del tipo e-book/físico.");
            return ['status' => 'unavailable'];
        }
        
        ThemeLogger::log("Producto VALIDADO (ID E-book: $download_id, ID Físico: $physical_id). Procediendo...");

        // --- LÓGICA MEJORADA PARA PREVENTAS ---
        $download_variation = wc_get_product($download_id);
        if ($user_id > 0 && $download_variation) {
            $has_purchased = wc_customer_bought_product('', $user_id, $download_id);
            $has_download_file = !empty($download_variation->get_downloads());

            if ($has_purchased && !$has_download_file) {
                ThemeLogger::log("Estado detectado: El usuario ha comprado el producto, pero no hay archivo de descarga (Preventa).");
                self::$ebook_status_holder = 'purchased_but_unavailable'; // Almacenar estado
                return [
                    'status' => 'purchased_but_unavailable',
                    'ebook_price_html' => $download_variation->get_price_html(),
                    'book_price_html'  => wc_get_product($physical_id) ? wc_get_product($physical_id)->get_price_html() : ''
                ];
            }
        }
        // --- FIN DE LA LÓGICA MEJORADA ---

        $transient_key = "ebook_status_{$user_id}_prod_{$download_id}";
        $cached_data = get_transient($transient_key);

        if (false !== $cached_data) {
            ThemeLogger::log("Cache HIT para la clave: $transient_key");
            self::$ebook_status_holder = $cached_data['status']; // Almacenar estado desde caché
            return $cached_data;
        }

        ThemeLogger::log("Cache MISS para la clave: $transient_key. Regenerando...");

        $status = 'purchase_required';

        if ($user_id > 0) {
            $has_active_subscription = class_exists('\boctulus\TutorNewCourses\libs\WCSubscriptionsExtended') && (new \boctulus\TutorNewCourses\libs\WCSubscriptionsExtended())->hasActive($user_id);
            $plugin_remain = class_exists('\boctulus\TutorNewCourses\core\libs\CustomDownloadPermissions') ? \boctulus\TutorNewCourses\core\libs\CustomDownloadPermissions::getCount('', $download_id, $user_id) : false;
            $plugin_perm = $plugin_remain !== false;
            $plugin_available = $plugin_perm && ($plugin_remain < 0 || $plugin_remain > 0);
            $customer_downloads = wc_get_customer_available_downloads($user_id);
            $wc_perm = false;
            $wc_available = false;
            foreach ($customer_downloads as $perm) {
                if ((int)$perm['product_id'] === $download_id) {
                    $wc_perm = true;
                    $rem = $perm['downloads_remaining'];
                    $wc_available = ($rem === '' || (int)$rem !== 0);
                    break;
                }
            }
            $already_bought = wc_customer_bought_product('', $user_id, $download_id);
            $can_download = ($has_active_subscription && $plugin_available) || ($wc_perm && $wc_available) || ($already_bought && !$wc_perm && !$plugin_perm && $wc_available);
            $is_exhausted = ($plugin_perm && !$plugin_available) || ($wc_perm && !$wc_available);

            if ($can_download) {
                $status = 'can_download';
            } elseif ($is_exhausted) {
                $status = 'exhausted';
            }
        }
        
        $physical_variation = wc_get_product($physical_id);
        
        $result = [
            'status'            => $status,
            'ebook_price_html'  => $download_variation ? $download_variation->get_price_html() : '',
            'book_price_html'   => $physical_variation ? $physical_variation->get_price_html() : '',
            'ebook_url'         => wc_get_checkout_url() . '?add-to-cart=' . $download_id,
            'book_url'          => wc_get_checkout_url() . '?add-to-cart=' . $physical_id,
            'download_page_url' => wc_get_endpoint_url('downloads', '', wc_get_page_permalink('myaccount'))
        ];

        self::$ebook_status_holder = $status; // Almacenar estado antes de guardar en caché
        set_transient($transient_key, $result, 12 * HOUR_IN_SECONDS);
        ThemeLogger::log("Cache SET para la clave: $transient_key con estado: $status");

        return $result;
    }
}