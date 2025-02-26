<!DOCTYPE html>
<html lang="<?= _e('lang') ?>">

<head>
    <?= $this->insert('parts/head', ['title' => 'PushBase']) ?>

    <?= $this->section('page_styles'); ?>
</head>

<body class="bg-light d-flex flex-column vh-100">
    <main class="w-100 m-auto">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <?= $this->section('page_content') ?>
                </div>
            </div>
        </div>
    </main>
</body>

</html>