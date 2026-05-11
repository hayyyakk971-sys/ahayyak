// Helper to create map link // دالة مساعدة لإنشاء رابط الخريطة
const getMapLink = (lat, lon, name, rating, price, time) =>
  `map.html?lat=${lat}&lon=${lon}&name=${encodeURIComponent(name)}&rating=${rating||''}&price=${encodeURIComponent(price||'')}&time=${encodeURIComponent(time||'')}`; // Constructs URL with parameters for map page // بناء عنوان URL مع معاملات لصفحة الخريطة

// Central database of places for AI context with coordinates // قاعدة بيانات مركزية للأماكن مع إحداثيات للسياق الذكي
const HAYYAK_PLACES = [];

// Generate links for all places // إنشاء روابط لجميع الأماكن
HAYYAK_PLACES.forEach(p => { // Loop through each place // التكرار على كل مكان
  if (p.lat && p.lon) { // If coordinates exist // إذا كانت الإحداثيات موجودة
    p.link = getMapLink(p.lat, p.lon, p.name, p.rating, p.price, p.time); // Create map link using helper // إنشاء رابط الخريطة باستخدام الدالة المساعدة
  } else {
    p.link = p.page || '#'; // Otherwise fallback to page link or # // وإلا استخدام رابط الصفحة أو #
  }
});

/**
 * Fetch approved places from the PHP API
 * @param {Object} filters - Optional filters: category, city, emirate, q (search text)
 * @returns {Promise<Array>}
 */
async function fetchPlacesFromAPI(filters = {}) {
  const params = new URLSearchParams();
  if (filters.category) params.set('category', filters.category);
  if (filters.city)     params.set('city', filters.city);
  if (filters.emirate)  params.set('emirate', filters.emirate);
  if (filters.q)        params.set('q', filters.q);
  try {
    const res  = await fetch('/api/places/index.php?' + params.toString(), { credentials: 'include' });
    const json = await res.json();
    return json.ok ? json.data : [];
  } catch (_) { return []; }
}

/**
 * Search for places in the local database // البحث عن أماكن في قاعدة البيانات المحلية
 * @param {string} query - The search text // نص البحث
 * @returns {Array} - Array of matching place objects // مصفوفة من كائنات الأماكن المطابقة
 */
function searchWebsitePlaces(query) {
  if (!query || query.length < 2) return []; // Return empty if query too short // إرجاع فارغ إذا كان الاستعلام قصيراً جداً
  const q = query.toLowerCase(); // Convert to lowercase for case‑insensitive search // تحويل إلى أحرف صغيرة للبحث غير حساس لحالة الأحرف

  return HAYYAK_PLACES.filter(p => { // Filter places // تصفية الأماكن
    // 1. Standard search: Place property contains query // بحث عادي: خاصية المكان تحتوي على الاستعلام
    if ((p.name && p.name.toLowerCase().includes(q)) ||
        (p.nameAr && p.nameAr.includes(q)) ||
        (p.desc && p.desc.toLowerCase().includes(q)) ||
        (p.category && p.category.toLowerCase().includes(q)) ||
        (p.city && p.city.toLowerCase().includes(q))) {
      return true; // Include if any property matches // تضمين إذا تطابقت أي خاصية
    }
    // 2. Context search: Query contains city or category (e.g. "Plan in Dubai") // بحث سياقي: الاستعلام يحتوي على مدينة أو فئة (مثل "خطة في دبي")
    if (p.city && q.includes(p.city)) return true; // Match city // تطابق المدينة
    if (p.city === 'abudhabi' && q.includes('abu dhabi')) return true; // Special case for Abu Dhabi // حالة خاصة لأبوظبي
    if (p.city === 'rak' && (q.includes('ras al khaimah') || q.includes('ras alkhaimah'))) return true; // Special case for RAK // حالة خاصة لرأس الخيمة
    if (p.city === 'uaq' && (q.includes('umm al quwain') || q.includes('umm alquwain'))) return true; // Special case for UAQ // حالة خاصة لأم القيوين
    if (p.category && q.includes(p.category.toLowerCase())) return true; // Match category // تطابق الفئة

    return false; // Exclude if no match // استبعاد إذا لم يطابق
  }).map(p => ({ // Map to a simplified object // تحويل إلى كائن مبسط
    name: p.name,
    nameAr: p.nameAr,
    description: p.desc,
    type: p.category,
    link: p.link,
    city: p.city,
    lat: p.lat,
    lon: p.lon,
    rating: p.rating,
    price: p.price,
    time: p.time
  }));
}
