// src/screens/TwoFAScreen.tsx
import { useState } from "react";
import { View, Alert } from "react-native";
import Screen from "../components/Screen";
import Header from "../components/Header";
import Input from "../components/Input";
import ButtonPrimary from "../components/ButtonPrimary";
import s from "../theme/spacing";
import { useAuth } from "../hooks/useAuth";

export default function TwoFAScreen({ navigation }: any) {
  const [code, setCode] = useState("");
  const [loading, setLoading] = useState(false);
  const { verify2FA, logout, email } = useAuth();

  async function onVerify() {
    if (!code) return Alert.alert("Błąd", "Podaj kod 2FA");
    try {
      setLoading(true);
      await verify2FA(code);
      // po poprawnym kodzie RootNavigator sam przełączy na MainTabs
    } catch (e: any) {
      Alert.alert(
        "Błąd",
        e?.response?.data?.message ?? "Niepoprawny kod 2FA"
      );
    } finally {
      setLoading(false);
    }
  }

  return (
    <Screen>
      <Header
        title="Weryfikacja 2FA"
        subtitle={
          email
            ? `Kod został wysłany na: ${email}`
            : "Podaj kod z wiadomości"
        }
      />

      <View style={{ marginTop: s.lg }}>
        <Input
          label="Kod 2FA"
          placeholder="123456"
          keyboardType="number-pad"
          onChangeText={setCode}
        />

        <ButtonPrimary
          title={loading ? "Sprawdzanie..." : "Potwierdź"}
          onPress={onVerify}
        />

        <ButtonPrimary
          title="← Powrót do logowania"
          variant="secondary"
          onPress={() => navigation.replace("Login")}
        />

        <ButtonPrimary
          title="Wyloguj (wyczyść sesję)"
          variant="ghost"
          onPress={logout}
        />
      </View>
    </Screen>
  );
}
