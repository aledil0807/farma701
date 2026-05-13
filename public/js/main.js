window.cartControl = function (config) {
    return {
        productId: config.productId,
        quantity: config.initialQuantity || 0,
        addUrl: config.addUrl,
        incrementUrl: config.incrementUrl,
        decrementUrl: config.decrementUrl,

        pendingOps: [],
        processing: false,

        enqueue(url, optimisticChange = 0) {
            this.quantity = Math.max(0, this.quantity + optimisticChange);

            this.pendingOps.push({
                url,
                optimisticChange,
            });

            this.processQueue();
        },

        async processQueue() {
            if (this.processing || this.pendingOps.length === 0) return;

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

                    this.quantity = data.quantity ?? this.quantity;

                    window.dispatchEvent(
                        new CustomEvent("cart-updated", {
                            detail: {
                                unitsCount: data.totals?.units_count ?? 0,
                            },
                        }),
                    );
                } catch (error) {
                    console.error(error);

                    if (op.optimisticChange > 0) {
                        this.quantity = Math.max(
                            0,
                            this.quantity - op.optimisticChange,
                        );
                    } else if (op.optimisticChange < 0) {
                        this.quantity =
                            this.quantity + Math.abs(op.optimisticChange);
                    }
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
            if (this.quantity <= 0) return;
            this.enqueue(this.decrementUrl, -1);
        },
    };
};

window.cartBadge = function (config) {
    return {
        unitsCount: config.initialUnits || 0,

        init() {
            window.addEventListener("cart-updated", (event) => {
                this.unitsCount = event.detail.unitsCount ?? 0;
            });
        },
    };
};

window.cartPage = function (config) {
    return {
        items: config.items || [],
        totals: config.totals || {},
        exchangeRate: config.exchangeRate || 0,
        loading: false,

        async syncFromServer(url, options = {}) {
            this.loading = true;

            try {
                const response = await fetch(url, {
                    method: options.method || "POST",
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                        "Content-Type": "application/json",
                    },
                    body: options.body
                        ? JSON.stringify(options.body)
                        : JSON.stringify({}),
                });

                if (!response.ok) {
                    throw new Error("Error en la petición");
                }

                const data = await response.json();

                this.items = Object.values(data.cart?.items || {});
                this.totals = data.totals || {};

                window.dispatchEvent(
                    new CustomEvent("cart-updated", {
                        detail: {
                            unitsCount: data.totals?.units_count ?? 0,
                        },
                    }),
                );
            } catch (error) {
                console.error(error);
            } finally {
                this.loading = false;
            }
        },

        increment(productId) {
            this.syncFromServer(`/ajax/carrito/incrementar/${productId}`);
        },

        decrement(productId) {
            this.syncFromServer(`/ajax/carrito/disminuir/${productId}`);
        },

        remove(productId) {
            this.syncFromServer(`/ajax/carrito/eliminar/${productId}`);
        },

        clear() {
            this.syncFromServer(`/ajax/carrito/vaciar`);
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
            return this.exchangeRate > 0
                ? this.lineUsd(item) * this.exchangeRate
                : 0;
        },
    };
};

document.addEventListener("DOMContentLoaded", () => {
    const countdown = document.querySelector(".countdown");

    if (countdown) {
        const deadline = new Date(countdown.dataset.deadline).getTime();
        const daysEl = countdown.querySelector("[data-days]");
        const hoursEl = countdown.querySelector("[data-hours]");
        const minutesEl = countdown.querySelector("[data-minutes]");

        const format = (value) => String(value).padStart(2, "0");

        const updateCountdown = () => {
            const now = Date.now();
            const diff = deadline - now;

            if (diff <= 0) {
                daysEl.textContent = "00";
                hoursEl.textContent = "00";
                minutesEl.textContent = "00";
                return;
            }

            const totalMinutes = Math.floor(diff / (1000 * 60));
            const days = Math.floor(totalMinutes / (60 * 24));
            const hours = Math.floor((totalMinutes % (60 * 24)) / 60);
            const minutes = totalMinutes % 60;

            daysEl.textContent = format(days);
            hoursEl.textContent = format(hours);
            minutesEl.textContent = format(minutes);
        };

        updateCountdown();
        setInterval(updateCountdown, 60000);
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const dropdowns = document.querySelectorAll(".has-dropdown");

    dropdowns.forEach((dropdown) => {
        const button = dropdown.querySelector(".dropdown-toggle");

        button.addEventListener("click", function (e) {
            e.stopPropagation();

            dropdowns.forEach((item) => {
                if (item !== dropdown) {
                    item.classList.remove("active");
                }
            });

            dropdown.classList.toggle("active");
        });
    });

    document.addEventListener("click", function () {
        dropdowns.forEach((dropdown) => {
            dropdown.classList.remove("active");
        });
    });
});
