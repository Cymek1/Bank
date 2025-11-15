// src/screens/RegisterScreen.tsx
import { useState } from "react";
import { View, Alert } from "react-native";
import Screen from "../components/Screen";
import Header from "../components/Header";
import Input from "../components/Input";
import ButtonPrimary from "../components/ButtonPrimary";
import s from "../theme/spacing";
import { register } from "../api/auth";

export default function RegisterScreen({ navigation }: any) {
  const [username, setUsername] = useState("");
  const [email, setE] = useState("");
  const [password, setP] = useState("");
  const [loading, setLoading] = useState(false);

  async function onSubmit() {
    if (!username || !email || !password) {
      return Alert.alert(
        "Błąd",
        "Uzupełnij nazwę użytkownika, email i hasło"
      );
    }

    try {
      setLoading(true);

      const res = await register(username, email, password);
      console.log("REGISTER_RESPONSE", res);

      if (res.error || res.status !== "OK") {
        return Alert.alert(
          "Rejestracja nieudana",
          res.message || res.error || "Błąd serwera"
        );
      }

      Alert.alert(
        "Sukces",
        `Konto utworzone.\nID: ${res.user_id}\nNr konta: ${res.nr_konta}`,
        [
          {
            text: "OK",
            onPress: () => navigation.replace("Login"),
          },
        ]
      );
    } catch (e: any) {
      console.log("REGISTER_ERROR", e?.response?.data || e);

      const backendMsg =
        e?.response?.data?.message ||
        e?.response?.data?.error ||
        JSON.stringify(e?.response?.data || {});

      Alert.alert(
        "Rejestracja nieudana",
        backendMsg || "Wystąpił błąd podczas rejestracji"
      );
    } finally {
      setLoading(false);
    }
  }

  return (
    <Screen>
      <Header title="Rejestracja" subtitle="Utwórz nowe konto w BankApp" />
      <View style={{ marginTop: s.lg }}>
        <Input
          label="Nazwa użytkownika"
          placeholder="np. jan_kowalski"
          autoCapitalize="none"
          value={username}
          onChangeText={setUsername}
        />
        <Input
          label="Email"
          placeholder="np. user@bank.pl"
          autoCapitalize="none"
          keyboardType="email-address"
          value={email}
          onChangeText={setE}
        />
        <Input
          label="Hasło"
          placeholder="••••••••"
          secureTextEntry
          value={password}
          onChangeText={setP}
        />

        <ButtonPrimary
          title={loading ? "Rejestrowanie..." : "Zarejestruj"}
          onPress={onSubmit}
        />

        <ButtonPrimary
          title="Masz już konto? Zaloguj się"
          variant="ghost"
          onPress={() => navigation.replace("Login")}
        />
      </View>
    </Screen>
  );
}
