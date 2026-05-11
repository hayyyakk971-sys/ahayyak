// Achievements — conditions stay localStorage-based; sync goes to PHP API

const achievements = [
  { id: 1,  name: 'getting_started',  icon: '🚀', arName: 'البداية',          enName: 'Getting Started',   arDesc: 'إنشاء حسابك الأول على HAYYAK',                          enDesc: 'Create your first account on HAYYAK',             condition: () => getLocalProfile() !== null },
  { id: 2,  name: 'avatar_set',       icon: '📸', arName: 'صورة شخصية',       enName: 'Profile Picture',    arDesc: 'إضافة صورة شخصية لملفك',                               enDesc: 'Add a profile picture',                           condition: () => !!localStorage.getItem('hayyak_user_avatar') },
  { id: 3,  name: 'full_name',        icon: '🎭', arName: 'الاسم الكامل',      enName: 'Full Name',          arDesc: 'إدخال اسمك الكامل بالعربية والإنجليزية',               enDesc: 'Enter your full name in Arabic and English',      condition: () => { const p = getLocalProfile() || {}; return (p.name_ar||p.nameAr||'').length > 2 && (p.name_en||p.nameEn||'').length > 2; } },
  { id: 4,  name: 'bio_written',      icon: '📝', arName: 'نبذة شخصية',       enName: 'Bio Written',        arDesc: 'كتابة نبذة شخصية (10 أحرف على الأقل)',                  enDesc: 'Write a bio (at least 10 characters)',            condition: () => { const p = getLocalProfile() || {}; return (p.bio||'').length > 10; } },
  { id: 5,  name: 'tags_collector',   icon: '🏷️', arName: 'جامع العلامات',    enName: 'Tag Collector',      arDesc: 'إضافة 5 أو أكثر من الحساسية والطعام والمخاوف',         enDesc: 'Add 5 or more allergies, food prefs, or phobias', condition: () => { const p = getLocalProfile() || {}; return ((p.allergies||[]).length + (p.food_preferences||p.foodPreferences||[]).length + (p.phobias||[]).length) >= 5; } },
  { id: 6,  name: 'health_conscious', icon: '🏥', arName: 'واعي صحيا',        enName: 'Health Conscious',   arDesc: 'إضافة مشاكل صحية إلى ملفك',                            enDesc: 'Add health conditions to your profile',           condition: () => { const p = getLocalProfile() || {}; return (p.medical_conditions||p.healthConditions||[]).length > 0; } },
  { id: 7,  name: 'complete_profile', icon: '✨', arName: 'ملف كامل',          enName: 'Complete Profile',   arDesc: 'ملء 80% من معلومات ملفك الشخصي',                       enDesc: 'Complete 80% of your profile information',        condition: () => {
      const p = getLocalProfile() || {};
      let done = 0;
      if ((p.name_ar||p.nameAr||'').length > 2 && (p.name_en||p.nameEn||'').length > 2) done++;
      if (p.emirate) done++;
      if ((p.bio||'').length > 10) done++;
      if (localStorage.getItem('hayyak_user_avatar')) done++;
      if ((p.allergies||[]).length > 0) done++;
      if ((p.food_preferences||p.foodPreferences||[]).length > 0) done++;
      if ((p.phobias||[]).length > 0) done++;
      if ((p.medical_conditions||p.healthConditions||[]).length > 0) done++;
      return Math.round((done / 8) * 100) >= 80;
  }},
  { id: 8,  name: 'night_owl',       icon: '🌙', arName: 'معشر الليل',        enName: 'Night Owl',          arDesc: 'تفعيل الوضع الليلي',                                    enDesc: 'Enable dark mode',                               condition: () => localStorage.getItem('hayyak_has_used_dark_mode') === 'true' },
  { id: 9,  name: 'social_butterfly',icon: '👥', arName: 'فراشة اجتماعية',   enName: 'Social Butterfly',   arDesc: 'إضافة صديق',                                            enDesc: 'Add a friend',                                   condition: () => { try { return JSON.parse(localStorage.getItem('hayyak_family') || '[]').length > 0; } catch(_){ return false; } } },
  { id: 10, name: 'survivor',        icon: '😷', arName: 'الناجي',             enName: 'The Survivor',       arDesc: 'تواجدت في مكان مزدحم بجودة هواء سيئة',                enDesc: 'Visited a crowded place with poor air quality',   condition: () => false },
];

let _syncPending = false;

async function checkAchievementsGlobal() {
  const p       = getLocalProfile() || {};
  const lang    = p.language || localStorage.getItem('hayyak_language') || 'ar';
  const unlocked = JSON.parse(localStorage.getItem('hayyak_unlocked_achievements') || '[]');
  let newUnlock  = false;

  achievements.forEach(a => {
    if (!unlocked.includes(a.id) && a.condition()) {
      unlocked.push(a.id);
      newUnlock = true;
      if (typeof showNotification === 'function') {
        showNotification(lang === 'ar' ? `🏆 إنجاز جديد: ${a.arName}` : `🏆 New Achievement: ${a.enName}`, 'success');
      }
    }
  });

  if (newUnlock) {
    localStorage.setItem('hayyak_unlocked_achievements', JSON.stringify(unlocked));

    // Sync to PHP API (fire-and-forget, skip if not authenticated)
    if (!_syncPending && typeof apiPut === 'function') {
      _syncPending = true;
      apiPut('/api/users/achievements.php', { unlocked }).catch(() => {}).finally(() => { _syncPending = false; });
    }

    if (typeof renderAchievements === 'function') renderAchievements();
    if (typeof renderLeaderboard  === 'function') renderLeaderboard();
  }
}

setInterval(checkAchievementsGlobal, 2000);
