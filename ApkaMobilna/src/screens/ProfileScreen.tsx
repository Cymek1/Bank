import { useState } from "react";
import { View, Alert } from "react-native";
import Screen from "../components/Screen";
import Header from "../components/Header";
import Input from "../components/Input";
import ButtonPrimary from "../components/ButtonPrimary";
import colors from "../theme/colors";
import s from "../theme/spacing";
import { changePassword, enable2fa, disable2fa } from "../api/auth";

export default function ProfileScreen({ navigation }: any) {
  const [oldP, setOldP] = useState("");
  const [newP, setNewP] = useState("");
  const [loading, setLoading] = useState(false);

  async function onChangePass() {
    if (!oldP || !newP) return Alert.alert("Błąd", "Uzupełnij oba pola");
    try {
      setLoading(true);
      await changePassword(oldP, newP);
      Alert.alert("OK", "Hasło zmienione");
      setOldP("");
      setNewP("");
    } catch {
      Alert.alert("Błąd", "Nie udało się zmienić hasła");
    } finally {
      setLoading(false);
    }
  }

  return (
    <Screen>
      <Header title="Profil" subtitle="Bezpieczeństwo i ustawienia" />

      <View
        style={{
          backgroundColor: colors.card,
          borderColor: colors.border,
          borderWidth: 1,
          borderRadius: 16,
          padding: s.lg,
          marginBottom: s.lg,
        }}
      >
        <Input
          label="Stare hasło"
          secureTextEntry
          value={oldP}
          onChangeText={setOldP}
        />
        <Input
          label="Nowe hasło"
          secureTextEntry
          value={newP}
          onChangeText={setNewP}
        />
        <ButtonPrimary
          title={loading ? "Zapisywanie..." : "Zmień hasło"}
          onPress={onChangePass}
        />
      </View>

      <View
        style={{
          backgroundColor: colors.card,
          borderColor: colors.border,
          borderWidth: 1,
          borderRadius: 16,
          padding: s.lg,
        }}
      >
        <ButtonPrimary
          title="Włącz 2FA"
          variant="secondary"
          onPress={() =>
            enable2fa()
              .then(() => Alert.alert("OK", "2FA włączone"))
              .catch(() =>
                Alert.alert("Błąd", "Nie udało się włączyć 2FA")
              )
          }
        />
        <ButtonPrimary
          title="Wyłącz 2FA"
          variant="secondary"
          onPress={() =>
            disable2fa()
              .then(() => Alert.alert("OK", "2FA wyłączone"))
              .catch(() =>
                Alert.alert("Błąd", "Nie udało się wyłączyć 2FA")
              )
          }
        />
      </View>

      <ButtonPrimary
        title="← Powrót"
        variant="ghost"
        onPress={() => navigation.navigate("Dashboard")}
        style={{ marginTop: s.lg }}
      />
    </Screen>
  );
}
