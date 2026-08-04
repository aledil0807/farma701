document.addEventListener("alpine:init", () => {
    Alpine.store("cart", {
        serverUnitsCount: window.cartInitial?.unitsCount || 0,
        pendingUnitsDelta: 0,

        get unitsCount() {
            return Math.max(0, this.serverUnitsCount + this.pendingUnitsDelta);
        },

        queueDelta(delta) {
            this.pendingUnitsDelta += Number(delta || 0);
        },

        confirm(serverCount, processedDelta) {
            this.serverUnitsCount = Number(serverCount || 0);
            this.pendingUnitsDelta -= Number(processedDelta || 0);
        },

        rollback(delta) {
            this.pendingUnitsDelta -= Number(delta || 0);
        },

        forceSync(serverCount) {
            this.serverUnitsCount = Number(serverCount || 0);
            this.pendingUnitsDelta = 0;
        },
    });
});

window.floatingCart = function (config) {
    return {
        open: false,
        step: "summary",
        loading: false,
        processing: false,
        cartLoaded: false,

        detailUrl: config.detailUrl,
        checkoutUrl: config.checkoutUrl,

        items: [],
        totals: {
            subtotal_usd: 0,
            subtotal_bs: 0,
            discount_percent: 0,
            discount_usd: 0,
            discount_bs: 0,
            total_usd: 0,
            total_bs: 0,
            items_count: 0,
            units_count: 0,
        },
        exchangeRate: 0,

        pendingOps: [],
        processingQueue: false,
        lastServerCart: null,
        lastServerTotals: null,

        async init() {
            await this.loadCart(false);

            window.addEventListener("floating-cart-refresh", async () => {
                await this.loadCart(false);
            });
        },

        async toggle() {
            this.open = !this.open;

            if (this.open) {
                this.step = "summary";

                if (!this.cartLoaded) {
                    await this.loadCart(true);
                } else {
                    this.loadCart(false);
                }
            }
        },

        close() {
            this.open = false;
            this.step = "summary";
        },

        goToCheckout() {
            if (!this.items.length) return;
            this.step = "checkout";
        },

        async loadCart(showLoading = true) {
            if (showLoading && !this.cartLoaded) {
                this.loading = true;
            }

            try {
                const response = await fetch(this.detailUrl, {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(
                        data.message || "No se pudo cargar el carrito.",
                    );
                }

                this.applyServerCart(data);
                this.cartLoaded = true;
            } catch (error) {
                console.error(error);
            } finally {
                this.loading = false;
            }
        },

        applyServerCart(data) {
            this.items = Object.values(data.cart?.items || {});
            this.totals = data.totals || this.totals;
            this.exchangeRate = Number(
                data.exchange_rate || this.exchangeRate || 0,
            );

            if (
                this.totals?.units_count !== undefined &&
                window.Alpine?.store("cart")
            ) {
                Alpine.store("cart").forceSync(this.totals.units_count);
            }
        },

        recalculateTotals() {
            let subtotalUsd = 0;
            let unitsCount = 0;

            this.items.forEach((item) => {
                subtotalUsd +=
                    Number(item.price_usd || 0) * Number(item.quantity || 0);
                unitsCount += Number(item.quantity || 0);
            });

            const discountPercent = 5;
            const discountUsd = subtotalUsd * (discountPercent / 100);
            const totalUsd = subtotalUsd - discountUsd;

            this.totals.subtotal_usd = subtotalUsd;
            this.totals.subtotal_bs = subtotalUsd * this.exchangeRate;

            this.totals.discount_percent = discountPercent;
            this.totals.discount_usd = discountUsd;
            this.totals.discount_bs = discountUsd * this.exchangeRate;

            this.totals.total_usd = totalUsd;
            this.totals.total_bs = totalUsd * this.exchangeRate;

            this.totals.items_count = this.items.length;
            this.totals.units_count = unitsCount;

            if (window.Alpine?.store("cart")) {
                Alpine.store("cart").forceSync(unitsCount);
            }
        },

        syncWithServerSnapshot() {
            if (this.lastServerCart && this.lastServerCart.items) {
                this.items = Object.values(this.lastServerCart.items);
            }

            if (this.lastServerTotals) {
                this.totals = this.lastServerTotals;
            } else {
                this.recalculateTotals();
            }

            if (
                this.lastServerTotals?.units_count !== undefined &&
                window.Alpine?.store("cart")
            ) {
                Alpine.store("cart").forceSync(
                    this.lastServerTotals.units_count,
                );
            }
        },

        findItem(productId) {
            return this.items.find((item) => {
                return Number(item.product_id) === Number(productId);
            });
        },

        enqueue(operation) {
            this.pendingOps.push(operation);
            this.processQueue();
        },

        async processQueue() {
            if (this.processingQueue) return;

            this.processingQueue = true;

            while (this.pendingOps.length > 0) {
                const op = this.pendingOps.shift();

                try {
                    const response = await fetch(op.url, {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                            "X-Requested-With": "XMLHttpRequest",
                            Accept: "application/json",
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify({}),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(
                            data.message || "No se pudo actualizar el carrito.",
                        );
                    }

                    this.lastServerCart = data.cart || null;
                    this.lastServerTotals = data.totals || null;
                } catch (error) {
                    console.error(error);

                    if (typeof op.rollback === "function") {
                        op.rollback();
                        this.recalculateTotals();
                    }
                }
            }

            this.syncWithServerSnapshot();
            this.processingQueue = false;
        },

        increment(productId) {
            const item = this.findItem(productId);

            if (!item) return;

            const maxStock = Number(item.stock || 0);

            if (maxStock <= 0 || Number(item.quantity) >= maxStock) {
                window.dispatchEvent(
                    new CustomEvent("stock-limit-reached", {
                        detail: {
                            message: `No hay más stock disponible para ${item.name}.`,
                        },
                    }),
                );
                return;
            }

            item.quantity++;
            this.recalculateTotals();

            this.enqueue({
                url: `/ajax/carrito/incrementar/${productId}`,
                rollback: () => {
                    item.quantity = Math.max(0, item.quantity - 1);
                },
            });
        },

        decrement(productId) {
            const item = this.findItem(productId);

            if (!item) return;

            const previousQuantity = item.quantity;

            item.quantity--;

            if (item.quantity <= 0) {
                this.items = this.items.filter((cartItem) => {
                    return Number(cartItem.product_id) !== Number(productId);
                });
            }

            this.recalculateTotals();

            this.enqueue({
                url: `/ajax/carrito/disminuir/${productId}`,
                rollback: () => {
                    const existing = this.findItem(productId);

                    if (existing) {
                        existing.quantity = previousQuantity;
                    } else {
                        item.quantity = previousQuantity;
                        this.items.push(item);
                    }
                },
            });
        },

        remove(productId) {
            const oldItems = [...this.items];

            this.items = this.items.filter((item) => {
                return Number(item.product_id) !== Number(productId);
            });

            this.recalculateTotals();

            this.enqueue({
                url: `/ajax/carrito/eliminar/${productId}`,
                rollback: () => {
                    this.items = oldItems;
                },
            });
        },

        clear() {
            const oldItems = [...this.items];

            this.items = [];
            this.recalculateTotals();

            this.enqueue({
                url: `/ajax/carrito/vaciar`,
                rollback: () => {
                    this.items = oldItems;
                },
            });
        },

        async checkout(event) {
            if (!this.items.length) {
                alert("Tu carrito está vacío.");
                return;
            }

            this.processing = true;

            try {
                const form = event.target;
                const formData = new FormData(form);

                const response = await fetch(this.checkoutUrl, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                    body: formData,
                });

                const data = await response.json();

                if (!response.ok) {
                    if (data.errors) {
                        alert(Object.values(data.errors).flat().join("\n"));
                        return;
                    }

                    throw new Error(
                        data.message || "No se pudo procesar la compra.",
                    );
                }

                if (data.success && data.whatsapp_url) {
                    this.items = [];
                    this.recalculateTotals();

                    if (window.Alpine?.store("cart")) {
                        Alpine.store("cart").forceSync(0);
                    }

                    window.location.href = data.whatsapp_url;
                }
            } catch (error) {
                console.error(error);
                alert("Ocurrió un error al procesar la compra.");
            } finally {
                this.processing = false;
            }
        },

        formatUsd(value) {
            const number = Number(value || 0);
            return number.toFixed(2);
        },

        formatBs(value) {
            const number = Number(value || 0);
            return number.toLocaleString("es-VE", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },
    };
};

window.cartControl = function (config) {
    return {
        productId: config.productId,
        addUrl: config.addUrl,
        incrementUrl: config.incrementUrl,
        decrementUrl: config.decrementUrl,
        maxStock: Number(config.maxStock || 0),
        productName: config.productName || "Este producto",

        serverQuantity: Number(config.initialQuantity || 0),
        pendingDelta: 0,

        pendingOps: [],
        processing: false,

        get quantity() {
            return Math.max(0, this.serverQuantity + this.pendingDelta);
        },

        canIncrease() {
            if (this.maxStock <= 0) return false;
            return this.quantity < this.maxStock;
        },

        notifyStockLimit() {
            window.dispatchEvent(
                new CustomEvent("stock-limit-reached", {
                    detail: {
                        message: `No hay más stock disponible para ${this.productName}.`,
                    },
                }),
            );
        },

        enqueue(url, delta) {
            if (delta < 0 && this.quantity <= 0) return;

            if (delta > 0 && !this.canIncrease()) {
                this.notifyStockLimit();
                return;
            }

            this.pendingDelta += delta;
            Alpine.store("cart").queueDelta(delta);

            this.pendingOps.push({ url, delta });
            this.processQueue();
        },

        async processQueue() {
            if (this.processing) return;
            this.processing = true;

            while (this.pendingOps.length > 0) {
                const op = this.pendingOps.shift();

                try {
                    const response = await fetch(op.url, {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                            "X-Requested-With": "XMLHttpRequest",
                            Accept: "application/json",
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify({}),
                    });

                    if (!response.ok) {
                        throw new Error("Error en la petición");
                    }

                    const data = await response.json();

                    this.serverQuantity = Number(
                        data.quantity ?? this.serverQuantity,
                    );
                    this.pendingDelta -= op.delta;

                    if (data.totals?.units_count !== undefined) {
                        Alpine.store("cart").confirm(
                            data.totals.units_count,
                            op.delta,
                        );
                    } else {
                        Alpine.store("cart").rollback(op.delta);
                    }
                } catch (error) {
                    console.error(error);

                    this.pendingDelta -= op.delta;
                    Alpine.store("cart").rollback(op.delta);
                }
            }

            this.processing = false;
        },

        add() {
            this.enqueue(this.addUrl, 1);
        },

        increment() {
            this.enqueue(this.incrementUrl, 1);
        },

        decrement() {
            this.enqueue(this.decrementUrl, -1);
        },
    };
};

window.cartControlFromAttributes = function (el) {
    try {
        return window.cartControl({
            productId: Number(el.dataset.productId || 0),
            initialQuantity: Number(el.dataset.initialQuantity || 0),
            maxStock: Number(el.dataset.maxStock || 0),
            productName: el.dataset.productName || "Producto",
            addUrl: el.dataset.addUrl || "",
            incrementUrl: el.dataset.incrementUrl || "",
            decrementUrl: el.dataset.decrementUrl || "",
        });
    } catch (error) {
        console.error("Error leyendo atributos del carrito:", error);

        return window.cartControl({
            productId: 0,
            initialQuantity: 0,
            maxStock: 0,
            productName: "Producto",
            addUrl: "",
            incrementUrl: "",
            decrementUrl: "",
        });
    }
};

window.cartControlFromDataset = function (el) {
    try {
        const raw = el.dataset.cartConfig || "{}";
        const config = JSON.parse(raw);
        return window.cartControl(config);
    } catch (error) {
        console.error("Error parseando data-cart-config:", error);

        return window.cartControl({
            productId: 0,
            initialQuantity: 0,
            maxStock: 0,
            productName: "Producto",
            addUrl: "",
            incrementUrl: "",
            decrementUrl: "",
        });
    }
};

window.stockToast = function () {
    return {
        visible: false,
        message: "",
        timeout: null,

        init() {
            window.addEventListener("stock-limit-reached", (event) => {
                this.message =
                    event.detail?.message || "No hay más stock disponible.";
                this.visible = true;

                if (this.timeout) {
                    clearTimeout(this.timeout);
                }

                this.timeout = setTimeout(() => {
                    this.visible = false;
                }, 2500);
            });
        },
    };
};

window.cartPage = function (config) {
    return {
        items: config.items || [],
        totals: config.totals || {},
        exchangeRate: Number(config.exchangeRate || 0),

        pendingOps: [],
        processing: false,
        lastServerCart: null,
        lastServerTotals: null,

        recalculateTotals() {
            let subtotalUsd = 0;
            let unitsCount = 0;

            this.items.forEach((item) => {
                subtotalUsd +=
                    Number(item.price_usd || 0) * Number(item.quantity || 0);
                unitsCount += Number(item.quantity || 0);
            });

            const discountPercent = 5;
            const discountUsd = subtotalUsd * (discountPercent / 100);
            const totalUsd = subtotalUsd - discountUsd;

            this.totals.subtotal_usd = subtotalUsd;
            this.totals.subtotal_bs = subtotalUsd * this.exchangeRate;

            this.totals.discount_percent = discountPercent;
            this.totals.discount_usd = discountUsd;
            this.totals.discount_bs = discountUsd * this.exchangeRate;

            this.totals.total_usd = totalUsd;
            this.totals.total_bs = totalUsd * this.exchangeRate;

            this.totals.units_count = unitsCount;
            this.totals.items_count = this.items.length;

            if (window.Alpine?.store("cart")) {
                Alpine.store("cart").forceSync(unitsCount);
            }
        },

        syncWithServerSnapshot() {
            if (this.lastServerCart && this.lastServerCart.items) {
                this.items = Object.values(this.lastServerCart.items);
            }

            if (this.lastServerTotals) {
                this.totals = this.lastServerTotals;
            } else {
                this.recalculateTotals();
            }

            if (
                this.lastServerTotals?.units_count !== undefined &&
                window.Alpine?.store("cart")
            ) {
                Alpine.store("cart").forceSync(
                    this.lastServerTotals.units_count,
                );
            }
        },

        findItem(productId) {
            return this.items.find(
                (item) => Number(item.product_id) === Number(productId),
            );
        },

        enqueue(operation) {
            this.pendingOps.push(operation);
            this.processQueue();
        },

        async processQueue() {
            if (this.processing) return;
            this.processing = true;

            while (this.pendingOps.length > 0) {
                const op = this.pendingOps.shift();

                try {
                    const response = await fetch(op.url, {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                            "X-Requested-With": "XMLHttpRequest",
                            Accept: "application/json",
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify({}),
                    });

                    if (!response.ok) {
                        throw new Error("Error en la petición");
                    }

                    const data = await response.json();

                    // Guardamos la última foto real del backend,
                    // pero NO la aplicamos todavía si siguen quedando operaciones.
                    this.lastServerCart = data.cart || null;
                    this.lastServerTotals = data.totals || null;
                } catch (error) {
                    console.error(error);

                    if (typeof op.rollback === "function") {
                        op.rollback();
                        this.recalculateTotals();
                    }
                }
            }

            // Solo cuando la cola se vacía aplicamos la foto real del backend.
            this.syncWithServerSnapshot();

            this.processing = false;
        },

        increment(productId) {
            const item = this.findItem(productId);
            if (!item) return;

            const maxStock = Number(item.stock || 0);

            if (maxStock <= 0 || Number(item.quantity) >= maxStock) {
                window.dispatchEvent(
                    new CustomEvent("stock-limit-reached", {
                        detail: {
                            message: `No hay más stock disponible para ${item.name}.`,
                        },
                    }),
                );
                return;
            }

            item.quantity++;
            this.recalculateTotals();

            this.enqueue({
                url: `/ajax/carrito/incrementar/${productId}`,
                rollback: () => {
                    item.quantity = Math.max(0, item.quantity - 1);
                },
            });
        },

        decrement(productId) {
            const item = this.findItem(productId);
            if (!item) return;

            const previousQuantity = item.quantity;
            item.quantity--;

            if (item.quantity <= 0) {
                this.items = this.items.filter(
                    (i) => Number(i.product_id) !== Number(productId),
                );
            }

            this.recalculateTotals();

            this.enqueue({
                url: `/ajax/carrito/disminuir/${productId}`,
                rollback: () => {
                    const existing = this.findItem(productId);

                    if (existing) {
                        existing.quantity = previousQuantity;
                    } else {
                        item.quantity = previousQuantity;
                        this.items.push(item);
                    }
                },
            });
        },

        remove(productId) {
            const oldItems = [...this.items];
            this.items = this.items.filter(
                (item) => Number(item.product_id) !== Number(productId),
            );
            this.recalculateTotals();

            this.enqueue({
                url: `/ajax/carrito/eliminar/${productId}`,
                rollback: () => {
                    this.items = oldItems;
                },
            });
        },

        clear() {
            const oldItems = [...this.items];
            this.items = [];
            this.recalculateTotals();

            this.enqueue({
                url: `/ajax/carrito/vaciar`,
                rollback: () => {
                    this.items = oldItems;
                },
            });
        },

        formatUsd(value) {
            const number = Number(value || 0);
            return number.toFixed(2);
        },

        formatBs(value) {
            const number = Number(value || 0);
            return number.toLocaleString("es-VE", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },

        lineUsd(item) {
            return Number(item.price_usd || 0) * Number(item.quantity || 0);
        },

        lineBs(item) {
            return this.lineUsd(item) * this.exchangeRate;
        },
    };
};

document.addEventListener("DOMContentLoaded", function () {
    const siteHeader = document.querySelector(".site-header");

    if (!siteHeader) return;

    const SCROLL_DOWN_THRESHOLD = 55;
    const SCROLL_UP_THRESHOLD = 8;
    const DESKTOP_BREAKPOINT = 769;

    let isScrolled = false;
    let ticking = false;

    function isDesktop() {
        return window.innerWidth >= DESKTOP_BREAKPOINT;
    }

    function resetHeaderState() {
        isScrolled = false;
        siteHeader.classList.remove("is-scrolled");
    }

    function updateHeaderState() {
        if (!isDesktop()) {
            resetHeaderState();
            ticking = false;
            return;
        }

        const y = window.scrollY || window.pageYOffset;

        if (!isScrolled && y > SCROLL_DOWN_THRESHOLD) {
            isScrolled = true;
            siteHeader.classList.add("is-scrolled");
        } else if (isScrolled && y < SCROLL_UP_THRESHOLD) {
            isScrolled = false;
            siteHeader.classList.remove("is-scrolled");
        }

        ticking = false;
    }

    function onScroll() {
        if (!ticking) {
            window.requestAnimationFrame(updateHeaderState);
            ticking = true;
        }
    }

    function onResize() {
        if (!isDesktop()) {
            resetHeaderState();
        } else {
            updateHeaderState();
        }
    }

    updateHeaderState();

    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("resize", onResize);
});

window.heroCarousel = function () {
    return {
        current: 0,
        total: 0,
        interval: null,

        init() {
            this.total = this.$el.querySelectorAll(
                ".hero-carousel__slide",
            ).length;

            if (this.total > 1) {
                this.startAutoPlay();
            }
        },

        startAutoPlay() {
            this.stopAutoPlay();

            this.interval = setInterval(() => {
                this.next();
            }, 5000);
        },

        stopAutoPlay() {
            if (this.interval) {
                clearInterval(this.interval);
            }
        },

        next() {
            this.current = (this.current + 1) % this.total;
        },

        prev() {
            this.current = (this.current - 1 + this.total) % this.total;
        },

        goTo(index) {
            this.current = index;
        },
    };
};

window.labsDropdown = function () {
    return {
        open: false,
        query: "",

        toggle() {
            this.open = !this.open;

            if (!this.open) {
                this.query = "";
            }
        },

        close() {
            this.open = false;
            this.query = "";
        },

        matches(name) {
            if (!this.query.trim()) return true;

            return name.toLowerCase().includes(this.query.trim().toLowerCase());
        },
    };
};
document.addEventListener("DOMContentLoaded", function () {
    const navMenu = document.getElementById("mainNavMenu");
    const hoverLine = document.getElementById("navHoverLine");

    if (!navMenu || !hoverLine) return;

    const triggers = navMenu.querySelectorAll(
        ":scope > li > a, :scope > li > button",
    );

    function moveLine(el) {
        const menuRect = navMenu.getBoundingClientRect();
        const elRect = el.getBoundingClientRect();

        const left = elRect.left - menuRect.left;
        const width = elRect.width;

        hoverLine.style.width = `${width}px`;
        hoverLine.style.transform = `translateX(${left}px)`;
        hoverLine.style.opacity = "1";
    }

    function hideLine() {
        hoverLine.style.opacity = "0";
    }

    triggers.forEach((el) => {
        el.addEventListener("mouseenter", () => moveLine(el));
        el.addEventListener("focus", () => moveLine(el));
    });

    navMenu.addEventListener("mouseleave", hideLine);
});

window.labsMarquee = function () {
    return {
        position: 0,
        speed: 0.35,
        animationFrame: null,
        dragging: false,
        startX: 0,
        startPosition: 0,
        firstSetWidth: 0,
        pausedUntil: 0,

        init() {
            if (window.innerWidth > 768) return;

            this.$nextTick(() => {
                this.measure();
                this.bindDrag();
                this.start();
                window.addEventListener("resize", this.handleResize.bind(this));
            });
        },

        measure() {
            const track = this.$refs.track;
            if (!track) return;

            const items = Array.from(track.children);
            const half = Math.floor(items.length / 2);

            if (half === 0) return;

            let width = 0;

            for (let i = 0; i < half; i++) {
                width += items[i].offsetWidth;
            }

            width += (half - 1) * 14; // gap

            this.firstSetWidth = width;
        },

        handleResize() {
            if (window.innerWidth > 768) {
                this.stop();
                this.$refs.track.style.transform = "";
                return;
            }

            this.measure();
            if (!this.animationFrame) {
                this.start();
            }
        },

        start() {
            this.stop();

            const loop = () => {
                if (window.innerWidth > 768) return;

                const now = performance.now();

                if (!this.dragging && now > this.pausedUntil) {
                    this.position -= this.speed;

                    if (Math.abs(this.position) >= this.firstSetWidth) {
                        this.position += this.firstSetWidth;
                    }

                    this.applyTransform();
                }

                this.animationFrame = requestAnimationFrame(loop);
            };

            this.animationFrame = requestAnimationFrame(loop);
        },

        stop() {
            if (this.animationFrame) {
                cancelAnimationFrame(this.animationFrame);
                this.animationFrame = null;
            }
        },

        applyTransform() {
            this.$refs.track.style.transform = `translateX(${this.position}px)`;
        },

        bindDrag() {
            const marquee = this.$refs.marquee;

            const startDrag = (clientX) => {
                this.dragging = true;
                this.startX = clientX;
                this.startPosition = this.position;
                this.pausedUntil = performance.now();
                marquee.classList.add("is-dragging");
            };

            const moveDrag = (clientX) => {
                if (!this.dragging) return;

                const delta = clientX - this.startX;
                this.position = this.startPosition + delta;

                while (this.position > 0) {
                    this.position -= this.firstSetWidth;
                }

                while (Math.abs(this.position) >= this.firstSetWidth) {
                    this.position += this.firstSetWidth;
                }

                this.applyTransform();
            };

            const endDrag = () => {
                if (!this.dragging) return;

                this.dragging = false;
                this.pausedUntil = performance.now();
                marquee.classList.remove("is-dragging");
            };

            marquee.addEventListener("mousedown", (e) => {
                startDrag(e.clientX);
            });

            window.addEventListener("mousemove", (e) => {
                moveDrag(e.clientX);
            });

            window.addEventListener("mouseup", endDrag);

            marquee.addEventListener(
                "touchstart",
                (e) => {
                    if (e.touches.length !== 1) return;
                    startDrag(e.touches[0].clientX);
                },
                { passive: true },
            );

            marquee.addEventListener(
                "touchmove",
                (e) => {
                    if (e.touches.length !== 1) return;
                    moveDrag(e.touches[0].clientX);
                },
                { passive: true },
            );

            marquee.addEventListener("touchend", endDrag);
            marquee.addEventListener("touchcancel", endDrag);
        },
    };
};

document.addEventListener("DOMContentLoaded", function () {
    const checkoutForm = document.getElementById("checkoutForm");

    if (!checkoutForm) return;

    checkoutForm.addEventListener("submit", async function (e) {
        e.preventDefault();

        const submitButton = checkoutForm.querySelector(
            'button[type="submit"]',
        );
        const originalText = submitButton ? submitButton.innerHTML : "";

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = "Procesando...";
        }

        try {
            const formData = new FormData(checkoutForm);

            const response = await fetch(checkoutForm.action, {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: formData,
            });

            const data = await response.json();

            if (!response.ok) {
                if (data.errors) {
                    let messages = [];

                    Object.values(data.errors).forEach((group) => {
                        messages = messages.concat(group);
                    });

                    alert(messages.join("\n"));
                } else if (data.message) {
                    alert(data.message);
                } else {
                    alert("No se pudo procesar la compra.");
                }

                return;
            }

            if (data.success && data.whatsapp_url) {
                window.location.href = data.whatsapp_url;
                return;
            }
        } catch (error) {
            console.error(error);
            alert("Ocurrió un error al procesar la compra.");
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        }
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const carousels = document.querySelectorAll("[data-monthly-carousel]");

    if (!carousels.length) return;

    carousels.forEach(function (carousel) {
        const viewport = carousel.querySelector(".monthly-carousel__viewport");
        const track = carousel.querySelector("[data-carousel-track]");
        const prevButton = carousel.querySelector("[data-carousel-prev]");
        const nextButton = carousel.querySelector("[data-carousel-next]");

        if (!viewport || !track || !prevButton || !nextButton) return;

        let activeIndex = 0;
        let activePage = 0;
        let autoplay = null;

        let isPageAnimating = false;
        const pageTransitionDuration = 240;

        let dragStartX = 0;
        let dragStartY = 0;
        let dragDeltaX = 0;
        let isDragging = false;
        let isHorizontalDrag = false;

        const autoplayDelay = 3500;
        const desktopPageSize = 2;

        function isDesktopCarousel() {
            return window.innerWidth >= 901;
        }

        function getCards() {
            return Array.from(track.querySelectorAll(".product-card"));
        }

        function normalizeIndex(index, total) {
            if (!total) return 0;
            return ((index % total) + total) % total;
        }

        function getPageCount(total) {
            if (!total) return 0;

            if (isDesktopCarousel()) {
                return Math.ceil(total / desktopPageSize);
            }

            return total;
        }

        function updateCarousel() {
            const cards = getCards();
            const total = cards.length;
            const isDesktop = isDesktopCarousel();

            carousel.classList.toggle(
                "monthly-carousel--desktop-pages",
                isDesktop,
            );

            if (!total) {
                prevButton.disabled = true;
                nextButton.disabled = true;
                return;
            }

            const pageCount = getPageCount(total);

            prevButton.disabled = pageCount <= 1;
            nextButton.disabled = pageCount <= 1;

            cards.forEach(function (card) {
                card.classList.remove(
                    "is-monthly-active",
                    "is-monthly-prev",
                    "is-monthly-next",
                    "is-monthly-preview",
                    "is-monthly-visible",
                    "is-monthly-hidden",
                );
            });

            if (isDesktop) {
                activePage = normalizeIndex(activePage, pageCount);

                const start = activePage * desktopPageSize;
                const end = start + desktopPageSize;

                cards.forEach(function (card, index) {
                    if (index >= start && index < end) {
                        card.classList.add("is-monthly-visible");
                    } else {
                        card.classList.add("is-monthly-hidden");
                    }
                });

                return;
            }

            activeIndex = normalizeIndex(activeIndex, total);

            const prevIndex = normalizeIndex(activeIndex - 1, total);
            const nextIndex = normalizeIndex(activeIndex + 1, total);

            cards.forEach(function (card, index) {
                if (index === activeIndex) {
                    card.classList.add("is-monthly-active");
                    return;
                }

                if (index === prevIndex) {
                    card.classList.add("is-monthly-prev", "is-monthly-preview");
                    return;
                }

                if (index === nextIndex) {
                    card.classList.add("is-monthly-next", "is-monthly-preview");
                    return;
                }

                card.classList.add("is-monthly-hidden");
            });
        }

        function changeDesktopPage(direction) {
            const total = getCards().length;
            const pageCount = getPageCount(total);

            if (pageCount <= 1 || isPageAnimating) return;

            isPageAnimating = true;

            const leavingClass =
                direction > 0
                    ? "is-monthly-page-leaving-next"
                    : "is-monthly-page-leaving-prev";

            const enteringClass =
                direction > 0
                    ? "is-monthly-page-entering-next"
                    : "is-monthly-page-entering-prev";

            track.classList.add(leavingClass);

            setTimeout(function () {
                activePage = normalizeIndex(activePage + direction, pageCount);

                updateCarousel();

                track.classList.remove(leavingClass);
                track.classList.add(enteringClass);

                track.offsetHeight;

                requestAnimationFrame(function () {
                    track.classList.remove(enteringClass);

                    setTimeout(function () {
                        isPageAnimating = false;
                    }, pageTransitionDuration);
                });
            }, pageTransitionDuration);
        }

        function next() {
            const total = getCards().length;

            if (total <= 1) return;

            if (isDesktopCarousel()) {
                changeDesktopPage(1);
                return;
            }

            activeIndex = normalizeIndex(activeIndex + 1, total);
            updateCarousel();
        }

        function prev() {
            const total = getCards().length;

            if (total <= 1) return;

            if (isDesktopCarousel()) {
                changeDesktopPage(-1);
                return;
            }

            activeIndex = normalizeIndex(activeIndex - 1, total);
            updateCarousel();
        }

        function startAutoplay() {
            stopAutoplay();

            if (getPageCount(getCards().length) <= 1) return;

            autoplay = setInterval(function () {
                next();
            }, autoplayDelay);
        }

        function stopAutoplay() {
            if (autoplay) {
                clearInterval(autoplay);
                autoplay = null;
            }
        }

        function restartAutoplay() {
            stopAutoplay();
            startAutoplay();
        }

        nextButton.addEventListener("click", function () {
            next();
            restartAutoplay();
        });

        prevButton.addEventListener("click", function () {
            prev();
            restartAutoplay();
        });

        viewport.addEventListener("pointerdown", function (event) {
            if (getPageCount(getCards().length) <= 1) return;
            if (event.pointerType === "mouse") return;

            dragStartX = event.clientX;
            dragStartY = event.clientY;
            dragDeltaX = 0;
            isDragging = true;
            isHorizontalDrag = false;

            carousel.classList.add("is-swiping");

            if (viewport.setPointerCapture) {
                viewport.setPointerCapture(event.pointerId);
            }

            stopAutoplay();
        });

        viewport.addEventListener(
            "pointermove",
            function (event) {
                if (!isDragging) return;

                const deltaX = event.clientX - dragStartX;
                const deltaY = event.clientY - dragStartY;

                if (!isHorizontalDrag) {
                    if (Math.abs(deltaX) < 8 && Math.abs(deltaY) < 8) return;

                    if (Math.abs(deltaY) > Math.abs(deltaX)) {
                        return;
                    }

                    isHorizontalDrag = true;
                }

                event.preventDefault();
                dragDeltaX = deltaX;
            },
            { passive: false },
        );

        function finishDrag(event) {
            if (!isDragging) return;

            const swipeDistance = 45;

            carousel.classList.remove("is-swiping");

            if (viewport.releasePointerCapture && event && event.pointerId) {
                try {
                    viewport.releasePointerCapture(event.pointerId);
                } catch (error) {}
            }

            if (isHorizontalDrag && Math.abs(dragDeltaX) > swipeDistance) {
                if (dragDeltaX < 0) {
                    next();
                } else {
                    prev();
                }
            }

            isDragging = false;
            isHorizontalDrag = false;
            dragDeltaX = 0;

            startAutoplay();
        }

        viewport.addEventListener("pointerup", finishDrag);
        viewport.addEventListener("pointercancel", finishDrag);

        window.addEventListener("resize", function () {
            updateCarousel();
            restartAutoplay();
        });

        updateCarousel();
        startAutoplay();
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const countdowns = document.querySelectorAll("[data-countdown-monthly]");

    if (!countdowns.length) return;

    countdowns.forEach(function (countdown) {
        const daysEl = countdown.querySelector("[data-days]");
        const hoursEl = countdown.querySelector("[data-hours]");
        const minutesEl = countdown.querySelector("[data-minutes]");

        if (!daysEl || !hoursEl || !minutesEl) return;

        const serverNowMs = Number(countdown.dataset.serverNowMs);
        const deadlineMs = Number(countdown.dataset.deadlineMs);

        if (!serverNowMs || !deadlineMs) return;

        const pageLoadedAt = performance.now();

        function pad(value) {
            return String(value).padStart(2, "0");
        }

        function getServerNow() {
            const elapsedSincePageLoad = performance.now() - pageLoadedAt;
            return serverNowMs + elapsedSincePageLoad;
        }

        function updateCountdown() {
            const now = getServerNow();
            let remaining = deadlineMs - now;

            if (remaining < 0) {
                remaining = 0;
            }

            const days = Math.floor(remaining / (1000 * 60 * 60 * 24));
            const hours = Math.floor((remaining / (1000 * 60 * 60)) % 24);
            const minutes = Math.floor((remaining / (1000 * 60)) % 60);

            daysEl.textContent = pad(days);
            hoursEl.textContent = pad(hours);
            minutesEl.textContent = pad(minutes);
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("monthlyProductsForm");

    if (!form) return;

    const searchInput = form.querySelector("[data-monthly-search-input]");
    const resultsContainer = form.querySelector("[data-monthly-results]");
    const loadMoreButton = form.querySelector("[data-monthly-load-more]");
    const selectedInputsContainer = document.getElementById(
        "monthlySelectedInputs",
    );

    if (
        !searchInput ||
        !resultsContainer ||
        !loadMoreButton ||
        !selectedInputsContainer
    )
        return;

    const searchUrl = form.dataset.searchUrl;

    let nextPageUrl = null;
    let searchTimeout = null;
    let activeController = null;

    function getSelectedIds() {
        return Array.from(
            selectedInputsContainer.querySelectorAll(
                "[data-selected-product-input]",
            ),
        ).map((input) => input.value);
    }

    function addSelectedInput(productId) {
        const existing = selectedInputsContainer.querySelector(
            `[data-selected-product-input="${productId}"]`,
        );

        if (existing) return;

        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "monthly_products[]";
        input.value = productId;
        input.dataset.selectedProductInput = productId;

        selectedInputsContainer.appendChild(input);
    }

    function removeSelectedInput(productId) {
        const existing = selectedInputsContainer.querySelector(
            `[data-selected-product-input="${productId}"]`,
        );

        if (existing) {
            existing.remove();
        }
    }

    function syncVisibleCheckboxes() {
        const selectedIds = getSelectedIds();

        form.querySelectorAll("[data-monthly-product-checkbox]").forEach(
            (checkbox) => {
                const productId = checkbox.dataset.productId;

                checkbox.checked = selectedIds.includes(productId);
            },
        );
    }

    async function fetchProducts(url, append = false) {
        if (activeController) {
            activeController.abort();
        }

        activeController = new AbortController();

        resultsContainer.classList.add("is-loading");

        try {
            const response = await fetch(url, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
                signal: activeController.signal,
            });

            if (!response.ok) {
                throw new Error("No se pudieron cargar los productos.");
            }

            const data = await response.json();

            if (append) {
                resultsContainer.insertAdjacentHTML("beforeend", data.html);
            } else {
                resultsContainer.innerHTML = data.html;
            }

            nextPageUrl = data.next_page_url;

            loadMoreButton.style.display = data.has_more
                ? "inline-flex"
                : "none";

            syncVisibleCheckboxes();
        } catch (error) {
            if (error.name !== "AbortError") {
                console.error(error);
                resultsContainer.innerHTML =
                    '<p class="admin-empty-text">No se pudieron cargar los productos.</p>';
                loadMoreButton.style.display = "none";
            }
        } finally {
            resultsContainer.classList.remove("is-loading");
        }
    }

    function buildSearchUrl() {
        const params = new URLSearchParams();
        const query = searchInput.value.trim();

        if (query) {
            params.set("q", query);
        }

        return `${searchUrl}?${params.toString()}`;
    }

    searchInput.addEventListener("input", function () {
        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(function () {
            fetchProducts(buildSearchUrl(), false);
        }, 350);
    });

    loadMoreButton.addEventListener("click", function () {
        if (!nextPageUrl) return;

        fetchProducts(nextPageUrl, true);
    });

    form.addEventListener("change", function (event) {
        const checkbox = event.target.closest(
            "[data-monthly-product-checkbox]",
        );

        if (!checkbox) return;

        const productId = checkbox.dataset.productId;

        if (checkbox.checked) {
            addSelectedInput(productId);
        } else {
            removeSelectedInput(productId);
        }

        syncVisibleCheckboxes();
    });

    fetchProducts(buildSearchUrl(), false);
});

document.addEventListener("DOMContentLoaded", function () {
    const quoteBuilder = document.getElementById("quoteBuilder");

    if (!quoteBuilder) return;

    const productSearchUrl = quoteBuilder.dataset.productSearchUrl;

    if (!productSearchUrl) return;

    function escapeHtml(value) {
        return String(value ?? "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    function renderProducts(resultsContainer, products, append = false) {
        if (!products.length && !append) {
            resultsContainer.innerHTML =
                '<p class="admin-empty-text">No se encontraron productos.</p>';
            return;
        }

        const html = products
            .map(function (product) {
                return `
                <div class="quote-product-result">
                    <div class="quote-product-result__image">
                        <img src="${escapeHtml(product.image_url)}" alt="${escapeHtml(product.name)}">
                    </div>

                    <div class="quote-product-result__info">
                        <strong>${escapeHtml(product.name)}</strong>
                        <span>
                            ${escapeHtml(product.laboratory || "Sin laboratorio")}
                            · Stock: ${escapeHtml(product.stock)}
                            · $ ${escapeHtml(product.price)}
                        </span>
                    </div>

                    <button
                        type="button"
                        class="admin-btn admin-btn--secondary"
                        data-add-product-button
                        data-product-id="${escapeHtml(product.id)}"
                    >
                        Agregar
                    </button>
                </div>
            `;
            })
            .join("");

        if (append) {
            resultsContainer.insertAdjacentHTML("beforeend", html);
        } else {
            resultsContainer.innerHTML = html;
        }
    }

    async function searchProducts(input, page = 1, append = false) {
        const groupCard = input.closest("[data-quote-group]");
        const resultsContainer = groupCard.querySelector(
            "[data-quote-search-results]",
        );
        const loadMoreButton = groupCard.querySelector(
            "[data-quote-load-more-products]",
        );

        const query = input.value.trim();

        if (query.length < 2) {
            resultsContainer.innerHTML = "";

            if (loadMoreButton) {
                loadMoreButton.style.display = "none";
                loadMoreButton.dataset.nextPage = "";
            }

            return;
        }

        if (!append) {
            resultsContainer.innerHTML =
                '<p class="admin-empty-text">Buscando productos...</p>';
        } else if (loadMoreButton) {
            loadMoreButton.disabled = true;
            loadMoreButton.textContent = "Cargando...";
        }

        const url = `${productSearchUrl}?q=${encodeURIComponent(query)}&page=${page}`;

        try {
            const response = await fetch(url, {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            });

            if (!response.ok) {
                throw new Error("Error al buscar productos.");
            }

            const data = await response.json();

            renderProducts(resultsContainer, data.products || [], append);

            if (loadMoreButton) {
                if (data.has_more && data.next_page) {
                    loadMoreButton.style.display = "inline-flex";
                    loadMoreButton.dataset.nextPage = data.next_page;
                    loadMoreButton.dataset.currentQuery = query;
                    loadMoreButton.disabled = false;
                    loadMoreButton.textContent = "Cargar más productos";
                } else {
                    loadMoreButton.style.display = "none";
                    loadMoreButton.dataset.nextPage = "";
                    loadMoreButton.dataset.currentQuery = "";
                }
            }
        } catch (error) {
            console.error(error);

            if (!append) {
                resultsContainer.innerHTML =
                    '<p class="admin-empty-text">No se pudieron cargar los productos.</p>';
            }

            if (loadMoreButton) {
                loadMoreButton.disabled = false;
                loadMoreButton.textContent = "Cargar más productos";
            }
        }
    }

    quoteBuilder.addEventListener("input", function (event) {
        const input = event.target.closest("[data-quote-search-input]");

        if (!input) return;

        const groupCard = input.closest("[data-quote-group]");
        const loadMoreButton = groupCard.querySelector(
            "[data-quote-load-more-products]",
        );

        if (loadMoreButton) {
            loadMoreButton.style.display = "none";
            loadMoreButton.dataset.nextPage = "";
            loadMoreButton.dataset.currentQuery = "";
        }

        clearTimeout(input.searchTimeout);

        input.searchTimeout = setTimeout(function () {
            searchProducts(input, 1, false);
        }, 350);
    });

    quoteBuilder.addEventListener("click", function (event) {
        const button = event.target.closest("[data-add-product-button]");

        if (!button) return;

        const groupCard = button.closest("[data-quote-group]");
        const addProductForm = groupCard.querySelector(
            "[data-add-product-form]",
        );
        const productIdInput = groupCard.querySelector("[data-add-product-id]");
        const quantityHiddenInput = groupCard.querySelector(
            "[data-add-product-quantity]",
        );
        const quantityInput = groupCard.querySelector(
            "[data-quote-product-qty]",
        );

        productIdInput.value = button.dataset.productId;
        quantityHiddenInput.value = quantityInput.value || 1;

        if (addProductForm.requestSubmit) {
            addProductForm.requestSubmit();
        } else {
            addProductForm.dispatchEvent(
                new Event("submit", {
                    bubbles: true,
                    cancelable: true,
                }),
            );
        }
    });

    quoteBuilder.addEventListener("click", function (event) {
        const loadMoreButton = event.target.closest(
            "[data-quote-load-more-products]",
        );

        if (!loadMoreButton) return;

        const groupCard = loadMoreButton.closest("[data-quote-group]");
        const input = groupCard.querySelector("[data-quote-search-input]");
        const nextPage = Number(loadMoreButton.dataset.nextPage || 0);

        if (!input || !nextPage) return;

        searchProducts(input, nextPage, true);
    });

    async function submitQuoteAjaxForm(form) {
        const confirmMessage = form.dataset.confirmMessage;

        if (confirmMessage && !confirm(confirmMessage)) {
            return;
        }

        const submitButton = form.querySelector('button[type="submit"]');

        if (submitButton) {
            submitButton.disabled = true;
        }

        const formData = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: formData,
            });

            const data = await response.json();

            if (!response.ok) {
                if (data.errors) {
                    alert(Object.values(data.errors).flat().join("\n"));
                    return;
                }

                throw new Error(
                    data.message || "No se pudo actualizar el presupuesto.",
                );
            }

            const totalCard = quoteBuilder.querySelector(
                "[data-quote-total-card]",
            );

            if (totalCard && data.quote_total_html) {
                totalCard.outerHTML = data.quote_total_html;
            }

            if (data.group_id && data.group_html) {
                const currentGroup = quoteBuilder.querySelector(
                    `[data-quote-group][data-group-id="${data.group_id}"]`,
                );

                if (currentGroup) {
                    currentGroup.outerHTML = data.group_html;
                }
            }
        } catch (error) {
            console.error(error);
            alert("No se pudo actualizar el presupuesto. Intenta nuevamente.");
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    }

    quoteBuilder.addEventListener("submit", function (event) {
        const form = event.target.closest("[data-quote-ajax-form]");

        if (!form) return;

        event.preventDefault();

        submitQuoteAjaxForm(form);
    });
    quoteBuilder.addEventListener("input", function (event) {
        const input = event.target.closest("[data-quote-item-auto-update]");

        if (!input) return;

        const form = input.closest("[data-quote-ajax-form]");

        if (!form) return;

        clearTimeout(form.autoUpdateTimeout);

        form.autoUpdateTimeout = setTimeout(function () {
            const quantityInput = form.querySelector('input[name="quantity"]');

            const quantity = Number(quantityInput?.value || 0);

            submitQuoteAjaxForm(form);
        }, 450);
    });

    window.downloadQuoteCapture = async function (groupCard) {
        if (!groupCard) return;

        if (!window.html2canvas) {
            alert("No se pudo cargar la herramienta para generar la imagen.");
            return;
        }

        const captureContent = groupCard.querySelector(
            "[data-quote-capture-content]",
        );

        if (!captureContent) {
            alert("No se encontró el contenido para generar la imagen.");
            return;
        }

        try {
            captureContent.classList.add("is-generating-png");

            const images = Array.from(captureContent.querySelectorAll("img"));

            await Promise.all(
                images.map(function (img) {
                    if (img.complete) return Promise.resolve();

                    return new Promise(function (resolve) {
                        img.onload = resolve;
                        img.onerror = resolve;
                    });
                }),
            );

            const canvas = await html2canvas(captureContent, {
                backgroundColor: "#ffffff",
                scale: 2,
                useCORS: true,
                allowTaint: false,
                scrollX: 0,
                scrollY: 0,
            });

            const imageUrl = canvas.toDataURL("image/png");

            const groupTitle =
                groupCard.querySelector(".quote-group-title-row h2")
                    ?.textContent || "presupuesto";

            const safeTitle = groupTitle
                .toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/[^a-z0-9]+/g, "-")
                .replace(/^-+|-+$/g, "");

            const link = document.createElement("a");
            link.href = imageUrl;
            link.download = `${safeTitle || "presupuesto"}-captura.png`;

            document.body.appendChild(link);
            link.click();
            link.remove();
        } catch (error) {
            console.error(error);
            alert("No se pudo generar la imagen PNG.");
        } finally {
            captureContent.classList.remove("is-generating-png");
        }
    };
});
