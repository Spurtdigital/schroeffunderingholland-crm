<div
    x-data="{
        drawing: false,
        ctx: null,
        canvas: null,
        statePath: '{{ $field->getStatePath() }}',
        lastX: 0,
        lastY: 0,
        hasStrokes: false,
        _observer: null,
        _timers: [],
        _destroyed: false,

        setup() {
            if (this._destroyed) return;
            this.canvas = this.$refs.canvas;
            if (!this.canvas) return;
            const dpr = window.devicePixelRatio || 1;
            this.canvas.width  = this.canvas.offsetWidth  * dpr;
            this.canvas.height = this.canvas.offsetHeight * dpr;
            this.ctx = this.canvas.getContext('2d');
            this.ctx.scale(dpr, dpr);
            this.ctx.fillStyle = '#ffffff';
            this.ctx.fillRect(0, 0, this.canvas.offsetWidth, this.canvas.offsetHeight);
            this.ctx.strokeStyle = '#000000';
            this.ctx.lineWidth = 2;
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this.hasStrokes = false;
        },

        _setTimeout(fn, delay) {
            const id = setTimeout(() => {
                if (!this._destroyed) fn();
            }, delay);
            this._timers.push(id);
            return id;
        },

        init() {
            const trySetup = () => {
                if (this._destroyed) return;
                if (this.$refs.canvas && this.$refs.canvas.offsetWidth > 0) {
                    this.setup();
                } else {
                    this._setTimeout(trySetup, 50);
                }
            };
            this.$nextTick(() => this._setTimeout(trySetup, 80));

            // Herinitialiseer wanneer wizard-stap actief wordt
            const stepEl = this.$el.closest('.fi-sc-wizard-step');
            if (stepEl) {
                this._observer = new MutationObserver(() => {
                    if (this._destroyed) return;
                    if (stepEl.classList.contains('fi-active') && (!this.canvas || this.canvas.width === 0)) {
                        this.$nextTick(() => this._setTimeout(() => this.setup(), 80));
                    }
                });
                this._observer.observe(stepEl, { attributes: true, attributeFilter: ['class'] });
            }
        },

        destroy() {
            this._destroyed = true;
            this._timers.forEach(id => clearTimeout(id));
            this._timers = [];
            if (this._observer) {
                this._observer.disconnect();
                this._observer = null;
            }
        },

        getPos(e) {
            const rect = this.canvas.getBoundingClientRect();
            if (e.touches) {
                return {
                    x: e.touches[0].clientX - rect.left,
                    y: e.touches[0].clientY - rect.top,
                };
            }
            return {
                x: e.clientX - rect.left,
                y: e.clientY - rect.top,
            };
        },

        startDraw(e) {
            e.preventDefault();
            if (!this.ctx) return;
            this.drawing = true;
            const pos = this.getPos(e);
            this.lastX = pos.x;
            this.lastY = pos.y;
            this.ctx.beginPath();
            this.ctx.arc(pos.x, pos.y, 1, 0, Math.PI * 2);
            this.ctx.fillStyle = '#000000';
            this.ctx.fill();
        },

        draw(e) {
            e.preventDefault();
            if (!this.drawing || !this.ctx) return;
            const pos = this.getPos(e);
            this.ctx.beginPath();
            this.ctx.moveTo(this.lastX, this.lastY);
            this.ctx.lineTo(pos.x, pos.y);
            this.ctx.stroke();
            this.lastX = pos.x;
            this.lastY = pos.y;
            this.hasStrokes = true;
        },

        stopDraw(e) {
            if (!this.drawing) return;
            this.drawing = false;
            if (this.hasStrokes && !this._destroyed && this.$wire) {
                try {
                    this.$wire.set(this.statePath, this.canvas.toDataURL('image/png'));
                } catch (err) {
                    // Livewire component may already be unmounted
                }
            }
        },

        clear() {
            if (!this.ctx) return;
            this.ctx.fillStyle = '#ffffff';
            this.ctx.fillRect(0, 0, this.canvas.offsetWidth, this.canvas.offsetHeight);
            this.hasStrokes = false;
            if (!this._destroyed && this.$wire) {
                try {
                    this.$wire.set(this.statePath, '');
                } catch (err) {
                    // Livewire component may already be unmounted
                }
            }
        }
    }"
    class="space-y-2"
>
    <div
        class="relative rounded-lg border-2 border-gray-300 dark:border-gray-600 bg-white"
        style="touch-action: none; user-select: none;"
    >
        <canvas
            x-ref="canvas"
            class="block w-full rounded-lg"
            style="height: 200px; cursor: crosshair; touch-action: none;"
            x-on:mousedown="startDraw($event)"
            x-on:mousemove="draw($event)"
            x-on:mouseup="stopDraw($event)"
            x-on:mouseleave="stopDraw($event)"
            x-on:touchstart.prevent="startDraw($event)"
            x-on:touchmove.prevent="draw($event)"
            x-on:touchend="stopDraw($event)"
        ></canvas>
    </div>

    <div class="flex items-center justify-between">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Teken met vinger (iPad) of muis
        </p>
        <button
            type="button"
            x-on:click="clear()"
            class="text-xs text-danger-600 hover:text-danger-700 font-medium"
        >
            Opnieuw tekenen
        </button>
    </div>
</div>
