// js/apiClient.js

// Adres Twojego API (folder "api" w XAMPP)
const API_BASE_URL = "http://localhost/bank_api/api";

// --- Obsługa tokenów w localStorage ---

function saveTokens(access, refresh) {
  if (access) localStorage.setItem("access_token", access);
  if (refresh) localStorage.setItem("refresh_token", refresh);
}

function getAccessToken() {
  return localStorage.getItem("access_token");
}

function clearTokens() {
  localStorage.removeItem("access_token");
  localStorage.removeItem("refresh_token");
}

// --- Ogólna funkcja do requestów ---

async function apiRequest(endpoint, options = {}) {
  const url = API_BASE_URL + endpoint;

  const headers = options.headers ? { ...options.headers } : {};
  const token = getAccessToken();

  if (token) {
    headers["Authorization"] = "Bearer " + token;
  }
  if (!headers["Content-Type"] && options.method && options.method !== "GET") {
    headers["Content-Type"] = "application/json";
  }

  let response;
  try {
    response = await fetch(url, {
      method: options.method || "GET",
      headers,
      body: options.body ? JSON.stringify(options.body) : undefined,
    });
  } catch (e) {
    // Błąd połączenia z serwerem (np. XAMPP wyłączony)
    throw new Error("Błąd połączenia z API (serwer nie odpowiada)");
  }

  // Sesja wygasła / brak tokena
  if (response.status === 401) {
    clearTokens();
    window.location.href = "login.html";
    throw new Error("Sesja wygasła, zaloguj się ponownie");
  }

  let data = {};
  try {
    data = await response.json();
  } catch (e) {
    // brak JSON-a – traktujemy jako błąd ogólny
    if (!response.ok) {
      throw new Error("Błąd połączenia z API");
    }
    return {};
  }

  if (!response.ok) {
    const msg = data.message || data.error || "Błąd połączenia z API";
    throw new Error(msg);
  }

  return data;
}

// --- KONKRETNE ENDPOINTY ---
//
// Uwaga: nazwy dopasowane do plików z BANK_API/api/*
// które już masz w projekcie.

// POST /auth_login.php { email, password }
async function apiLogin(email, password) {
  const data = await apiRequest("/auth_login.php", {
    method: "POST",
    body: { email, password },
  });

  // Jeśli backend NIE wymaga 2FA – od razu zapisujemy tokeny
  if (!data.twofa_required && data.access && data.refresh) {
    saveTokens(data.access, data.refresh);
  }

  return data; // { twofa_required?, access?, refresh?, message?, debug_code? }
}

// POST /auth_register.php { username, email, password }
async function apiRegister(username, email, password) {
  return apiRequest("/auth_register.php", {
    method: "POST",
    body: { username, email, password },
  });
}

// POST /auth_change_password.php { old_password, new_password }
async function apiChangePassword(oldPassword, newPassword) {
  return apiRequest("/auth_change_password.php", {
    method: "POST",
    body: {
      old_password: oldPassword,
      new_password: newPassword,
    },
  });
}

// POST /auth_2fa_enable.php
async function apiTwofaEnable() {
  return apiRequest("/auth_2fa_enable.php", {
    method: "POST",
  });
}

// POST /auth_2fa_disable.php
async function apiTwofaDisable() {
  return apiRequest("/auth_2fa_disable.php", {
    method: "POST",
  });
}

// (opcjonalne) POST /auth_2fa_verify.php { email, code }
async function apiTwofaVerify(email, code) {
  return apiRequest("/auth_2fa_verify.php", {
    method: "POST",
    body: { email, code },
  });
}

// GET /users_me.php – pełny profil
async function apiGetProfile() {
  return apiRequest("/users_me.php");
}

// GET /balance.php – tylko saldo + nr konta
async function apiGetBalance() {
  return apiRequest("/balance.php");
}

// GET /transactions.php?page=1
async function apiGetTransactions(page = 1) {
  return apiRequest("/transactions.php?page=" + page);
}

// POST /transactions_transfer.php { counterparty, title, amount }
async function apiTransfer(counterparty, title, amount) {
  return apiRequest("/transactions_transfer.php", {
    method: "POST",
    body: { counterparty, title, amount },
  });
}

// POST /transactions_withdraw.php { amount }
async function apiWithdraw(amount) {
  return apiRequest("/transactions_withdraw.php", {
    method: "POST",
    body: { amount },
  });
}

// GET /recommendations.php?read=false|true (opcjonalnie)
async function apiListRecommendations(onlyUnread = false) {
  const query = onlyUnread ? "?read=false" : "";
  return apiRequest("/recommendations.php" + query);
}

// POST /recommendations_read.php { id }
async function apiMarkRecommendationRead(id) {
  return apiRequest("/recommendations_read.php", {
    method: "POST",
    body: { id },
  });
}

// Wylogowanie – czyścimy tokeny i przerzucamy na login
function apiLogout() {
  clearTokens();
  window.location.href = "login.html";
}

// Udostępnienie globalnie
window.ApiClient = {
  // tokeny
  saveTokens,
  getAccessToken,
  clearTokens,

  // auth
  login: apiLogin,
  register: apiRegister,
  changePassword: apiChangePassword,

  // 2FA
  twofaEnable: apiTwofaEnable,
  twofaDisable: apiTwofaDisable,
  twofaVerify: apiTwofaVerify,

  // profil / saldo
  getProfile: apiGetProfile,
  getBalance: apiGetBalance,

  // transakcje
  getTransactions: apiGetTransactions,
  transfer: apiTransfer,
  withdraw: apiWithdraw,

  // alerty / rekomendacje
  listRecommendations: apiListRecommendations,
  markRecommendationRead: apiMarkRecommendationRead,

  // sesja
  logout: apiLogout,
};
