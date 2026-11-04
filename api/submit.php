<?php
/**
 * 问卷提交转发 —— 上传到网站根目录下的 api/ 目录即可，无需任何配置。
 * 网页与它同源，因此不受浏览器跨域限制；由服务器转发到问卷接口。
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$TARGET = 'https://seller.kuajingmaihuo.com/gmpapi/flower/survey/feedback/submit';

// 健康检查
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    echo json_encode(['ok' => true, 'service' => 'survey-forward'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$decoded = json_decode($raw, true);
if (!$raw || !is_array($decoded)) {
    echo json_encode(['ok' => false, 'msg' => '提交内容不是合法 JSON'], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = json_encode([
    'surveyId' => 244535225837,
    'version'  => 1,
    'itemList' => $decoded['itemList'] ?? [],
], JSON_UNESCAPED_UNICODE);

$ch = curl_init($TARGET);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json, text/plain, */*',
        'Origin: https://seller.kuajingmaihuo.com',
        'Referer: https://seller.kuajingmaihuo.com/',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$resp = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

if ($resp === false) {
    echo json_encode(['ok' => false, 'msg' => '转发失败：' . $err], JSON_UNESCAPED_UNICODE);
    exit;
}

$ok = $code >= 200 && $code < 300;
$data = json_decode($resp, true);
if (is_array($data)) {
    if (array_key_exists('success', $data) && !$data['success']) $ok = false;
    if (array_key_exists('ok', $data) && !$data['ok']) $ok = false;
    if (isset($data['errorCode']) && !in_array($data['errorCode'], [0, '0', null], true)) $ok = false;
}

echo json_encode([
    'ok'     => $ok,
    'status' => $code,
    'data'   => $data ?? mb_substr($resp, 0, 500),
], JSON_UNESCAPED_UNICODE);
