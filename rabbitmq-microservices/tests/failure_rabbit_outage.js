const axios = require('axios');
const fs = require('fs');
const { execSync } = require('child_process');
const path = require('path');

(async function(){
  const projectDir = path.join(__dirname, '..');
  const PHP_API = process.env.PHP_API || 'http://localhost:8000';
  const CONSUMER = process.env.CONSUMER_URL || 'http://localhost:4001';
  const REPORT = { test: 'failure_rabbit_outage', steps: [] };

  try {
    // 1) Stop rabbitmq
    REPORT.steps.push({ action: 'stop_rabbit' });
    execSync('docker-compose stop rabbitmq', { cwd: projectDir, stdio: 'inherit' });

    // 2) Trigger an event via PHP (register endpoint) which should enqueue
    const username = 'fail_rabbit_' + Date.now();
    const email = `${username}@example.test`;
    REPORT.steps.push({ action: 'register', email });
    try {
      const r = await axios.post(`${PHP_API}/auth_register.php`, { username, email, password: 'test123' }, { timeout: 5000 });
      REPORT.steps.push({ register_response: r.data });
    } catch (err) {
      REPORT.steps.push({ register_error: err.message });
    }

    // 3) Wait a short while while worker attempts retries
    await new Promise(r => setTimeout(r, 10000));

    // 4) Start rabbit back up
    REPORT.steps.push({ action: 'start_rabbit' });
    execSync('docker-compose start rabbitmq', { cwd: projectDir, stdio: 'inherit' });

    // 5) Poll consumer for created account (if register returned an id) or inspect queue/dead-letter
    const deadline = Date.now() + 30000;
    let ok = false;
    const reg = REPORT.steps.find(s => s.register_response);
    const userId = reg && reg.register_response && reg.register_response.user_id ? reg.register_response.user_id : null;

    while (Date.now() < deadline) {
      try {
        if (userId) {
          const acc = await axios.get(`${CONSUMER}/api/account/${userId}`, { timeout: 3000 });
          if (acc && acc.status === 200 && acc.data && acc.data.account) {
            REPORT.steps.push({ consumer_found: acc.data.account });
            ok = true; break;
          }
        }

        // check queue status as fallback
        const qs = await axios.get(`${PHP_API}/queue_status.php`, { timeout: 5000 });
        REPORT.steps.push({ queue_status: qs.data });
        if (qs.data.event_queue_count > 0 || qs.data.dead_letter_count > 0) { ok = true; break; }
      } catch (err) {
        REPORT.steps.push({ poll_error: err.message });
      }
      await new Promise(r => setTimeout(r, 1000));
    }

    REPORT.success = ok;
    fs.writeFileSync('failure_rabbit_outage_report.json', JSON.stringify(REPORT, null, 2));
    if (ok) { console.log('Failure test (rabbit outage) observed processing (account or queue)'); process.exit(0); }
    console.error('Failure test timed out without observing processing');
    process.exit(2);
  } catch (err) {
    REPORT.error = err.message;
    fs.writeFileSync('failure_rabbit_outage_report.json', JSON.stringify(REPORT, null, 2));
    console.error('Test failed', err.message);
    process.exit(2);
  }
})();
