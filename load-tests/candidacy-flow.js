import http from 'k6/http';
import { check, group } from 'k6';
import { Counter, Trend } from 'k6/metrics';
import exec from 'k6/execution';

/**
 * Reproduces the API's core write-then-read flow under concurrency:
 * register a candidate, assign it to an evaluator (the path guarded by
 * SELECT ... FOR UPDATE against double assignment), then read the
 * consolidated evaluators view (the GROUP_CONCAT aggregate query).
 *
 * Run against a LOCAL stack only. See load-tests/README.md.
 */

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';
const EMAIL = __ENV.API_EMAIL || 'test@example.com';
const PASSWORD = __ENV.API_PASSWORD || 'password';
const CANDIDATES = Number(__ENV.CANDIDATES || 100);

/** Evaluator.MAX_CONCURRENT_CANDIDATES in the domain layer. */
const SLOTS_PER_EVALUATOR = 10;

/**
 * 409 means "evaluator at capacity" or "already assigned": the API answering
 * correctly under contention, not a failure. Without this k6 would fold those
 * into http_req_failed and make a healthy run look 16% broken.
 */
http.setResponseCallback(http.expectedStatuses({ min: 200, max: 299 }, 409));

const serverErrors = new Counter('server_errors_5xx');
const throttled = new Counter('throttled_429');
const doubleAssignmentConflicts = new Counter('assignment_conflicts_409');
const registerDuration = new Trend('candidate_register_duration', true);
const assignDuration = new Trend('candidate_assign_duration', true);
const consolidatedDuration = new Trend('consolidated_read_duration', true);
const consolidatedBaseline = new Trend('consolidated_baseline_duration', true);

export const options = {
  scenarios: {
    // Writes and reads interleaved: this is what the headline claim covers.
    candidacy_flow: {
      executor: 'shared-iterations',
      exec: 'candidacyFlow',
      vus: 10,
      iterations: CANDIDATES,
      maxDuration: '3m',
    },
    // Reads only, once the writes are done. Separates the cost of the
    // GROUP_CONCAT aggregate itself from contention with the write path,
    // which holds row locks and invalidates the consolidated cache.
    consolidated_baseline: {
      executor: 'constant-vus',
      exec: 'consolidatedBaselineRead',
      vus: 5,
      duration: '20s',
      startTime: '3m',
    },
  },
  thresholds: {
    // The headline claim: not one request may fail with a 5xx.
    server_errors_5xx: ['count==0'],
    http_req_failed: ['rate<0.01'],
    candidate_register_duration: ['p(95)<1500'],
    // Under concurrent writes. Deliberately loose: it documents the observed
    // ceiling on this machine rather than asserting a performance target.
    consolidated_read_duration: ['p(95)<6000'],
    // Uncontended, which is what the query actually costs.
    consolidated_baseline_duration: ['p(95)<400'],
  },
};

function authHeaders(token) {
  return {
    headers: {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
  };
}

/** Logs in once and shares the token and an evaluator pool with every VU. */
export function setup() {
  const login = http.post(
    `${BASE_URL}/api/login`,
    JSON.stringify({ email: EMAIL, password: PASSWORD }),
    { headers: { 'Content-Type': 'application/json', Accept: 'application/json' } },
  );

  check(login, { 'login succeeded': (r) => r.status === 200 });
  if (login.status !== 200) {
    throw new Error(`Login failed (${login.status}): ${login.body}`);
  }

  const token = login.json('token');
  const stamp = Date.now();

  // Enough capacity for every candidate in the run, plus headroom: with a
  // short pool the run would measure the capacity rule rejecting assignments
  // rather than the assignment path itself.
  const evaluatorCount = Math.ceil(CANDIDATES / SLOTS_PER_EVALUATOR) + 2;
  const evaluatorIds = [];
  for (let i = 0; i < evaluatorCount; i++) {
    const created = http.post(
      `${BASE_URL}/api/v1/evaluators`,
      JSON.stringify({
        name: `Load Evaluator ${i}`,
        email: `load.evaluator.${stamp}.${i}@example.com`,
        specialty: 'Backend',
      }),
      authHeaders(token),
    );

    if (created.status === 201) {
      evaluatorIds.push(created.json('data.id'));
    }
  }

  if (evaluatorIds.length === 0) {
    throw new Error('No evaluator could be created; aborting run.');
  }

  return { token, evaluatorIds, stamp };
}

function track(response) {
  if (response.status >= 500) {
    serverErrors.add(1);
  }
  if (response.status === 429) {
    throttled.add(1);
  }
}

export function candidacyFlow(data) {
  const { token, evaluatorIds, stamp } = data;
  const auth = authHeaders(token);
  const unique = `${stamp}-${exec.scenario.iterationInTest}`;

  let candidateId = null;

  group('register candidate', () => {
    const response = http.post(
      `${BASE_URL}/api/v1/candidates`,
      JSON.stringify({
        name: `Load Candidate ${unique}`,
        email: `load.candidate.${unique}@example.com`,
        years_of_experience: 3 + (exec.scenario.iterationInTest % 8),
        primary_specialty: 'Backend',
        cv:
          'Backend engineer with production PHP and Laravel experience, ' +
          'REST APIs, MySQL and Redis. Load-test fixture.',
      }),
      auth,
    );

    track(response);
    registerDuration.add(response.timings.duration);
    check(response, { 'candidate created (201)': (r) => r.status === 201 });

    candidateId = response.json('data.id');
  });

  if (candidateId) {
    group('assign candidate', () => {
      const evaluatorId = evaluatorIds[exec.scenario.iterationInTest % evaluatorIds.length];

      const response = http.post(
        `${BASE_URL}/api/v1/evaluators/${evaluatorId}/assign-candidate`,
        JSON.stringify({ candidate_id: candidateId }),
        auth,
      );

      track(response);
      assignDuration.add(response.timings.duration);

      // 409 is a correct answer here (evaluator at capacity, or already
      // assigned): it is contention handled, not a failure.
      if (response.status === 409) {
        doubleAssignmentConflicts.add(1);
      }

      // Assignment answers 200 on success and 409 on contention.
      check(response, {
        'assignment resolved without a server error': (r) =>
          r.status === 200 || r.status === 409 || r.status === 422,
      });
    });
  }

  group('read consolidated', () => {
    const response = http.get(
      `${BASE_URL}/api/v1/evaluators/consolidated?per_page=15`,
      auth,
    );

    track(response);
    consolidatedDuration.add(response.timings.duration);
    check(response, { 'consolidated read (200)': (r) => r.status === 200 });
  });
}

/** Reads the consolidated view with no writes in flight. */
export function consolidatedBaselineRead(data) {
  const response = http.get(
    `${BASE_URL}/api/v1/evaluators/consolidated?per_page=15`,
    authHeaders(data.token),
  );

  track(response);
  consolidatedBaseline.add(response.timings.duration);
  check(response, { 'consolidated baseline read (200)': (r) => r.status === 200 });
}

function ms(value) {
  return value === undefined ? 'n/a' : `${value.toFixed(1)} ms`;
}

function metricRows(data) {
  const trend = (name) => data.metrics[name]?.values ?? {};
  return [
    ['Register candidate', trend('candidate_register_duration')],
    ['Assign candidate', trend('candidate_assign_duration')],
    ['Consolidated read (under write load)', trend('consolidated_read_duration')],
    ['Consolidated read (no writes in flight)', trend('consolidated_baseline_duration')],
  ];
}

/**
 * Writes the run's artifacts next to the script, so the numbers quoted in the
 * README always have the output that produced them sitting beside them.
 */
export function handleSummary(data) {
  const checks = data.metrics.checks?.values ?? {};
  const reqs = data.metrics.http_reqs?.values?.count ?? 0;
  const failedRate = data.metrics.http_req_failed?.values?.rate ?? 0;
  const errors = data.metrics.server_errors_5xx?.values?.count ?? 0;

  const rows = metricRows(data)
    .map(
      ([label, v]) => `<tr><td>${label}</td><td>${ms(v.avg)}</td><td>${ms(v.med)}</td>` +
        `<td>${ms(v['p(95)'])}</td><td>${ms(v.max)}</td></tr>`,
    )
    .join('\n      ');

  const html = `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>k6 load test - Candidacy Management API</title>
<style>
  body { font-family: ui-sans-serif, system-ui, sans-serif; margin: 2rem auto; max-width: 60rem;
         padding: 0 1rem; line-height: 1.5; color: #1a1a1a; background: #fff; }
  h1 { font-size: 1.5rem; } h2 { font-size: 1.1rem; margin-top: 2rem; }
  table { border-collapse: collapse; width: 100%; margin-top: .5rem; }
  th, td { border: 1px solid #ddd; padding: .5rem .6rem; text-align: left; font-size: .9rem; }
  th { background: #f5f5f5; }
  .ok { color: #0a7d33; font-weight: 600; } .bad { color: #b00020; font-weight: 600; }
  .note { background: #fffbe6; border-left: 4px solid #e6c200; padding: .75rem 1rem; font-size: .9rem; }
  code { background: #f2f2f2; padding: .1rem .3rem; border-radius: 3px; }
</style>
</head>
<body>
<h1>k6 load test &mdash; Candidacy Management API</h1>
<p class="note"><strong>Local run.</strong> Executed against the Docker Compose stack on a
development machine, not against the production VPS. Treat these figures as a regression
baseline, not as production capacity.</p>

<h2>Headline</h2>
<table>
  <tr><th>Metric</th><th>Value</th></tr>
  <tr><td>HTTP requests</td><td>${reqs}</td></tr>
  <tr><td>Responses with a 5xx</td><td class="${errors === 0 ? 'ok' : 'bad'}">${errors}</td></tr>
  <tr><td>Failed requests</td><td class="${failedRate === 0 ? 'ok' : 'bad'}">${(failedRate * 100).toFixed(2)}%</td></tr>
  <tr><td>Checks passed</td><td>${checks.passes ?? 0} / ${(checks.passes ?? 0) + (checks.fails ?? 0)}</td></tr>
</table>

<h2>Latency by step</h2>
<table>
  <tr><th>Step</th><th>avg</th><th>median</th><th>p95</th><th>max</th></tr>
      ${rows}
</table>

<h2>Generated</h2>
<p>${new Date().toISOString()} &mdash; scenario <code>load-tests/candidacy-flow.js</code></p>
</body>
</html>`;

  const pad = (label) => label.padEnd(40, '.');
  const text = [
    '',
    '  Candidacy Management API - k6 load test (LOCAL run, not the VPS)',
    '',
    `  ${pad('http requests')}: ${reqs}`,
    `  ${pad('responses with a 5xx')}: ${errors}`,
    `  ${pad('failed requests')}: ${(failedRate * 100).toFixed(2)}%`,
    `  ${pad('checks passed')}: ${checks.passes ?? 0} / ${(checks.passes ?? 0) + (checks.fails ?? 0)}`,
    '',
    '  latency                                      avg       median    p95       max',
    ...metricRows(data).map(([label, v]) =>
      `  ${label.padEnd(44, ' ')} ${ms(v.avg).padEnd(9)} ${ms(v.med).padEnd(9)} ` +
      `${ms(v['p(95)']).padEnd(9)} ${ms(v.max)}`),
    '',
  ].join('\n');

  return {
    stdout: text,
    'results/summary.txt': text,
    'results/summary.json': JSON.stringify(data, null, 2),
    'results/report.html': html,
  };
}
