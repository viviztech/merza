<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            scanner: null,
            scanning: false,
            busy: false,
            error: '',
            scanned: '',
            readerId: @js($getId().'_reader'),
            statePath: @js($getStatePath()),
            moduleUrl: @js(\Illuminate\Support\Facades\Vite::asset('resources/js/filament/order-tracking-scanner.js')),
            async setup() {
                if (this.scanner) return;
                const { Html5Qrcode, Html5QrcodeSupportedFormats } = await import(this.moduleUrl);
                this.scanner = new Html5Qrcode(this.readerId, {
                    formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
                    verbose: false,
                });
            },
            async start() {
                this.error = '';
                this.busy = true;
                this.scanning = true;
                try {
                    await this.setup();
                    await this.scanner.start(
                        { facingMode: 'environment' },
                        { fps: 10, qrbox: { width: 240, height: 240 } },
                        (value) => { this.scanned = value; $wire.set(this.statePath, value); this.stop(); },
                        () => {},
                    );
                } catch (error) {
                    this.scanning = false;
                    this.error = 'Camera unavailable. Choose a QR image or enter the tracking ID manually.';
                } finally {
                    this.busy = false;
                }
            },
            async stop() {
                if (! this.scanner || ! this.scanning) return;
                this.scanning = false;
                try { await this.scanner.stop(); } catch (error) {}
            },
            async scanImage(event) {
                const file = event.target.files?.[0];
                if (! file) return;
                this.error = '';
                this.busy = true;
                try {
                    await this.stop();
                    await this.setup();
                    const value = await this.scanner.scanFile(file, true);
                    this.scanned = value;
                    $wire.set(this.statePath, value);
                } catch (error) {
                    this.error = 'Could not read a QR code from that image. Try another image or enter the ID manually.';
                } finally {
                    this.busy = false;
                    event.target.value = '';
                }
            },
            destroy() { this.stop(); },
        }"
        class="space-y-2"
    >
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" x-show="! scanning" x-bind:disabled="busy" x-on:click="start()" class="fi-btn fi-size-sm fi-color-primary">
                Scan courier QR
            </button>
            <button type="button" x-show="scanning" x-on:click="stop()" class="fi-btn fi-size-sm">Stop camera</button>
            <label class="fi-btn fi-size-sm cursor-pointer">
                Scan QR image
                <input type="file" accept="image/*" class="sr-only" x-on:change="scanImage($event)">
            </label>
        </div>
        <div :id="readerId" class="max-w-sm overflow-hidden rounded-lg" x-show="scanning"></div>
        <p x-show="scanned" x-text="`Scanned: ${scanned}`" class="text-xs text-gray-500 break-all"></p>
        <p x-show="error" x-text="error" class="text-xs text-red-600" role="alert"></p>
        <p class="text-xs text-gray-500">The scan fills the fields below. Review them before saving or dispatching.</p>
    </div>
</x-dynamic-component>
