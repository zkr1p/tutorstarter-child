<?php
/**
 * Variable product add to cart
 * VERSIÓN FINAL CORREGIDA - Asegura que ambos precios se muestren.
 */
defined( 'ABSPATH' ) || exit;

global $product;

// 1. Obtenemos el estado del Ebook y los datos para los botones (sin cambios)
$status_data = (new \TutorstarterChild\EbookStatusManager())->get_status(get_current_user_id(), $product);
wp_localize_script('tutorstarter-child-main-script', 'productPageData', $status_data);

// 2. Obtenemos las variaciones y sus precios de forma robusta
$variations = $product->get_available_variations();
$ebook_price_html = __('No disponible', 'tutorstarter-child');
$book_price_html = __('No disponible', 'tutorstarter-child');

// --- INICIO DE LA CORRECCIÓN ---
// Recorremos las variaciones para extraer sus precios de forma segura.
foreach ($product->get_children() as $child_id) {
    $variation = wc_get_product($child_id);
    if ($variation && $variation->is_visible()) {
        // Usamos el filtro 'woocommerce_available_variation' para obtener el precio HTML
        // que ya ha sido modificado por nuestro código en el plugin (a $0.00 si aplica).
        $variation_data = $product->get_available_variation($variation);

        if ($variation->is_downloadable()) {
            $ebook_price_html = $variation_data['price_html'];
        } else {
            $book_price_html = $variation_data['price_html'];
        }
    }
}
// --- FIN DE LA CORRECCIÓN ---
?>

<div class="custom-cart-options professional-style">
    <form class="variations_form cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data'>
        
        <div id="ebook-purchase-options-container" class="variations">
            <div class="purchase-option">
                <label class="purchase-label">
                    <input type="radio" name="purchase_option" value="ebook" checked>
                    <div class="option-content">
                        <span class="option-title"><?php esc_html_e('E-book', 'Tutorstarterchild'); ?></span>
                        <span class="option-price" id="ebook-price-placeholder">
                            <?php echo $ebook_price_html; // Imprimimos el precio del e-book ?>
                        </span>
                    </div>
                </label>
            </div>
            <div class="purchase-option">
                <label class="purchase-label">
                    <input type="radio" name="purchase_option" value="book">
                    <div class="option-content">
                        <span class="option-title"><?php esc_html_e('Libro físico', 'Tutorstarterchild'); ?></span>
                        <span class="option-price" id="book-price-placeholder">
                             <?php echo $book_price_html; // Imprimimos el precio del libro físico ?>
                        </span>
                    </div>
                </label>
            </div>

            <div class="single_variation_wrap">
                <div class="custom-add-to-cart">
                    <a id="btn-download" href="#" class="button alt wp-element-button" style="display:none;"><?php esc_html_e('Descargar ahora', 'Tutorstarterchild'); ?></a>
                    <p id="msg-exhausted" style="display:none;"><?php esc_html_e('Descargas agotadas — vuelve a comprar para renovar tu cupo.', 'Tutorstarterchild'); ?></p>
                    <p id="msg-unavailable" style="display:none;"><?php esc_html_e('Ya has adquirido este producto. Estará disponible para descargar próximamente.', 'Tutorstarterchild'); ?></p>
                    <button type="button" id="btn-buy" class="button alt wp-element-button" style="display:none;"><?php esc_html_e('Ir a pagar', 'Tutorstarterchild'); ?></button>
                </div>
            </div>
        </div>
        
    </form>
</div>