<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $title == 'Alô' ? $this->e($title) : $this->e($title) . ' | Alô' ?></title>
<link rel="shortcut icon" href="/dist/images/alo.svg" type="image/svg">
<style>
    <?php
    $bootstrap = "/dist/styles/bootstrap.min.css";
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $bootstrap)) {
        include $_SERVER['DOCUMENT_ROOT'] . $bootstrap;
    }
    ?>
</style>
<style>
    <?php
    $ldLoaderCSSPath = "/dist/styles/ldloader.min.css";
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $ldLoaderCSSPath)) {
        include $_SERVER['DOCUMENT_ROOT'] . $ldLoaderCSSPath;
    }
    ?>
</style>