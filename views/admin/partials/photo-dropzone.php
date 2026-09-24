<?php
/**
 * Shared MEA-style photo dropzone upload form.
 *
 * @var string $uploadAction   Form POST action URL
 * @var string $inputId        Unique file input id
 * @var string $captionId      Unique caption input id
 * @var string $captionPlaceholder
 * @var bool   $solo           True when there are no existing photos (no top border)
 */
$uploadAction = (string) ($uploadAction ?? '');
$inputId = (string) ($inputId ?? 'admin-photos');
$captionId = (string) ($captionId ?? 'admin-photo-caption');
$captionPlaceholder = (string) ($captionPlaceholder ?? 'e.g. Front view, serial plate…');
$solo = !empty($solo);
?>
<form method="post"
      action="<?= htmlspecialchars($uploadAction) ?>"
      enctype="multipart/form-data"
      class="admin-dropzone-form<?= $solo ? ' admin-dropzone-form--solo' : '' ?>"
      x-data="adminPhotoDropzone"
      @submit="if (!previews.length) { $event.preventDefault(); openPicker(); }">
    <div class="admin-dropzone"
         :class="{ 'is-drag': dragging, 'has-files': previews.length > 0 }"
         @dragenter.prevent="dragging = true"
         @dragover.prevent="dragging = true"
         @dragleave.prevent="dragging = false"
         @drop.prevent="onDrop($event)"
         @click="if (!previews.length) openPicker()">
        <input type="file"
               id="<?= htmlspecialchars($inputId) ?>"
               name="images[]"
               class="sr-only"
               accept="image/jpeg,image/png,image/webp,image/gif"
               multiple
               x-ref="fileInput"
               @change="onPick($event)"
               @click.stop>

        <template x-if="previews.length === 0">
            <div class="admin-dropzone__idle">
                <span class="admin-dropzone__icon" aria-hidden="true">
                    <i data-lucide="cloud-upload"></i>
                </span>
                <strong class="admin-dropzone__title">Drop photos here or click to upload</strong>
                <span class="admin-dropzone__hint" x-text="hint"></span>
            </div>
        </template>

        <div class="admin-dropzone__previews" x-show="previews.length > 0" x-cloak @click.stop>
            <template x-for="(item, index) in previews" :key="item.name + '-' + index">
                <div class="admin-dropzone__preview">
                    <img :src="item.url" :alt="item.name">
                    <span class="admin-dropzone__preview-name" x-text="item.name"></span>
                    <button type="button"
                            class="admin-dropzone__remove"
                            @click="removeAt(index)"
                            :aria-label="'Remove ' + item.name">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </template>
            <button type="button" class="admin-dropzone__add" @click="openPicker()">
                <i data-lucide="plus"></i>
                Add more
            </button>
        </div>
    </div>

    <div class="admin-dropzone__meta">
        <div class="finance-field">
            <label class="finance-label" for="<?= htmlspecialchars($captionId) ?>">Caption (optional)</label>
            <input type="text"
                   id="<?= htmlspecialchars($captionId) ?>"
                   name="caption"
                   class="finance-input"
                   placeholder="<?= htmlspecialchars($captionPlaceholder) ?>">
        </div>
        <div class="admin-dropzone__actions">
            <button type="button"
                    class="finance-btn-secondary"
                    x-show="previews.length > 0"
                    x-cloak
                    @click="clearAll()">
                Clear
            </button>
            <button type="submit" class="finance-btn-primary">
                <i data-lucide="upload" class="w-4 h-4"></i>
                <span x-text="previews.length ? ('Upload ' + previews.length + ' photo' + (previews.length === 1 ? '' : 's')) : 'Upload photos'"></span>
            </button>
        </div>
    </div>
</form>
