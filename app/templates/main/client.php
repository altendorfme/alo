<?php $this->layout('layout/default', ['title' =>  _e('client_configuration')]); ?>

<?php $this->start('page_content') ?>
<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Service Worker Download</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Download the PushBase Service Worker file and save it to the root of your website:</p>
                    <a href="/download/pushBaseSW" class="btn btn-primary" download>Download pushBaseSW.js</a>
                    <div class="mt-3 alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        The service worker file must be placed in the root directory of your website to function properly.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Client Configuration</h5>
                </div>
                <div class="card-body">
                    <form id="configForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="registrationMode" class="form-label">Registration Mode</label>
                                <select class="form-select" id="registrationMode">
                                    <option value="auto">Auto</option>
                                    <option value="manual">Manual</option>
                                </select>
                                <div class="form-text">Choose how the push notification registration should be triggered.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="registrationDelay" class="form-label">Registration Delay (ms)</label>
                                <input type="number" class="form-control" id="registrationDelay" value="0" min="0">
                                <div class="form-text">Delay in milliseconds before auto-registration is triggered (0 for immediate).</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enableCustomSegments">
                                    <label class="form-check-label" for="enableCustomSegments">Enable Custom Segments</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enableLogging">
                                    <label class="form-check-label" for="enableLogging">Enable Logging</label>
                                </div>
                            </div>
                        </div>

                        <div id="customSegmentsSection" class="row mb-3 d-none">
                            <div class="col-md-6">
                                <label for="tagSegment" class="form-label">Tag</label>
                                <input type="text" class="form-control" id="tagSegment" value="tag-test-manual">
                            </div>
                            <div class="col-md-6">
                                <label for="categorySegment" class="form-label">Category</label>
                                <input type="text" class="form-control" id="categorySegment" value="cat-test-manual">
                            </div>
                        </div>

                        <div id="manualButtonSection" class="row mb-3 d-none">
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Manual Mode Selected:</strong> You will need to add a button to your website to trigger the subscription.
                                </div>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <pre class="mb-0"><code>const pushbaseBtn = document.getElementById('subscribeBtn');
pushbaseBtn.addEventListener('click', async () => {
    try {
        await pushBaseClient.subscribe();
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
                                <label for="codeOutput" class="form-label">Implementation Code</label>
                                <div class="mb-2">
                                    <button type="button" id="copyCodeBtn" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-copy me-1"></i> Copy Code
                                    </button>
                                </div>
                                <textarea class="form-control" id="codeOutput" rows="12" readonly></textarea>
                                <div class="form-text mt-2">
                                    Add this code before the closing <code>&lt;/body&gt;</code> tag of your website.
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

        // Show/hide custom segments section based on checkbox
        enableCustomSegmentsCheckbox.addEventListener('change', function() {
            customSegmentsSection.classList.toggle('d-none', !this.checked);
            updateCode();
        });

        // Show/hide manual button section based on registration mode
        registrationModeSelect.addEventListener('change', function() {
            manualButtonSection.classList.toggle('d-none', this.value !== 'manual');
            updateCode();
        });

        // Update code when any input changes
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

        // Copy code button
        copyCodeBtn.addEventListener('click', function() {
            codeOutputTextarea.select();
            document.execCommand('copy');
            
            // Visual feedback
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check me-1"></i> Copied!';
            this.classList.replace('btn-outline-secondary', 'btn-success');
            
            setTimeout(() => {
                this.innerHTML = originalText;
                this.classList.replace('btn-success', 'btn-outline-secondary');
            }, 2000);
        });

        // Initial code update
        updateCode();

        // Function to generate the code
        function updateCode() {
            const registrationMode = registrationModeSelect.value;
            const registrationDelay = parseInt(registrationDelayInput.value) || 0;
            const enableLogging = enableLoggingCheckbox.checked;
            const enableCustomSegments = enableCustomSegmentsCheckbox.checked;
            
            let code = `<script type="module">
    import PushBaseClient from 'https://push.pushbase.localhost/clientSDK';

    const pushBaseConfig = {
        registrationMode: '${registrationMode}'`;
            
            // Only include registrationDelay if it's not 0
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

            // Only include enableLogging if it's true
            if (enableLogging) {
                code += `
        enableLogging: true`;
            }
            
            code += `
    };
    const pushBaseClient = new PushBaseClient(pushBaseConfig);
`;

            if (registrationMode === 'manual') {
                code += `
    // Add this button to your HTML: <button id="subscribeBtn">Subscribe to Notifications</button>
    const pushbaseBtn = document.getElementById('subscribeBtn');
    pushbaseBtn.addEventListener('click', async () => {
        try {
            await pushBaseClient.subscribe();
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