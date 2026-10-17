import fs from 'node:fs';
import crypto from 'node:crypto';
import {execFileSync} from 'node:child_process';
const dir = '/mnt/disk-a/dev/backups/team-encounter-' + new Date().toISOString().replaceAll(':', '-');
fs.mkdirSync(dir, {recursive: true, mode: 0o700});
for (const [name, args] of [
  ['database.sql', ['exec', 'local-joomla62-db', 'sh', '-c', 'exec mariadb-dump -uroot -p"$(cat /run/secrets/root-password)" --single-transaction --routines --triggers joomla']],
  ['webroot.tar', ['exec', 'local-joomla62-app', 'tar', '-cf', '-', '-C', '/var/www/html', '.']],
]) {
  const fd = fs.openSync(dir + '/' + name, 'w', 0o600);
  try { execFileSync('docker', args, {stdio: ['ignore', fd, 'pipe']}); } finally { fs.closeSync(fd); }
}
const sql = fs.readFileSync(dir + '/database.sql', 'utf8');
if (!sql.includes('Dump completed') || !sql.includes('CREATE TABLE')) throw Error('Invalid database backup.');
execFileSync('tar', ['-tf', dir + '/webroot.tar'], {stdio: 'ignore'});
fs.writeFileSync(dir + '/SHA256SUMS', ['database.sql', 'webroot.tar'].map(n => crypto.createHash('sha256').update(fs.readFileSync(dir + '/' + n)).digest('hex') + '  ' + n).join('\n') + '\n', {mode: 0o600});
console.log('Verified backup: ' + dir);
