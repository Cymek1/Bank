import React from "react";
import { View, Text } from "react-native";

type State = { hasError: boolean; message?: string };

export default class ErrorBoundary extends React.Component<React.PropsWithChildren, State> {
  state: State = { hasError: false, message: undefined };

  static getDerivedStateFromError(error: any) {
    return { hasError: true, message: String(error?.message ?? error) };
  }

  componentDidCatch(error: any, info: any) {
    console.error("ErrorBoundary caught:", error, info);
  }

  render() {
    if (this.state.hasError) {
      return (
        <View style={{ flex: 1, padding: 16, justifyContent: "center" }}>
          <Text style={{ fontSize: 18, fontWeight: "700", marginBottom: 8 }}>
            Ups! Coś poszło nie tak.
          </Text>
          <Text selectable>{this.state.message}</Text>
        </View>
      );
    }
    return this.props.children;
  }
}
