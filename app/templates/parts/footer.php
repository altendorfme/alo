<footer class="border-top bg-white">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center py-3 small text-body-secondary">
            <div class="col-md-4 d-flex align-items-center">
                <i class="bi bi-bell-fill me-2"></i>
                <span class="mb-3 mb-md-0">
                    Pushbase &copy; <?= date('Y') ?>
                </span>
            </div>

            <div class="nerd-metrics text-end">
                <?php if (isset($user) && $user['role'] != 'editor') { ?>
                    <div class="d-inline-block me-3 text-nowrap" title="<?= _e('rabbitmq_status') ?>">
                        <i class="bi bi-chat-right-dots"></i>
                        <?php
                        function checkRabbitMQConnection()
                        {
                            try {
                                global $container;
                                $config = $container->get('config');
                                $rabbitmqConfig = $config->get('rabbitmq');

                                if (!class_exists('PhpAmqpLib\Connection\AMQPStreamConnection')) {
                                    return false;
                                }

                                $connection = new PhpAmqpLib\Connection\AMQPStreamConnection(
                                    $rabbitmqConfig['host'],
                                    $rabbitmqConfig['port'],
                                    $rabbitmqConfig['user'],
                                    $rabbitmqConfig['password'],
                                    $rabbitmqConfig['vhost']
                                );

                                $isConnected = $connection->isConnected();

                                $connection->close();

                                return $isConnected;
                            } catch (Exception $e) {
                                return false;
                            }
                        }
                        $rabbitmqConnected = checkRabbitMQConnection();

                        if ($rabbitmqConnected) { ?>
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
<div class="ldld full"></div>
<script>
    <?php
    $bootstrap = "/dist/scripts/bootstrap.min.js";
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $bootstrap)) {
        include $_SERVER['DOCUMENT_ROOT'] . $bootstrap;
    }
    ?>
</script>
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