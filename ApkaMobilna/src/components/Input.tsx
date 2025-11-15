import React, { useState } from "react";
import { TextInput, View, Text, TextInputProps } from "react-native";
import colors from "../theme/colors";
import s from "../theme/spacing";

export type InputProps = TextInputProps & { label?: string; errorText?: string };

export default function Input({ label, errorText, style, ...props }: InputProps) {
  const [focus, setFocus] = useState(false);

  return (
    <View style={{ width: "100%", marginBottom: s.md }}>
      {!!label && (
        <Text style={{ color: colors.textDim, marginBottom: 6, fontSize: 13 }}>
          {label}
        </Text>
      )}
      <TextInput
        {...props}
        onFocus={(e) => { setFocus(true); props.onFocus?.(e); }}
        onBlur={(e) => { setFocus(false); props.onBlur?.(e); }}
        placeholderTextColor={colors.textDim}
        style={[
          {
            borderWidth: 1,
            borderColor: focus ? colors.primary : colors.border,
            backgroundColor: colors.card,
            color: colors.text,
            borderRadius: 12,
            paddingVertical: 12,
            paddingHorizontal: 14,
          },
          style as any,
        ]}
      />
      {!!errorText && (
        <Text style={{ color: colors.danger, marginTop: 4, fontSize: 12 }}>
          {errorText}
        </Text>
      )}
    </View>
  );
}
