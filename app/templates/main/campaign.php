<?php $this->layout('layout/default', ['title' => $isEdit ? _e('campaign_edit') : _e('campaign_publish')]); ?>

<?php $this->start('page_content') ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div class="title d-flex">
        <i class="bi bi-<?= $isEdit ? 'pencil-square' : 'plus-circle'; ?> me-2 fs-5"></i>
        <h1 class="h4"><?= $isEdit ? _e('campaign_edit') : _e('campaign_publish'); ?></h1>
    </div>
    <div class="btn-toolbar">
        <button type="submit" form="campaignForm" name="action" value="save" class="btn btn-primary me-2">
            <?= _e('campaign_publish'); ?>
        </button>
        <button type="submit" form="campaignForm" name="action" value="draft" class="btn btn-secondary">
            <?= _e('save_as_draft') ?>
        </button>
    </div>
</div>

<form method="POST" action="<?= $isEdit ? '/campaign/edit/' . $campaign['id'] : '/campaign' ?>" class="needs-validation" id="campaignForm" novalidate>
    <div class="row">
        <div class="col-md-6">

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?= _e('import_metadata') ?> (<?= _e('optional') ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 col-md-8">
                            <input type="url" class="form-control" id="importUrl" placeholder="Enter URL to import metadata">
                        </div>
                        <div class="col-12 col-md-4 mt-2 mt-md-0">
                            <button type="button" class="btn btn-primary w-100" id="importButton">
                                <span class="d-flex align-items-center justify-content-center">
                                    <span class="import-text"><?= _e('import_metadata') ?></span>
                                    <span class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?= _e('campaign') ?> (<?= _e('required') ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold"><i class="bi bi-pencil-square me-2"></i><?= _e('campaign_name') ?></label>
                        <input type="text" class="form-control" id="name" name="name" required
                            value="<?= htmlspecialchars($campaign['name'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="push_title" class="form-label fw-bold"><i class="bi bi-card-heading me-2"></i><?= _e('push_title') ?></label>
                        <input type="text" class="form-control" id="push_title" name="push_title" required
                            value="<?= htmlspecialchars($campaign['push_title'] ?? ''); ?>">
                        <div class="d-flex justify-content-between mt-1">
                            <small class="form-text mt-0 text-muted"><?= _e('push_title_description') ?></small>
                            <small class="char-counter" id="push_title_counter">0/65</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="push_body" class="form-label fw-bold"><i class="bi bi-body-text me-2"></i><?= _e('push_body') ?></label>
                        <textarea class="form-control" id="push_body" name="push_body" rows="3" required><?php
                                                                                                            echo htmlspecialchars($campaign['push_body'] ?? '');
                                                                                                            ?></textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="form-text mt-0 text-muted"><?= _e('push_body_description') ?></small>
                            <small class="char-counter" id="push_body_counter">0/180</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="push_url" class="form-label fw-bold"><i class="bi bi-link-45deg me-2"></i><?= _e('push_url') ?></label>
                        <input type="url" class="form-control" id="push_url" name="push_url"
                            value="<?= htmlspecialchars($campaign['push_url'] ?? ''); ?>">
                        <small class="form-text text-muted"><?= _e('push_url_description') ?></small>
                    </div>

                    <div>
                        <label for="push_image" class="form-label fw-bold"><i class="bi bi-image me-2"></i><?= _e('push_image_url') ?></label>
                        <div class="d-flex">
                            <input type="url" class="form-control" id="push_image" name="push_image"
                                value="<?= htmlspecialchars($campaign['push_image'] ?? ''); ?>">
                            <div id="push_image_preview" class="d-flex justify-content-center bg-light align-items-center rounded overflow-hidden ms-2 text-white" style="min-width: 38px; height: 38px;"></div>
                        </div>
                        <small class="form-text text-muted"><?= _e('push_image_url_description') ?></small>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?= _e('campaign_will_be_sent_to') ?></h5>
                </div>
                <div class="card-body text-center">
                    <span id="subscriberBySegmentCount" class="fs-1 text-muted">0</span>
                    <span><?= _e('subscribers') ?></span>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?= _e('segments') ?> (<?= _e('optional') ?>)</h5>
                </div>
                <div class="card-body">
                    <div id="segmentsContainer">

                        <?php
                        $segments = null;
                        if (isset($campaign['segments'])) {
                            if (is_array($campaign['segments'])) {
                                $segments = $campaign['segments'];
                            } else {
                                $segments = json_decode($campaign['segments'], true);
                            }
                        }

                        if (isset($segments) && is_array($segments)) {
                            foreach ($segments as $key => $segment) {
                                $values = null;
                                foreach ($listSegments as $listSegment) {
                                    if ($segment['type'] == $listSegment['id']) {
                                        $values = $listSegment['values'];
                                    }
                                }
                        ?>
                                <div class="row g-2 mb-3 segment-row">
                                    <div class="col-4">
                                        <select class="form-select segment-select" name="segments[<?= $key ?>][type]">
                                            <option value=""><?= _e('select_segment') ?></option>
                                            <?php foreach ($listSegments as $listSegment) { ?>
                                                <option <?= $segment['type'] == $listSegment['id'] ? 'selected' : '' ?> value="<?= $listSegment['id']; ?>"><?= $listSegment['description'] ?? $listSegment['name']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col">
                                        <select class="form-select segment-values" name="segments[<?= $key ?>][values][]" multiple="" size="3">
                                            <?php foreach ($values as $value) {
                                                $selected = '';

                                                foreach ($segment['values'] as $selectedValue) {
                                                    if ($selectedValue == $value) {
                                                        $selected = 'selected';
                                                    }
                                                }
                                            ?>
                                                <option <?= $selected ?> value="<?= $value; ?>"><?= $value; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-danger remove-segment">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                        <?php }
                        } ?>
                    </div>
                    <button type="button" id="addSegmentBtn" class="btn btn-outline-primary">
                        <i class="bi bi-plus-circle me-2"></i><?= _e('segment_add_button') ?>
                    </button>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?= _e('schedule') ?> (<?= _e('optional') ?>)</h5>
                </div>
                <div class="card-body">
                    <div>
                        <label for="send_at" class="form-label fw-bold"><i class="bi bi-calendar-event me-2"></i><?= _e('send_at') ?></label>
                        <input type="datetime-local" class="form-control" id="send_at" name="send_at"
                            value="<?= isset($campaign['send_at']) ? date('Y-m-d\TH:i', strtotime($campaign['send_at'])) : ''; ?>">
                        <small class="form-text text-muted"><?= _e('schedule_description') ?></small>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?= _e('settings') ?> (<?= _e('optional') ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="push_icon" class="form-label fw-bold"><i class="bi bi-image-fill me-2"></i><?= _e('push_icon_url') ?> (<?= _e('optional') ?>)</label>
                        <div class="d-flex">
                            <input type="url" class="form-control" id="push_icon" name="push_icon"
                                value="<?= $isEdit ? htmlspecialchars($campaign['push_icon'] ?? '') : htmlspecialchars($client['icon']); ?>">
                            <div id="push_icon_preview" class="d-flex justify-content-center bg-light align-items-center rounded overflow-hidden ms-2 text-white" style="min-width: 38px; height: 38px;"></div>
                        </div>
                        <small class="form-text text-muted"><?= _e('push_icon_url_description') ?></small>
                    </div>

                    <div class="mb-3">
                        <label for="push_badge" class="form-label fw-bold"><i class="bi bi-patch-check-fill me-2"></i><?= _e('push_badge_url') ?> (<?= _e('optional') ?>)</label>
                        <div class="d-flex">
                            <input type="url" class="form-control" id="push_badge" name="push_badge"
                                value="<?= $isEdit ? htmlspecialchars($campaign['push_badge'] ?? '') : htmlspecialchars($client['badge']); ?>">
                            <div id="push_badge_preview" class="d-flex justify-content-center bg-light align-items-center rounded overflow-hidden ms-2 text-white" style="min-width: 38px; height: 38px;"></div>
                        </div>
                        <small class="form-text text-muted"><?= _e('push_badge_url_description') ?></small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="push_requireInteraction"
                                name="push_requireInteraction" value="1"
                                <?= ($campaign['push_requireInteraction'] ?? false) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-bold" for="push_requireInteraction">
                                <i class="bi bi-hand-index me-2"></i><?= _e('require_interaction') ?>
                            </label>
                            <small class="form-text text-muted d-block"><?= _e('require_interaction_description') ?></small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="push_renotify"
                                name="push_renotify" value="1"
                                <?= ($campaign['push_renotify'] ?? false) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-bold" for="push_renotify">
                                <i class="bi bi-bell me-2"></i><?= _e('renotify') ?>
                            </label>
                            <small class="form-text text-muted d-block"><?= _e('renotify_description') ?></small>
                        </div>
                    </div>

                    <div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="push_silent"
                                name="push_silent" value="1"
                                <?= ($campaign['push_silent'] ?? false) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-bold" for="push_silent">
                                <i class="bi bi-volume-mute me-2"></i><?= _e('silent') ?>
                            </label>
                            <small class="form-text text-muted d-block"><?= _e('silent_description') ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php $this->end() ?>

<?php $this->start('page_scripts') ?>
<script id="listSegments" type="application/json">
    <?= json_encode($listSegments); ?>
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Publish confirmation
        const campaignForm = document.getElementById('campaignForm');
        const publishButton = document.querySelector('button[name="action"][value="save"]');

        if (publishButton && campaignForm) {
            publishButton.addEventListener('click', function(e) {
                e.preventDefault();

                const sendAtInput = document.getElementById('send_at');
                const isScheduled = sendAtInput && sendAtInput.value.trim() !== '';

                let confirmMessage = '<?= _e('confirm_campaign_publish') ?>';
                if (isScheduled) {
                    const scheduledDate = new Date(sendAtInput.value);
                    const formattedDate = scheduledDate.toLocaleDateString();
                    const formattedTime = scheduledDate.toLocaleTimeString();

                    let scheduledMessage = '<?= _e('confirm_campaign_scheduled') ?>';
                    confirmMessage = scheduledMessage
                        .replace('{date}', formattedDate)
                        .replace('{time}', formattedTime) + '\n' + confirmMessage;
                }

                if (confirm(confirmMessage)) {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'action';
                    hiddenInput.value = 'save';
                    campaignForm.appendChild(hiddenInput);
                    campaignForm.submit();
                }
            });
        }

        // User and segments counter
        const segmentsContainer = document.getElementById('segmentsContainer');
        const addSegmentBtn = document.getElementById('addSegmentBtn');
        const subscriberBySegmentCount = document.getElementById('subscriberBySegmentCount');
        const existingSegments = document.querySelectorAll('.segment-row');
        let segmentIndex = existingSegments.length > 0 ? existingSegments.length : 0;

        const listSegments = JSON.parse(document.getElementById('listSegments').textContent);
        const segmentsArray = Object.values(listSegments);

        function reindexSegments() {
            const segmentRows = document.querySelectorAll('.segment-row');
            segmentRows.forEach((row, index) => {
                const segmentSelect = row.querySelector('.segment-select');
                const segmentValues = row.querySelector('.segment-values');

                segmentSelect.name = `segments[${index}][type]`;
                segmentValues.name = `segments[${index}][values][]`;
            });

            segmentIndex = segmentRows.length;
        }

        function findSegment(array, predicate) {
            for (let i = 0; i < array.length; i++) {
                if (predicate(array[i])) {
                    return array[i];
                }
            }
            return null;
        }

        function fetchSegmentValues(segmentSelect) {
            const segmentId = segmentSelect.value;
            const valuesSelect = segmentSelect.closest('.segment-row').querySelector('.segment-values');

            if (!segmentId) {
                valuesSelect.innerHTML = '';
                valuesSelect.disabled = true;
                updateUserCount();
                return;
            }

            fetch(`/api/segments/values/${segmentId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('<?= _e('error_network_response') ?>');
                    }
                    return response.json();
                })
                .then(data => {
                    const values = Array.isArray(data) ? data : (data.values || []);

                    valuesSelect.innerHTML = '';
                    valuesSelect.disabled = false;

                    values.forEach(value => {
                        const option = document.createElement('option');
                        option.value = value;
                        option.textContent = value;
                        valuesSelect.appendChild(option);
                    });

                    updateUserCount();
                })
                .catch(error => {
                    valuesSelect.innerHTML = '';
                    valuesSelect.disabled = true;
                    updateUserCount();
                });
        }

        function updateSelectedSegmentTypes() {
            const segmentRows = document.querySelectorAll('.segment-row');
            const selectedTypes = new Set();

            segmentRows.forEach(row => {
                const segmentSelect = row.querySelector('.segment-select');
                if (segmentSelect.value) {
                    selectedTypes.add(segmentSelect.value);
                }
            });

            segmentRows.forEach(row => {
                const segmentSelect = row.querySelector('.segment-select');
                const currentValue = segmentSelect.value;

                Array.from(segmentSelect.options).forEach(option => {
                    if (option.value && option.value !== currentValue) {
                        option.disabled = selectedTypes.has(option.value);
                    }
                });
            });

            return selectedTypes;
        }

        function updateUserCount() {
            const segmentRows = document.querySelectorAll('.segment-row');
            const segmentData = [];

            segmentRows.forEach(row => {
                const segmentSelect = row.querySelector('.segment-select');
                const valuesSelect = row.querySelector('.segment-values');

                if (segmentSelect.value) {
                    const selectedSegment = findSegment(segmentsArray, s => String(s.id) === String(segmentSelect.value));
                    const selectedValues = Array.from(valuesSelect.selectedOptions)
                        .map(option => option.value);

                    if (selectedValues.length > 0) {
                        segmentData.push({
                            segmentId: segmentSelect.value,
                            segmentName: selectedSegment.original_name,
                            values: selectedValues
                        });
                    }
                }
            });

            updateSelectedSegmentTypes();

            fetch('/api/segments', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(segmentData)
                })
                .then(response => {
                    return response.json();
                })
                .then(responseData => {
                    let count = 0;
                    if (responseData) {
                        if (typeof responseData.count !== 'undefined') {
                            count = responseData.count;
                        } else if (responseData.data && typeof responseData.data.count !== 'undefined') {
                            count = responseData.data.count;
                        } else {
                            throw new Error('<?= _e('error_network_response_structure') ?>');
                        }
                    }

                    subscriberBySegmentCount.textContent = new Intl.NumberFormat().format(count);
                })
                .catch(error => {
                    subscriberBySegmentCount.textContent = 0;
                });
        }

        addSegmentBtn.addEventListener('click', function() {
            const selectedTypes = updateSelectedSegmentTypes();

            const segmentOptionsHTML = segmentsArray.map(segment =>
                `<option value="${segment.id}" ${selectedTypes.has(segment.id.toString()) ? 'disabled' : ''}>${segment.description || segment.name}</option>`
            ).join('');

            const newRow = document.createElement('div');
            newRow.classList.add('row', 'g-2', 'mb-3', 'segment-row');
            newRow.innerHTML = `
            <div class="col-4">
                <select class="form-select segment-select" name="segments[${segmentIndex}][type]">
                    <option value=""><?= _e('select_segment') ?></option>
                    ${segmentOptionsHTML}
                </select>
            </div>
            <div class="col">
                <select class="form-select segment-values" name="segments[${segmentIndex}][values][]" multiple size="3" disabled></select>
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-danger remove-segment">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;

            const segmentSelect = newRow.querySelector('.segment-select');
            const valuesSelect = newRow.querySelector('.segment-values');
            const removeBtn = newRow.querySelector('.remove-segment');

            segmentSelect.addEventListener('change', function() {
                fetchSegmentValues(this);
                updateSelectedSegmentTypes();
            });

            valuesSelect.addEventListener('change', updateUserCount);

            removeBtn.addEventListener('click', function() {
                newRow.remove();
                reindexSegments();
                updateUserCount();
            });

            segmentsContainer.appendChild(newRow);
            segmentIndex++;
        });

        document.querySelectorAll('.segment-select').forEach(select => {
            select.addEventListener('change', function() {
                fetchSegmentValues(this);
                updateSelectedSegmentTypes();
            });
        });

        document.querySelectorAll('.segment-values').forEach(select => {
            select.addEventListener('change', updateUserCount);
        });

        document.querySelectorAll('.remove-segment').forEach(removeBtn => {
            removeBtn.addEventListener('click', function() {
                this.closest('.segment-row').remove();
                reindexSegments();
                updateSelectedSegmentTypes();
                updateUserCount();
            });
        });
        updateUserCount();

        // Image Preview
        function updateImagePreview(preview, url) {
            preview.innerHTML = '';

            if (url && url.startsWith('https://')) {
                const img = document.createElement('img');
                img.src = url;
                img.style.width = 'auto';
                img.style.height = '38px';
                img.classList.add('img-fluid');

                img.onerror = function() {
                    preview.innerHTML = '<div class="d-flex justify-content-center bg-danger align-items-center h-100 w-100"><i class="bi bi-x-square"></i></div>';
                };

                preview.appendChild(img);
            }
        }

        function imagePreview(inputId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(inputId + '_preview');

            const initialUrl = input.value.trim();
            updateImagePreview(preview, initialUrl);

            input.addEventListener('input', function() {
                const url = this.value.trim();
                updateImagePreview(preview, url);
            });
        }
        imagePreview('push_image');
        imagePreview('push_icon');
        imagePreview('push_badge');

        // Char Counter
        function updateCharCounter(inputId, maxChars) {
            const input = document.getElementById(inputId);
            const counter = document.getElementById(inputId + '_counter');
            const currentLength = input.value.length;

            counter.textContent = `${currentLength}/${maxChars}`;
            if (currentLength > maxChars) {
                counter.classList.add('text-danger');
                counter.classList.add('fw-bold');
                counter.classList.remove('text-muted');
            } else {
                counter.classList.remove('text-danger');
                counter.classList.remove('fw-bold');
                counter.classList.add('text-muted');
            }
        }
        updateCharCounter('push_title', 65);
        updateCharCounter('push_body', 180);

        document.getElementById('push_title').addEventListener('input', function() {
            updateCharCounter('push_title', 65);
        });

        document.getElementById('push_body').addEventListener('input', function() {
            updateCharCounter('push_body', 180);
        });

        // Import Metadata
        document.getElementById('importButton').addEventListener('click', async function(e) {
            const urlInput = document.getElementById('importUrl');
            const url = urlInput.value.trim();

            if (!url) {
                alert('<?= _e('validate_url') ?>');
                return;
            }

            try {
                new ldloader({
                    root: ".ldld.full"
                }).on();
                const response = await fetch('/api/campaign/import/metadata', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        url
                    })
                });

                if (!response.ok) {
                    throw new Error('<?= _e('error_network_response') ?>');
                }

                const data = await response.json();
                if (data.title) {
                    document.getElementById('push_title').value = data.title;
                }
                if (data.description) {
                    document.getElementById('push_body').value = data.description;
                }
                if (data.image) {
                    document.getElementById('push_image').value = data.image;
                }
                if (data.icon) {
                    document.getElementById('push_icon').value = data.icon;
                }
                document.getElementById('push_url').value = url;

            } catch (error) {
                alert('<?= _e('error_network_response') ?>');
            } finally {
                new ldloader({
                    root: ".ldld.full"
                }).off();
                updateCharCounter('push_title', 65);
                updateCharCounter('push_body', 180);
                imagePreview('push_image');
                imagePreview('push_icon');
                imagePreview('push_badge');
            }
        });
    });
</script>
<?php $this->end() ?>