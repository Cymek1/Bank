// App.tsx
import React from "react";
import { NavigationContainer } from "@react-navigation/native";
import { createNativeStackNavigator } from "@react-navigation/native-stack";
import { createBottomTabNavigator } from "@react-navigation/bottom-tabs";

import { useAuth, AuthProvider } from "./src/hooks/useAuth";

// ekrany
import LoginScreen from "./src/screens/LoginScreen";
import RegisterScreen from "./src/screens/RegisterScreen";
import TwoFAScreen from "./src/screens/TwoFAScreen";
import DashboardScreen from "./src/screens/DashboardScreen";
import HistoryScreen from "./src/screens/HistoryScreen";
import TransferScreen from "./src/screens/TransferScreen";
import WithdrawScreen from "./src/screens/WithdrawScreen";
import AlertsScreen from "./src/screens/AlertsScreen";
import ProfileScreen from "./src/screens/ProfileScreen";

const Stack = createNativeStackNavigator();
const Tab = createBottomTabNavigator();

// dolne zakładki po zalogowaniu
function MainTabs() {
  return (
    <Tab.Navigator>
      <Tab.Screen
        name="Dashboard"
        component={DashboardScreen}
        options={{ title: "Konto" }}
      />
      <Tab.Screen
        name="History"
        component={HistoryScreen}
        options={{ title: "Historia" }}
      />
      <Tab.Screen
        name="Transfer"
        component={TransferScreen}
        options={{ title: "Przelew" }}
      />
      <Tab.Screen
        name="Withdraw"
        component={WithdrawScreen}
        options={{ title: "Wypłata" }}
      />
      <Tab.Screen
        name="Alerts"
        component={AlertsScreen}
        options={{ title: "Alerty" }}
      />
      <Tab.Screen
        name="Profile"
        component={ProfileScreen}
        options={{ title: "Profil" }}
      />
    </Tab.Navigator>
  );
}

function RootNavigator() {
  const { user, loading, pending2fa } = useAuth();

  if (loading) {
    return null; // tu możesz dać spinner
  }

  // 1) jeśli wymagana weryfikacja 2FA -> pokazujemy tylko TwoFA
  if (!user && pending2fa) {
    return (
      <Stack.Navigator screenOptions={{ headerShown: false }}>
        <Stack.Screen name="TwoFA" component={TwoFAScreen} />
      </Stack.Navigator>
    );
  }

  // 2) jeśli niezalogowany i bez 2FA -> logowanie / rejestracja
  if (!user) {
    return (
      <Stack.Navigator
        screenOptions={{ headerShown: false }}
        initialRouteName="Login"
      >
        <Stack.Screen name="Login" component={LoginScreen} />
        <Stack.Screen name="Register" component={RegisterScreen} />
      </Stack.Navigator>
    );
  }

  // 3) jeśli zalogowany -> zakładki
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen name="MainTabs" component={MainTabs} />
    </Stack.Navigator>
  );
}

export default function App() {
  return (
    <AuthProvider>
      <NavigationContainer>
        <RootNavigator />
      </NavigationContainer>
    </AuthProvider>
  );
}
