<div class="floating-cart" x-data="floatingCart({
        detailUrl: '{{ route('cart.ajax.detail') }}',
        checkoutUrl: '{{ route('cart.checkout') }}'
    })">
    <button type="button" class="floating-cart__button" @click="toggle()" aria-label="Abrir carrito">
        <i class="fa-solid fa-cart-shopping"></i>

        <span x-cloak class="floating-cart__badge" x-show="$store.cart.unitsCount > 0"
            x-text="$store.cart.unitsCount"></span>
    </button>

    <div x-cloak class="floating-cart__overlay" x-show="open" x-transition.opacity @click="close()"></div>

    <aside x-cloak class="floating-cart__panel" x-show="open" x-transition @click.stop>
        <div class="floating-cart__header">
            <div>
                <h2 x-text="step === 'summary' ? 'Tu carrito' : 'Datos del cliente'"></h2>
                <p x-show="step === 'summary'">
                    Revisa los productos agregados.
                </p>
                <p x-show="step === 'checkout'">
                    Completa tus datos para procesar la compra.
                </p>
            </div>

            <button type="button" class="floating-cart__close" @click="close()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="floating-cart__body">
            <template x-if="loading && !cartLoaded">
                <p class="floating-cart__empty">Cargando carrito...</p>
            </template>

            <template x-if="!loading && step === 'summary'">
                <div>
                    <template x-if="items.length === 0">
                        <div class="floating-cart__empty">
                            <p>Tu carrito está vacío.</p>
                        </div>
                    </template>

                    <template x-if="items.length > 0">
                        <div>
                            <div class="floating-cart__items">
                                <template x-for="item in items" :key="item.product_id">
                                    <article class="floating-cart-item">
                                        <div class="floating-cart-item__image">
                                            <img :src="item.image_url" :alt="item.name">
                                        </div>

                                        <div class="floating-cart-item__info">
                                            <h3 x-text="item.name"></h3>
                                            <p x-text="item.laboratory"></p>

                                            <strong>
                                                $ <span x-text="formatUsd(item.price_usd)"></span>
                                                |
                                                Bs. <span
                                                    x-text="formatBs(Number(item.price_usd || 0) * exchangeRate)"></span>
                                            </strong>

                                            <div class="floating-cart-item__actions">
                                                <button type="button" @click="decrement(item.product_id)">-</button>
                                                <span x-text="item.quantity"></span>
                                                <button type="button" @click="increment(item.product_id)">+</button>

                                                <button type="button" class="floating-cart-item__remove"
                                                    @click="remove(item.product_id)">
                                                    Eliminar
                                                </button>
                                            </div>
                                        </div>
                                    </article>
                                </template>
                            </div>

                            <div class="floating-cart__totals">
                                <p>
                                    Subtotal:
                                    <strong>
                                        $ <span x-text="formatUsd(totals.subtotal_usd)"></span>
                                        |
                                        Bs. <span x-text="formatBs(totals.subtotal_bs)"></span>
                                    </strong>
                                </p>

                                <p>
                                    Descuento:
                                    <strong>
                                        -<span x-text="totals.discount_percent || 0"></span>%
                                    </strong>
                                </p>

                                <p class="floating-cart__total-final">
                                    Total:
                                    <strong>
                                        $ <span x-text="formatUsd(totals.total_usd)"></span>
                                        |
                                        Bs. <span x-text="formatBs(totals.total_bs)"></span>
                                    </strong>
                                </p>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="!loading && step === 'checkout'">
                <form class="floating-cart-form" x-ref="checkoutForm" @submit.prevent="checkout($event)"
                    @input="refreshFormValidity()" @change="refreshFormValidity()" x-data="{
        deliveryType: '',
        paymentMethod: '',
        selectionNotice: '',
        noticeTimeout: null,
        formIsValid: false,

        refreshFormValidity() {
            this.$nextTick(() => {
                this.formIsValid =
                    this.$refs.checkoutForm.checkValidity()
                    && this.deliveryType !== ''
                    && this.paymentMethod !== '';
            });
        },

        isDeliveryBlocked(value) {
            return value === 'delivery' && this.paymentMethod === 'tarjeta';
        },

        isPaymentBlocked(value) {
            return value === 'tarjeta' && this.deliveryType === 'delivery';
        },

        showSelectionNotice(message) {
            this.selectionNotice = message;

            if (this.noticeTimeout) {
                clearTimeout(this.noticeTimeout);
            }

            this.noticeTimeout = setTimeout(() => {
                this.selectionNotice = '';
            }, 2800);
        },

        selectDelivery(value) {
            if (this.isDeliveryBlocked(value)) {
                this.showSelectionNotice('No se puede seleccionar este método de entrega en pagos por tarjeta de crédito.');
                return;
            }

            this.deliveryType = value;
            this.refreshFormValidity();
        },

        selectPayment(value) {
            if (this.isPaymentBlocked(value)) {
                this.showSelectionNotice('No se puede seleccionar tarjeta de crédito/débito cuando el método de entrega es Delivery.');
                return;
            }

            this.paymentMethod = value;
            this.refreshFormValidity();
        }
    }">
                    <div class="cart-field">
                        <label for="floating_document">Cédula o RIF</label>
                        <input type="text" id="floating_document" name="document" required>
                    </div>

                    <div class="cart-field">
                        <label for="floating_name">Nombre y apellido ó empresa</label>
                        <input type="text" id="floating_name" name="name" required>
                    </div>

                    <div class="cart-field">
                        <label for="floating_phone">Número de teléfono</label>
                        <input type="text" id="floating_phone" name="phone" required>
                    </div>

                    <div class="cart-field">
                        <label>Tipo de entrega</label>

                        <input type="hidden" name="delivery_type" :value="deliveryType">

                        <div class="cart-choice-grid">
                            <button type="button" class="cart-choice-btn"
                                :class="{ 'is-active': deliveryType === 'pickup' }" @click="selectDelivery('pickup')">
                                Retiro en tienda
                            </button>

                            <button type="button" class="cart-choice-btn" :class="{
                'is-active': deliveryType === 'delivery',
                'is-blocked': isDeliveryBlocked('delivery')
            }" @click="selectDelivery('delivery')">
                                Delivery
                            </button>
                        </div>
                    </div>

                    <div class="cart-field" x-show="deliveryType === 'delivery'" x-transition>
                        <label for="floating_delivery_address">Dirección de envío</label>
                        <textarea id="floating_delivery_address" name="delivery_address" class="cart-field__textarea"
                            rows="3" placeholder="Escribe la dirección completa de entrega"></textarea>
                    </div>

                    <div class="cart-field">
                        <label>Método de pago</label>

                        <input type="hidden" name="payment_method" :value="paymentMethod">

                        <div class="cart-choice-grid cart-choice-grid--payments">
                            <button type="button" class="cart-choice-btn"
                                :class="{ 'is-active': paymentMethod === 'pago_movil' }"
                                @click="selectPayment('pago_movil')">
                                Pago móvil
                            </button>

                            <button type="button" class="cart-choice-btn"
                                :class="{ 'is-active': paymentMethod === 'transferencia' }"
                                @click="selectPayment('transferencia')">
                                Transferencia
                            </button>

                            <button type="button" class="cart-choice-btn"
                                :class="{ 'is-active': paymentMethod === 'efectivo_usd' }"
                                @click="selectPayment('efectivo_usd')">
                                Efectivo USD
                            </button>

                            <button type="button" class="cart-choice-btn"
                                :class="{ 'is-active': paymentMethod === 'efectivo_bs' }"
                                @click="selectPayment('efectivo_bs')">
                                Efectivo Bs
                            </button>

                            <button type="button" class="cart-choice-btn" :class="{
                'is-active': paymentMethod === 'tarjeta',
                'is-blocked': isPaymentBlocked('tarjeta')
            }" @click="selectPayment('tarjeta')">
                                Tarjeta de crédito/débito
                            </button>

                            <button type="button" class="cart-choice-btn"
                                :class="{ 'is-active': paymentMethod === 'zelle' }" @click="selectPayment('zelle')">
                                Zelle
                            </button>

                            <button type="button" class="cart-choice-btn"
                                :class="{ 'is-active': paymentMethod === 'cashea' }" @click="selectPayment('cashea')">
                                Cashea
                            </button>
                        </div>
                    </div>

                    <div class="cart-field">
                        <label for="floating_attention_code">Código Atención</label>
                        <input type="text" id="floating_attention_code" name="attention_code" placeholder="Opcional">
                    </div>

                    <button type="submit" class="checkout-btn"
                        :disabled="processing || !formIsValid">
                        <span x-show="!processing">Procesar compra</span>
                        <span x-show="processing">Procesando...</span>
                    </button>
                </form>
            </template>
        </div>

        <div class="floating-cart__footer" x-show="!loading">
            <template x-if="step === 'summary'">
                <div class="floating-cart__footer-actions">
                    <button type="button" class="clear-cart-btn" x-show="items.length > 0" @click="clear()">
                        Vaciar carrito
                    </button>

                    <button type="button" class="checkout-btn" x-show="items.length > 0" @click="goToCheckout()">
                        Siguiente
                    </button>
                </div>
            </template>

            <template x-if="step === 'checkout'">
                <button type="button" class="floating-cart__back" @click="step = 'summary'">
                    Volver al resumen
                </button>
            </template>
        </div>
    </aside>
</div>