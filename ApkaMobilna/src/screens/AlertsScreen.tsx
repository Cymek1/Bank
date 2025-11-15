// src/screens/AlertsScreen.tsx
import { useEffect, useState } from "react";
import {
  View,
  Text,
  FlatList,
  ActivityIndicator,
  Alert,
  TouchableOpacity,
} from "react-native";
import Screen from "../components/Screen";
import Header from "../components/Header";
import ButtonPrimary from "../components/ButtonPrimary";
import s from "../theme/spacing";
import { listRecommendations, markRecommendationRead } from "../api/auth";

type Recommendation = {
  id: number;
  message: string;
  created_at: string;
};

// ← DODANE { navigation }
export default function AlertsScreen({ navigation }: any) {
  const [items, setItems] = useState<Recommendation[]>([]);
  const [loading, setLoading] = useState(false);
  const [refreshing, setRefreshing] = useState(false);

  async function loadAlerts(showSpinner = true) {
    try {
      showSpinner && setLoading(true);
      const data = await listRecommendations();
      setItems(data.items ?? []);
    } catch (e: any) {
      console.log("ERR listRecommendations", e?.response?.data || e);
      Alert.alert("Błąd", "Nie udało się pobrać alertów.");
    } finally {
      showSpinner && setLoading(false);
      setRefreshing(false);
    }
  }

  useEffect(() => {
    loadAlerts();
  }, []);

  async function onMarkRead(id: number) {
    try {
      await markRecommendationRead(String(id));
      // po oznaczeniu jako przeczytane — odśwież listę
      loadAlerts(false);
    } catch (e: any) {
      console.log("ERR markRecommendationRead", e?.response?.data || e);
      Alert.alert("Błąd", "Nie udało się oznaczyć alertu jako przeczytanego.");
    }
  }

  function renderItem({ item }: { item: Recommendation }) {
    return (
      <TouchableOpacity
        onPress={() => onMarkRead(item.id)}
        style={{
          padding: s.md,
          marginBottom: s.sm,
          borderRadius: 8,
          backgroundColor: "#1f2933", // ciemniejsze tło jak reszta apki
        }}
      >
        <Text style={{ color: "white", fontWeight: "600", marginBottom: 4 }}>
          Alert #{item.id}
        </Text>
        <Text style={{ color: "white", marginBottom: 4 }}>{item.message}</Text>
        <Text style={{ color: "#9ca3af", fontSize: 12 }}>
          {new Date(item.created_at).toLocaleString()}
        </Text>
        <Text style={{ color: "#38bdf8", fontSize: 12, marginTop: 4 }}>
          Kliknij, aby oznaczyć jako przeczytany
        </Text>
      </TouchableOpacity>
    );
  }

  if (loading && !refreshing) {
    return (
      <Screen>
        <Header title="Alerty" subtitle="Twoje powiadomienia i rekomendacje" />

        {/* 🔙 PRZYCISK POWRÓT TAKŻE W STANIE ŁADOWANIA */}
        <View style={{ marginTop: s.md }}>
          <ButtonPrimary
            title="← Powrót"
            variant="secondary"
            onPress={() => navigation.goBack()}
          />
        </View>

        <View style={{ marginTop: s.lg, alignItems: "center" }}>
          <ActivityIndicator />
        </View>
      </Screen>
    );
  }

  return (
    <Screen>
      <Header title="Alerty" subtitle="Twoje powiadomienia i rekomendacje" />

      {/* 🔙 PRZYCISK POWRÓT */}
      <View style={{ marginTop: s.md }}>
        <ButtonPrimary
          title="← Powrót"
          variant="secondary"
          onPress={() => navigation.goBack()}
        />
      </View>

      <View style={{ marginTop: s.lg, flex: 1 }}>
        {items.length === 0 ? (
          <View style={{ alignItems: "center", marginTop: s.lg }}>
            <Text style={{ color: "white", marginBottom: s.md }}>
              Brak nowych alertów.
            </Text>
            <ButtonPrimary title="Odśwież" onPress={() => loadAlerts()} />
          </View>
        ) : (
          <FlatList
            data={items}
            keyExtractor={(item) => String(item.id)}
            renderItem={renderItem}
            onRefresh={() => {
              setRefreshing(true);
              loadAlerts(false);
            }}
            refreshing={refreshing}
            contentContainerStyle={{ paddingBottom: s.lg }}
          />
        )}
      </View>
    </Screen>
  );
}
