import React from "react";
import { View, Text } from "react-native";
import colors from "../theme/colors";
import s from "../theme/spacing";

export default function Header({
  title,
  subtitle,
  right,
}: { title: string; subtitle?: string; right?: React.ReactNode }) {
  return (
    <View style={{ marginBottom: s.xl, flexDirection: "row", alignItems: "center" }}>
      <View style={{ flex: 1 }}>
        <Text style={{ color: colors.text, fontSize: 22, fontWeight: "800" }}>
          {title}
        </Text>
        {!!subtitle && (
          <Text style={{ color: colors.textDim, marginTop: 4 }}>{subtitle}</Text>
        )}
      </View>
      {right}
    </View>
  );
}
