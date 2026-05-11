<?php
// update.php

// IMPORTANT FOR ESP32 SETUP:
// 1. Rename your project folder to "hayyak" (remove Arabic characters/spaces).
// 2. Use your PC's IP address (e.g., 192.168.1.5), NOT "localhost".
// 3. The URL in your ESP32 code should be:
//    http://<YOUR_PC_IP>/hayyak/api/update.php

// Enable full error reporting for debugging purposes.
// تفعيل عرض جميع الأخطاء لأغراض التصحيح.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the path for the data file and a log file for debugging.
// __DIR__ ensures the path is relative to the current script's directory.
// تعريف مسار ملف البيانات وملف السجل للتصحيح.
// __DIR__ يضمن أن المسار نسبي لمجلد السكريبت الحالي.
$dataFile = __DIR__ . '/data.json';
$logFile = __DIR__ . '/debug_update.log';

// Log every request attempt to the log file for debugging.
// تسجيل كل محاولة وصول في ملف Log للتأكد
file_put_contents($logFile, date("Y-m-d H:i:s") . " - Request Received\n", FILE_APPEND);

// Log raw input for debugging (Check this file to see what ESP32 sends!)
// تسجيل المدخلات الخام للتصحيح (تحقق من هذا الملف لترى ما يرسله ESP32!)
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    file_put_contents($logFile, "Raw Body: " . $rawInput . "\n", FILE_APPEND);
}
if (!empty($_REQUEST)) {
    file_put_contents($logFile, "REQUEST Params: " . print_r($_REQUEST, true) . "\n", FILE_APPEND);
}

// Receive data from the request. Supports both POST and GET for testing flexibility.
// The ESP32 should use POST, but GET is useful for browser-based testing.
// استقبال البيانات (دعم POST و GET للتجربة)
$temp  = $_REQUEST['temp'] ?? $_REQUEST['temperature'] ?? null;
$hum   = $_REQUEST['hum'] ?? $_REQUEST['humidity'] ?? null;
$aqi   = isset($_REQUEST['aqi'])  ? $_REQUEST['aqi']  : null;
$crowd = $_REQUEST['crowd'] ?? $_REQUEST['overcrowding_percent'] ?? null;

// 2. If not found in URL params, try getting data from JSON body (Fix for ESP32)
if ($temp === null) {
    $jsonData = json_decode($rawInput, true);
    
    if (is_array($jsonData)) {
        $temp  = $jsonData['temp'] ?? $jsonData['temperature'] ?? null;
        $hum   = $jsonData['hum'] ?? $jsonData['humidity'] ?? null;
        $aqi   = $jsonData['aqi'] ?? null;
        $crowd = $jsonData['crowd'] ?? $jsonData['overcrowding_percent'] ?? null;
        if ($temp !== null) file_put_contents($logFile, "JSON Payload received\n", FILE_APPEND);
    }
}

// Check if the 'temp' parameter was provided. This is the primary check for valid data.
// التحقق مما إذا تم توفير معامل 'temp'. هذا هو التحقق الرئيسي من صحة البيانات.
if ($temp !== null) {
    // Create a new data record array with the received values.
    // Values are cast to their expected types (float, int).
    // إنشاء مصفوفة سجل بيانات جديدة بالقيم المستلمة.
    // يتم تحويل القيم إلى أنواعها المتوقعة (float, int).
    $newData = [
        "temperature" => (float)$temp,
        "humidity" => (float)$hum,
        "aqi" => (int)$aqi,
        // Determine air quality level based on AQI value.
        // تحديد مستوى جودة الهواء بناءً على قيمة AQI.
        "air_quality_level" => ($aqi < 100) ? "Good" : "Polluted",
        "overcrowding_percent" => (int)$crowd,
        // Determine overcrowding level based on percentage.
        // تحديد مستوى الازدحام بناءً على النسبة المئوية.
        "overcrowding_level" => ($crowd > 50) ? "High" : "Low",
        // Add a timestamp for when the data was received.
        // إضافة طابع زمني لوقت استلام البيانات.
        "timestamp" => date("Y-m-d H:i:s")
    ];

    // This script overwrites the file with only the latest data.
    // To keep a history, you would read the existing file, add the new data, and then write it back.
    // يقوم هذا السكريبت بالكتابة فوق الملف بأحدث البيانات فقط.
    // للاحتفاظ بسجل تاريخي، يجب قراءة الملف الحالي، وإضافة البيانات الجديدة، ثم كتابتها مرة أخرى.
    $jsonContent = json_encode([$newData], JSON_UNESCAPED_UNICODE);
    
    // Attempt to write the new data to the JSON file.
    // محاولة الكتابة مع التحقق
    if (file_put_contents($dataFile, $jsonContent)) {
        // Log success and send a success message back to the client (e.g., ESP32).
        // تسجيل النجاح وإرسال رسالة نجاح إلى العميل (مثل ESP32).
        file_put_contents($logFile, "SUCCESS: Data written to data.json\n", FILE_APPEND);
        echo "Data Updated Successfully";
    } else {
        // Log the error if writing fails, which is often a file permission issue.
        // تسجيل الخطأ في حالة فشل الكتابة، والذي غالبًا ما يكون مشكلة في أذونات الملف.
        file_put_contents($logFile, "ERROR: Could not write to data.json. Check permissions!\n", FILE_APPEND);
        echo "Error: Write Permission Denied";
    }
} else {
    // If no 'temp' data is found, log the error and inform the client.
    // إذا لم يتم العثور على بيانات 'temp'، قم بتسجيل الخطأ وإبلاغ العميل.
    file_put_contents($logFile, "ERROR: No data in request keys (temp/hum/aqi)\n", FILE_APPEND);
    echo "No Data Received";
}
?>