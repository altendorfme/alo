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
                <div class="d-inline-block me-3">
                    <i class="bi bi-memory" title="<?= _e('memory_usage') ?>"></i>
                    <?= round(memory_get_peak_usage() / 1024 / 1024, 2) ?> MB
                </div>

                <div class="d-inline-block me-3">
                    <i class="bi bi-hourglass-split" title="<?= _e('load_time') ?>"></i>
                    <?= round((microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))) * 1000, 2) ?> ms
                </div>
                
                <div class="d-inline-block me-3">
                    <i class="bi bi-clock" title="<?= _e('timezone') ?>"></i>
                    <?= date_default_timezone_get() ?>
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