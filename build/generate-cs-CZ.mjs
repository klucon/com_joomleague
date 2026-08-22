import { readFile, mkdir, writeFile } from 'node:fs/promises';

const root = new URL('../administrator/components/com_joomleague/language/', import.meta.url);
const memories = [
	'/mnt/disk-a/dev/com_joomleague_sync/administrator/components/com_joomleague/language/cs-CZ/com_joomleague.ini',
	'/mnt/disk-a/docker/apps/translate/weblate-data/vcs/com_joomleague/source/cs-CZ.ini',
];

function parseIni(text) {
	const rows = [];
	for (const line of text.split(/\r?\n/)) {
		const match = line.match(/^([A-Z][A-Z0-9_]*)="(.*)"$/);
		if (match) rows.push([match[1], match[2].replace(/\\"/g, '"').replace(/\\n/g, '\n')]);
	}
	return rows;
}

function protect(text) {
	const values = [];
	const protectedText = text.replace(/https?:\/\/\S+|<[^>]+>|%\d*\$?[bcdeEfFgGosuxX]|%[A-Z0-9_]+%|\\n|\{[A-Z0-9_]+\}/g, value => {
		values.push(value);
		return ` __KSM_PH_${values.length - 1}__ `;
	});
	return [protectedText, values];
}

function restore(text, values) {
	for (let index = 0; index < values.length; index++) text = text.replaceAll(`__KSM_PH_${index}__`, values[index]);
	return text.replace(/\s+([,.!?;:])/g, '$1').trim()
		.replaceAll('Joomleague', 'JoomLeague')
		.replaceAll('joomleague', 'JoomLeague')
		.replaceAll('Javascript', 'JavaScript')
		.replaceAll('Php', 'PHP')
		.replaceAll('sportovní typ', 'typ sportu')
		.replaceAll('Sportovní typ', 'Typ sportu')
		.replaceAll('umístění', 'pořadí');
}

async function translate(text) {
	if (!/[A-Za-z]/.test(text) || /^(JoomLeague|SQL|JSON|ID|URL|UTC|VAR|MMA|Esports)$/.test(text)) return text;
	const [input, placeholders] = protect(text);
	const url = new URL('https://translate.googleapis.com/translate_a/single');
	url.search = new URLSearchParams({ client: 'gtx', sl: 'en', tl: 'cs', dt: 't', q: input });
	const response = await fetch(url, { signal: AbortSignal.timeout(30000) });
	if (!response.ok) throw new Error(`Translation service returned HTTP ${response.status}.`);
	const payload = await response.json();
	return restore(payload[0].map(part => part?.[0] ?? '').join(''), placeholders);
}

async function mapConcurrent(items, limit, callback) {
	const output = new Array(items.length);
	let cursor = 0;
	await Promise.all(Array.from({ length: limit }, async () => {
		while (cursor < items.length) {
			const index = cursor++;
			for (let attempt = 1; attempt <= 4; attempt++) {
				try { output[index] = await callback(items[index]); break; }
				catch (error) { if (attempt === 4) throw error; await new Promise(resolve => setTimeout(resolve, attempt * 1000)); }
			}
			if ((index + 1) % 100 === 0) process.stdout.write(`Translated ${index + 1}/${items.length}\n`);
		}
	}));
	return output;
}

const memory = new Map();
for (const file of memories) {
	try { for (const row of parseIni(await readFile(file, 'utf8'))) memory.set(row[0], row[1]); } catch {}
}

for (const filename of ['com_joomleague.ini', 'com_joomleague.sys.ini']) {
	const source = parseIni(await readFile(new URL(`en-GB/${filename}`, root), 'utf8'));
	const missing = source.filter(([key]) => !memory.has(key));
	const translated = await mapConcurrent(missing, 12, async ([key, value]) => [key, await translate(value)]);
	const values = new Map([...memory, ...translated]);
	const lines = ['; JoomLeague 6.2 Czech translation', '; Generated from the canonical en-GB key set and reviewed terminology.', ''];
	for (const [key] of source) lines.push(`${key}="${values.get(key).replaceAll('\\', '\\\\').replaceAll('"', '\\"').replaceAll('\n', '\\n')}"`);
	const outputDir = new URL('cs-CZ/', root);
	await mkdir(outputDir, { recursive: true });
	await writeFile(new URL(filename, outputDir), `${lines.join('\n')}\n`);
	process.stdout.write(`${filename}: ${source.length} keys, ${missing.length} newly translated\n`);
}
