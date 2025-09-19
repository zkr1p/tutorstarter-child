<?php
/**
 * Gestiona la lógica de negocio y el estado de compra/descarga para los E-books.
 * Esta clase está diseñada para ser segura, eficiente y mantenible.
 *
 * @version 3.0.0
 * @author B. O.
 */
namespace TutorstarterChild;

// Se importan las clases de WooCommerce para asegurar la correcta declaración de tipos (type-hinting).
use WC_Product_Variable;
use WC_Product;

class EbookStatusManager {
    
    /**
     * @var string|null Almacena el estado del e-book para que otros archivos puedan consultarlo.
     */
    public static $ebook_status_holder = null;

    /**
     * @var array Caché estática para almacenar resultados durante una única solicitud (request).
     * La clave es una combinación de ID de producto y ID de usuario para evitar colisiones.
     */
    private static $request_cache = [];

    /**
     * Prefijo para las claves de caché de transients de WordPress.
     */
    private const TRANSIENT_PREFIX = 'ebook_status_';

    /**
     * Duración de la caché de transients en segundos (12 horas).
     */
    private const TRANSIENT_EXPIRATION = 12 * HOUR_IN_SECONDS;

    /**
     * Orquesta la obtención del estado de un e-book para un usuario.
     * Es el único método público y punto de entrada a la lógica de la clase.
     *
     * @param int $user_id ID del usuario.
     * @param WC_Product_Variable|null $product Objeto del producto de WooCommerce.
     * @return array Datos de estado para la UI.
     */
    public function get_status(int $user_id, ?WC_Product_Variable $product): array {
        
        // 1. Validación de entrada: previene errores fatales si el producto no es válido.
        if (!$product instanceof WC_Product_Variable) {
            return ['status' => 'unavailable', 'reason' => 'Invalid product object'];
        }

        $product_id = $product->get_id();
        $cache_key = "{$product_id}_{$user_id}";

        // 2. Caché a nivel de solicitud: previene bucles y trabajo duplicado en una misma carga de página.
        if (isset(self::$request_cache[$cache_key])) {
            return self::$request_cache[$cache_key];
        }

        ThemeLogger::log("==============================================================");
        ThemeLogger::log("Iniciando análisis para Producto ID: {$product_id} | Usuario ID: {$user_id}");

        // 3. Obtención y validación de las variaciones del producto.
        $variation_ids = $this->getVariationIds($product);
        if (!$variation_ids['download_id'] || !$variation_ids['physical_id']) {
            ThemeLogger::log("¡VALIDACIÓN FALLIDA! Producto ID: {$product_id} no es del tipo e-book/físico.");
            return $this->cacheAndReturn($cache_key, null, ['status' => 'unavailable']);
        }

        ThemeLogger::log("Producto VALIDADO (E-book: {$variation_ids['download_id']}, Físico: {$variation_ids['physical_id']}).");

        $variations = [
            'download' => wc_get_product($variation_ids['download_id']),
            'physical' => wc_get_product($variation_ids['physical_id']),
        ];

        // 4. Lógica de preventa: un caso especial que se resuelve antes de la caché principal.
        if ($user_id > 0 && $variations['download']) {
            $has_purchased = wc_customer_bought_product('', $user_id, $variation_ids['download_id']);
            $has_download_file = !empty($variations['download']->get_downloads());

            if ($has_purchased && !$has_download_file) {
                ThemeLogger::log("Estado detectado para Producto ID: {$product_id}: Preventa.");
                self::$ebook_status_holder = 'purchased_but_unavailable';
                $result = $this->buildResult('purchased_but_unavailable', $variation_ids, $variations);
                return $this->cacheAndReturn($cache_key, null, $result);
            }
        }
        
        // 5. Caché persistente (Transients de WordPress).
        $transient_key = self::TRANSIENT_PREFIX . "{$user_id}_prod_{$variation_ids['download_id']}";
        $cached_data = get_transient($transient_key);

        if (false !== $cached_data) {
            ThemeLogger::log("Cache HIT para la clave: {$transient_key}");
            self::$ebook_status_holder = $cached_data['status'] ?? 'unavailable';
            return $this->cacheAndReturn($cache_key, null, $cached_data);
        }

        ThemeLogger::log("Cache MISS para la clave: {$transient_key}. Regenerando...");
        
        // 6. Si no hay caché, se calcula el estado del usuario.
        $status = $this->resolveUserStatus($user_id, $variation_ids['download_id']);
        
        // 7. Se construye el array de resultado final.
        $result = $this->buildResult($status, $variation_ids, $variations);

        // 8. Se guarda el resultado en ambas cachés y se devuelve.
        self::$ebook_status_holder = $status;
        return $this->cacheAndReturn($cache_key, $transient_key, $result);
    }

    /**
     * Extrae los IDs de las variaciones física y descargable de un producto.
     *
     * @param WC_Product $product Objeto del producto.
     * @return array Un array con 'download_id' y 'physical_id'.
     */
    private function getVariationIds(WC_Product $product): array {
        $ids = ['download_id' => 0, 'physical_id' => 0];
        foreach ($product->get_children() as $child_id) {
            $variation = wc_get_product($child_id);
            if ($variation) {
                if ($variation->is_downloadable()) {
                    $ids['download_id'] = $child_id;
                } else {
                    $ids['physical_id'] = $child_id;
                }
            }
        }
        return $ids;
    }

    /**
     * Determina el estado de compra y descarga para un usuario específico.
     *
     * @param int $user_id ID del usuario.
     * @param int $download_id ID de la variación descargable.
     * @return string El estado resuelto ('can_download', 'exhausted', 'purchase_required').
     */
    private function resolveUserStatus(int $user_id, int $download_id): string {
        if ($user_id <= 0) {
            return 'purchase_required';
        }

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
        
        if (($has_active_subscription && $plugin_available) || ($wc_perm && $wc_available) || ($already_bought && !$wc_perm && !$plugin_perm && $wc_available)) {
            return 'can_download';
        }

        if (($plugin_perm && !$plugin_available) || ($wc_perm && !$wc_available)) {
            return 'exhausted';
        }
        
        return 'purchase_required';
    }

    /**
     * Construye el array de resultado final que se enviará a la UI.
     *
     * @param string $status El estado calculado.
     * @param array $variation_ids IDs de las variaciones.
     * @param array $variations Objetos WC_Product de las variaciones.
     * @return array El array de resultado.
     */
    private function buildResult(string $status, array $variation_ids, array $variations): array {
        return [
            'status'            => $status,
            'ebook_price_html'  => $variations['download'] ? $variations['download']->get_price_html() : '',
            'book_price_html'   => $variations['physical'] ? $variations['physical']->get_price_html() : '',
            'ebook_url'         => wc_get_checkout_url() . '?add-to-cart=' . $variation_ids['download_id'],
            'book_url'          => wc_get_checkout_url() . '?add-to-cart=' . $variation_ids['physical_id'],
            'download_page_url' => wc_get_endpoint_url('downloads', '', wc_get_page_permalink('myaccount'))
        ];
    }

    /**
     * Almacena un resultado en ambas cachés (solicitud y transient) y lo devuelve.
     *
     * @param string $request_key Clave para la caché de la solicitud.
     * @param string|null $transient_key Clave para el transient. Si es null, no se guarda.
     * @param array $result El valor a cachear y devolver.
     * @return array El resultado.
     */
    private function cacheAndReturn(string $request_key, ?string $transient_key, array $result): array {
        // Siempre guardar en la caché de la solicitud para prevenir bucles.
        self::$request_cache[$request_key] = $result;
        
        // Guardar en la caché persistente si se proporciona una clave.
        if ($transient_key !== null) {
            set_transient($transient_key, $result, self::TRANSIENT_EXPIRATION);
            ThemeLogger::log("Cache SET para la clave: {$transient_key} con estado: {$result['status']}");
        }

        return $result;
    }
}