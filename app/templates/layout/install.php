<!DOCTYPE html>
<html lang="<?= _e('lang') ?>" data-bs-theme="light">

<head>
    <?= $this->insert('parts/head', ['title' => $title ?? 'Alô', 'css' => $css ?? null]) ?>

    <?= $this->section('page_styles'); ?>
</head>

<body>

    <div class="container py-4">
        <main>
            <?php
            if (isset($error)) {
                ?>
                <div class="alert alert-danger">
                    <span class="bi bi-exclamation-triangle-fill me-2"></span>
                    <?= $error; ?>
                </div>
                <?php
            } elseif (isset($_GET['error'])) {
                ?>
                <div class="alert alert-danger">
                    <?= _e($_GET['error']); ?>
                </div>
                <?php
            }

            if (isset($warning)) {
                ?>
                <div class="alert alert-warning">
                    <span class="bi bi-exclamation-triangle-fill me-2"></span>
                    <?= $warning; ?>
                </div>
                <?php
            } elseif (isset($_GET['warning'])) {
                ?>
                <div class="alert alert-warning">
                    <span class="bi bi-exclamation-triangle-fill me-2"></span>
                    <?= _e($_GET['warning']); ?>
                </div>
                <?php
            }

            if (isset($success)) {
                ?>
                <div class="alert alert-success">
                    <span class="bi bi-check-circle-fill me-2"></span>
                    <?= $success; ?>
                </div>
                <?php
            } elseif (isset($_GET['success'])) {
                ?>
                <div class="alert alert-success">
                    <span class="bi bi-check-circle-fill me-2"></span>
                    <?= _e($_GET['success']); ?>
                </div>
                <?php
            }
            ?>

            <?= $this->section('page_content'); ?>
        </main>
    </div>

    <?= $this->insert('parts/footer', ['js' => $js ?? null]) ?>

    <?= $this->section('page_scripts'); ?>
</body>

</html>