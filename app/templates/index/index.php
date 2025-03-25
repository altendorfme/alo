<?php $this->layout('layout/index'); ?>

<?php $this->start('page_content') ?>

<div class="text-center mb-4">
    <a href="/login"><i class="bi bi-megaphone-fill text-primary d-inline-block"></i></a>
</div>

<?php $this->end() ?>

<?php $this->start('page_styles') ?>
<style>
    @keyframes bell-shake {
        0% {
            transform: rotate(0deg);
        }

        15% {
            transform: rotate(-15deg);
        }

        30% {
            transform: rotate(10deg);
        }

        45% {
            transform: rotate(-10deg);
        }

        60% {
            transform: rotate(5deg);
        }

        75% {
            transform: rotate(-5deg);
        }

        100% {
            transform: rotate(0deg);
        }
    }

    .bi.bi-megaphone-fill {
        display: inline-block;
        font-size: 4rem;
        animation: bell-shake 1s ease-in-out infinite;
        text-shadow: 0 0 20px rgba(0,0,0,.1);
    }
</style>
<?php $this->end() ?>