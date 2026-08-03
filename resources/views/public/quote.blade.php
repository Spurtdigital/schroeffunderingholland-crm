@php
    $logoBase64 = null;
    if (!empty($settings->company_logo)) {
        $logoPath = storage_path('app/public/' . $settings->company_logo);
        if (file_exists($logoPath)) {
            $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $mime = $ext === 'jpg' || $ext === 'jpeg' ? 'image/jpeg' : 'image/png';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }
    }
    if (!$logoBase64) {
        $fallbackLogo = public_path('images/logo.jpg');
        if (file_exists($fallbackLogo)) {
            $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($fallbackLogo));
        }
    }

    // Generate signed URL for PDF
    $pdfUrl = URL::signedRoute('quotes.public.pdf', ['quote' => $quote->id], now()->addDays(30));
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Offerte {{ $quote->displayNumber() ?? 'bekijken' }} - {{ $settings->company_name ?? 'Offerte' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .signature-canvas {
            touch-action: none;
            cursor: crosshair;
        }
        .pdf-container {
            height: calc(100vh - 180px);
            min-height: 600px;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    {{-- Header --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div class="max-w-[1600px] mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="{{ $settings->company_name ?? 'Logo' }}" class="h-10 max-w-[150px] object-contain">
                    @else
                        <span class="text-xl font-bold text-blue-600">{{ $settings->company_name ?? 'Offerte' }}</span>
                    @endif
                    <div class="hidden sm:block border-l border-gray-300 pl-4">
                        <p class="text-sm text-gray-500">Offerte</p>
                        <p class="font-semibold text-gray-900">{{ $quote->displayNumber() ?? 'Concept' }}</p>
                    </div>
                </div>
                <div class="text-right">
                    @if($quote->valid_until)
                    <p class="text-sm {{ $quote->isExpired() ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                        Geldig tot: {{ $quote->valid_until->format('d-m-Y') }}
                        @if($quote->isExpired()) (verlopen) @endif
                    </p>
                    @endif
                    <p class="text-lg font-bold text-gray-900">&euro; {{ number_format($quote->total, 2, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="max-w-[1600px] mx-auto px-4 py-6">
        {{-- Success/Error Messages --}}
        @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <p class="text-green-800 font-medium">{{ session('success') }}</p>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <p class="text-red-800 font-medium">{{ session('error') }}</p>
            </div>
        </div>
        @endif

        {{-- Status Banner for signed/expired --}}
        @if($quote->isSigned())
        <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="bg-green-100 rounded-full p-2">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-green-800">Offerte goedgekeurd en ondertekend</p>
                    <p class="text-sm text-green-600">Ondertekend door {{ $quote->signed_by_name }} op {{ $quote->signed_at->format('d-m-Y \o\m H:i') }}</p>
                </div>
            </div>
        </div>
        @elseif($quote->isExpired())
        <div class="mb-6 bg-amber-50 border border-amber-200 rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="bg-amber-100 rounded-full p-2">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-amber-800">Offerte verlopen</p>
                    <p class="text-sm text-amber-600">Deze offerte was geldig tot {{ $quote->valid_until->format('d-m-Y') }}. Neem contact met ons op voor een nieuwe offerte.</p>
                </div>
            </div>
        </div>
        @endif

        {{-- Two Column Layout: PDF (8 cols) + Sign Form (4 cols) --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- PDF Viewer - 8 columns --}}
            <div class="lg:col-span-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden h-full">
                    <div class="bg-gray-50 border-b border-gray-200 px-4 py-3 flex items-center justify-between">
                        <h2 class="font-semibold text-gray-700">Offerte document</h2>
                        <a href="{{ $pdfUrl }}" target="_blank" class="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-700 font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Download PDF
                        </a>
                    </div>
                    <div class="pdf-container">
                        <iframe
                            src="{{ $pdfUrl }}"
                            class="w-full h-full border-0"
                            title="Offerte PDF"
                        ></iframe>
                    </div>
                </div>
            </div>

            {{-- Signature Form - 4 columns --}}
            <div class="lg:col-span-4">
                @if(!$quote->isSigned() && !$quote->isExpired())
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-24" x-data="signaturePad()">
                    <h2 class="text-lg font-semibold text-gray-900 mb-2">Offerte goedkeuren</h2>
                    <p class="text-gray-600 text-sm mb-6">Door te ondertekenen gaat u akkoord met de voorwaarden in deze offerte.</p>

                    <form method="POST" action="{{ URL::signedRoute('quotes.public.sign', $quote) }}" @submit="onSubmit">
                        @csrf

                        {{-- Name Input --}}
                        <div class="mb-4">
                            <label for="signed_by_name" class="block text-sm font-medium text-gray-700 mb-1">Naam</label>
                            <input
                                type="text"
                                id="signed_by_name"
                                name="signed_by_name"
                                required
                                value="{{ old('signed_by_name', $quote->company->contact_person ?? '') }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                placeholder="Uw volledige naam"
                            >
                            @error('signed_by_name')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Date (readonly, prefilled) --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Datum</label>
                            <input
                                type="text"
                                readonly
                                value="{{ now()->format('d-m-Y') }}"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-600 text-sm cursor-not-allowed"
                            >
                        </div>

                        {{-- Signature Canvas --}}
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Handtekening</label>
                            <div class="border-2 border-gray-300 rounded-lg bg-white" :class="{ 'border-red-500': signatureError }">
                                <canvas
                                    x-ref="canvas"
                                    class="signature-canvas w-full rounded-lg"
                                    style="height: 150px;"
                                    @mousedown="startDrawing"
                                    @mousemove="draw"
                                    @mouseup="stopDrawing"
                                    @mouseleave="stopDrawing"
                                    @touchstart.prevent="startDrawing"
                                    @touchmove.prevent="draw"
                                    @touchend="stopDrawing"
                                ></canvas>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <p class="text-xs text-gray-400">Teken met muis of vinger</p>
                                <button type="button" @click="clear()" class="text-xs text-red-600 hover:text-red-700 font-medium">
                                    Wissen
                                </button>
                            </div>
                            <input type="hidden" name="signature" x-ref="signatureInput">
                            @error('signature')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                            <p x-show="signatureError" x-cloak class="text-red-600 text-xs mt-1">Een handtekening is verplicht.</p>
                        </div>

                        {{-- Terms Checkbox --}}
                        <div class="mb-5">
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="accept_terms"
                                    required
                                    class="mt-0.5 w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500"
                                >
                                <span class="text-sm text-gray-700">
                                    Ik ga akkoord met de
                                    @if(!empty($settings->terms_pdf))
                                    <a href="{{ route('terms') }}" target="_blank" class="text-blue-600 hover:text-blue-700 underline">algemene voorwaarden</a>
                                    @else
                                    algemene voorwaarden
                                    @endif
                                </span>
                            </label>
                            @error('accept_terms')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Submit Button --}}
                        <button
                            type="submit"
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors flex items-center justify-center gap-2"
                            :disabled="submitting"
                        >
                            <template x-if="!submitting">
                                <span class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Ondertekenen
                                </span>
                            </template>
                            <template x-if="submitting">
                                <span class="flex items-center gap-2">
                                    <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Verwerken...
                                </span>
                            </template>
                        </button>
                    </form>
                </div>
                @elseif($quote->isSigned())
                {{-- Show signature details --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-24">
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h2 class="text-lg font-semibold text-green-800">Ondertekend</h2>
                    </div>

                    <div class="space-y-3 mb-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Naam</p>
                            <p class="font-medium text-gray-900">{{ $quote->signed_by_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Datum</p>
                            <p class="font-medium text-gray-900">{{ $quote->signed_at->format('d-m-Y \o\m H:i') }}</p>
                        </div>
                    </div>

                    @if($quote->getFirstMediaUrl('quote-signature'))
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-2">Handtekening</p>
                        <div class="border border-gray-200 rounded-lg p-3 bg-gray-50">
                            <img src="{{ $quote->getFirstMediaUrl('quote-signature') }}" alt="Handtekening" class="max-h-24 mx-auto">
                        </div>
                    </div>
                    @endif
                </div>
                @else
                {{-- Expired state --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-24">
                    <div class="text-center py-6">
                        <svg class="w-12 h-12 text-amber-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="font-semibold text-gray-900 mb-1">Offerte verlopen</h3>
                        <p class="text-sm text-gray-600">Neem contact met ons op voor een nieuwe offerte.</p>
                        @if($settings->company_phone)
                        <p class="mt-4 text-sm">
                            <a href="tel:{{ $settings->company_phone }}" class="text-blue-600 hover:text-blue-700 font-medium">
                                {{ $settings->company_phone }}
                            </a>
                        </p>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Footer --}}
        <div class="mt-8 text-center text-sm text-gray-500">
            @if($settings->company_name)
            <p class="font-medium">{{ $settings->company_name }}</p>
            @endif
            @if($settings->company_phone || $settings->company_email)
            <p class="mt-1">
                @if($settings->company_phone) Tel: {{ $settings->company_phone }} @endif
                @if($settings->company_phone && $settings->company_email) | @endif
                @if($settings->company_email) E-mail: {{ $settings->company_email }} @endif
            </p>
            @endif
        </div>
    </div>

    <script>
    function signaturePad() {
        return {
            drawing: false,
            ctx: null,
            canvas: null,
            lastX: 0,
            lastY: 0,
            hasStrokes: false,
            signatureError: false,
            submitting: false,

            init() {
                this.$nextTick(() => {
                    this.setup();
                    window.addEventListener('resize', () => this.setup());
                });
            },

            setup() {
                this.canvas = this.$refs.canvas;
                if (!this.canvas) return;

                const rect = this.canvas.getBoundingClientRect();
                const dpr = window.devicePixelRatio || 1;

                this.canvas.width = rect.width * dpr;
                this.canvas.height = rect.height * dpr;

                this.ctx = this.canvas.getContext('2d');
                this.ctx.scale(dpr, dpr);

                this.ctx.fillStyle = '#ffffff';
                this.ctx.fillRect(0, 0, rect.width, rect.height);

                this.ctx.strokeStyle = '#000000';
                this.ctx.lineWidth = 2;
                this.ctx.lineCap = 'round';
                this.ctx.lineJoin = 'round';

                this.hasStrokes = false;
            },

            getPos(e) {
                const rect = this.canvas.getBoundingClientRect();
                if (e.touches) {
                    return {
                        x: e.touches[0].clientX - rect.left,
                        y: e.touches[0].clientY - rect.top
                    };
                }
                return {
                    x: e.clientX - rect.left,
                    y: e.clientY - rect.top
                };
            },

            startDrawing(e) {
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
                if (!this.drawing || !this.ctx) return;
                const pos = this.getPos(e);

                this.ctx.beginPath();
                this.ctx.moveTo(this.lastX, this.lastY);
                this.ctx.lineTo(pos.x, pos.y);
                this.ctx.stroke();

                this.lastX = pos.x;
                this.lastY = pos.y;
                this.hasStrokes = true;
                this.signatureError = false;
            },

            stopDrawing() {
                this.drawing = false;
            },

            clear() {
                if (!this.ctx || !this.canvas) return;
                const rect = this.canvas.getBoundingClientRect();
                this.ctx.fillStyle = '#ffffff';
                this.ctx.fillRect(0, 0, rect.width, rect.height);
                this.hasStrokes = false;
            },

            onSubmit(e) {
                if (!this.hasStrokes) {
                    e.preventDefault();
                    this.signatureError = true;
                    return false;
                }

                this.$refs.signatureInput.value = this.canvas.toDataURL('image/png');
                this.submitting = true;
                return true;
            }
        }
    }
    </script>
</body>
</html>
