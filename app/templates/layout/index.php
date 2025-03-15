<!DOCTYPE html>
<html lang="<?= _e('lang') ?>" data-bs-theme="light">

<head>
    <?= $this->insert('parts/head', ['title' => 'PushBase']) ?>

    <?= $this->section('page_styles'); ?>
</head>

<body class="d-flex flex-column vh-100">
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const htmlElement = document.documentElement;

        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            htmlElement.setAttribute('data-bs-theme', 'dark');
        } else {
            htmlElement.setAttribute('data-bs-theme', 'light');
        }
    });
</script>
</html>