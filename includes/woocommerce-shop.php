<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Shortcodes para la página de la tienda.
 */
add_action('init', function() {
    add_shortcode('custom_woocommerce_ordering', 'custom_woocommerce_ordering_shortcode');
    add_shortcode('custom_woocommerce_category_filter', 'crf_category_filter_shortcode');
    add_shortcode('custom_woocommerce_result_count', 'custom_woocommerce_result_count_shortcode');
});

function custom_woocommerce_ordering_shortcode() {
    ob_start();
    woocommerce_catalog_ordering();
    return ob_get_clean();
}

function custom_woocommerce_result_count_shortcode() {
    ob_start();
    woocommerce_result_count();
    return ob_get_clean();
}

function crf_category_filter_shortcode($atts) {
    $atts = shortcode_atts(['form_class' => 'custom-category-filter', 'select_class' => 'custom-category-select'], $atts, 'custom_woocommerce_category_filter');
    
    // RECOMENDACIÓN: Añade un comentario explicando qué son estos IDs.
    // Ejemplo: Excluir 'Uncategorized' (15), 'Cursos' (34), etc.
    $exclude_ids = [34, 15, 80, 92];
    
    $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'exclude' => $exclude_ids, 'orderby' => 'name', 'order' => 'ASC']);
    if (is_wp_error($terms) || empty($terms)) return '';

    $current = isset($_GET['product_cat']) ? sanitize_text_field($_GET['product_cat']) : '';

    ob_start();
    // ... (El resto de la función del shortcode de filtro de categoría va aquí, sin cambios)
    ?>
    <form class="<?php echo esc_attr( $atts['form_class'] ); ?>" method="get" style="margin-bottom:1em;">
        <?php
        foreach ( $_GET as $key => $value ) {
            if ( in_array( $key, [ 'product_cat', 'submit', 'paged' ], true ) ) continue;
            if ( is_array( $value ) ) {
                foreach ( $value as $v ) { printf('<input type="hidden" name="%1$s[]" value="%2$s">', esc_attr($key), esc_attr($v)); }
            } else {
                printf('<input type="hidden" name="%1$s" value="%2$s">', esc_attr($key), esc_attr($value));
            }
        }
        ?>
        <select name="product_cat" class="<?php echo esc_attr( $atts['select_class'] ); ?>" onchange="this.form.submit()" style="min-width:12em;">
            <option value=""><?php esc_html_e( 'Todas las categorías', 'woocommerce' ); ?></option>
            <?php foreach ( $terms as $term ) : ?>
                <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current, $term->slug ); ?>>
                    <?php echo esc_html( $term->name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php
    return ob_get_clean();
}

/**
 * Aplica el filtro de categoría a la consulta principal de la tienda.
 */
add_action('pre_get_posts', function($query) {
    if (is_admin() || !$query->is_main_query()) return;

    if (is_shop() || is_post_type_archive('product') || is_tax('product_cat')) {
        if (!empty($_GET['product_cat'])) {
            $query->set('tax_query', [[
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_GET['product_cat']),
            ]]);
        }
    }
}, 20);

/**
 * Reemplaza el contenido del carrito si se añade un producto "Vendido Individualmente".
 */
add_filter('woocommerce_add_to_cart_validation', function($passed, $product_id) {
    $product_to_add = wc_get_product($product_id);

    if ($product_to_add && $product_to_add->is_sold_individually() && !WC()->cart->is_empty()) {
        $cart_items = WC()->cart->get_cart();
        $first_item = reset($cart_items);
        $removed_product_name = $first_item['data']->get_name();

        WC()->cart->empty_cart();
        
        $notice_text = sprintf(
            'Solo se permite un producto por pedido. Hemos reemplazado "%s" con "%s" en tu carrito.',
            esc_html($removed_product_name),
            esc_html($product_to_add->get_name())
        );
        wc_add_notice($notice_text, 'notice');
    }
    return $passed;
}, 20, 2);