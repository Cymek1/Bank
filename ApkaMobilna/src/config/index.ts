// src/config/index.ts
import { Platform } from "react-native";

export const API_URL =
  Platform.OS === "android"
    ? "http://10.0.2.2/bank_api/api"
    : "http://localhost/bank_api/api";
