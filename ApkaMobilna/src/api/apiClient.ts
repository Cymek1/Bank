// src/api/apiClient.ts
import axios, { AxiosError, InternalAxiosRequestConfig } from "axios";
import type { AxiosRequestHeaders } from "axios";
import { API_URL } from "../config"; // jeśli przeniesiesz API_URL do src/config, zmień na: "../config"

const api = axios.create({
  baseURL: API_URL,
  timeout: 8000,
  headers: { "Content-Type": "application/json" },
});

// --- Tokeny: w web -> localStorage; w RN tymczasowo pamięć (później podmienisz na SecureStore) ---
const hasLS = typeof localStorage !== "undefined";
let memory = { access: null as string | null, refresh: null as string | null };

export const getAccess = () =>
  hasLS ? localStorage.getItem("access_token") : memory.access;

export const getRefresh = () =>
  hasLS ? localStorage.getItem("refresh_token") : memory.refresh;

export const setTokens = (access?: string | null, refresh?: string | null) => {
  if (hasLS) {
    if (access !== undefined) {
      access === null
        ? localStorage.removeItem("access_token")
        : localStorage.setItem("access_token", access);
    }
    if (refresh !== undefined) {
      refresh === null
        ? localStorage.removeItem("refresh_token")
        : localStorage.setItem("refresh_token", refresh);
    }
  } else {
    if (access !== undefined) memory.access = access ?? null;
    if (refresh !== undefined) memory.refresh = refresh ?? null;
  }
};

export const clearTokens = () => setTokens(null, null);

// --- Dołączanie Bearera ---
api.interceptors.request.use((cfg) => {
  const access = getAccess();
  if (access) {
    (cfg.headers as AxiosRequestHeaders | undefined) ??=
      {} as AxiosRequestHeaders;
    (cfg.headers as any).Authorization = `Bearer ${access}`;
  }
  return cfg;
});

// --- Auto-refresh po 401 (jeśli masz endpoint POST /auth/refresh) ---
let refreshing: Promise<string | null> | null = null;

api.interceptors.response.use(
  (res) => res,
  async (error: AxiosError) => {
    const original = error.config as
      | (InternalAxiosRequestConfig & { _retry?: boolean })
      | undefined;

    if (error.response?.status === 401 && original && !original._retry) {
      original._retry = true;

      if (!refreshing) {
        refreshing = (async () => {
          const refresh = getRefresh();
          if (!refresh) return null;
          try {
            const { data } = await axios.post(`${API_URL}/auth/refresh`, {
              refresh,
            });
            const newAccess = (data as any).access as string;
            setTokens(newAccess, undefined);
            return newAccess;
          } catch {
            clearTokens();
            return null;
          } finally {
            // no-op
          }
        })().finally(() => {
          refreshing = null;
        });
      }

      const newAccess = await refreshing;
      if (newAccess && original) {
        (original.headers as any) = {
          ...(original.headers ?? {}),
          Authorization: `Bearer ${newAccess}`,
        };
        return api(original);
      }
    }

    return Promise.reject(error);
  }
);

export default api;
export { api };
