// Auth.js — replaces Firebase-config.js
// All communication uses PHP session cookies + CSRF tokens.

const API_BASE = '';

// ── CSRF ────────────────────────────────────────────────────────────────────
let _csrfToken = null;

async function getCsrfToken() {
  if (_csrfToken) return _csrfToken;
  const res  = await fetch(API_BASE + '/api/auth/csrf.php', { credentials: 'include' });
  const json = await res.json();
  _csrfToken = json.data?.token || '';
  return _csrfToken;
}

function _invalidateCsrf() { _csrfToken = null; }

// ── Fetch helpers ────────────────────────────────────────────────────────────
async function apiGet(path) {
  const res = await fetch(API_BASE + path, { credentials: 'include' });
  return res.json();
}

async function apiPost(path, data = {}) {
  const token = await getCsrfToken();
  const res   = await fetch(API_BASE + path, {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
    body: JSON.stringify(data),
  });
  return res.json();
}

async function apiPut(path, data = {}) {
  const token = await getCsrfToken();
  const res   = await fetch(API_BASE + path, {
    method: 'PUT',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
    body: JSON.stringify(data),
  });
  return res.json();
}

async function apiDelete(path, data = {}) {
  const token = await getCsrfToken();
  const res   = await fetch(API_BASE + path, {
    method: 'DELETE',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
    body: JSON.stringify(data),
  });
  return res.json();
}

// ── Auth state ───────────────────────────────────────────────────────────────
async function checkAuth(requiredRole = null) {
  const res = await apiGet('/api/auth/check.php');
  if (!res.ok || !res.data.logged_in) {
    window.location.href = 'Index.html';
    return null;
  }
  if (requiredRole && res.data.user.role !== requiredRole) {
    window.location.href = 'Index.html';
    return null;
  }
  return res.data.user;
}

async function logout() {
  try {
    await apiPost('/api/auth/logout.php');
  } catch (_) {}
  _invalidateCsrf();
  // Clear all hayyak_ localStorage keys
  Object.keys(localStorage)
    .filter(k => k.startsWith('hayyak_'))
    .forEach(k => localStorage.removeItem(k));
  window.location.href = 'Index.html';
}

// ── Local profile cache ──────────────────────────────────────────────────────
function getLocalProfile() {
  try {
    const raw = localStorage.getItem('hayyak_user_profile');
    return raw ? JSON.parse(raw) : null;
  } catch (_) { return null; }
}

function setLocalProfile(data) {
  localStorage.setItem('hayyak_user_profile', JSON.stringify(data));
}
