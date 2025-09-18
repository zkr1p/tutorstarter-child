<?php
/**
 * Variable product add to cart
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

// --- INICIO DE NUESTRA LÓGICA DE VERIFICACIÓN ---
if (!class_exists('TutorstarterChild\EbookStatusManager')) {
    wc_get_template('single-product/add-to-cart/variable.php', [
        'available_variations' => $product->get_available_variations(),
        'attributes'           => $product->get_variation_attributes(),
        'selected_attributes'  => $product->get_default_attributes(),
    ]);
    return;
}
$status_data = (new TutorstarterChild\EbookStatusManager())->get_status(get_current_user_id(), $product);

if (isset($status_data['status']) && $status_data['status'] === 'unavailable') {
    wc_get_template('single-product/add-to-cart/variable.php', [
        'available_variations' => $product->get_available_variations(),
        'attributes'           => $product->get_variation_attributes(),
        'selected_attributes'  => $product->get_default_attributes(),
    ]);
    return;
}

wp_localize_script('tutorstarter-child-main-script', 'productPageData', $status_data);
// --- FIN DE NUESTRA LÓGICA DE VERIFICACIÓN ---

$attribute_keys  = array_keys( $product->get_variation_attributes() );
$variations_json = wp_json_encode( $product->get_available_variations( 'objects' ) );
$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

do_action( 'woocommerce_before_add_to_cart_form' ); ?>

<div class="custom-cart-options professional-style">
    <form class="variations_form cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo $variations_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
        <?php do_action( 'woocommerce_before_variations_form' ); ?>

        <div id="ebook-purchase-options-container" class="variations">
            <div class="purchase-option">
                <label class="purchase-label">
                    <input type="radio" name="purchase_option" value="ebook" checked>
                    <div class="option-content">
                        <span class="option-title"><?php esc_html_e('E-book', 'Tutorstarterchild'); ?></span>
                        <span class="option-price" id="ebook-price-placeholder"></span>
                    </div>
                </label>
            </div>
            <div class="purchase-option">
                <label class="purchase-label">
                    <input type="radio" name="purchase_option" value="book">
                    <div class="option-content">
                        <span class="option-title"><?php esc_html_e('Libro físico', 'Tutorstarterchild'); ?></span>
                        <span class="option-price" id="book-price-placeholder"></span>
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
        
        <?php do_action( 'woocommerce_after_variations_form' ); ?>
    </form>
</div>

<?php
do_action( 'woocommerce_after_add_to_cart_form' );