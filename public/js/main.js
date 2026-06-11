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

document.addEventListener('DOMContentLoaded', function () {
  const siteHeader = document.querySelector('.site-header');

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
    siteHeader.classList.remove('is-scrolled');
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
      siteHeader.classList.add('is-scrolled');
    } else if (isScrolled && y < SCROLL_UP_THRESHOLD) {
      isScrolled = false;
      siteHeader.classList.remove('is-scrolled');
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

  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onResize);
});

window.heroCarousel = function () {
    return {
        current: 0,
        total: 0,
        interval: null,

        init() {
            this.total = this.$el.querySelectorAll('.hero-carousel__slide').length;

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
        }
    };
};

window.labsDropdown = function () {
    return {
        open: false,
        query: '',

        toggle() {
            this.open = !this.open;

            if (!this.open) {
                this.query = '';
            }
        },

        close() {
            this.open = false;
            this.query = '';
        },

        matches(name) {
            if (!this.query.trim()) return true;

            return name.toLowerCase().includes(this.query.trim().toLowerCase());
        }
    };
};
document.addEventListener('DOMContentLoaded', function () {
  const navMenu = document.getElementById('mainNavMenu');
  const hoverLine = document.getElementById('navHoverLine');

  if (!navMenu || !hoverLine) return;

  const triggers = navMenu.querySelectorAll(':scope > li > a, :scope > li > button');

  function moveLine(el) {
    const menuRect = navMenu.getBoundingClientRect();
    const elRect = el.getBoundingClientRect();

    const left = elRect.left - menuRect.left;
    const width = elRect.width;

    hoverLine.style.width = `${width}px`;
    hoverLine.style.transform = `translateX(${left}px)`;
    hoverLine.style.opacity = '1';
  }

  function hideLine() {
    hoverLine.style.opacity = '0';
  }

  triggers.forEach((el) => {
    el.addEventListener('mouseenter', () => moveLine(el));
    el.addEventListener('focus', () => moveLine(el));
  });

  navMenu.addEventListener('mouseleave', hideLine);
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
                window.addEventListener('resize', this.handleResize.bind(this));
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
                this.$refs.track.style.transform = '';
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
                marquee.classList.add('is-dragging');
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
                marquee.classList.remove('is-dragging');
            };

            marquee.addEventListener('mousedown', (e) => {
                startDrag(e.clientX);
            });

            window.addEventListener('mousemove', (e) => {
                moveDrag(e.clientX);
            });

            window.addEventListener('mouseup', endDrag);

            marquee.addEventListener('touchstart', (e) => {
                if (e.touches.length !== 1) return;
                startDrag(e.touches[0].clientX);
            }, { passive: true });

            marquee.addEventListener('touchmove', (e) => {
                if (e.touches.length !== 1) return;
                moveDrag(e.touches[0].clientX);
            }, { passive: true });

            marquee.addEventListener('touchend', endDrag);
            marquee.addEventListener('touchcancel', endDrag);
        }
    };
};

document.addEventListener('DOMContentLoaded', function () {
    const checkoutForm = document.getElementById('checkoutForm');

    if (!checkoutForm) return;

    checkoutForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const submitButton = checkoutForm.querySelector('button[type="submit"]');
        const originalText = submitButton ? submitButton.innerHTML : '';

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = 'Procesando...';
        }

        try {
            const formData = new FormData(checkoutForm);

            const response = await fetch(checkoutForm.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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

                    alert(messages.join('\n'));
                } else if (data.message) {
                    alert(data.message);
                } else {
                    alert('No se pudo procesar la compra.');
                }

                return;
            }

            if (data.success && data.whatsapp_url) {
                window.open(data.whatsapp_url, '_blank');

                if (window.Alpine?.store('cart')) {
                    Alpine.store('cart').forceSync(0);
                }

                window.location.reload();
            }
        } catch (error) {
            console.error(error);
            alert('Ocurrió un error al procesar la compra.');
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        }
    });
});
