<?php
$messages = st_messages();
$msg_key  = $_GET['st_msg'] ?? '';

if (isset($messages[$msg_key])) :
    [$msg_text, $msg_type] = $messages[$msg_key];
?>
    <p class="notice notice--toast notice--<?= esc_attr($msg_type) ?>" data-toast role="<?= $msg_type === 'error' ? 'alert' : 'status' ?>">
        <?= esc_html(sprintf($msg_text, (int) ($_GET['st_n'] ?? 0))) ?>
    </p>
<?php endif; ?>