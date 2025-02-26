<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $title == 'PushBase' ? $this->e($title) : $this->e($title) . ' | PushBase' ?></title>
<link rel="shortcut icon" href="/dist/images/pushbase.svg" type="image/svg">
<style>
    <?php
    $bootstrap = "/dist/styles/bootstrap.min.css";
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $bootstrap)) {
        include $_SERVER['DOCUMENT_ROOT'] . $bootstrap;
    }
    ?>
</style>