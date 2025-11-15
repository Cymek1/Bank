import { useEffect, useState } from "react";
import { View, Text, Alert } from "react-native";
import Screen from "../components/Screen";
import Header from "../components/Header";
import ButtonPrimary from "../components/ButtonPrimary";
import colors from "../theme/colors";
import s from "../theme/spacing";
import { getBalance } from "../api/auth";
import { useAuth } from "../hooks/useAuth";


export default function DashboardScreen({ navigation }: any) {
  const [balance, setBalance] = useState<number>();
  const { email, logout } = useAuth();

  useEffect(() => {
    getBalance()
      .then((d) => setBalance(d.balance))
      .catch(() => Alert.alert("Błąd", "Nie udało się pobrać salda"));
  }, []);

  return (
    <Screen>
      <Header
        title="Pulpit"
        subtitle={email ? `Witaj, ${email}` : "Witaj w BankApp"}
        right={
          <ButtonPrimary
            title="Wyloguj"
            variant="ghost"
            onPress={logout}
          />
        }
      />

      {/* Karta salda */}
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
        <Text style={{ color: colors.textDim, fontSize: 12 }}>Dostępne środki</Text>
        <Text
          style={{
            color: colors.text,
            fontSize: 28,
            fontWeight: "800",
            marginTop: 6,
          }}
        >
          {balance !== undefined ? `${balance.toFixed(2)} PLN` : "…"}
        </Text>
      </View>

      {/* Siatka kafelków */}
      <View style={{ flexDirection: "row", gap: s.lg }}>
        <Tile
          title="Historia"
          subtitle="Ostatnie operacje"
          emoji="📜"
          onPress={() => navigation.navigate("History")}
        />
        <Tile
          title="Alerty"
          subtitle="Powiadomienia"
          emoji="🔔"
          onPress={() => navigation.navigate("Alerts")}
        />
      </View>

      <View style={{ height: s.lg }} />

      <View style={{ flexDirection: "row", gap: s.lg }}>
        <Tile
          title="Przelew"
          subtitle="Nowy przelew"
          emoji="💸"
          onPress={() => navigation.navigate("Transfer")}
        />
        <Tile
          title="Wypłata"
          subtitle="Wypłać środki"
          emoji="🏧"
          onPress={() => navigation.navigate("Withdraw")}
        />
      </View>

      <View style={{ height: s.lg }} />
      
      <ButtonPrimary
        title="Profil"
        variant="secondary"
        onPress={() => navigation.navigate("Profile")}
      />
    </Screen>
  );
}

// nie zapomnij o imporcie Tile na górze:
import Tile from "../components/Tile";
