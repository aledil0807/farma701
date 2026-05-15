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

window.cartControl = function (config) {
    return {
        productId: config.productId,
        addUrl: config.addUrl,
        incrementUrl: config.incrementUrl,
        decrementUrl: config.decrementUrl,

        serverQuantity: Number(config.initialQuantity || 0),
        pendingDelta: 0,

        pendingOps: [],
        processing: false,

        get quantity() {
            return Math.max(0, this.serverQuantity + this.pendingDelta);
        },

        enqueue(url, delta) {
            if (delta < 0 && this.quantity <= 0) return;

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
