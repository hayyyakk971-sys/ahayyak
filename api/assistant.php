<?php
// assistant.php - AI Assistant API endpoint for HAYYAK
// مساعد.بي إتش بي - نقطة نهاية API للمساعد الذكي في حياك

header('Content-Type: application/json; charset=utf-8');
// Sets the response content type to JSON with UTF-8 encoding // يضبط نوع محتوى الاستجابة إلى JSON بترميز UTF-8

// Basic CORS handling (adjust origin in production)
// معالجة أساسية لـ CORS (قم بتعديل الأصل في الإنتاج)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']); // Allow specific origin // السماح بأصل محدد
} else {
    header('Access-Control-Allow-Origin: *'); // Allow any origin (less secure) // السماح بأي أصل (أقل أمانًا)
}
header('Access-Control-Allow-Methods: POST, GET, OPTIONS'); // Allowed HTTP methods // طرق HTTP المسموح بها
header('Access-Control-Allow-Headers: Content-Type, Authorization'); // Allowed headers // الترويسات المسموح بها

// Handle preflight
// معالجة طلب الفحص المبدئي
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); // OK // موافق
    echo json_encode(['status' => 'ok']); // Return success // إرجاع نجاح
    exit; // Stop script execution // إيقاف تنفيذ السكريبت
}

// Allow both GET (for testing) and POST (for real requests)
// السماح بكل من GET (للاختبار) و POST (للطلبات الحقيقية)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(['status' => 'Assistant is running', 'method' => 'GET', 'request_method' => $_SERVER['REQUEST_METHOD']]); // Informational response // استجابة إعلامية
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed // الطريقة غير مسموح بها
    echo json_encode(['error' => 'Method not allowed, use POST', 'received' => $_SERVER['REQUEST_METHOD']]); // Error message // رسالة خطأ
    exit;
}

// Load config if provided (optional)
// تحميل ملف الإعدادات إذا كان موجودًا (اختياري)
if (file_exists(__DIR__ . '/config.php')) {
    include_once __DIR__ . '/config.php'; // Include configuration file // تضمين ملف الإعدادات
}

// Get API key from environment variable or config file
// الحصول على مفتاح API من متغير البيئة أو ملف الإعدادات
$apiKey = getenv('OPENROUTER_API_KEY') ?: (defined('OPENROUTER_API_KEY') ? OPENROUTER_API_KEY : null);
if (!$apiKey) {
    http_response_code(500); // Internal Server Error // خطأ داخلي في الخادم
    echo json_encode(['error' => 'API key not configured. Copy api/config.php.example to api/config.php and set OPENROUTER_API_KEY, or set environment variable.']); // Error instruction // تعليمات الخطأ
    exit;
}

// Decode incoming JSON request // فك تشفير طلب JSON الوارد
$input = json_decode(file_get_contents('php://input'), true);
$message = isset($input['message']) ? trim($input['message']) : ''; // Extract message // استخراج الرسالة
$userContext = isset($input['userContext']) ? $input['userContext'] : []; // Extract user context // استخراج سياق المستخدم
$websiteContext = isset($input['websiteContext']) ? $input['websiteContext'] : []; // Extract website context // استخراج سياق الموقع

if (!$message) {
    echo json_encode(['error' => 'No message provided']); // Error if no message // خطأ إذا لم تكن هناك رسالة
    exit;
}

// Build request for OpenRouter API (chat completions format)
// بناء طلب لواجهة OpenRouter API (تنسيق إكمال الدردشة)
$systemPrompt = "You are an AI assistant for tourism in the UAE. Your name is HAYYAK. Instructions: 1) Use HAYYAK instead of HELLO in greetings. 2) You are an AI assistant helping tourists in the UAE. 3) Only answer questions related to the UAE. 4) You can speak both Arabic and English. 5) Keep your answers short and concise. 6) Be helpful and friendly."; // System prompt for AI // الموجه النظامي للذكاء الاصطناعي

// Add user profile context to system prompt if available
// إضافة سياق ملف المستخدم إلى الموجه النظامي إذا كان متاحًا
if (!empty($userContext)) {
    $contextStr = "\n\nUser Profile Context (Use this to filter recommendations):"; // Header for context // عنوان للسياق
    if (!empty($userContext['name'])) $contextStr .= "\n- Name: " . $userContext['name']; // User name // اسم المستخدم
    if (!empty($userContext['allergies'])) $contextStr .= "\n- Allergies: " . implode(', ', $userContext['allergies']); // Allergies // الحساسيات
    if (!empty($userContext['medicalConditions'])) $contextStr .= "\n- Health Issues: " . implode(', ', $userContext['medicalConditions']); // Health issues // مشاكل صحية
    if (!empty($userContext['phobias'])) $contextStr .= "\n- Phobias: " . implode(', ', $userContext['phobias']); // Phobias // الفوبيا
    if (!empty($userContext['foodPreferences'])) $contextStr .= "\n- Food Preferences: " . implode(', ', $userContext['foodPreferences']); // Food preferences // تفضيلات الطعام
    if (!empty($userContext['interests'])) $contextStr .= "\n- Interests: " . implode(', ', $userContext['interests']); // Interests // الاهتمامات
    if (!empty($userContext['city'])) $contextStr .= "\n- Current City: " . $userContext['city']; // City // المدينة
    
    $contextStr .= "\n\nIMPORTANT: When recommending restaurants or places (tourism/entertainment), you MUST filter based on the user's allergies, health issues, phobias, and food preferences. For example, avoid high places if they have a fear of heights, avoid allergens in food recommendations, and prioritize their food preferences. For stores, hotels, and farms, you can be less strict unless a specific phobia or condition applies directly. Use their interests to suggest relevant activities. PRIORITIZE places in the user's Current City if available."; // Important instructions // تعليمات مهمة
    
    $systemPrompt .= $contextStr; // Append to system prompt // إلحاق بالموجه النظامي
}

// Add website context (available places) if provided
// إضافة سياق الموقع (الأماكن المتاحة) إذا تم توفيره
if (!empty($websiteContext) && !empty($websiteContext['availablePlaces'])) {
    $systemPrompt .= "\n\nRelevant Places from our Website (Use these to create plans or recommendations):"; // Header for places // عنوان للأماكن
    foreach ($websiteContext['availablePlaces'] as $place) {
        $name = $place['name'] ?? 'Unknown'; // Place name // اسم المكان
        $nameAr = $place['nameAr'] ?? $name; // Arabic name // الاسم بالعربية
        $desc = $place['description'] ?? ''; // Description // الوصف
        $link = $place['link'] ?? '#'; // Link to page // رابط الصفحة
        $city = $place['city'] ?? ''; // City // المدينة
        $systemPrompt .= "\n- Name: $name ($nameAr) [$city]\n  Description: $desc\n  Link: $link (ALWAYS use this link format when mentioning this place: [$name]($link))"; // Place details // تفاصيل المكان
    }
    $systemPrompt .= "\n\nIf the user asks for a plan or itinerary, create a structured schedule (Morning, Afternoon, Evening) using the places listed above. Ensure the places are in the same city if possible. Do not ask clarifying questions (like budget or interests) if you have enough places to propose a plan; just create a suggested plan immediately."; // Planning instructions // تعليمات التخطيط
}

// Prepare payload for OpenRouter // إعداد الحمولة لـ OpenRouter
$payload = [
    'model' => 'google/gemini-2.0-flash-001', // Model to use // النموذج المراد استخدامه
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt], // System message // رسالة النظام
        ['role' => 'user', 'content' => $message] // User message // رسالة المستخدم
    ],
    'temperature' => 0.7, // Creativity level // مستوى الإبداع
    'max_tokens' => 512 // Maximum tokens in response // الحد الأقصى للرموز في الرد
];

// Initialize cURL session // تهيئة جلسة cURL
$ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as string // إرجاع الاستجابة كسلسلة نصية
curl_setopt($ch, CURLOPT_POST, true); // POST request // طلب POST
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload)); // Set payload // تعيين الحمولة
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json', // JSON content // محتوى JSON
    'Authorization: Bearer ' . $apiKey, // API key // مفتاح API
]);

$result = curl_exec($ch); // Execute request // تنفيذ الطلب
$err = curl_error($ch); // Get error if any // الحصول على الخطأ إن وجد
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP status code // الحصول على رمز حالة HTTP
curl_close($ch); // Close session // إغلاق الجلسة

if ($err) {
    http_response_code(500); // Internal Server Error // خطأ داخلي في الخادم
    echo json_encode(['error' => 'Request error: ' . $err]); // Return error // إرجاع الخطأ
    exit;
}

$decoded = json_decode($result, true); // Decode response // فك تشفير الاستجابة

// Better error handling for API responses
// معالجة أفضل للأخطاء لاستجابات API
if ($httpCode === 401) {
    http_response_code(401); // Unauthorized // غير مصرح
    echo json_encode(['error' => 'API Authentication failed. Invalid or expired API key.', 'raw' => $decoded]); // Authentication error // خطأ في المصادقة
    exit;
}

if ($httpCode === 429) {
    http_response_code(429); // Too Many Requests // طلبات كثيرة جدًا
    echo json_encode(['error' => 'Rate limited by OpenRouter. Please try again later.', 'raw' => $decoded]); // Rate limit // حد المعدل
    exit;
}

if ($httpCode >= 400) {
    http_response_code($httpCode); // Return the actual error code // إرجاع رمز الخطأ الفعلي
    echo json_encode(['error' => 'OpenRouter API error: ' . ($decoded['error']['message'] ?? 'Unknown error'), 'raw' => $decoded]); // API error // خطأ API
    exit;
}

// Extract reply from OpenRouter chat completions format
// استخراج الرد من تنسيق إكمال الدردشة في OpenRouter
$reply = null;
if (isset($decoded['choices']) && is_array($decoded['choices']) && count($decoded['choices']) > 0) {
    $choice = $decoded['choices'][0]; // First choice // الخيار الأول
    if (isset($choice['message']['content'])) {
        $reply = $choice['message']['content']; // Extract content // استخراج المحتوى
    }
}

if (!$reply) {
    // Return full decoded object for debugging but keep user-friendly message
    // إرجاع كامل الكائن المفكك للتصحيح ولكن الاحتفاظ برسالة سهلة للمستخدم
    http_response_code($httpCode >= 400 ? $httpCode : 500); // Appropriate code // الرمز المناسب
    echo json_encode(['error' => 'No reply from model', 'raw' => $decoded]); // Error // خطأ
    exit;
}

echo json_encode(['reply' => $reply]); // Return successful reply // إرجاع الرد الناجح