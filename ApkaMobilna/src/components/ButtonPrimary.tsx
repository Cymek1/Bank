import React from "react";
import { TouchableOpacity, Text, ViewStyle } from "react-native";
import colors from "../theme/colors";
import s from "../theme/spacing";

type Variant = "primary" | "secondary" | "ghost";

export default function ButtonPrimary({
  title,
  onPress,
  disabled,
  style,
  variant = "primary",
}: {
  title: string;
  onPress?: () => void;
  disabled?: boolean;
  style?: ViewStyle;
  variant?: Variant;
}) {
  const bg =
    variant === "primary" ? colors.primary :
    variant === "secondary" ? colors.card :
    "transparent";

  const borderColor = variant === "ghost" ? colors.border : "transparent";
  const textColor =
    variant === "primary" ? colors.primaryText : colors.text;

  return (
    <TouchableOpacity
      onPress={onPress}
      disabled={disabled}
      style={[
        {
          backgroundColor: disabled ? "#2c3445" : bg,
          borderWidth: 1,
          borderColor,
          paddingVertical: s.md,
          paddingHorizontal: s.lg,
          borderRadius: 12,
          marginVertical: 6,
          alignItems: "center",
        },
        style,
      ]}
    >
      <Text style={{ color: textColor, fontWeight: "800", letterSpacing: 0.3 }}>
        {title}
      </Text>
    </TouchableOpacity>
  );
}
