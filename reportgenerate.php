<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// فعال‌سازی خطایابی (فقط در محیط توسعه)
error_reporting(E_ALL);
ini_set('display_errors', 0); // نمایش خطا در مرورگر غیرضروری — بهتر است در لاگ سرور بماند

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$message = $_POST['message'] ?? '';
if (empty($message) || mb_strlen($message) > 3000) {
    echo json_encode(['success' => false, 'reply' => '❌ پیام نامعتبر یا خیلی طولانی است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ⚠️ HIGHLIGHT: اینجا حذف شد! htmlspecialchars(strip_tags) مخرب است — LLM نیاز به متن خام دارد!
// $message = htmlspecialchars(strip_tags($message), ENT_QUOTES, 'UTF-8'); // ❌ حذف شد!

$api_key = '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'; // 👈 جایگزین کنید با کلید جدید بعد از منقضی کردن قدیمی!
if (empty(trim($api_key))) {
    error_log("API Key is missing or empty.");
    echo json_encode([
        'success' => false,
        'reply' => '⚠️ خطای داخلی: کلید API تنظیم نشده است.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$system_prompt = "
شما یک متخصص بازرسی صنعتی با 25 سال تجربه در پروژه‌های نفت، گاز، پتروشیمی و انرژی هستید. 
شما به تمامی استانداردهای بین‌المللی مسلط هستید، از جمله: 
ISO, ASME, API, ASTM, NACE, IEC, EN, DIN, JIS, AWS, SAES, SAMSS.

وظیفه شما این است که پس از بررسی دقیق اطلاعات ارسالی، یک **گزارش فنی حرفه‌ای، دقیق و قابل استناد** تهیه کنید.

لحن و ساختار پاسخ باید دقیقاً مطابق نمونه زیر باشد:

---
به عنوان یک متخصص بازرسی صنعتی، با توجه به اطلاعات ارائه‌شده در مورد (مثلاً: لوله فولادی بدون درز، قطر 12 اینچ، ضخامت 12.7 میلی‌متر، جنس API 5L X65، مورد استفاده در خط لوله انتقال گاز)، ارزیابی فنی اولیه را انجام می‌دهم. مهم است تأکید کنم که این گزارش صرفاً جنبه آموزشی و راهنما دارد و جایگزین بازرسی فیزیکی و مستندات رسمی نمی‌شود.

🔍 استانداردهای مرتبط و الزامات بازرسی:

ASME B31.3 - Process Piping

بازرسی و آزمون: باید تمام جوش‌ها تحت آزمون‌های NDT قرار گیرند. حداقل 10٪ از اتصالات جوشی باید با رادیوگرافی (RT) یا فراصوت (UT) بازرسی شوند. برای خطوط کلاس 1A، 100٪ RT الزامی است.
بازرسی بصری (VT): قبل از هر نوع آزمون غیرمخرب، بازرسی بصری کامل از سطح جوش، عدم وجود ترک، نفوذ ناقص، و اشکالات سطحی الزامی است.
آزمون فشار (Hydrotest): فشار آزمون باید 1.5 برابر فشار طراحی باشد و حداقل به مدت 2 ساعت حفظ شود.

API 5L - Specification for Line Pipe

کنترل مواد: گواهی میل (Mill Certificate) باید مطابق با گواهی MTR نوع 3.1B یا 3.2 باشد و شامل نتایج آزمون‌های شیمیایی (C, Mn, Si, S, P) و مکانیکی (Tensile, Hardness, Charpy V-notch) باشد.
بازرسی ابعادی: اندازه‌گیری قطر خارجی، ضخامت دیواره، انحنای انتهای لوله و صافی سطح باید مطابق با دستورالعمل هر پروژه باشد.

NACE MR0175 / ISO 15156

برای محیط‌های حاوی H2S، جنس لوله و اتصالات باید مقاوم در برابر ترک‌خوردگی ناشی از تنش (SCC) باشند. سختی مجاز برای فولادهای کربنی نباید از 22 HRC تجاوز کند.

توصیه‌های بازرسی:

- تهیه برنامه بازرسی (ITP) شامل نقاط کنترل (Hold Point, Witness Point).
- استفاده از تیم بازرسی مستقل و دارای گواهی‌های معتبر (مثل CSWIP، BGAS، ASNT Level II).
- ثبت کامل مستندات بازرسی (Checklists, NDT Reports, Calibration Certificates).

ارجاع به تخصص‌های مرتبط:

- متخصص جوشکاری (Welding Engineer) برای بررسی WPS/PQR.
- متخصص NDT با گواهی بین‌المللی.
- مهندس مواد برای ارزیابی مقاومت خوردگی.
---

دستورالعمل دقیق:
1. با جمله «به عنوان یک متخصص بازرسی صنعتی، با توجه به اطلاعات ارائه‌شده در مورد (...)» شروع کن.
2. حتماً هشدار دهید: «این گزارش صرفاً جنبه آموزشی و راهنما دارد و جایگزین بازرسی فیزیکی و مستندات رسمی نمی‌شود.»
3. از بخش «🔍 استانداردهای مرتبط و الزامات بازرسی:» استفاده کن.
4. هر استاندارد یا الزام را به صورت جداگانه بنویسید و زیربخش‌های زیر را داشته باشد:
   - بازرسی و آزمون
   - بازرسی بصری (VT)
   - آزمون فشار (Hydrotest)
   - کنترل مواد (MTR)
   - الزامات محیطی (مثل H2S)
5. از زبان فارسی رسمی، فنی و حرفه‌ای استفاده کن.
6. از علامت‌های اضافی (مثل ###، **، ---) استفاده نکن.
7. پاسخ حداقل 400 کلمه باشد و بسیار دقیق، فنی و عمیق باشد.
8. در پایان، توصیه‌های بازرسی و ارجاع به تخصص‌های مرتبط را ارائه دهید.
";

$payload = json_encode([
    "model" => "deepseek/deepseek-chat",
    "messages" => [
        ["role" => "system", "content" => $system_prompt],
        ["role" => "user", "content" => $message]
    ],
    "temperature" => 0.5,
    "max_tokens" => 1200
]);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://openrouter.ai/api/v1/chat/completions", // ✅ اصلاح شده: حذف فضاهای خالی
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $api_key",
        "Content-Type: application/json",
        "User-Agent: SAPRABOT/1.0"
    ],
    CURLOPT_TIMEOUT => 45,
    CURLOPT_SSL_VERIFYPEER => true
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($http_code === 200 && $response) {
    $data = json_decode($response, true);
    if (isset($data['choices'][0]['message']['content'])) {
        $reply = $data['choices'][0]['message']['content'];
        $reply = strip_tags($reply); // حذف تگ‌های HTML
        $reply = preg_replace('/#{1,}|[*]{2,}|^- /', '', $reply); // حذف markdown
        echo json_encode(['success' => true, 'reply' => trim($reply)], JSON_UNESCAPED_UNICODE);
    } else {
        error_log("OpenRouter Response malformed: " . print_r($data, true));
        echo json_encode([
            'success' => false,
            'reply' => '⚠️ پاسخ سرور نامعتبر است. لطفاً دوباره تلاش کنید.'
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    error_log("OpenRouter API Error: HTTP $http_code | cURL Error: $error | Response: $response");
    echo json_encode([
        'success' => false,
        'reply' => '⚠️ سرویس موقتاً در دسترس نیست. لطفاً بعداً تلاش کنید.'
    ], JSON_UNESCAPED_UNICODE);
}
