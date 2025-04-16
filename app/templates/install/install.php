<?php $this->layout('layout/install', ['title' => _e('installation'), 'js' => 'install']); ?>

<?php $this->start('page_content') ?>

<main class="container">
    <div class="text-center mb-4">
        <i class="install-logo bi bi-megaphone-fill text-primary d-inline-block"></i>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php if (!empty($error)){ ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php } ?>

            <form method="POST" action="/install" id="installForm">
                <div class="card mb-4">
                    <div class="card-header fw-bold"><?= _e('configuration') ?></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="app_url" class="form-label"><?= _e('url') ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-link"></i></span>
                                <input type="url" class="form-control" id="app_url" name="app_url"
                                    value="<?= htmlspecialchars($formData['app_url'] ?? '') ?>"
                                    pattern="^https://.*"
                                    title="<?= _e('https_fill_all') ?>"
                                    readonly required>
                            </div>
                            <div id="app_url_error" class="text-danger"></div>
                        </div>
                        <div class="mb-3">
                            <label for="app_language" class="form-label"><?= _e('language') ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                <select id="app_language" name="app_language" class="form-select" required>
                                    <option value="en" <?= isset($formData['app_language']) && $formData['app_language'] === 'en' ? 'selected' : '' ?>>English</option>
                                    <option value="pt-BR" <?= isset($formData['app_language']) && $formData['app_language'] === 'pt-BR' ? 'selected' : '' ?>>Português (Brasil)</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="client_url" class="form-label"><?= _e('client_url') ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-globe"></i></span>
                                <input type="url" class="form-control" id="client_url" name="client_url"
                                    value="<?= htmlspecialchars($formData['client_url'] ?? '') ?>"
                                    pattern="^https://.*"
                                    title="<?= _e('https_fill_all') ?>"
                                    required>
                            </div>
                            <div id="client_url_error" class="text-danger"></div>
                        </div>
                        <div class="mb-3">
                            <label for="client_icon_url" class="form-label mb-0"><?= _e('push_icon_url') ?></label>
                            <small class="form-text text-muted d-block mt-0 mb-2">
                                <?= _e('image_client_icon_instruction') ?>
                            </small>
                            <div class="d-flex">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-image-fill"></i></span>
                                    <input type="url" class="form-control" id="client_icon_url" name="client_icon_url"
                                        value="<?= htmlspecialchars($formData['client_icon_url'] ?? '') ?>"
                                        pattern="^https://.*"
                                        title="<?= _e('https_fill_all') ?>"
                                        required>
                                </div>
                                <div id="client_icon_url_preview" class="d-flex justify-content-center bg-light align-items-center rounded overflow-hidden ms-2 text-white" style="min-width: 38px; height: 38px;">></div>
                            </div>
                        </div>
                        <div>
                            <label for="client_badge_url" class="form-label mb-0"><?= _e('push_badge_url') ?></label>
                            <small class="form-text text-muted d-block mt-0 mb-2">
                                <?= _e('image_client_badge_instruction') ?>
                            </small>
                            <div class="d-flex">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-patch-check"></i></span>
                                    <input type="url" class="form-control" id="client_badge_url" name="client_badge_url"
                                        value="<?= htmlspecialchars($formData['client_badge_url'] ?? '') ?>"
                                        pattern="^https://.*"
                                        title="<?= _e('https_fill_all') ?>"
                                        required>
                                </div>
                                <div id="client_badge_url_preview" class="d-flex justify-content-center bg-light align-items-center rounded overflow-hidden ms-2 text-white" style="min-width: 38px; height: 38px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header fw-bold"><?= _e('database') ?></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="db_host" class="form-label"><?= _e('host') ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-server"></i></span>
                                        <input type="text" class="form-control" id="db_host" name="db_host"
                                            value="<?= htmlspecialchars($formData['db_host'] ?? '') ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="db_encoding" class="form-label"><?= _e('encoding') ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-type"></i></span>
                                        <input type="text" class="form-control" id="db_encoding" name="db_encoding"
                                            value="<?= htmlspecialchars($formData['db_encoding'] ?? '') ?>" readonly required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="db_user" class="form-label"><?= _e('user') ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                        <input type="text" class="form-control" id="db_user" name="db_user"
                                            value="<?= htmlspecialchars($formData['db_user'] ?? '') ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="db_pass" class="form-label"><?= _e('password') ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                        <input type="password" class="form-control" id="db_pass" name="db_pass"
                                            value="<?= htmlspecialchars($formData['db_pass'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="db_name" class="form-label"><?= _e('name') ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-journal"></i></span>
                                        <input type="text" class="form-control" id="db_name" name="db_name"
                                            value="<?= htmlspecialchars($formData['db_name'] ?? '') ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div id="mysqlConnectionResult"></div>
                                <button type="button" id="testMySQLConnection" class="btn btn-secondary">
                                    <i class="bi bi-plug"></i> <?= _e('test_mysql_connection') ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header fw-bold"><?= _e('amqp') ?></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-8">
                                <div class="mb-3">
                                    <label for="amqp_host" class="form-label"><?= _e('host') ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-hdd-network"></i></span>
                                        <input type="text" class="form-control" id="amqp_host" required name="amqp_host"
                                            value="<?= htmlspecialchars($formData['amqp_host'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="mb-3">
                                    <label for="amqp_port" class="form-label"><?= _e('port') ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-door-open"></i></span>
                                        <input type="number" class="form-control" id="amqp_port" name="amqp_port"
                                            value="<?= htmlspecialchars($formData['amqp_port'] ?? '') ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="amqp_user" class="form-label"><?= _e('user') ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person-lines-fill"></i></span>
                                        <input type="text" class="form-control" id="amqp_user" required name="amqp_user"
                                            value="<?= htmlspecialchars($formData['amqp_user'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="amqp_pass" class="form-label"><?= _e('password') ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                        <input type="password" class="form-control" id="amqp_pass" required name="amqp_pass"
                                            value="<?= htmlspecialchars($formData['amqp_pass'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="amqp_vhost" class="form-label"><?= _e('virtual_host') ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-diagram-3"></i></span>
                                        <input type="text" class="form-control" id="amqp_vhost" name="amqp_vhost"
                                            value="<?= htmlspecialchars($formData['amqp_vhost'] ?? '') ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div id="amqpConnectionResult"></div>
                                <button type="button" id="testAMQPConnection" class="btn btn-secondary">
                                    <i class="bi bi-plug"></i> <?= _e('test_amqp_connection') ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header fw-bold">Email (SMTP)</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-8">
                                <div class="mb-3">
                                    <label for="smtp_host" class="form-label"><?= _e('host') ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-hdd-network"></i></span>
                                        <input type="text" class="form-control" id="smtp_host" name="smtp_host"
                                            value="<?= htmlspecialchars($formData['smtp_host'] ?? '') ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="mb-3">
                                    <label for="smtp_port" class="form-label"><?= _e('port') ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-door-open"></i></span>
                                        <input type="number" class="form-control" id="smtp_port" name="smtp_port"
                                            value="<?= htmlspecialchars($formData['smtp_port'] ?? '') ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="smtp_user" class="form-label"><?= _e('user') ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person-lines-fill"></i></span>
                                        <input type="text" class="form-control" id="smtp_user" required name="smtp_user"
                                            value="<?= htmlspecialchars($formData['smtp_user'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="smtp_pass" class="form-label"><?= _e('password') ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                        <input type="password" class="form-control" id="smtp_pass" name="smtp_pass"
                                            value="<?= htmlspecialchars($formData['smtp_pass'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="smtp_security" class="form-label"><?= _e('security') ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-shield-shaded"></i></span>
                                        <select id="smtp_security" name="smtp_security" class="form-select" required>
                                            <option value="tls" <?= isset($formData['smtp_security']) && $formData['smtp_security'] === 'tls' ? 'selected' : '' ?>>tls</option>
                                            <option value="ssl" <?= isset($formData['smtp_security']) && $formData['smtp_security'] === 'ssl' ? 'selected' : '' ?>>ssl</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="smtp_auth" class="form-label"><?= _e('auth') ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                                        <select id="smtp_auth" name="smtp_auth" class="form-select" required>
                                            <option value="true" <?= isset($formData['smtp_auth']) && $formData['smtp_auth'] === 'true' ? 'selected' : '' ?>>true</option>
                                            <option value="false" <?= isset($formData['smtp_auth']) && $formData['smtp_auth'] === 'false' ? 'selected' : '' ?>>false</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div>
                                    <label for="smtp_from" class="form-label"><?= _e('from_email') ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope-at"></i></span>
                                        <input type="email" class="form-control" id="smtp_from" required name="smtp_from"
                                            value="<?= htmlspecialchars($formData['smtp_from'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div>
                                    <label for="smtp_from_name" class="form-label"><?= _e('from_name') ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person-vcard"></i></span>
                                        <input type="text" class="form-control" id="smtp_from_name" name="smtp_from_name"
                                            value="<?= htmlspecialchars($formData['smtp_from_name'] ?? '') ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div id="smtpConnectionResult"></div>
                                <button type="button" id="testSMTPConnection" class="btn btn-secondary">
                                    <i class="bi bi-plug"></i> <?= _e('test_smtp_connection') ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header fw-bold">Firebase</div>
                    <div class="card-body">
                        <p class="fw-bold text-muted"><?= _e('firebase_tutorial_sdk') ?></p>
                        <div class="mb-3">
                            <label for="firebase_apikey" class="form-label">apiKey</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-key"></i></span>
                                <input type="text" class="form-control" id="firebase_apikey" min="30" required name="firebase_apikey"
                                    value="<?= htmlspecialchars($formData['firebase_apikey'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="firebase_authdomain" class="form-label">authDomain</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-house"></i></span>
                                <input type="text" class="form-control" id="firebase_authdomain" min="22" required name="firebase_authdomain"
                                    value="<?= htmlspecialchars($formData['firebase_authdomain'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="firebase_projectid" class="form-label">projectId</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-house-gear"></i></span>
                                <input type="text" class="form-control" id="firebase_projectid" min="6" required name="firebase_projectid"
                                    value="<?= htmlspecialchars($formData['firebase_projectid'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="firebase_storagebucket" class="form-label">storageBucket</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-floppy"></i></span>
                                <input type="text" class="form-control" id="firebase_storagebucket" min="26" required name="firebase_storagebucket"
                                    value="<?= htmlspecialchars($formData['firebase_storagebucket'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="firebase_messagingsenderid" class="form-label">messagingSenderId</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-chat"></i></span>
                                <input type="text" class="form-control" id="firebase_messagingsenderid" required min="10" name="firebase_messagingsenderid"
                                    value="<?= htmlspecialchars($formData['firebase_messagingsenderid'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="firebase_appid" class="form-label">appId</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                <input type="text" class="form-control" id="firebase_appid" min="38" required name="firebase_appid"
                                    value="<?= htmlspecialchars($formData['firebase_appid'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="firebase_measurementid" class="form-label">measurementId</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-pie-chart"></i></span>
                                <input type="text" class="form-control" id="firebase_measurementid" min="12" name="firebase_measurementid"
                                    value="<?= htmlspecialchars($formData['firebase_measurementid'] ?? '') ?>">
                            </div>
                        </div>

                        <p class="fw-bold text-muted"><?= _e('firebase_tutorial_webpush') ?></p>
                        <div class="mb-3">
                            <label for="firebase_vapid_public" class="form-label">VAPID Public Key</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-key"></i></span>
                                <input type="text" class="form-control" id="firebase_vapid_public" min="80" required name="firebase_vapid_public"
                                    value="<?= htmlspecialchars($formData['firebase_vapid_public'] ?? '') ?>">
                            </div>
                        </div>
                        <div>
                            <label for="firebase_vapid_private" class="form-label">VAPID Private Key</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                <input type="text" class="form-control" id="firebase_vapid_private" min="40" required name="firebase_vapid_private"
                                    value="<?= htmlspecialchars($formData['firebase_vapid_private'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header fw-bold"><?= _e('install_useradmin') ?></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <div>
                                    <label for="user_email" class="form-label"><?= _e('email') ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input type="email" class="form-control" id="user_email" name="user_email"
                                            value="<?= htmlspecialchars($formData['user_email'] ?? '') ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div>
                                    <label for="user_password" class="form-label"><?= _e('password') ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                                        <input type="text" class="form-control" id="user_password" name="user_password"
                                            value="<?= htmlspecialchars($formData['user_password'] ?? '') ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-grid">
                    <p class="m-0 pb-2 text-muted"><?= _e('install_instruction') ?></p>
                    <button type="submit" id="installButton" class="btn btn-primary btn-lg" disabled><?= _e('install_button') ?></button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php $this->end() ?>

<?php $this->start('page_scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const appUrlInput = document.getElementById('app_url');
        const clientUrlInput = document.getElementById('client_url');
        const appUrlError = document.getElementById('app_url_error');
        const clientUrlError = document.getElementById('client_url_error');

        function validateHttpsUrl(input, errorElement) {
            const url = input.value.trim();
            if (url && !url.startsWith('https://')) {
                errorElement.textContent = '<?= _e('https_fill_all') ?>';
                input.setCustomValidity('<?= _e('https_invalid') ?>');
                return false;
            } else {
                errorElement.textContent = '';
                input.setCustomValidity('');
                return true;
            }
        }

        // Add input event listeners for real-time validation
        appUrlInput.addEventListener('input', function() {
            validateHttpsUrl(this, appUrlError);
        });

        clientUrlInput.addEventListener('input', function() {
            validateHttpsUrl(this, clientUrlError);
        });

        const testMySQLBtn = document.getElementById('testMySQLConnection');
        const testAMQPBtn = document.getElementById('testAMQPConnection');
        const testSMTPBtn = document.getElementById('testSMTPConnection');
        const mysqlConnectionResult = document.getElementById('mysqlConnectionResult');
        const amqpConnectionResult = document.getElementById('amqpConnectionResult');
        const smtpConnectionResult = document.getElementById('smtpConnectionResult');
        const installButton = document.getElementById('installButton');

        let mysqlTestPassed = false;
        let amqpTestPassed = false;
        let smtpTestPassed = false;

        function updateInstallButton() {
            installButton.disabled = !(mysqlTestPassed && amqpTestPassed && smtpTestPassed);
        }

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
            const preview = document.getElementById(inputId+'_preview');

            const initialUrl = input.value.trim();
            updateImagePreview(preview, initialUrl);

            input.addEventListener('input', function() {
                const url = this.value.trim();
                updateImagePreview(preview, url);
            });
        }
        imagePreview('client_icon_url');
        imagePreview('client_badge_url');

        testMySQLBtn.addEventListener('click', function() {
            const host = document.getElementById('db_host').value;
            const port = document.getElementById('db_port')?.value || 3306;
            const user = document.getElementById('db_user').value;
            const pass = document.getElementById('db_pass').value;
            const name = document.getElementById('db_name').value;

            if (!host || !user || !name) {
                mysqlConnectionResult.innerHTML = '<div class="alert alert-warning"><?= _e('mysql_fill_all') ?></div>';
                return;
            }

            // Disable button during test
            testMySQLBtn.disabled = true;
            mysqlConnectionResult.innerHTML = '<div class="alert alert-info"><?= _e('mysql_testing') ?></div>';

            fetch('/install/mysql', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        host: host,
                        port: port,
                        user: user,
                        pass: pass,
                        name: name
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mysqlConnectionResult.innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= _e('mysql_valid') ?></div>';
                        mysqlTestPassed = true;
                    } else {
                        mysqlConnectionResult.innerHTML = `<div class="alert alert-danger"><i class="bi bi-x-circle"></i> <?= _e('mysql_invalid') ?> ${data.message}</div>`;
                        mysqlTestPassed = false;
                    }
                    updateInstallButton();
                })
                .catch(error => {
                    mysqlConnectionResult.innerHTML = `<div class="alert alert-danger"><i class="bi bi-x-circle"></i> <?= _e('error') ?>: ${error.message}</div>`;
                    mysqlTestPassed = false;
                    updateInstallButton();
                })
                .finally(() => {
                    testMySQLBtn.disabled = false;
                });
        });

        testAMQPBtn.addEventListener('click', function() {
            const host = document.getElementById('amqp_host').value;
            const port = document.getElementById('amqp_port').value;
            const user = document.getElementById('amqp_user').value;
            const pass = document.getElementById('amqp_pass').value;
            const vhost = document.getElementById('amqp_vhost').value;

            if (!host || !port || !user || !pass) {
                amqpConnectionResult.innerHTML = '<div class="alert alert-warning"><?= _e('amqp_fill_all') ?></div>';
                return;
            }

            // Disable button during test
            testAMQPBtn.disabled = true;
            amqpConnectionResult.innerHTML = '<div class="alert alert-info"><?= _e('amqp_testing') ?></div>';

            fetch('/install/amqp', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        host: host,
                        port: port,
                        user: user,
                        pass: pass,
                        vhost: vhost
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        amqpConnectionResult.innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= _e('amqp_valid') ?></div>';
                        amqpTestPassed = true;
                    } else {
                        amqpConnectionResult.innerHTML = `<div class="alert alert-danger"><i class="bi bi-x-circle"></i> <?= _e('amqp_invalid') ?>: ${data.message}</div>`;
                        amqpTestPassed = false;
                    }
                    updateInstallButton();
                })
                .catch(error => {
                    amqpConnectionResult.innerHTML = `<div class="alert alert-danger"><i class="bi bi-x-circle"></i> <?= _e('amqp_error') ?>: ${error.message}</div>`;
                    amqpTestPassed = false;
                    updateInstallButton();
                })
                .finally(() => {
                    testAMQPBtn.disabled = false;
                });
        });

        testSMTPBtn.addEventListener('click', function() {
            const host = document.getElementById('smtp_host').value;
            const port = document.getElementById('smtp_port').value;
            const user = document.getElementById('smtp_user').value;
            const pass = document.getElementById('smtp_pass').value;
            const auth = document.getElementById('smtp_auth').value;
            const from = document.getElementById('smtp_from').value;
            const fromName = document.getElementById('smtp_from_name').value;

            if (!host || !port) {
                smtpConnectionResult.innerHTML = '<div class="alert alert-warning"><?= _e('smtp_fill_all') ?></div>';
                return;
            }

            // Disable button during test
            testSMTPBtn.disabled = true;
            smtpConnectionResult.innerHTML = '<div class="alert alert-info"><?= _e('smtp_testing') ?></div>';

            fetch('/install/smtp', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        host: host,
                        port: port,
                        user: user,
                        pass: pass,
                        auth: auth,
                        from: from,
                        fromName: fromName
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        smtpConnectionResult.innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= _e('smtp_valid') ?>!</div>';
                        smtpTestPassed = true;
                    } else {
                        smtpConnectionResult.innerHTML = `<div class="alert alert-danger"><i class="bi bi-x-circle"></i> <?= _e('smtp_invalid') ?>: ${data.message}</div>`;
                        smtpTestPassed = false;
                    }
                    updateInstallButton();
                })
                .catch(error => {
                    smtpConnectionResult.innerHTML = `<div class="alert alert-danger"><i class="bi bi-x-circle"></i> <?= _e('error') ?>: ${error.message}</div>`;
                    smtpTestPassed = false;
                    updateInstallButton();
                })
                .finally(() => {
                    testSMTPBtn.disabled = false;
                });
        });
    });
</script>
<?php $this->end() ?>

<?php $this->start('page_styles') ?>
<style>
    @keyframes bell-shake {
        0% {
            transform: rotate(0deg);
        }

        15% {
            transform: rotate(-15deg);
        }

        30% {
            transform: rotate(10deg);
        }

        45% {
            transform: rotate(-10deg);
        }

        60% {
            transform: rotate(5deg);
        }

        75% {
            transform: rotate(-5deg);
        }

        100% {
            transform: rotate(0deg);
        }
    }

    .install-logo {
        display: inline-block;
        font-size: 2rem;
        animation: bell-shake 1s ease-in-out infinite;
    }
</style>
<?php $this->end() ?>