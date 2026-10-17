import fs from 'node:fs';
import crypto from 'node:crypto';
import {execFileSync} from 'node:child_process';
const dir = '/mnt/disk-a/dev/backups/demo-tennis-encounter-' + new Date().toISOString().replaceAll(':', '-');
fs.mkdirSync(dir, {mode: 0o700});
for (const [name, args] of [
  ['database.sql', ['exec', 'demo-joomleague-eu-db', 'sh', '-c', 'exec mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" --single-transaction --routines --triggers demo_joomleague_eu']],
  ['webroot.tar', ['exec', 'demo-joomleague-eu-app', 'tar', '-cf', '-', '-C', '/var/www/html', '.']],
]) {
  const fd = fs.openSync(dir + '/' + name, 'w', 0o600);
  try { execFileSync('docker', args, {stdio: ['ignore', fd, 'pipe']}); } finally { fs.closeSync(fd); }
}
if (!fs.readFileSync(dir + '/database.sql', 'utf8').includes('Dump completed')) throw Error('Incomplete dump');
execFileSync('tar', ['-tf', dir + '/webroot.tar'], {stdio: 'ignore'});
fs.writeFileSync(dir + '/SHA256SUMS', ['database.sql', 'webroot.tar'].map(n => crypto.createHash('sha256').update(fs.readFileSync(dir + '/' + n)).digest('hex') + '  ' + n).join('\n') + '\n', {mode: 0o600});
console.log('Verified backup: ' + dir);
