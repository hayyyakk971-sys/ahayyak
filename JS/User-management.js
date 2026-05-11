/**
 * HAYYAK User Management — PHP/MySQL backend
 * Replaces Firebase Firestore with REST API calls via Auth.js helpers.
 */

class HAYYAKUserManager {
  constructor() {
    this.profile  = {};
    this.friends  = {};
    this.initialized = false;
  }

  async init() {
    const res = await apiGet('/api/auth/check.php');
    if (!res.ok || !res.data.logged_in) return false;
    this.user = res.data.user;
    await this.loadUserProfile();
    await this.loadFriendsAndSettings();
    this.initialized = true;
    return true;
  }

  // ── Profile ──────────────────────────────────────────────────────────────

  async createOrUpdateUserProfile(profileData) {
    const payload = {
      name_ar:            profileData.nameAr   || profileData.name_ar   || '',
      name_en:            profileData.nameEn   || profileData.name_en   || '',
      emirate:            profileData.emirate  || '',
      bio:                profileData.bio      || '',
      avatar_color:       profileData.avatarColor || profileData.avatar_color || '#16243d',
      is_healthy:         profileData.isHealthy ?? profileData.is_healthy ?? false,
      phobias:            profileData.phobias  || [],
      allergies:          profileData.allergies || [],
      food_preferences:   profileData.foodPreferences || profileData.food_preferences || [],
      medical_conditions: profileData.healthConditions || profileData.medical_conditions || [],
      interests:          profileData.interests || [],
      language:           profileData.language || 'ar',
      theme:              profileData.theme    || 'light',
    };

    const res = await apiPut('/api/users/profile.php', payload);
    if (!res.ok) throw new Error(res.error || 'Profile update failed');

    // Sync avatar separately if present
    if (profileData.avatar) {
      await apiPost('/api/users/avatar.php', { avatar: profileData.avatar });
      localStorage.setItem('hayyak_user_avatar', profileData.avatar);
    }

    // Keep localStorage cache fresh
    const merged = { ...this.profile, ...payload };
    this.profile = merged;
    setLocalProfile(merged);
    return merged;
  }

  async loadUserProfile() {
    const res = await apiGet('/api/users/profile.php');
    if (!res.ok) return;
    this.profile = res.data;
    setLocalProfile(res.data);
    if (res.data.avatar_data) {
      localStorage.setItem('hayyak_user_avatar', res.data.avatar_data);
    }
  }

  // ── User search ───────────────────────────────────────────────────────────

  async getUserById(userId) {
    const res = await apiGet('/api/users/search.php?q=' + encodeURIComponent(userId));
    return res.ok && res.data.length ? res.data[0] : null;
  }

  async getUserByEmail(email) {
    const res = await apiGet('/api/users/search.php?q=' + encodeURIComponent(email));
    return res.ok && res.data.length ? res.data[0] : null;
  }

  async searchUsers(searchTerm) {
    if (!searchTerm || searchTerm.length < 2) return [];
    const res = await apiGet('/api/users/search.php?q=' + encodeURIComponent(searchTerm));
    return res.ok ? res.data : [];
  }

  // ── Friends ───────────────────────────────────────────────────────────────

  async loadFriendsAndSettings() {
    const res = await apiGet('/api/friends/index.php');
    if (!res.ok) return;
    this.friends = {};
    for (const f of res.data) {
      this.friends[f.id] = {
        ...f,
        privacySettings: this.getDefaultPrivacySettings(),
      };
    }
  }

  async sendFriendRequest(targetUserId) {
    const res = await apiPost('/api/friends/index.php', { target_user_id: Number(targetUserId) });
    if (!res.ok) throw new Error(res.error || 'Could not send request');
    return true;
  }

  async acceptFriendRequest(friendshipId) {
    const res = await apiPost('/api/friends/respond.php', { friendship_id: Number(friendshipId), action: 'accept' });
    if (!res.ok) throw new Error(res.error || 'Could not accept request');
    return true;
  }

  async declineFriendRequest(friendshipId) {
    const res = await apiPost('/api/friends/respond.php', { friendship_id: Number(friendshipId), action: 'decline' });
    if (!res.ok) throw new Error(res.error || 'Could not decline request');
    return true;
  }

  async removeFriend(friendId) {
    const res = await apiDelete('/api/friends/remove.php', { friend_id: Number(friendId) });
    if (!res.ok) throw new Error(res.error || 'Could not remove friend');
    delete this.friends[friendId];
    return true;
  }

  async getFriendRequests() {
    const res = await apiGet('/api/friends/requests.php');
    return res.ok ? res.data : [];
  }

  async getPendingRequestsCount() {
    const requests = await this.getFriendRequests();
    return requests.length;
  }

  // ── Privacy ───────────────────────────────────────────────────────────────

  getDefaultPrivacySettings() {
    return {
      share_health_conditions: false,
      share_food_preferences:  false,
      share_phobias:           false,
      share_allergies:         false,
      share_bio:               true,
      share_avatar:            true,
      share_emirate_location:  true,
      share_contact_info:      false,
    };
  }

  async updatePrivacySettings(friendId, settings) {
    const res = await apiPut('/api/friends/privacy.php?friend_id=' + friendId, settings);
    if (!res.ok) throw new Error(res.error || 'Could not update privacy');
    if (this.friends[friendId]) {
      this.friends[friendId].privacySettings = settings;
    }
    return true;
  }

  getSharedDataForFriend(friendId) {
    const friend  = this.friends[friendId];
    if (!friend) return null;
    const privacy = friend.privacySettings || this.getDefaultPrivacySettings();
    const shared  = {};
    if (privacy.share_avatar)            shared.avatar   = friend.avatar_color;
    if (privacy.share_bio)               shared.bio      = friend.bio;
    if (privacy.share_emirate_location)  shared.emirate  = friend.emirate;
    if (privacy.share_health_conditions) shared.medical_conditions = friend.medical_conditions;
    if (privacy.share_food_preferences)  shared.food_preferences   = friend.food_preferences;
    if (privacy.share_phobias)           shared.phobias  = friend.phobias;
    if (privacy.share_allergies)         shared.allergies = friend.allergies;
    return shared;
  }

  // ── Sync helper (kept for backward compat) ───────────────────────────────
  async syncData() {
    const local = getLocalProfile();
    if (!local) return false;
    try {
      await this.createOrUpdateUserProfile(local);
      return true;
    } catch (_) { return false; }
  }
}

// Global instance
let userManager = null;

async function initializeUserManager() {
  userManager = new HAYYAKUserManager();
  return await userManager.init();
}

function getUserManager() {
  return userManager;
}
