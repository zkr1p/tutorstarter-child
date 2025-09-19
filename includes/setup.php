/**
 * Lógica de frontend para precios dinámicos y la UI del producto variable.
 * VERSIÓN FINAL: Lógica unificada y selectores de DOM robustos.
 * @author B. O.
 * @version 9.0.0
 */
document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;

    /**
     * Lógica #1: Para páginas de producto variable.
     * Se ejecuta de forma aislada y maneja su propia UI.
     */
    function handleVariableProductPage() {
        // Esta lógica solo se ejecuta si estamos en una página de producto Y existe el objeto productPageData.
        if (body.classList.contains('single-product') && typeof productPageData !== 'undefined') {
            const container = document.getElementById('ebook-purchase-options-container');
            if (productPageData.status === 'unavailable') {
                if (container) container.style.display = 'none';
                return;
            }

            const radioEbook = document.querySelector('input[name="purchase_option"][value="ebook"]');
            const radioBook = document.querySelector('input[name="purchase_option"][value="book"]');
            const btnDownload = document.getElementById('btn-download');
            const btnBuy = document.getElementById('btn-buy');
            const msgExhausted = document.getElementById('msg-exhausted');
            const msgUnavailable = document.getElementById('msg-unavailable');
            const ebookPriceEl = document.getElementById('ebook-price-placeholder');
            const bookPriceEl = document.getElementById('book-price-placeholder');

            function initializeStaticData() {
                if (bookPriceEl && productPageData.book_price_html) {
                    bookPriceEl.innerHTML = productPageData.book_price_html;
                }
                if (ebookPriceEl) {
                    // Lógica anti-caché: Decidimos el precio basándonos en el 'status', no en el precio cacheado.
                    if (productPageData.status === 'can_download') {
                        ebookPriceEl.innerHTML = `<ins><span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">$</span>0.00</bdi></span></ins>`;
                    } else {
                        ebookPriceEl.innerHTML = productPageData.ebook_price_html || '';
                    }
                }
            }

            function updateUI() {
                if (!btnDownload || !btnBuy || !msgExhausted || !msgUnavailable || !radioBook) return;
                btnDownload.style.display = 'none';
                btnBuy.style.display = 'none';
                msgExhausted.style.display = 'none';
                msgUnavailable.style.display = 'none';
                if (radioBook.checked) {
                    btnBuy.style.display = 'block';
                    btnBuy.dataset.link = productPageData.book_url;
                } else {
                    switch (productPageData.status) {
                        case 'can_download':
                            btnDownload.style.display = 'block';
                            btnDownload.href = productPageData.download_page_url;
                            break;
                        case 'exhausted':
                            msgExhausted.style.display = 'block';
                            btnBuy.style.display = 'block';
                            btnBuy.dataset.link = productPageData.ebook_url;
                            break;
                        case 'purchased_but_unavailable':
                            msgUnavailable.style.display = 'block';
                            break;
                        default:
                            btnBuy.style.display = 'block';
                            btnBuy.dataset.link = productPageData.ebook_url;
                            break;
                    }
                }
            }

            if (radioEbook && radioBook) {
                radioEbook.addEventListener('change', updateUI);
                radioBook.addEventListener('change', updateUI);
            }
            if (btnBuy) {
                btnBuy.addEventListener('click', function() {
                    if (this.dataset.link) window.location.href = this.dataset.link;
                });
            }

            initializeStaticData();
            updateUI();
        }
    }

    /**
     * Lógica #2: Para la tienda, archivos y páginas de producto simple (AJAX).
     */
    function updateDynamicPrices() {
        if (typeof theme_ajax_object === 'undefined') return;
        // No ejecutar AJAX en páginas de producto variable.
        if (body.classList.contains('single-product') && typeof productPageData !== 'undefined') return;

        const productIds = new Set();
        
        // 1. Buscar productos en la tienda/archivos
        document.querySelectorAll('.product[class*="post-"]').forEach(el => {
            const idMatch = el.className.match(/post-(\d+)/);
            if (idMatch && idMatch[1]) productIds.add(idMatch[1]);
        });
        
        // 2. Buscar el producto en una página de producto simple
        if (body.classList.contains('single-product')) {
            const idMatch = body.className.match(/postid-(\d+)/);
            if (idMatch && idMatch[1]) productIds.add(idMatch[1]);
        }
        
        if (productIds.size === 0) return;

        const formData = new FormData();
        formData.append('action', 'get_subscriber_prices');
        formData.append('security', theme_ajax_object.nonce);
        Array.from(productIds).forEach(id => formData.append('product_ids[]', id));
        
        fetch(theme_ajax_object.ajax_url, { method: 'POST', body: formData })
        .then(response => response.json())
        .then(response => {
            if (response.success && response.data) {
                for (const productId in response.data) {
                    const productData = response.data[productId];
                    
                    // --- SELECTOR DE PRECIO A PRUEBA DE FALLOS ---
                    const productWrapper = document.querySelector(`.post-${productId}`);
                    if (productWrapper) {
                        // WooCommerce usa 'p.price' en la página de producto simple y '.price' en la tienda.
                        // Al buscar ambos, nos aseguramos de encontrarlo.
                        const priceElement = productWrapper.querySelector('p.price, .price');
                        if (priceElement) {
                            priceElement.innerHTML = productData.price_html;
                        }
                    }
                }
            }
        })
        .catch(error => console.error('Error al actualizar precios:', error));
    }
    
    // Ejecutar ambas lógicas.
    handleVariableProductPage();
    updateDynamicPrices();
});