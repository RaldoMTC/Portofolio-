<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$message = $_POST['message'] ?? '';
$recaptcha = $_POST['g-recaptcha-response'] ?? '';

// Validasi reCAPTCHA (ganti SECRET_KEY)
$secretKey = '6LdkJ8grAAAAAM0LYv6C8CGaFsiKmemL08fk4-SX';
$verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$recaptcha}");
$captcha_success = json_decode($verify);

if (!$captcha_success->success) {
    echo json_encode(['success' => false, 'message' => 'reCAPTCHA gagal. Coba lagi.']);
    exit;
}

if (empty($name) || empty($email) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Form gak lengkap.']);
    exit;
}

$data = "New Contact:\nName: {$name}\nEmail: {$email}\nMessage: {$message}";

// Kirim Email via SendGrid
$sendGridApiKey = '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'; // Ganti kalau perlu
$toEmail = 'akunbaruh210207@gmail.com';
$fromEmail = 'raldoackerman@gmail.com';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://api.sendgrid.com/v3/mail/send',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'personalizations' => [['to' => [['email' => $toEmail]], 'subject' => 'New Portfolio Contact']],
        'from' => ['email' => $fromEmail, 'name' => 'Raldo Portfolio'],
        'content' => [['type' => 'text/plain', 'value' => $data]]
    ]),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $sendGridApiKey,
        'Content-Type: application/json'
    ]
]);
$emailResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 202) {
    echo json_encode(['success' => false, 'message' => 'Gagal kirim email: ' . $httpCode]);
    exit;
}

// Kirim ke Telegram (opsional)
$telegramToken = '7822471568:AAE89BHf7mxBx4kqhKNLyE7yP4IlMgi8wFE'; // Hapus atau ganti
$telegramChatId = '7680606818';
if ($telegramToken && $telegramChatId) {
    $telegramUrl = "https://api.telegram.org/bot{$telegramToken}/sendMessage?chat_id={$telegramChatId}&text=" . urlencode($data) . "&parse_mode=HTML";
    file_get_contents($telegramUrl); // Simple GET
}

echo json_encode(['success' => true, 'message' => 'Terkirim!']);
?>
