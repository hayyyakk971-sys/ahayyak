<?php
// Set the content type of the response to JSON.
// يضبط نوع محتوى الاستجابة إلى JSON.
header('Content-Type: application/json');

// Allow cross-origin requests from any domain.
// يسمح بطلبات من أي نطاق (Cross-Origin).
header('Access-Control-Allow-Origin: *');

// Set cache control headers to prevent caching of the response.
// يضبط ترويسات التحكم في التخزين المؤقت لمنع تخزين الاستجابة في ذاكرة التخزين المؤقت.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

// Define the path to the data file. Using __DIR__ ensures the path is always correct relative to this script.
// تأكد أن هذا المسار هو نفس المسار في update.php
$dataFile = __DIR__ . '/data.json';

// Clear the file status cache to ensure we get the latest file information.
// يمسح ذاكرة التخزين المؤقت لحالة الملف لضمان الحصول على أحدث معلومات الملف.
clearstatcache();

// Check if the data file exists.
// يتحقق مما إذا كان ملف البيانات موجودًا.
if (file_exists($dataFile)) {
    // Read the content of the JSON file.
    // يقرأ محتوى ملف JSON.
    $content = file_get_contents($dataFile);
    
    // Decode the JSON content into a PHP associative array.
    // يفك ترميز محتوى JSON إلى مصفوفة PHP ترابطية.
    $allData = json_decode($content, true);
    
    // Check if the decoded data is a non-empty array.
    // تأكدنا أن البيانات ليست فارغة
    if (!empty($allData) && is_array($allData)) {
        // Get the latest record, which is the first element in the array.
        // يحصل على أحدث سجل، وهو العنصر الأول في المصفوفة.
        $latest = $allData[0];
        
        // Encode the latest data into a JSON response with a success status.
        // The keys are explicitly set to match what the ESP32 sends.
        // قمنا بتعديل المفاتيح لتطابق ما يرسله الـ ESP32 الآن
        echo json_encode([
            'success' => true,
            'data' => [
                'temperature' => $latest['temperature'],
                'humidity' => $latest['humidity'],
                'aqi' => $latest['aqi'],
                'air_quality_level' => $latest['air_quality_level'],
                'overcrowding_percent' => $latest['overcrowding_percent'],
                'overcrowding_level' => $latest['overcrowding_level'],
                'timestamp' => $latest['timestamp']
            ]
        ], JSON_UNESCAPED_UNICODE);
        
        // Stop script execution after sending the response.
        // يوقف تنفيذ السكريبت بعد إرسال الاستجابة.
        exit;
    }
}

// If the file doesn't exist or is empty, return an error message.
// This provides a clear status to the frontend.
// إذا لم يجد الملف، سيعطي رسالة خطأ واضحة بدلاً من بيانات وهمية
echo json_encode([
    'success' => false,
    'message' => 'Waiting for ESP32 data... File not found at: ' . $dataFile
]);
?>