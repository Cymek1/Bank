const axios = require('axios');
const fs = require('fs');

// Simple integration test: send to producer, poll consumer account endpoint
(async function main(){
  const producer = process.env.PRODUCER_URL || 'http://localhost:4000';
  const consumer = process.env.CONSUMER_URL || 'http://localhost:4001';
  const id = 'test-' + Date.now();
  const payload = { id, balance: 123.45, metadata: { source: 'integration' } };
  const report = { id, producer, consumer, attempts: [], success: false };

  try {
    const r = await axios.post(`${producer}/api/send`, payload, { timeout: 5000 });
    report.attempts.push({ stage: 'send', status: r.status, data: r.data });
  } catch (err) {
    report.attempts.push({ stage: 'send', error: err.message });
    fs.writeFileSync('report.json', JSON.stringify(report, null, 2));
    console.error('Send failed', err.message);
    process.exit(2);
  }

  // poll consumer account
  const deadline = Date.now() + 30000; // 30s
  while (Date.now() < deadline) {
    try {
      const r = await axios.get(`${consumer}/api/account/${id}`, { timeout: 3000 });
      if (r.status === 200 && r.data && r.data.account) {
        report.success = true;
        report.attempts.push({ stage: 'fetch', status: r.status, account: r.data.account });
        break;
      }
    } catch (err) {
      report.attempts.push({ stage: 'fetch', error: err.message });
    }
    await new Promise(r => setTimeout(r, 1000));
  }

  fs.writeFileSync('report.json', JSON.stringify(report, null, 2));
  if (report.success) {
    console.log('Integration successful, report written to report.json');
    process.exit(0);
  } else {
    console.error('Integration failed or timed out, see report.json');
    process.exit(1);
  }
})();
