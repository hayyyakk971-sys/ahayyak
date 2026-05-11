// ==========================================
// Chart Initialization File - HAYYAK Project // ملف تشغيل الرسوم البيانية - مشروع حياك
// ==========================================

// 1. Setup Temperature and Humidity Charts // 1. إعداد الرسم البياني للحرارة والرطوبة
let tempChart, humChart; // Variables to hold chart instances // متغيرات لتخزين مثيلات الرسم البياني

// Function to initialize charts // دالة لتهيئة الرسوم البيانية
function initCharts() {
    // Setup Temperature Chart // إعداد مخطط درجة الحرارة
    const ctxTemp = document.getElementById('tempChart').getContext('2d'); // Get canvas context // الحصول على سياق الرسم
    tempChart = new Chart(ctxTemp, {
        type: 'line', // Chart type // نوع المخطط
        data: {
            labels: [], // Time labels // تسميات الوقت
            datasets: [{
                label: 'درجة الحرارة °C', // Dataset label (Temperature) // تسمية مجموعة البيانات (درجة الحرارة)
                data: [], // Data points // نقاط البيانات
                borderColor: '#ef4444', // Line color (Red) // لون الخط (أحمر)
                backgroundColor: 'rgba(239, 68, 68, 0.1)', // Fill color // لون التعبئة
                borderWidth: 3, // Line width // عرض الخط
                tension: 0.4, // Curve tension // انحناء الخط
                fill: true // Enable fill under line // تفعيل التعبئة تحت الخط
            }]
        },
        options: {
            responsive: true, // Make chart responsive // جعل المخطط متجاوبًا
            scales: { y: { beginAtZero: false } } // Y-axis configuration // إعدادات المحور الصادي
        }
    });

    // Setup Humidity Chart // إعداد مخطط الرطوبة
    const ctxHum = document.getElementById('humChart').getContext('2d'); // Get canvas context // الحصول على سياق الرسم
    humChart = new Chart(ctxHum, {
        type: 'line', // Chart type // نوع المخطط
        data: {
            labels: [], // Time labels // تسميات الوقت
            datasets: [{
                label: 'الرطوبة %', // Dataset label (Humidity) // تسمية مجموعة البيانات (الرطوبة)
                data: [], // Data points // نقاط البيانات
                borderColor: '#3b82f6', // Line color (Blue) // لون الخط (أزرق)
                backgroundColor: 'rgba(59, 130, 246, 0.1)', // Fill color // لون التعبئة
                borderWidth: 3, // Line width // عرض الخط
                tension: 0.4, // Curve tension // انحناء الخط
                fill: true // Enable fill under line // تفعيل التعبئة تحت الخط
            }]
        },
        options: {
            responsive: true, // Make chart responsive // جعل المخطط متجاوبًا
            scales: { y: { beginAtZero: true, max: 100 } } // Y-axis configuration (0-100%) // إعدادات المحور الصادي (0-100%)
        }
    });
}

// 2. Function to update charts with new data // 2. دالة تحديث الرسوم بالبيانات الجديدة
function updateCharts(time, temp, hum) {
    if (!tempChart || !humChart) return; // Check if charts exist // التحقق من وجود الرسوم البيانية

    // Update Temperature Data // تحديث بيانات الحرارة
    tempChart.data.labels.push(time); // Add time label // إضافة وقت جديد
    tempChart.data.datasets[0].data.push(temp); // Add temperature value // إضافة قيمة درجة الحرارة

    // Update Humidity Data // تحديث بيانات الرطوبة
    humChart.data.labels.push(time); // Add time label // إضافة وقت جديد
    humChart.data.datasets[0].data.push(hum); // Add humidity value // إضافة قيمة الرطوبة

    // Remove old readings (keep only last 10) // حذف القراءات القديمة (نحتفظ بآخر 10 قراءات فقط)
    if (tempChart.data.labels.length > 10) {
        tempChart.data.labels.shift(); // Remove oldest label // إزالة أقدم تسمية
        tempChart.data.datasets[0].data.shift(); // Remove oldest temp data // إزالة أقدم بيانات حرارة
        humChart.data.labels.shift(); // Remove oldest label // إزالة أقدم تسمية
        humChart.data.datasets[0].data.shift(); // Remove oldest humidity data // إزالة أقدم بيانات رطوبة
    }

    tempChart.update('none'); // Fast update without heavy animation // تحديث سريع بدون أنيميشن ثقيل
    humChart.update('none'); // Fast update without heavy animation // تحديث سريع بدون أنيميشن ثقيل
}

// Initialize charts on page load // تشغيل الرسوم عند تحميل الصفحة
window.addEventListener('DOMContentLoaded', initCharts);