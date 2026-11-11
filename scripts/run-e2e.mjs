import { createServer } from 'node:net';
import { spawn } from 'node:child_process';
import { closeSync, copyFileSync, existsSync, mkdirSync, mkdtempSync, openSync, rmSync, writeFileSync } from 'node:fs';
import { join, resolve } from 'node:path';
import { randomBytes } from 'node:crypto';
import { fileURLToPath } from 'node:url';

const root = resolve(fileURLToPath(new URL('.', import.meta.url)), '..');
const runs = join(root, '.hrms-lite-e2e');
mkdirSync(runs, { recursive: true });
const run = mkdtempSync(join(runs, 'run-'));
if (!run.startsWith(runs + (process.platform === 'win32' ? '\\' : '/'))) {
    throw new Error('Unsafe E2E run path');
}

const database = join(run, 'hrms-lite-e2e.sqlite');
const serverLog = join(run, 'server.log');
writeFileSync(database, '');

const password = randomBytes(18).toString('base64url') + 'Aa1!';
const php = process.env.E2E_PHP || process.env.PHP_BINARY || 'php';
const fixturePng = join(root, 'tests', 'e2e', 'fixtures', 'face.png');

const port = await new Promise((resolvePort, reject) => {
    const socket = createServer();
    socket.once('error', reject);
    socket.listen(0, '127.0.0.1', () => {
        const address = socket.address();
        socket.close(() => resolvePort(address.port));
    });
});

const baseURL = `http://127.0.0.1:${port}`;
const env = {
    ...process.env,
    APP_ENV: 'testing',
    APP_DEBUG: 'true',
    APP_URL: baseURL,
    APP_KEY: 'base64:' + randomBytes(32).toString('base64'),
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: database,
    DB_URL: '',
    CACHE_STORE: 'array',
    SESSION_DRIVER: 'file',
    QUEUE_CONNECTION: 'sync',
    MAIL_MAILER: 'array',
    FILESYSTEM_DISK: 'local',
    E2E_PASSWORD: password,
    E2E_BASE_URL: baseURL,
    E2E_DB_ID: database,
    E2E_FIXTURE_PNG: fixturePng,
    E2E_SUPER_EMAIL: 'super@hrms-lite-e2e.example.invalid',
    E2E_OTHER_EMAIL: 'other@hrms-lite-e2e.example.invalid',
    HRMS_DISPLAY_TIMEZONE: 'Asia/Dhaka',
};

const runProcess = (command, args, options = {}) => new Promise((resolveRun, reject) => {
    const child = spawn(command, args, {
        cwd: root,
        env,
        stdio: options.stdio || 'inherit',
        windowsHide: true,
        shell: options.shell || false,
    });
    child.once('error', reject);
    child.once('exit', (code) => resolveRun(code ?? 1));
});

let server;
let serverLogFd;

try {
    if (!existsSync(join(root, 'public', 'build', 'manifest.json'))) {
        const buildCode = await runProcess(process.platform === 'win32' ? 'npm.cmd' : 'npm', ['run', 'build'], { shell: true });
        if (buildCode !== 0) {
            throw new Error('E2E frontend build failed');
        }
    }

    if (await runProcess(php, ['artisan', 'migrate', '--force', '--no-interaction']) !== 0) {
        throw new Error('E2E migration failed');
    }
    if (await runProcess(php, ['artisan', 'hrms-lite:e2e-fixture']) !== 0) {
        throw new Error('E2E fixture refused');
    }

    serverLogFd = openSync(serverLog, 'w');
    server = spawn(php, ['artisan', 'serve', '--no-reload', `--host=127.0.0.1`, `--port=${port}`], {
        cwd: root,
        env,
        stdio: ['ignore', serverLogFd, serverLogFd],
        windowsHide: true,
    });

    let ready = false;
    let lastHealthError = '';
    for (let i = 0; i < 60; i++) {
        try {
            const response = await fetch(`${baseURL}/up`);
            if (response.ok) {
                ready = true;
                break;
            }
            lastHealthError = `HTTP ${response.status}`;
        } catch (error) {
            lastHealthError = String(error);
        }
        await new Promise((r) => setTimeout(r, 500));
    }
    if (!ready) {
        throw new Error(`E2E server did not become healthy: ${lastHealthError}; server log: ${serverLog}`);
    }

    const playwrightCli = join(root, 'node_modules', '@playwright', 'test', 'cli.js');
    const code = await runProcess(process.execPath, [playwrightCli, 'test', ...process.argv.slice(2)]);
    process.exitCode = code;
} finally {
    if (server) {
        server.kill();
    }
    if (serverLogFd !== undefined) {
        closeSync(serverLogFd);
    }
    if (existsSync(run) && process.env.E2E_KEEP_RUN !== '1') {
        rmSync(run, { recursive: true, force: true });
    }
    if (process.env.E2E_KEEP_RUN === '1') {
        process.stderr.write(`Synthetic E2E run retained locally: ${run}\n`);
    }
}
