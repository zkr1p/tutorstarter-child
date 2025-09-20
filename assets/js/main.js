/**
 * Lógica de frontend para precios dinámicos y la UI del producto variable.
 * VERSIÓN FINAL CORREGIDA PARA LEER LA RESPUESTA AJAX ANIDADA Y CON LOGS
 */
document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;

    /**
     * Lógica #1: Para páginas de producto variable (sin cambios).
     */
    function handleVariableProductPage() {
        if (!body.classList.contains('single-product') || typeof productPageData === 'undefined') {
            return;
        }
        // ... (El código para la página de producto variable es correcto y se mantiene)
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
            if (bookPriceEl && productPageData.book_price_html) { bookPriceEl.innerHTML = productPageData.book_price_html; }
            if (ebookPriceEl) {
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
            btnBuy.addEventListener('click', function () {
                if (this.dataset.link) { window.location.href = this.dataset.link; }
            });
        }
        initializeStaticData();
        updateUI();
    }

    /**
     * Lógica #2: Para la tienda y productos simples (AJAX con Logs y Corrección Final).
     */
    function updateDynamicPrices() {
        console.log('--- [DEBUG] Iniciando actualización de precios dinámicos ---');

        if (typeof theme_ajax_object === 'undefined') {
            console.error('--- [DEBUG] FATAL: El objeto `theme_ajax_object` no está definido. ---');
            return;
        }

        if (body.classList.contains('single-product') && typeof productPageData !== 'undefined') {
            return;
        }

        const productIds = new Set();
        document.querySelectorAll('.elementor-loop-container .product[class*="post-"]').forEach(el => {
            const idMatch = el.className.match(/post-(\d+)/);
            if (idMatch && idMatch[1]) productIds.add(idMatch[1]);
        });
        if (body.classList.contains('single-product')) {
            const idMatch = body.className.match(/postid-(\d+)/);
            if (idMatch && idMatch[1]) productIds.add(idMatch[1]);
        }
        
        if (productIds.size === 0) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'get_subscriber_prices');
        formData.append('security', theme_ajax_object.nonce);
        Array.from(productIds).forEach(id => formData.append('product_ids[]', id));
        
        fetch(theme_ajax_object.ajax_url, { method: 'POST', body: formData })
        .then(response => response.json())
        .then(response => {
            console.log('[DEBUG] Respuesta recibida del servidor:', response);

            // --- CORRECCIÓN CRÍTICA ---
            // Accedemos a la propiedad 'data' del objeto de respuesta.
            const pricesData = response.data || {};

            if (response.success && Object.keys(pricesData).length > 0) {
                console.log('[DEBUG] La respuesta es válida. Procediendo a actualizar el HTML...');

                for (const productId in pricesData) {
                    const productData = pricesData[productId];
                    let priceWrapper = null;

                    const productInLoop = document.querySelector(`.e-loop-item-${productId}`);
                    
                    if (productInLoop) {
                        priceWrapper = productInLoop.querySelector('.elementor-element-986dd25');
                        if (priceWrapper) {
                           console.log(`[DEBUG] Producto #${productId}: Encontrado contenedor de precio en la tienda.`);
                        } else {
                           console.warn(`[DEBUG] Producto #${productId}: NO se encontró el div del precio (.elementor-element-986dd25).`);
                        }
                    } 
                    else if (body.classList.contains('single-product')) {
                        const summary = document.querySelector('.product .summary');
                        if(summary){
                            priceWrapper = summary.querySelector('.price');
                        }
                    }

                    if (priceWrapper) {
                        priceWrapper.innerHTML = `<h2 class="elementor-heading-title elementor-size-default">${productData.price_html}</h2>`;
                        console.log(`[DEBUG] ÉXITO: Precio del producto #${productId} actualizado.`);
                    } else {
                        console.error(`[DEBUG] FALLO: No se encontró un lugar para mostrar el precio del producto #${productId}.`);
                    }
                }
            } else {
                 console.warn('[DEBUG] La respuesta del servidor no contenía datos de precios para actualizar.');
            }
            console.log('--- [DEBUG] Proceso de actualización finalizado. ---');
        })
        .catch(error => {
            console.error('--- [DEBUG] ERROR FATAL en la petición AJAX. Revisa la pestaña "Red" (Network). ---', error);
        });
    }
    
    handleVariableProductPage();
    updateDynamicPrices();
});