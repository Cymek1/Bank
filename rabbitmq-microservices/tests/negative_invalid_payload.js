const axios = require('axios');
const fs = require('fs');

(async function(){
  const producer = process.env.PRODUCER_URL || 'http://localhost:4000';
  const report = { test: 'invalid_payload', attempts: [] };
  try {
    // missing id should cause validation error
    const payload = { balance: 10 };
    await axios.post(`${producer}/api/send`, payload, { timeout: 5000 });
    report.result = 'unexpected_success';
  } catch (err) {
    report.attempts.push(err.response ? { status: err.response.status, data: err.response.data } : { error: err.message });
    report.result = 'expected_failure';
  }
  fs.writeFileSync('negative_invalid_payload_report.json', JSON.stringify(report, null, 2));
  if (report.result === 'expected_failure') process.exit(0);
  process.exit(2);
})();
