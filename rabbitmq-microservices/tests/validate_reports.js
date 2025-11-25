const fs = require('fs');
const path = require('path');

const reportFiles = [
  'report.json',
  'php_integration_report.json',
  'negative_invalid_payload_report.json',
  'failure_rabbit_outage_report.json',
  'failure_mysql_outage_report.json'
];

const results = { checked: [], errors: [] };

function safeRead(file) {
  try {
    const raw = fs.readFileSync(file, 'utf8');
    return JSON.parse(raw);
  } catch (e) {
    results.errors.push({ file, error: 'read_or_parse_failed', message: e.message });
    return null;
  }
}

function checkReport() {
  for (const fname of reportFiles) {
    const p = path.join(__dirname, fname);
    if (!fs.existsSync(p)) {
      results.errors.push({ file: fname, error: 'missing_file' });
      continue;
    }

    const data = safeRead(p);
    if (!data) continue;

    // Basic structural checks per file
    if (fname === 'report.json') {
      if (typeof data.id !== 'string') results.errors.push({ file: fname, error: 'missing_or_invalid_id' });
      if (typeof data.producer !== 'string') results.errors.push({ file: fname, error: 'missing_or_invalid_producer' });
      if (!Array.isArray(data.attempts)) results.errors.push({ file: fname, error: 'missing_or_invalid_attempts' });
      if (typeof data.success !== 'boolean') results.errors.push({ file: fname, error: 'missing_or_invalid_success' });
    }

    if (fname === 'php_integration_report.json') {
      if (!Array.isArray(data.steps)) results.errors.push({ file: fname, error: 'missing_or_invalid_steps' });
      // Accept either a boolean `success` or an `error` field explaining failure
      if (!(typeof data.success === 'boolean' || (data.error && typeof data.error === 'string'))) {
        results.errors.push({ file: fname, error: 'missing_success_or_error' });
      }
    }

    if (fname === 'negative_invalid_payload_report.json') {
      if (!Array.isArray(data.attempts)) results.errors.push({ file: fname, error: 'missing_or_invalid_attempts' });
      if (data.result !== 'expected_failure') results.errors.push({ file: fname, error: 'unexpected_result', value: data.result });
    }

    if (fname.startsWith('failure_')) {
      if (typeof data.test !== 'string' && !data.test) results.errors.push({ file: fname, error: 'missing_or_invalid_test_field' });
      if (!Array.isArray(data.steps)) results.errors.push({ file: fname, error: 'missing_or_invalid_steps' });
      if (typeof data.success !== 'boolean') results.errors.push({ file: fname, error: 'missing_or_invalid_success' });
    }

    results.checked.push(fname);
  }

  // Write validation report
  const out = path.join(__dirname, 'validation_report.json');
  fs.writeFileSync(out, JSON.stringify(results, null, 2));

  if (results.errors.length > 0) {
    console.error('Report validation failed:', results.errors);
    process.exit(2);
  }

  console.log('All reports validated successfully.');
  process.exit(0);
}

checkReport();
