// src/hooks/useAuth.tsx
import React, {
  useEffect,
  useState,
  useCallback,
  createContext,
  useContext,
} from "react";
import type { User } from "../types/User";
import {
  login as apiLogin,
  verify2fa as apiVerify2fa,
  logoutAll as apiLogoutAll,
  getMe,
} from "../api/auth";

type AuthState = {
  user: User | null;
  loading: boolean;
  pending2fa: boolean;
  email?: string;
};

type AuthContextValue = {
  user: User | null;
  loading: boolean;
  pending2fa: boolean;
  email?: string;
  login: (email: string, password: string) => Promise<{ twofaRequired: boolean }>;
  verify2FA: (code: string) => Promise<void>;
  logout: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

// ---- PROVIDER ----
export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [state, setState] = useState<AuthState>({
    user: null,
    loading: true,
    pending2fa: false,
    email: undefined,
  });

  // na starcie – próbujemy pobrać aktualnego usera
  useEffect(() => {
    const init = async () => {
      try {
        const user = await getMe();
        setState((prev) => ({
          ...prev,
          user,
          loading: false,
        }));
      } catch (e) {
        setState((prev) => ({
          ...prev,
          user: null,
          loading: false,
        }));
      }
    };

    init();
  }, []);

  // krok 1: logowanie email + hasło
  const login = useCallback(async (email: string, password: string) => {
    const data = await apiLogin(email, password);

    // jeśli backend mówi, że potrzebne 2FA
    if (data.twofa_required) {
      setState((prev) => ({
        ...prev,
        pending2fa: true,
        email,
      }));
      return { twofaRequired: true as const };
    }

    // jeśli nie ma 2FA, to tokeny są już zapisane w setTokens()
    const user = await getMe();
    setState((prev) => ({
      ...prev,
      user,
      loading: false,
      pending2fa: false,
      email,
    }));
    return { twofaRequired: false as const };
  }, []);

  // krok 2: weryfikacja kodu 2FA
  const verify2FA = useCallback(
    async (code: string) => {
      if (!state.email) {
        throw new Error("Brak emaila w stanie – zaloguj się ponownie.");
      }

      await apiVerify2fa(code, state.email);

      const user = await getMe();

      setState((prev) => ({
        ...prev,
        user,
        loading: false,
        pending2fa: false,
        email: undefined,
      }));
    },
    [state.email]
  );

  // wylogowanie
  const logout = useCallback(async () => {
    await apiLogoutAll();
    setState({
      user: null,
      loading: false,
      pending2fa: false,
      email: undefined,
    });
  }, []);

  const value: AuthContextValue = {
    user: state.user,
    loading: state.loading,
    pending2fa: state.pending2fa,
    email: state.email,
    login,
    verify2FA,
    logout,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

// ---- HOOK ----
export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error("useAuth musi być użyty wewnątrz AuthProvider");
  }
  return ctx;
}
