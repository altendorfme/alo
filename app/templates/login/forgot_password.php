<?php $this->layout('layout/login', ['title' => _e('forgot_password')]) ?>

<?php $this->start('page_content') ?>

<form method="post" action="/login/forgot_password">
    <div class="form-floating mb-3">
        <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required autocomplete="email">
        <label for="email"><?= _e('email') ?></label>
    </div>
    <button class="btn btn-primary w-100 py-2" type="submit"><?= _e('forgot_password_button') ?></button>
</form>

<div class="mt-3 text-center">
    <a href="/login" class="text-muted"><?= _e('back_to_login') ?></a>
</div>

<?php $this->end() ?>

<?php $this->start('page_scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const loginForm = document.querySelector('form');

        if (loginForm) {
            loginForm.addEventListener('submit', function(event) {
                // Optional: Add a visual indicator of loading
                const submitButton = loginForm.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.classList.add('disabled');
                }
            });
        }
    });
</script>
<?php $this->end() ?>