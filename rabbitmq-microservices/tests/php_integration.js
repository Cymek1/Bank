const axios = require('axios');
const fs = require('fs');

(async function(){
  const API = process.env.PHP_API || 'http://localhost/bank_api/api';
  const PRODUCER = process.env.PRODUCER || 'http://localhost:4000';
  const CONSUMER = process.env.CONSUMER || 'http://localhost:4001';

  const report = { steps: [] };

  try {
    // 1) register a new user
    const username = 'e2e_' + Date.now();
    const email = `${username}@example.test`;
    const password = 'test123';

    console.log('Registering user', email);
    report.steps.push({ step: 'register', email });
    const r = await axios.post(`${API}/auth_register.php`, { username, email, password }, { timeout: 5000 });
    report.steps.push({ step: 'register_response', status: r.status, data: r.data });

    const userId = r.data.user_id;
    console.log('Registered user id', userId);

    // 2) Poll consumer for account
    const deadline = Date.now() + 30000;
    let found = false;
    while (Date.now() < deadline) {
      try {
        const a = await axios.get(`${CONSUMER}/api/account/${userId}`, { timeout: 3000 });
        if (a.status === 200 && a.data && a.data.account) {
          report.steps.push({ step: 'consumer_found', account: a.data.account });
          found = true;
          break;
        }
      } catch (err) {
        report.steps.push({ step: 'consumer_poll_error', err: err.message });
      }
      await new Promise(r=>setTimeout(r, 1000));
    }

    report.success = found;
    fs.writeFileSync('php_integration_report.json', JSON.stringify(report, null, 2));
    if (found) console.log('PHP -> producer -> consumer flow OK');
    else console.error('Timed out waiting for consumer');

  } catch (err) {
    report.error = err.message;
    fs.writeFileSync('php_integration_report.json', JSON.stringify(report, null, 2));
    console.error('Test failed', err.message);
    process.exit(2);
  }
})();
