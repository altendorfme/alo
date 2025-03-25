<?php $this->layout('layout/default', ['title' => _e('client_configuration')]); ?>

<?php $this->start('page_content') ?>
<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?= _e('service_worker_download') ?></h5>
                </div>
                <div class="card-body">
                    <p class="card-text"><?= _e('service_worker_download_instruction') ?></p>
                    <a href="/download/aloSW" class="btn btn-primary" download><?= _e('download_alosw_button') ?></a>
                    <div class="mt-3 alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <?= _e('service_worker_placement_note') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?= _e('client_configuration') ?></h5>
                </div>
                <div class="card-body">
                    <form id="configForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="registrationMode" class="form-label"><?= _e('registration_mode') ?></label>
                                <select class="form-select" id="registrationMode">
                                    <option value="auto"><?= _e('registration_mode_auto') ?></option>
                                    <option value="manual"><?= _e('registration_mode_manual') ?></option>
                                </select>
                                <div class="form-text"><?= _e('registration_mode_description') ?></div>
                            </div>
                            <div class="col-md-6">
                                <label for="registrationDelay" class="form-label"><?= _e('registration_delay') ?></label>
                                <input type="number" class="form-control" id="registrationDelay" value="0" min="0">
                                <div class="form-text"><?= _e('registration_delay_description') ?></div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enableCustomSegments">
                                    <label class="form-check-label" for="enableCustomSegments"><?= _e('enable_custom_segments') ?></label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enableLogging">
                                    <label class="form-check-label" for="enableLogging"><?= _e('enable_logging') ?></label>
                                </div>
                            </div>
                        </div>

                        <div id="customSegmentsSection" class="row mb-3 d-none">
                            <div class="col-md-6">
                                <label for="tagSegment" class="form-label"><?= _e('tag') ?></label>
                                <input type="text" class="form-control" id="tagSegment" value="tag-test-manual">
                            </div>
                            <div class="col-md-6">
                                <label for="categorySegment" class="form-label"><?= _e('category') ?></label>
                                <input type="text" class="form-control" id="categorySegment" value="cat-test-manual">
                            </div>
                        </div>

                        <div id="manualButtonSection" class="row mb-3 d-none">
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong><?= _e('manual_mode_selected') ?></strong> <?= _e('manual_mode_instruction') ?>
                                </div>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <pre class="mb-0"><code>const aloBtn = document.getElementById('subscribeBtn');
aloBtn.addEventListener('click', async () => {
    try {
        await aloClient.subscribe();
        console.log('Push notification subscription successful!');
    } catch (error) {
        console.log('Subscription failed: ' + error.message);
    }
});</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="codeOutput" class="form-label"><?= _e('implementation_code') ?></label>
                                <div class="mb-2">
                                    <button type="button" id="copyCodeBtn" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-copy me-1"></i> <?= _e('copy_code') ?>
                                    </button>
                                </div>
                                <textarea class="form-control" id="codeOutput" rows="7" readonly></textarea>
                                <div class="form-text mt-2">
                                    <?= _e('code_placement_instruction') ?>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->end() ?>

<?php $this->start('page_scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const registrationModeSelect = document.getElementById('registrationMode');
        const registrationDelayInput = document.getElementById('registrationDelay');
        const enableCustomSegmentsCheckbox = document.getElementById('enableCustomSegments');
        const enableLoggingCheckbox = document.getElementById('enableLogging');
        const tagSegmentInput = document.getElementById('tagSegment');
        const categorySegmentInput = document.getElementById('categorySegment');
        const customSegmentsSection = document.getElementById('customSegmentsSection');
        const manualButtonSection = document.getElementById('manualButtonSection');
        const codeOutputTextarea = document.getElementById('codeOutput');
        const copyCodeBtn = document.getElementById('copyCodeBtn');

        enableCustomSegmentsCheckbox.addEventListener('change', function() {
            customSegmentsSection.classList.toggle('d-none', !this.checked);
            updateCode();
        });

        registrationModeSelect.addEventListener('change', function() {
            manualButtonSection.classList.toggle('d-none', this.value !== 'manual');
            updateCode();
        });

        const allInputs = [
            registrationModeSelect,
            registrationDelayInput,
            enableCustomSegmentsCheckbox,
            enableLoggingCheckbox,
            tagSegmentInput,
            categorySegmentInput
        ];

        allInputs.forEach(input => {
            input.addEventListener('change', updateCode);
            if (input.type === 'text' || input.type === 'number') {
                input.addEventListener('input', updateCode);
            }
        });

        copyCodeBtn.addEventListener('click', function() {
            codeOutputTextarea.select();
            document.execCommand('copy');

            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check me-1"></i> ' + '<?= _e('copied') ?>';
            this.classList.replace('btn-outline-secondary', 'btn-success');

            setTimeout(() => {
                this.innerHTML = originalText;
                this.classList.replace('btn-success', 'btn-outline-secondary');
            }, 2000);
        });

        updateCode();

        function updateCode() {
            const registrationMode = registrationModeSelect.value;
            const registrationDelay = parseInt(registrationDelayInput.value) || 0;
            const enableLogging = enableLoggingCheckbox.checked;
            const enableCustomSegments = enableCustomSegmentsCheckbox.checked;

            let code = `<script type="module">
    import aloClient from '<?= $appUrl ?>/clientSDK';
    const aloConfig = {
        registrationMode: '${registrationMode}'`;

            if (registrationDelay !== 0) {
                code += `,
        registrationDelay: ${registrationDelay}`;
            }

            code += `,`;

            if (enableCustomSegments) {
                code += `
        customSegments: {
            tag: '${tagSegmentInput.value}',
            category: '${categorySegmentInput.value}'
        },`;
            }

            if (enableLogging) {
                code += `
        enableLogging: true`;
            }

            code += `
    };
    const aloClientInit = new aloClient(aloConfig);`;

            if (registrationMode === 'manual') {
                code += `
    // Add this button to your HTML: <button id="subscribeBtn">Subscribe to Notifications</button>
    const aloBtn = document.getElementById('subscribeBtn');
    aloBtn.addEventListener('click', async () => {
        try {
            await aloClient.subscribe();
            console.log('Push notification subscription successful!');
        } catch (error) {
            console.log('Subscription failed: ' + error.message);
        }
    });`;
            }

            code += `
<\/script>`;

            codeOutputTextarea.value = code;
        }
    });
</script>
<?php $this->end() ?>