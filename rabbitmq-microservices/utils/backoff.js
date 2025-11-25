// prosty exponential backoff helper
function backoff(attempt, base = 500, cap = 30000) {
  // attempt: 0-based
  const factor = Math.pow(2, attempt);
  const ms = Math.min(base * factor, cap);
  // jitter +/- 20%
  const jitter = Math.floor(ms * 0.2 * (Math.random() * 2 - 1));
  return Math.max(0, ms + jitter);
}

module.exports = { backoff };
