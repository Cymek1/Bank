const axios = require('axios');
const fs = require('fs');
const { execSync } = require('child_process');
const path = require('path');

(async function(){
  const projectDir = path.join(__dirname, '..');
  const PRODUCER = process.env.PRODUCER_URL || 'http://localhost:4000';
  const CONSUMER = process.env.CONSUMER_URL || 'http://localhost:4001';
  const REPORT = { test: 'failure_mysql_outage', steps: [] };

  try {
    // 1) Stop MySQL
    REPORT.steps.push({ action: 'stop_mysql' });
    execSync('docker-compose stop mysql', { cwd: projectDir, stdio: 'inherit' });

    // 2) Send an event directly to producer
    const id = 'mysql_outage_' + Date.now();
    const payload = { id, balance: 42.0, metadata: { test: 'mysql_outage' } };
    try {
      const r = await axios.post(`${PRODUCER}/api/send`, payload, { timeout: 5000 });
      REPORT.steps.push({ send_response: r.data });
    } catch (err) {
      REPORT.steps.push({ send_error: err.message });
    }

    // 3) Wait for worker/producer retries (producer might do HTTP fallback)
    await new Promise(r => setTimeout(r, 8000));

    // 4) Start MySQL again
    REPORT.steps.push({ action: 'start_mysql' });
    execSync('docker-compose start mysql', { cwd: projectDir, stdio: 'inherit' });

    // 5) Poll consumer for the account
    const deadline = Date.now() + 30000;
    let found = false;
    while (Date.now() < deadline) {
      try {
        const a = await axios.get(`${CONSUMER}/api/account/${id}`, { timeout: 3000 });
        if (a.status === 200 && a.data && a.data.account) { found = true; REPORT.steps.push({ found: a.data.account }); break; }
      } catch (err) {
        REPORT.steps.push({ poll_error: err.message });
      }
      await new Promise(r => setTimeout(r, 1000));
    }

    REPORT.success = found;
    fs.writeFileSync('failure_mysql_outage_report.json', JSON.stringify(REPORT, null, 2));
    if (found) { console.log('Failure test (mysql outage) succeeded: account found'); process.exit(0); }
    console.error('Failure test (mysql outage) timed out'); process.exit(2);
  } catch (err) {
    REPORT.error = err.message;
    fs.writeFileSync('failure_mysql_outage_report.json', JSON.stringify(REPORT, null, 2));
    console.error('Test failed', err.message);
    process.exit(2);
  }
})();
