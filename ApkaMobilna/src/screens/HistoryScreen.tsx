import { useState } from "react";
import { View, Text, FlatList, Alert } from "react-native";
import Screen from "../components/Screen";
import Header from "../components/Header";
import Input from "../components/Input";
import ButtonPrimary from "../components/ButtonPrimary";
import colors from "../theme/colors";
import s from "../theme/spacing";
import { listTransactions } from "../api/auth";
import { formatDate } from "../utils/formatDate";

type Item = {
  id: number | string;
  created_at: string;
  type: "transfer" | "withdraw" | string;
  amount: number | string;
  title?: string;
  counterparty?: string;
};

export default function HistoryScreen({ navigation }: any) {
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const [type, setType] = useState("");
  const [page, setPage] = useState(1);
  const [items, setItems] = useState<Item[]>([]);

  async function load(p = 1) {
    try {
      const d = await listTransactions({
        page: p,
        from,
        to,
        type: type as any,
      });
      setItems((d?.items as Item[]) ?? []);
      setPage(p);
    } catch {
      Alert.alert("Błąd", "Nie udało się pobrać historii");
    }
  }

  return (
    <Screen>
      <Header title="Historia" subtitle="Przeglądaj swoje operacje" />
      <View style={{ marginBottom: s.lg }}>
        <Input
          label="Od (YYYY-MM-DD)"
          placeholder="2025-01-01"
          value={from}
          onChangeText={setFrom}
        />
        <Input
          label="Do (YYYY-MM-DD)"
          placeholder="2025-12-31"
          value={to}
          onChangeText={setTo}
        />
        <Input
          label="Typ (transfer/withdraw)"
          placeholder="transfer"
          value={type}
          onChangeText={setType}
        />
        <ButtonPrimary title="Filtruj" onPress={() => load(1)} />
      </View>

      <View style={{ flex: 1, borderTopWidth: 1, borderColor: colors.border }}>
        <FlatList
          data={items}
          keyExtractor={(x) => String(x.id)}
          contentContainerStyle={{ paddingVertical: 6 }}
          renderItem={({ item }) => (
            <View
              style={{
                backgroundColor: colors.card,
                borderColor: colors.border,
                borderWidth: 1,
                borderRadius: 12,
                padding: s.md,
                marginBottom: s.sm,
              }}
            >
              <Text style={{ color: colors.text, fontWeight: "700" }}>
                {item.type.toUpperCase()} — {Number(item.amount).toFixed(2)} PLN
              </Text>
              <Text style={{ color: colors.textDim, marginTop: 2 }}>
                {formatDate(item.created_at)}
              </Text>
              {!!item.counterparty && (
                <Text style={{ color: colors.textDim, marginTop: 2 }}>
                  Do/Od: {item.counterparty}
                </Text>
              )}
              {!!item.title && (
                <Text style={{ color: colors.textDim, marginTop: 2 }}>
                  Tytuł: {item.title}
                </Text>
              )}
            </View>
          )}
        />
      </View>

      <View style={{ flexDirection: "row", gap: s.sm }}>
        <ButtonPrimary
          title="◀︎"
          variant="secondary"
          onPress={() => load(Math.max(page - 1, 1))}
          style={{ flex: 1 }}
        />
        <ButtonPrimary
          title="▶︎"
          variant="secondary"
          onPress={() => load(page + 1)}
          style={{ flex: 1 }}
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
