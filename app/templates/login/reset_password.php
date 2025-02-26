<?php $this->layout('layout/login', ['title' => _e('reset_password')]) ?>

<?php $this->start('page_content') ?>

<form method="post" action="/login/reset_password?token=<?= htmlspecialchars($token ?? ''); ?>">
    <div class="form-floating mb-3">
        <input type="password" class="form-control" id="password" name="password" placeholder="New Password" required autocomplete="new-password">
        <label for="password"><?= _e('new_password') ?></label>
    </div>
    <div class="form-floating mb-3">
        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm New Password" required autocomplete="new-password">
        <label for="confirm_password"><?= _e('confirm_new_password') ?></label>
    </div>
    <button class="btn btn-primary w-100 py-2" type="submit"><?= _e('reset_password_button') ?></button>
</form>

<div class="mt-3 text-center">
    <a href="/login" class="text-muted">Back to Login</a>
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