export function formatDate(iso?: string) {
  if (!iso) return "";
  try {
    // szybkie i czytelne: "2025-11-12 15:41:00"
    return iso.replace("T", " ").slice(0, 19);
  } catch {
    return String(iso);
  }
}
