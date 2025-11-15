// src/api/auth.ts
import { api, setTokens, clearTokens } from "./apiClient";

export type TxType = "transfer" | "withdraw";

// ---- AUTH ----

// POST /auth_login.php { email, password }
export async function login(email: string, password: string) {
  const { data } = await api.post("/auth_login.php", { email, password });

  // jeśli backend nie wymaga 2FA, od razu zapisujemy tokeny
  if (!data?.twofa_required) {
    setTokens(data.access, data.refresh);
  }

  return data as {
    twofa_required?: boolean;
    access?: string;
    refresh?: string;
  };
}


export async function register(
  username: string,
  email: string,
  password: string
) {
  const { data } = await api.post("/auth_register.php", {
    username,
    email,
    password,
  });

  return data as {
    status?: string;
    message?: string;
    error?: string;
    user_id?: number;
    nr_konta?: string;
  };
}

// POST /auth_2fa_verify.php { code, email }
export async function verify2fa(code: string, email: string) {
  const { data } = await api.post("/auth_2fa_verify.php", { code, email });
  setTokens(data.access, data.refresh);
  return data;
}

// Wylogowanie – czyści tokeny po stronie apki
export async function logoutAll() {
  clearTokens();
}

// POST /auth_change_password.php { old_password, new_password }
export async function changePassword(
  old_password: string,
  new_password: string
) {
  return api.post("/auth_change_password.php", { old_password, new_password });
}

// W auth.ts, np. pod changePassword

// POST /auth_2fa_enable.php {}
export async function enable2fa() {
  const { data } = await api.post("/auth_2fa_enable.php", {});
  return data;
}

// POST /auth_2fa_disable.php {}
export async function disable2fa() {
  const { data } = await api.post("/auth_2fa_disable.php", {});
  return data;
}


// ---- KONTO / TRANSAKCJE ----

// GET /users_me.php
export async function getMe() {
  const { data } = await api.get("/users_me.php");
  return data;
}

// GET /accounts_me.php -> { balance }
export async function getBalance() {
  const { data } = await api.get("/accounts_me.php");
  return data as { balance: number };
}

// GET /transactions.php?{page,from,to,type}
export async function listTransactions(params?: {
  page?: number;
  from?: string;
  to?: string;
  type?: TxType;
}) {
  const { data } = await api.get("/transactions.php", { params });
  return data as { items: any[]; hasMore?: boolean };
}

// POST /transactions_transfer.php { counterparty, title, amount }
export async function makeTransfer(
  counterparty: string,
  title: string,
  amount: number
) {
  return api.post("/transactions_transfer.php", {
    counterparty,
    title,
    amount,
  });
}

// POST /transactions_withdraw.php { amount }
export async function withdraw(amount: number) {
  return api.post("/transactions_withdraw.php", { amount });
}

// ---- ALERTY / REKOMENDACJE ----

// GET /recommendations.php
export async function listRecommendations() {
  const { data } = await api.get("/recommendations.php", {
    params: { read: false },
  });
  return data as { items: any[] };
}

// POST /recommendations_read.php
export async function markRecommendationRead(id: string) {
  return api.post(`/recommendations_read.php`, { id });
}
