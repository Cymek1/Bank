import React from "react";
import { TouchableOpacity, Text, View } from "react-native";
import colors from "../theme/colors";
import s from "../theme/spacing";

export default function Tile({
  title,
  subtitle,
  emoji,
  onPress,
}: {
  title: string;
  subtitle?: string;
  emoji?: string;
  onPress?: () => void;
}) {
  return (
    <TouchableOpacity
      onPress={onPress}
      style={{
        backgroundColor: colors.card,
        borderColor: colors.border,
        borderWidth: 1,
        borderRadius: 16,
        padding: s.lg,
        flex: 1,
        minHeight: 100,
        justifyContent: "space-between",
      }}
    >
      <View style={{ flexDirection: "row", justifyContent: "space-between" }}>
        <Text style={{ color: colors.text, fontSize: 16, fontWeight: "700" }}>
          {title}
        </Text>
        {!!emoji && <Text style={{ fontSize: 20 }}>{emoji}</Text>}
      </View>
      {!!subtitle && (
        <Text style={{ color: colors.textDim, marginTop: 6, fontSize: 12 }}>
          {subtitle}
        </Text>
      )}
    </TouchableOpacity>
  );
}
