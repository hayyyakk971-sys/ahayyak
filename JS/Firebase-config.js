// Firebase Configuration
// إعدادات Firebase

// Get your config from: https://console.firebase.google.com/ -> Project Settings
// احصل على إعداداتك من: https://console.firebase.google.com/ -> إعدادات المشروع

const firebaseConfig = {
  apiKey: "AIzaSyA_1w0nuKk7xY7dud7jh7L7qrumSgd6o0A", // API key for Firebase // مفتاح API لـ Firebase
  authDomain: "hayyak-e06f4.firebaseapp.com",      // Authentication domain // نطاق المصادقة
  projectId: "hayyak-e06f4",                        // Project ID // معرف المشروع
  storageBucket: "hayyak-e06f4.firebasestorage.app", // Storage bucket // حاوية التخزين
  messagingSenderId: "713932423700",                 // Sender ID for messaging // معرف المرسل للرسائل
  appId: "1:713932423700:web:14f25f884cd96082055d01", // App ID // معرف التطبيق
  measurementId: "G-QEQX9FLCWS"                       // Measurement ID for analytics // معرف القياس للتحليلات
};

// Initialize Firebase // تهيئة Firebase
firebase.initializeApp(firebaseConfig); // Initializes the Firebase app with the config // تهيئة تطبيق Firebase بالإعدادات

// Get Firebase services // الحصول على خدمات Firebase
const auth = firebase.auth();      // Authentication service // خدمة المصادقة
const db = firebase.firestore();   // Firestore database // قاعدة بيانات Firestore
const storage = firebase.storage(); // Cloud Storage // التخزين السحابي

// Enable offline persistence for Firestore // تمكين الاستمرارية دون اتصال لـ Firestore
firebase.firestore().enablePersistence()
  .catch((err) => { // Handle errors // معالجة الأخطاء
    if (err.code == 'failed-precondition') {
      // Multiple tabs open, persistence can only be enabled in one tab at a time.
      // نوافذ متعددة مفتوحة، يمكن تمكين الاستمرارية في نافذة واحدة فقط في كل مرة.
      console.log('Multiple tabs open, persistence can only be enabled in one tab at a time.');
    } else if (err.code == 'unimplemented') {
      // The current browser does not support offline persistence
      // المتصفح الحالي لا يدعم الاستمرارية دون اتصال
      console.log('The current browser does not support offline persistence');
    }
  });

// Authentication State Change Listener // مستمع تغيير حالة المصادقة
auth.onAuthStateChanged((user) => {
  if (user) {
    // User is logged in // المستخدم مسجل الدخول
    console.log('User logged in:', user.uid); // Log user ID // تسجيل معرف المستخدم
    // Call any functions that depend on user being logged in
    // استدعاء أي دوال تعتمد على تسجيل دخول المستخدم
    if (window.onUserLoggedIn) {
      onUserLoggedIn(user); // Execute callback if defined // تنفيذ الدالة المعرفة إذا كانت موجودة
    }
  } else {
    // User is logged out // المستخدم غير مسجل الدخول
    console.log('User logged out'); // Log logout // تسجيل الخروج
    // Redirect to login if needed // إعادة التوجيه إلى صفحة تسجيل الدخول إذا لزم الأمر
    if (window.location.pathname.includes('profile') || 
        window.location.pathname.includes('home')) {
      window.location.href = '../index.html'; // Redirect to index // إعادة التوجيه إلى الصفحة الرئيسية
    }
  }
});