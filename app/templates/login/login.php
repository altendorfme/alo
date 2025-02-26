<?php $this->layout('layout/login', ['title' => _e('login')]) ?>

<?php $this->start('page_content') ?>

<form method="post" action="/login">
    <div class="form-floating">
        <input type="email" class="form-control rounded-0 border-bottom-0 rounded-top" id="email" name="email" placeholder="name@example.com" required autocomplete="username">
        <label for="email"><?= _e('email') ?></label>
    </div>
    <div class="form-floating mb-3">
        <input type="password" class="form-control rounded-0 rounded-bottom" id="password" name="password" placeholder="Password" required autocomplete="current-password">
        <label for="password"><?= _e('password') ?></label>
    </div>

    <div class="form-check mb-3">
        <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
        <label class="form-check-label" for="remember_me"><?= _e('remember_me') ?></label>
    </div>
    <button class="btn btn-primary w-100 py-2" type="submit"><?= _e('login_button') ?></button>
</form>

<div class="mt-3 text-center">
    <a href="/login/forgot_password" class="text-muted"><?= _e('forgot_password') ?></a>
</div>

<?php $this->end() ?>

<?php $this->start('page_scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const loginForm = document.querySelector('form');

        if (loginForm) {
            loginForm.addEventListener('submit', function(event) {
                const submitButton = loginForm.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.classList.add('disabled');
                }
            });
        }
    });
</script>
<?php $this->end() ?>