/**
 * HAYYAK Profile Enhancement — Friend & Privacy Management
 * Uses PHP API via Auth.js helpers (no Firebase).
 */

let currentFriendsView = 'friends';
let currentUser        = null;
let userMgr            = null;

async function initializeProfileEnhancement() {
  try {
    await initializeUserManager();
    userMgr     = getUserManager();
    currentUser = userMgr?.user || null;

    if (userMgr && currentUser) {
      setupFriendManagementUI();
      setupPrivacySettingsUI();
      await loadFriendsUI();
      await renderLeaderboard();
    }
  } catch (error) {
    console.error('Error initializing profile enhancement:', error);
  }
}

function setupFriendManagementUI() {
  if (!document.getElementById('friends-list')) return;
  createFriendsSection();
}

function createFriendsSection() {
  const friendsSection = document.getElementById('friends-list');
  if (!friendsSection) return;

  friendsSection.innerHTML = `
    <div class="friends-management" style="margin-top:20px;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h3 style="margin:0;font-size:18px;color:var(--primary);">إدارة الأصدقاء</h3>
        <button id="show-qr-btn" class="submit-btn" style="width:auto;padding:8px 16px;font-size:13px;display:flex;align-items:center;gap:6px;">
          <span>📱</span> بطاقتي
        </button>
      </div>

      <div class="friend-search" style="margin-bottom:20px;">
        <div style="display:flex;gap:8px;margin-bottom:12px;">
          <input type="text" id="friend-search-input" class="form-input" placeholder="ابحث عن صديق..." style="flex:1;padding:10px 12px;font-size:14px;">
          <button id="search-friend-btn" class="submit-btn" style="width:auto;padding:10px 16px;margin:0;font-size:14px;">🔍</button>
        </div>
      </div>

      <div class="friend-tabs" style="display:flex;gap:8px;margin-bottom:16px;border-bottom:2px solid var(--line);padding-bottom:8px;">
        <button data-view="friends"  class="friend-tab-btn active" style="flex:1;padding:8px;background:transparent;border:none;cursor:pointer;font-weight:600;color:var(--primary);border-bottom:3px solid var(--primary);margin-bottom:-8px;padding-bottom:8px;">👥 أصدقاء</button>
        <button data-view="requests" class="friend-tab-btn"        style="flex:1;padding:8px;background:transparent;border:none;cursor:pointer;font-weight:600;color:var(--muted);border-bottom:3px solid transparent;margin-bottom:-8px;padding-bottom:8px;">📨 الطلبات</button>
        <button data-view="search"   class="friend-tab-btn"        style="flex:1;padding:8px;background:transparent;border:none;cursor:pointer;font-weight:600;color:var(--muted);border-bottom:3px solid transparent;margin-bottom:-8px;padding-bottom:8px;">🔍 بحث</button>
      </div>

      <div id="friends-view"  class="friend-view-panel" style="display:block;"><div id="friends-list-container"  style="display:grid;gap:12px;"></div></div>
      <div id="requests-view" class="friend-view-panel" style="display:none;"><div id="requests-list-container" style="display:grid;gap:12px;"></div></div>
      <div id="search-view"   class="friend-view-panel" style="display:none;"><div id="search-results-container" style="display:grid;gap:12px;"></div></div>
    </div>
  `;

  document.querySelectorAll('.friend-tab-btn').forEach(btn => {
    btn.addEventListener('click', e => switchFriendView(e.currentTarget.dataset.view));
  });

  document.getElementById('search-friend-btn')?.addEventListener('click', handleFriendSearch);
  document.getElementById('friend-search-input')?.addEventListener('keydown', e => { if (e.key === 'Enter') handleFriendSearch(); });
  document.getElementById('show-qr-btn')?.addEventListener('click', showUserQRCode);
}

async function switchFriendView(view) {
  currentFriendsView = view;
  document.querySelectorAll('.friend-tab-btn').forEach(btn => {
    const active = btn.dataset.view === view;
    btn.style.borderBottomColor = active ? 'var(--primary)' : 'transparent';
    btn.style.color              = active ? 'var(--primary)' : 'var(--muted)';
  });
  document.querySelectorAll('.friend-view-panel').forEach(p => p.style.display = 'none');
  document.getElementById(view + '-view').style.display = 'block';

  if (view === 'friends')  await loadFriendsUI();
  if (view === 'requests') await loadFriendRequests();
}

async function loadFriendsUI() {
  const container = document.getElementById('friends-list-container');
  if (!container || !userMgr) return;
  await userMgr.loadFriendsAndSettings();
  const friends = userMgr.friends || {};

  if (!Object.keys(friends).length) {
    container.innerHTML = `<div style="text-align:center;padding:40px 20px;color:var(--muted);"><div style="font-size:48px;margin-bottom:12px;">👥</div><p>لا توجد أصدقاء حالياً</p></div>`;
    return;
  }

  container.innerHTML = Object.entries(friends).map(([friendId, friend]) => `
    <div class="friend-card" style="border:1px solid var(--line);border-radius:12px;padding:12px;display:flex;justify-content:space-between;align-items:center;background:var(--card);">
      <div style="display:flex;align-items:center;gap:12px;flex:1;">
        <div style="width:48px;height:48px;border-radius:50%;background:${friend.avatar_color||'#16243d'};display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:18px;">${(friend.name_ar||'?').charAt(0)}</div>
        <div><div style="font-weight:600;font-size:14px;">${friend.name_ar||friend.name_en||'Friend'}</div><div style="font-size:12px;color:var(--muted);">${friend.emirate||'UAE'}</div></div>
      </div>
      <div style="display:flex;gap:8px;">
        <button class="privacy-settings-btn" data-friend-id="${friendId}" title="إعدادات الخصوصية" style="background:var(--accent);color:var(--primary);border:none;border-radius:50%;width:36px;height:36px;cursor:pointer;">🔒</button>
        <button class="remove-friend-btn"    data-friend-id="${friendId}" title="إزالة صديق"       style="background:#ef4444;color:white;border:none;border-radius:50%;width:36px;height:36px;cursor:pointer;">✕</button>
      </div>
    </div>
  `).join('');

  container.querySelectorAll('.remove-friend-btn').forEach(btn => btn.addEventListener('click', () => removeFriend(btn.dataset.friendId)));
  container.querySelectorAll('.privacy-settings-btn').forEach(btn => btn.addEventListener('click', () => openPrivacyModal(btn.dataset.friendId)));
}

async function loadFriendRequests() {
  const container = document.getElementById('requests-list-container');
  if (!container || !userMgr) return;
  const requests = await userMgr.getFriendRequests();

  if (!requests.length) {
    container.innerHTML = `<div style="text-align:center;padding:40px 20px;color:var(--muted);"><div style="font-size:48px;margin-bottom:12px;">📨</div><p>لا توجد طلبات صداقة</p></div>`;
    return;
  }

  container.innerHTML = requests.map(req => `
    <div class="request-card" style="border:1px solid #f59e0b;border-left:4px solid #f59e0b;border-radius:8px;padding:12px;background:rgba(245,158,11,0.05);">
      <div style="font-weight:600;font-size:14px;margin-bottom:8px;">${req.name_ar||req.name_en||'User'}</div>
      <div style="display:flex;gap:8px;">
        <button class="accept-request-btn" data-fid="${req.friendship_id}" style="flex:1;background:#10b981;color:white;border:none;border-radius:6px;padding:8px;font-size:13px;font-weight:600;cursor:pointer;">✓ قبول</button>
        <button class="decline-request-btn" data-fid="${req.friendship_id}" style="flex:1;background:transparent;color:var(--primary);border:1px solid var(--line);border-radius:6px;padding:8px;font-size:13px;font-weight:600;cursor:pointer;">✕ رفض</button>
      </div>
    </div>
  `).join('');

  container.querySelectorAll('.accept-request-btn').forEach(btn => btn.addEventListener('click', async () => {
    await userMgr.acceptFriendRequest(btn.dataset.fid);
    showNotification && showNotification('تم قبول الطلب', 'info');
    await loadFriendRequests();
  }));
  container.querySelectorAll('.decline-request-btn').forEach(btn => btn.addEventListener('click', async () => {
    await userMgr.declineFriendRequest(btn.dataset.fid);
    showNotification && showNotification('تم رفض الطلب', 'info');
    await loadFriendRequests();
  }));
}

async function handleFriendSearch() {
  const searchTerm = document.getElementById('friend-search-input')?.value.trim();
  if (!searchTerm || !userMgr) return;
  const results    = await userMgr.searchUsers(searchTerm);
  const container  = document.getElementById('search-results-container');
  if (!container) return;

  await switchFriendView('search');

  if (!results.length) {
    container.innerHTML = `<div style="text-align:center;padding:20px;color:var(--muted);">لم يتم العثور على نتائج</div>`;
    return;
  }

  container.innerHTML = results.map(u => `
    <div class="search-result" style="border:1px solid var(--line);border-radius:12px;padding:12px;display:flex;justify-content:space-between;align-items:center;background:var(--card);">
      <div style="display:flex;align-items:center;gap:12px;flex:1;">
        <div style="width:48px;height:48px;border-radius:50%;background:${u.avatar_color||'#16243d'};display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:18px;">${(u.name_ar||'?').charAt(0)}</div>
        <div><div style="font-weight:600;font-size:14px;">${u.name_ar||u.name_en||'User'}</div><div style="font-size:12px;color:var(--muted);">${u.emirate||'UAE'}</div></div>
      </div>
      <button class="add-friend-btn" data-uid="${u.id}" style="background:var(--primary);color:var(--accent);border:none;border-radius:6px;padding:8px 12px;font-size:13px;font-weight:600;cursor:pointer;">+ إضافة</button>
    </div>
  `).join('');

  container.querySelectorAll('.add-friend-btn').forEach(btn => btn.addEventListener('click', async () => {
    try {
      await userMgr.sendFriendRequest(btn.dataset.uid);
      showNotification && showNotification('تم إرسال طلب الصداقة', 'info');
      btn.disabled = true;
      btn.textContent = '✓ أُرسل';
    } catch (e) {
      showNotification && showNotification('حدث خطأ: ' + e.message, 'error');
    }
  }));
}

async function removeFriend(friendId) {
  if (!confirm('هل أنت متأكد من حذف هذا الصديق؟') || !userMgr) return;
  try {
    await userMgr.removeFriend(friendId);
    showNotification && showNotification('تم حذف الصديق', 'info');
    await loadFriendsUI();
  } catch (_) {
    showNotification && showNotification('حدث خطأ', 'error');
  }
}

function showUserQRCode() {
  if (!currentUser) return;
  const qrData = JSON.stringify({ id: currentUser.id, name: currentUser.name_ar||currentUser.name_en });
  const qrUrl  = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(qrData)}&color=16243d`;

  document.body.insertAdjacentHTML('beforeend', `
    <div id="qr-modal" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:3000;display:flex;align-items:center;justify-content:center;padding:20px;">
      <div style="background:var(--card);border:2px solid var(--line);border-radius:20px;width:100%;max-width:320px;padding:30px 20px;text-align:center;position:relative;">
        <button onclick="document.getElementById('qr-modal').remove()" style="position:absolute;top:10px;right:10px;background:none;border:none;font-size:24px;cursor:pointer;color:var(--muted);">✕</button>
        <h3 style="margin:0 0 4px;color:var(--primary);">${currentUser.name_ar||currentUser.name_en||''}</h3>
        <p style="margin:0 0 20px;color:var(--muted);font-size:14px;">${currentUser.emirate||'UAE'}</p>
        <div style="background:white;padding:16px;border-radius:12px;display:inline-block;">
          <img src="${qrUrl}" alt="QR Code" style="width:180px;height:180px;display:block;">
        </div>
        <p style="margin:20px 0 0;font-size:13px;color:var(--muted);">امسح الرمز لإضافة صديق</p>
      </div>
    </div>
  `);
}

function setupPrivacySettingsUI() { /* modal created on demand */ }

async function openPrivacyModal(friendId) {
  const friend = userMgr?.friends?.[friendId];
  if (!friend) return;
  const privacy = friend.privacySettings || userMgr.getDefaultPrivacySettings();

  const row = (key, label) => `
    <div style="display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid rgba(0,0,0,0.05);">
      <input type="checkbox" class="privacy-checkbox" data-setting="${key}" ${privacy[key] ? 'checked' : ''} style="width:18px;height:18px;cursor:pointer;accent-color:var(--primary);">
      <label style="flex:1;cursor:pointer;font-size:14px;font-weight:500;">${label}</label>
    </div>`;

  document.body.insertAdjacentHTML('beforeend', `
    <div id="privacy-modal" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:3000;display:flex;align-items:center;justify-content:center;padding:20px;">
      <div style="background:var(--card);border:2px solid var(--line);border-radius:var(--radius);width:100%;max-width:400px;padding:20px;max-height:80vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
          <h2 style="margin:0;font-size:18px;color:var(--primary);">🔒 إعدادات الخصوصية</h2>
          <button onclick="document.getElementById('privacy-modal').remove()" style="background:none;border:none;font-size:24px;cursor:pointer;color:var(--muted);">✕</button>
        </div>
        <div style="margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid var(--line);">
          <div style="font-weight:600;color:var(--primary);margin-bottom:4px;">${friend.name_ar||friend.name_en}</div>
          <div style="font-size:12px;color:var(--muted);">تحكم في البيانات المشتركة مع هذا الصديق</div>
        </div>
        ${row('share_avatar','الصورة الشخصية 📸')}
        ${row('share_bio','السيرة الذاتية ✍️')}
        ${row('share_emirate_location','الموقع الجغرافي 🗺️')}
        ${row('share_health_conditions','الحالات الصحية 🏥')}
        ${row('share_food_preferences','تفضيلات الطعام 🍔')}
        ${row('share_allergies','الحساسيات 🥜')}
        ${row('share_phobias','المخاوف 😨')}
        ${row('share_contact_info','معلومات التواصل 📱')}
      </div>
    </div>
  `);

  document.querySelectorAll('#privacy-modal .privacy-checkbox').forEach(cb => {
    cb.addEventListener('change', async e => {
      const updated = { ...privacy, [e.target.dataset.setting]: e.target.checked };
      try {
        await userMgr.updatePrivacySettings(friendId, updated);
        privacy[e.target.dataset.setting] = e.target.checked;
        showNotification && showNotification('تم تحديث الإعدادات', 'info');
      } catch (_) {
        showNotification && showNotification('حدث خطأ', 'error');
        e.target.checked = !e.target.checked;
      }
    });
  });
}

async function renderLeaderboard() {
  const container = document.getElementById('leaderboard-list');
  if (!container || !userMgr || !currentUser) {
    if (container) container.innerHTML = `<div style="text-align:center;padding:20px;color:var(--muted);">لا يمكن تحميل لوحة الصدارة</div>`;
    return;
  }

  const localAchievements = JSON.parse(localStorage.getItem('hayyak_unlocked_achievements') || '[]');
  const leaderboard = [{
    id: currentUser.id,
    name: currentUser.name_ar || currentUser.name_en || 'أنت',
    avatar_color: currentUser.avatar_color || '#16243d',
    achievementCount: localAchievements.length,
    isCurrentUser: true,
  }];

  await userMgr.loadFriendsAndSettings();
  for (const f of Object.values(userMgr.friends || {})) {
    leaderboard.push({
      id: f.id, name: f.name_ar || f.name_en || 'Friend',
      avatar_color: f.avatar_color || '#16243d',
      achievementCount: (f.unlocked_achievements || []).length,
      isCurrentUser: false,
    });
  }

  leaderboard.sort((a, b) => b.achievementCount - a.achievementCount);

  if (leaderboard.length <= 1) {
    container.innerHTML = `<div style="text-align:center;padding:20px;color:var(--muted);">أضف أصدقاء للمنافسة!</div>`;
    return;
  }

  container.innerHTML = leaderboard.map((p, i) => {
    const rank = i === 0 ? '🥇' : i === 1 ? '🥈' : i === 2 ? '🥉' : i + 1;
    return `
      <div class="leaderboard-item" ${p.isCurrentUser ? 'style="background:rgba(212,175,55,0.1);"' : ''}>
        <div class="leaderboard-rank">${rank}</div>
        <div class="leaderboard-avatar" style="background-color:${p.avatar_color}"><span>${(p.name||'U').charAt(0)}</span></div>
        <div class="leaderboard-info">
          <div class="leaderboard-name">${p.name}</div>
          <div class="leaderboard-achievements">🏆 ${p.achievementCount} إنجازات</div>
        </div>
        <div class="leaderboard-score">${p.achievementCount * 10}</div>
      </div>`;
  }).join('');
}

document.addEventListener('DOMContentLoaded', () => {
  setTimeout(initializeProfileEnhancement, 300);
});
