<!DOCTYPE html>
<html lang="<?= _e('lang') ?>">

<head>
    <?= $this->insert('parts/head', ['title' => $title ?? 'PushBase', 'css' => $css ?? null]) ?>

    <?= $this->section('page_styles'); ?>
</head>

<body class="bg-light">

    <div class="container py-4">
        <main>
        <?php
            if (isset($error)) {
                ?>
                <div class="alert alert-danger"><?= $error; ?></div>
                <?php
            } elseif (isset($_GET['error'])) {
                ?>
                <div class="alert alert-danger"><?= _e($_GET['error']); ?></div>
                <?php
            }

            if (isset($warning)) {
                ?>
                <div class="alert alert-alert"><?= $warning; ?></div>
                <?php
            } elseif (isset($_GET['warning'])) {
                ?>
                <div class="alert alert-alert"><?= _e($_GET['warning']); ?></div>
                <?php
            }

            if (isset($success)) {
                ?>
                <div class="alert alert-success"><?= $success; ?></div>
                <?php
            } elseif (isset($_GET['success'])) {
                ?>
                <div class="alert alert-success"><?= _e($_GET['success']); ?></div>
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