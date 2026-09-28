@props([
    'name',
    'id' => null,
    'existing' => null,
    'accept' => 'image/jpeg,image/png,image/jpg,image/webp,image/gif,image/svg+xml,image/x-icon,.ico,.svg',
    'required' => false,
    'previewClass' => 'h-28 max-w-full rounded-xl object-contain border border-slate-200 bg-white p-1',
    'inputClass' => 'mt-1 w-full rounded-xl border-slate-200 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700',
    'compress' => true,
    'maxWidth' => 1920,
    'maxBytes' => 450000,
])

@php
    $inputId = $id ?: preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
@endphp

<div
    x-data="{
        preview: @js($existing),
        blob: null,
        compressing: false,
        status: '',
        maxWidth: {{ (int) $maxWidth }},
        maxBytes: {{ (int) $maxBytes }},
        compressEnabled: @js((bool) $compress),
        async onPick(event) {
            const input = event.target;
            let file = input.files && input.files[0];
            if (this.blob) {
                URL.revokeObjectURL(this.blob);
                this.blob = null;
            }
            this.status = '';
            if (!file) {
                this.preview = @js($existing);
                return;
            }

            if (
                this.compressEnabled
                && file.size > this.maxBytes
                && /^image\/(jpeg|jpg|png|webp)$/i.test(file.type)
            ) {
                this.compressing = true;
                this.status = 'Optimizing image for faster upload…';
                try {
                    file = await this.compressImage(file);
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    input.files = dt.files;
                    this.status = 'Optimized to ~' + Math.max(1, Math.round(file.size / 1024)) + ' KB';
                } catch (e) {
                    this.status = 'Could not optimize — uploading original';
                } finally {
                    this.compressing = false;
                }
            }

            this.blob = URL.createObjectURL(file);
            this.preview = this.blob;
        },
        compressImage(file) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                const url = URL.createObjectURL(file);
                img.onload = () => {
                    try {
                        let w = img.naturalWidth || img.width;
                        let h = img.naturalHeight || img.height;
                        if (!w || !h) {
                            URL.revokeObjectURL(url);
                            reject(new Error('bad size'));
                            return;
                        }
                        if (w > this.maxWidth) {
                            h = Math.round(h * (this.maxWidth / w));
                            w = this.maxWidth;
                        }
                        const canvas = document.createElement('canvas');
                        canvas.width = w;
                        canvas.height = h;
                        const ctx = canvas.getContext('2d');
                        ctx.fillStyle = '#fff';
                        ctx.fillRect(0, 0, w, h);
                        ctx.drawImage(img, 0, 0, w, h);
                        canvas.toBlob((blob) => {
                            URL.revokeObjectURL(url);
                            if (!blob) {
                                reject(new Error('blob failed'));
                                return;
                            }
                            const name = (file.name || 'poster').replace(/\.[^.]+$/, '') + '.jpg';
                            resolve(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
                        }, 'image/jpeg', 0.82);
                    } catch (err) {
                        URL.revokeObjectURL(url);
                        reject(err);
                    }
                };
                img.onerror = () => {
                    URL.revokeObjectURL(url);
                    reject(new Error('load failed'));
                };
                img.src = url;
            });
        }
    }"
>
    <input
        type="file"
        id="{{ $inputId }}"
        name="{{ $name }}"
        accept="{{ $accept }}"
        class="{{ $inputClass }}"
        @change="onPick($event)"
        @if($required) required @endif
        :disabled="compressing"
    >

    <p x-show="status" x-text="status" class="mt-1 text-[11px] font-medium text-indigo-600" x-cloak></p>

    <div x-show="preview" x-cloak class="mt-2 space-y-1">
        <img :src="preview" alt="Selected image preview" class="{{ $previewClass }}">
        <p class="text-[11px] font-medium text-emerald-700">Preview — click Save to keep this picture.</p>
    </div>
</div>
