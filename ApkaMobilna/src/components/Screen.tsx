import React from "react";
import { SafeAreaView, View } from "react-native";
import colors from "../theme/colors";
import s from "../theme/spacing";

export default function Screen({ children }: React.PropsWithChildren) {
  return (
    <SafeAreaView style={{ flex: 1, backgroundColor: colors.bg }}>
      <View style={{ flex: 1, paddingHorizontal: s.lg, paddingTop: s.lg }}>
        {children}
      </View>
    </SafeAreaView>
  );
}
