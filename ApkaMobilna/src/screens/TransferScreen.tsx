import { useState } from "react";
import { View, Alert } from "react-native";
import Screen from "../components/Screen";
import Header from "../components/Header";
import Input from "../components/Input";
import ButtonPrimary from "../components/ButtonPrimary";
import s from "../theme/spacing";
import { makeTransfer } from "../api/auth";

export default function TransferScreen({ navigation }: any) {
  const [toAcc, setToAcc] = useState("");
  const [title, setTitle] = useState("");
  const [amount, setAmount] = useState("");
  const [loading, setLoading] = useState(false);

  async function send() {
    const val = parseFloat(amount.replace(",", "."));
    if (!toAcc || !title || !amount)
      return Alert.alert("Błąd", "Uzupełnij wszystkie pola");
    if (isNaN(val) || val <= 0)
      return Alert.alert("Błąd", "Kwota musi być > 0");
    try {
      setLoading(true);
      await makeTransfer(toAcc, title, val);
      Alert.alert("Sukces", "Przelew wysłany");
      setToAcc("");
      setTitle("");
      setAmount("");
    } catch (e: any) {
      Alert.alert(
        "Błąd",
        e?.response?.data?.message || "Nie udało się wykonać przelewu"
      );
    } finally {
      setLoading(false);
    }
  }

  return (
    <Screen>
      <Header title="Nowy przelew" subtitle="Wyślij środki na inne konto" />
      <View style={{ marginBottom: s.lg }}>
        <Input
          label="Numer konta odbiorcy"
          placeholder="1234 5678 9012 3456"
          value={toAcc}
          onChangeText={setToAcc}
        />
        <Input
          label="Tytuł"
          placeholder="Np. Opłata"
          value={title}
          onChangeText={setTitle}
        />
        <Input
          label="Kwota (PLN)"
          placeholder="100.00"
          value={amount}
          onChangeText={setAmount}
          keyboardType="decimal-pad"
        />
        <ButtonPrimary
          title={loading ? "Wysyłanie..." : "Wyślij przelew"}
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
