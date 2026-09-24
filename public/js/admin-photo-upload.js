/**
 * Alpine helper for MEA-style photo dropzones on admin profiles.
 * Usage: x-data="adminPhotoDropzone()" or adminPhotoDropzone({ multiple: false, … })
 */
(function () {
    'use strict';

    function formatBytes(bytes) {
        bytes = Number(bytes) || 0;
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1).replace(/\.0$/, '') + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1).replace(/\.0$/, '') + ' MB';
    }

    function isAcceptedImage(file, acceptSvg) {
        if (!file) return false;
        const type = String(file.type || '').toLowerCase();
        if (type.startsWith('image/')) return true;
        if (acceptSvg && (type === 'image/svg+xml' || /\.svg$/i.test(file.name || ''))) return true;
        return false;
    }

    function adminPhotoDropzone(options) {
        const opts = options || {};
        const maxBytes = opts.maxBytes || 5 * 1024 * 1024;
        const multiple = opts.multiple !== false;
        const acceptSvg = !!opts.acceptSvg;
        const idleTitle = opts.title || (multiple ? 'Drop photos here or click to upload' : 'Drop image here or click to upload');
        const idleHint = opts.hint || (multiple
            ? 'JPG, PNG, WebP or GIF · max 5 MB each'
            : 'JPG, PNG, WebP or GIF · max 5 MB');

        return {
            dragging: false,
            previews: [],
            multiple,
            title: idleTitle,
            idleHint,
            hint: idleHint,

            init() {
                this.$nextTick(() => window.lucide?.createIcons?.());
            },

            openPicker() {
                this.$refs.fileInput?.click();
            },

            syncFiles(fileList) {
                const input = this.$refs.fileInput;
                if (!input) return;

                let accepted = [];
                Array.from(fileList || []).forEach((file) => {
                    if (!isAcceptedImage(file, acceptSvg)) return;
                    if (file.size > maxBytes) return;
                    accepted.push(file);
                });

                if (!multiple && accepted.length > 1) {
                    accepted = accepted.slice(0, 1);
                }

                const dt = new DataTransfer();
                accepted.forEach((file) => dt.items.add(file));
                input.files = dt.files;

                this.previews.forEach((p) => {
                    if (p.url) URL.revokeObjectURL(p.url);
                });
                this.previews = accepted.map((file) => ({
                    name: file.name,
                    size: file.size,
                    sizeLabel: formatBytes(file.size),
                    url: URL.createObjectURL(file),
                    file,
                }));

                if (accepted.length === 0) {
                    this.hint = idleHint;
                } else if (!multiple) {
                    this.hint = accepted[0].name + ' · ' + formatBytes(accepted[0].size);
                } else {
                    this.hint = accepted.length + ' photo' + (accepted.length === 1 ? '' : 's') + ' ready to upload';
                }

                this.$nextTick(() => window.lucide?.createIcons?.());
            },

            mergeFiles(list) {
                const incoming = Array.from(list || []);
                if (!multiple) {
                    this.syncFiles(incoming.slice(0, 1));
                    return;
                }
                const current = this.previews.map((p) => p.file);
                const byKey = new Map();
                [...current, ...incoming].forEach((file) => {
                    const key = file.name + ':' + file.size + ':' + file.lastModified;
                    byKey.set(key, file);
                });
                this.syncFiles([...byKey.values()]);
            },

            onPick(event) {
                this.mergeFiles(event.target.files);
            },

            onDrop(event) {
                this.dragging = false;
                this.mergeFiles(event.dataTransfer?.files);
            },

            removeAt(index) {
                const files = this.previews.map((p) => p.file);
                files.splice(index, 1);
                this.syncFiles(files);
            },

            clearAll() {
                this.syncFiles([]);
            },
        };
    }

    document.addEventListener('alpine:init', () => {
        window.Alpine.data('adminPhotoDropzone', adminPhotoDropzone);
    });

    window.adminPhotoDropzone = adminPhotoDropzone;
})();
