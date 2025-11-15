// src/screens/LoginScreen.tsx
import { useState } from "react";
import { View, Alert } from "react-native";
import Screen from "../components/Screen";
import Header from "../components/Header";
import Input from "../components/Input";
import ButtonPrimary from "../components/ButtonPrimary";
import s from "../theme/spacing";
import { useAuth } from "../hooks/useAuth";

export default function LoginScreen({ navigation }: any) {
  const [email, setE] = useState("");
  const [password, setP] = useState("");
  const [loading, setLoading] = useState(false);
  const { login } = useAuth();

  async function onSubmit() {
    if (!email || !password) {
      return Alert.alert("Błąd", "Podaj email i hasło");
    }
    try {
      setLoading(true);
      const res = await login(email, password);

      if (res.twofaRequired) {
        // NIC nie nawigujemy – RootNavigator pokaże TwoFA
        Alert.alert(
          "2FA",
          "Na twojego maila został wysłany kod 2FA. Podaj go na kolejnym ekranie."
        );
      }
    } catch (e: any) {
      Alert.alert(
        "Logowanie nieudane",
        e?.response?.data?.message ?? "Sprawdź dane"
      );
    } finally {
      setLoading(false);
    }
  }

  return (
    <Screen>
      <Header title="BankApp" subtitle="Zaloguj się do swojego konta" />
      <View style={{ marginTop: s.lg }}>
        <Input
          label="Email"
          placeholder="np. user@bank.pl"
          autoCapitalize="none"
          keyboardType="email-address"
          onChangeText={setE}
        />
        <Input
          label="Hasło"
          placeholder="••••••••"
          secureTextEntry
          onChangeText={setP}
        />

        <ButtonPrimary
          title={loading ? "Logowanie..." : "Zaloguj"}
          onPress={onSubmit}
        />

        <ButtonPrimary
          title="Nie masz konta? Zarejestruj się"
          variant="secondary"
          onPress={() => navigation.navigate("Register")}
        />
      </View>
    </Screen>
  );
}
