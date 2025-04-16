<footer class="border-top shadow">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center py-3 small text-body-secondary">
            <div class="col-md-4 d-flex align-items-center">
                <div class="d-flex align-items-center me-3">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="darkModeSwitch">
                        <label class="form-check-label" for="darkModeSwitch">
                            <i class="bi bi-moon-stars"></i>
                        </label>
                    </div>
                </div>
                <i class="bi bi-megaphone-fill me-2"></i>
                <span class="mb-3 mb-md-0">
                    Alô &copy; <?= date('Y') ?>
                </span>
            </div>

            <div class="nerd-metrics text-end">
                <?php if (isset($user) && $user['role'] != 'editor') { ?>
                    <div class="d-inline-block me-3 text-nowrap" title="<?= _e('amqp_status') ?>">
                        <i class="bi bi-chat-right-dots"></i>
                        <?php
                        function checkAMQPConnection()
                        {
                            try {
                                global $container;
                                $config = $container->get('config');
                                $amqpConfig = $config->get('amqp');

                                if (!class_exists('PhpAmqpLib\Connection\AMQPStreamConnection')) {
                                    return false;
                                }

                                $connection = new PhpAmqpLib\Connection\AMQPStreamConnection(
                                    $amqpConfig['host'],
                                    $amqpConfig['port'],
                                    $amqpConfig['user'],
                                    $amqpConfig['password'],
                                    $amqpConfig['vhost']
                                );

                                $isConnected = $connection->isConnected();

                                $connection->close();

                                return $isConnected;
                            } catch (Exception $e) {
                                return false;
                            }
                        }
                        $amqpConnected = checkAMQPConnection();

                        if ($amqpConnected) { ?>
                            <span><?= _e('connected') ?></span>
                        <?php } else { ?>
                            <span class="text-danger"><?= _e('disconnected') ?></span>
                        <?php }
                        ?>
                    </div>

                    <div class="d-inline-block me-3 text-nowrap" title="<?= _e('memory_usage') ?>">
                        <i class="bi bi-memory"></i>
                        <?= round(memory_get_peak_usage() / 1024 / 1024, 2) ?> MB
                    </div>

                    <div class="d-inline-block me-3 text-nowrap" title="<?= _e('load_time') ?>">
                        <i class="bi bi-hourglass-split"></i>
                        <?= round((microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))) * 1000, 2) ?> ms
                    </div>
                <?php } ?>

                <div class="d-inline-block me-3 text-nowrap" title="<?= _e('timezone') ?>">
                    <i class="bi bi-clock"></i>
                    <?= date_default_timezone_get() ?>
                </div>

                <div class="d-inline-block me-3 text-nowrap"title="<?= _e('language') ?>">
                    <i class="bi bi-translate" ></i>
                    <?= _e('lang_full') ?>
                </div>
            </div>
        </div>
    </div>
</footer>
<script>
    <?php
    $bootstrap = "/dist/scripts/bootstrap.min.js";
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $bootstrap)) {
        include $_SERVER['DOCUMENT_ROOT'] . $bootstrap;
    }
    ?>
</script>
<div class="ldld full"></div>
<script>
    <?php
    $ldLoaderJSPath = "/dist/scripts/ldloader.min.js";
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $ldLoaderJSPath)) {
        include $_SERVER['DOCUMENT_ROOT'] . $ldLoaderJSPath;
    }
    ?>

    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('submit', function(event) {
            if (event.target.tagName === 'FORM') {
                new ldloader({
                    root: ".ldld.full"
                }).on();
            }
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const darkModeSwitch = document.getElementById('darkModeSwitch');
        const htmlElement = document.documentElement;

        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            htmlElement.setAttribute('data-bs-theme', 'dark');
            darkModeSwitch.checked = true;
        } else {
            htmlElement.setAttribute('data-bs-theme', 'light');
            darkModeSwitch.checked = false;
        }
        
        darkModeSwitch.addEventListener('change', function() {
            if (this.checked) {
                htmlElement.setAttribute('data-bs-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            } else {
                htmlElement.setAttribute('data-bs-theme', 'light');
                localStorage.setItem('theme', 'light');
            }
        });
    });
</script>