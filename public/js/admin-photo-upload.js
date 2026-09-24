/**
 * Alpine helper for MEA-style photo dropzones on admin profiles.
 * Usage: x-data="adminPhotoDropzone()" on the upload <form>.
 */
(function () {
    'use strict';

    function formatBytes(bytes) {
        bytes = Number(bytes) || 0;
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1).replace(/\.0$/, '') + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1).replace(/\.0$/, '') + ' MB';
    }

    function adminPhotoDropzone(options) {
        const opts = options || {};
        const maxBytes = opts.maxBytes || 5 * 1024 * 1024;
        const idleHint = opts.hint || 'JPG, PNG, WebP or GIF · max 5 MB each';

        return {
            dragging: false,
            previews: [],
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

                const accepted = [];
                Array.from(fileList || []).forEach((file) => {
                    if (!file || !String(file.type || '').startsWith('image/')) return;
                    if (file.size > maxBytes) return;
                    accepted.push(file);
                });

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

                this.hint = accepted.length
                    ? accepted.length + ' photo' + (accepted.length === 1 ? '' : 's') + ' ready to upload'
                    : idleHint;

                this.$nextTick(() => window.lucide?.createIcons?.());
            },

            mergeFiles(list) {
                const current = this.previews.map((p) => p.file);
                const incoming = Array.from(list || []);
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
