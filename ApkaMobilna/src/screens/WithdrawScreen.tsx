import { useState } from "react";
import { View, Alert } from "react-native";
import Screen from "../components/Screen";
import Header from "../components/Header";
import Input from "../components/Input";
import ButtonPrimary from "../components/ButtonPrimary";
import s from "../theme/spacing";
import { withdraw } from "../api/auth";

export default function WithdrawScreen({ navigation }: any) {
  const [amount, setAmount] = useState("");
  const [loading, setLoading] = useState(false);

  async function send() {
    const val = parseFloat(amount.replace(",", "."));
    if (!amount) return Alert.alert("Błąd", "Podaj kwotę");
    if (isNaN(val) || val <= 0)
      return Alert.alert("Błąd", "Kwota musi być > 0");
    try {
      setLoading(true);
      await withdraw(val);
      Alert.alert("Sukces", "Wypłata wykonana");
      setAmount("");
    } catch (e: any) {
      Alert.alert(
        "Błąd",
        e?.response?.data?.message || "Nie udało się wykonać wypłaty"
      );
    } finally {
      setLoading(false);
    }
  }

  return (
    <Screen>
      <Header title="Wypłata" subtitle="Wypłać środki z konta" />
      <View style={{ marginBottom: s.lg }}>
        <Input
          label="Kwota (PLN)"
          placeholder="50.00"
          value={amount}
          onChangeText={setAmount}
          keyboardType="decimal-pad"
        />
        <ButtonPrimary
          title={loading ? "Przetwarzanie..." : "Wypłać"}
          onPress={send}
        />
      </View>
      <ButtonPrimary
        title="← Powrót"
        variant="ghost"
        onPress={() => navigation.navigate("Dashboard")}
      />
    </Screen>
  );
}
