const { chromium } = require('playwright');
const fs = require('node:fs');
const {runtimeFailurePattern} = require('./audit-result.cjs');

const baseUrl = process.env.JOOMLA_BASE_URL || 'https://demo.joomleague.eu';
const projectId = process.env.JOOMLA_PROJECT_ID || '3';
const stageId = process.env.JOOMLA_STAGE_ID || '3';
const entryId = process.env.JOOMLA_ENTRY_ID || '98';
const eventId = process.env.JOOMLA_EVENT_ID || '1408';
const cases = [
	['aboutjoomleague', 'index.php?option=com_joomleague&view=aboutjoomleague'],
	['bracket', `index.php?option=com_joomleague&view=bracket&project_id=${projectId}&stage_id=${stageId}`],
	['club', 'index.php?option=com_joomleague&view=club&club_id=1'],
	['clubpeople', 'index.php?option=com_joomleague&view=clubpeople&club_id=1'],
	['clubplan', `index.php?option=com_joomleague&view=clubplan&project_id=${projectId}&club_id=1`],
	['clubrivals', 'index.php?option=com_joomleague&view=clubrivals&club_id=1'],
	['clubs', 'index.php?option=com_joomleague&view=clubs'],
	['clubstaffhistory', 'index.php?option=com_joomleague&view=clubstaffhistory&club_id=1'],
	['comparison', `index.php?option=com_joomleague&view=comparison&project_id=${projectId}`],
	['eventranking', `index.php?option=com_joomleague&view=eventranking&project_id=${projectId}&event_code=assist`],
	['eventreport', `index.php?option=com_joomleague&view=eventreport&event_id=${eventId}`],
	['nextmatch', `index.php?option=com_joomleague&view=nextmatch&project_id=${projectId}`],
	['participant', `index.php?option=com_joomleague&view=participant&project_id=${projectId}&entry_id=${entryId}`],
	['participants', `index.php?option=com_joomleague&view=participants&project_id=${projectId}`],
	['participantstats', `index.php?option=com_joomleague&view=participantstats&project_id=${projectId}&entry_id=${entryId}`],
	['person', 'index.php?option=com_joomleague&view=person&person_id=6'],
	['personnel', `index.php?option=com_joomleague&view=personnel&project_id=${projectId}`],
	['programitem', `index.php?option=com_joomleague&view=programitem&event_id=${eventId}`],
	['project', `component/joomleague/project/${projectId}`],
	['projects', 'index.php?option=com_joomleague&view=projects'],
	['resultmatrix', `index.php?option=com_joomleague&view=resultmatrix&project_id=${projectId}`],
	['results', `index.php?option=com_joomleague&view=results&project_id=${projectId}`],
	['standingprogression', `index.php?option=com_joomleague&view=standingprogression&project_id=${projectId}`],
	['standings', `index.php?option=com_joomleague&view=standings&project_id=${projectId}`],
	['statisticsoverview', `index.php?option=com_joomleague&view=statisticsoverview&project_id=${projectId}`],
	['statranking', `index.php?option=com_joomleague&view=statranking&project_id=${projectId}&statistic_code=corners`],
	['team', 'index.php?option=com_joomleague&view=team&team_id=1'],
	['teamplan', `index.php?option=com_joomleague&view=teamplan&project_id=${projectId}&entry_id=${entryId}`],
	['venue', 'index.php?option=com_joomleague&view=venue&venue_id=1'],
	['venues', 'index.php?option=com_joomleague&view=venues'],
];
const emptyCases = [
	['clubs-empty', 'index.php?option=com_joomleague&view=clubs&filter_search=definitely-no-such-club-xyz'],
	['venues-empty', 'index.php?option=com_joomleague&view=venues&filter_search=definitely-no-such-venue-xyz'],
];
const metadataCases = process.env.JOOMLA_SECOND_ENTRY_ID ? [
	['comparison-selected', `index.php?option=com_joomleague&view=comparison&project_id=${projectId}&first_id=${entryId}&second_id=${process.env.JOOMLA_SECOND_ENTRY_ID}`],
] : [];
const missingCases = [
	['club-missing', 'index.php?option=com_joomleague&view=club&club_id=999999999'],
	['team-missing', 'index.php?option=com_joomleague&view=team&team_id=999999999'],
	['person-missing', 'index.php?option=com_joomleague&view=person&person_id=999999999'],
	['venue-missing', 'index.php?option=com_joomleague&view=venue&venue_id=999999999'],
	['project-missing', 'index.php?option=com_joomleague&view=project&project_id=999999999'],
	['participant-missing', 'index.php?option=com_joomleague&view=participant&project_id=1&entry_id=999999999'],
	['programitem-missing', 'index.php?option=com_joomleague&view=programitem&event_id=999999999'],
];
const profileIds = process.env.JOOMLA_PROFILE_PROJECT_IDS === undefined
	? Array.from({ length: 15 }, (_, index) => index + 1)
	: process.env.JOOMLA_PROFILE_PROJECT_IDS.split(',').filter(Boolean).map(Number);
if (profileIds.some((id) => !Number.isSafeInteger(id) || id < 1)) throw new Error('Invalid profile project IDs.');
const profileCases = profileIds.map((id) => [`profile-project-${id}`, `component/joomleague/project/${id}`]);

const failurePattern = /(?:COM_JOOMLEAGUE_[A-Z0-9_]+|An error has occurred)/;

(async () => {
	const browser = await chromium.launch({ headless: true });
	const failures = [];
	const internalLinks = new Set();
	const observations = [];
	const missingObservations = [];
	const context = await browser.newContext();

	try {
		if (process.env.JOOMLA_CREDENTIALS_FILE) {
			const credentials = fs.readFileSync(process.env.JOOMLA_CREDENTIALS_FILE, 'utf8');
			const username = credentials.match(/Username: `([^`]+)`/i)?.[1];
			const password = credentials.match(/Password: `([^`]+)`/i)?.[1];
			if (!username || !password) throw new Error('Credentials file has no username/password.');
			const login = await context.newPage();
			await login.goto(baseUrl, { waitUntil: 'domcontentloaded' });
			await login.locator('input[name="username"]').fill(username);
			await login.locator('input[name="password"]').fill(password);
			await Promise.all([login.waitForNavigation(), login.locator('button[type="submit"]').click()]);
			if (await login.locator('input[name="password"]').count()) throw new Error('Offline frontend login failed.');
			await login.close();
		}
		for (const viewport of [{ width: 1440, height: 1000 }, { width: 390, height: 844 }]) {
			const page = await context.newPage();
			await page.setViewportSize(viewport);
			for (const [view, relativeUrl] of [...cases, ...emptyCases, ...metadataCases, ...profileCases]) {
				const runtimeErrors = [];
				page.removeAllListeners('pageerror');
				page.removeAllListeners('console');
				page.on('pageerror', (error) => runtimeErrors.push(error.message));
				page.on('console', (message) => {
					if (message.type() === 'error' && !message.text().startsWith('Failed to load resource')) runtimeErrors.push(message.text());
				});
				const response = await page.goto(new URL(relativeUrl, `${baseUrl}/`).toString(), {
					waitUntil: 'domcontentloaded',
					timeout: 45000,
				});

				if (!response || response.status() >= 400) {
					failures.push(`${view}@${viewport.width}: HTTP ${response ? response.status() : 'no response'}`);
					continue;
				}

				const main = page.locator('main');
				if (await main.count() === 0) {
					failures.push(`${view}@${viewport.width}: missing main landmark`);
					continue;
				}

				const text = await main.innerText();
				const overflow = await page.evaluate(() => Math.max(
					document.documentElement.scrollWidth - document.documentElement.clientWidth,
					document.body.scrollWidth - document.body.clientWidth,
				));

				if (failurePattern.test(text) || runtimeFailurePattern.test(text)) failures.push(`${view}@${viewport.width}: error or untranslated key`);
				if (!text.trim()) failures.push(`${view}@${viewport.width}: empty main content`);
				if (overflow > 1) failures.push(`${view}@${viewport.width}: horizontal overflow ${overflow}px`);
				if (runtimeErrors.length) failures.push(`${view}@${viewport.width}: ${runtimeErrors.join('; ')}`);

				const canonicalCount = await page.locator('link[rel="canonical"]').count();
				const description = await page.locator('meta[name="description"]').getAttribute('content');
				const headingCount = await main.locator('h1').count();
				const canonical = await page.locator('link[rel="canonical"]').first().getAttribute('href').catch(() => null);
				const schemas = await page.locator('script[type="application/ld+json"]').allTextContents();
				for (const schema of schemas) {
					try { JSON.parse(schema); } catch { failures.push(`${view}@${viewport.width}: invalid JSON-LD`); }
				}
				if (canonical && (!canonical.startsWith(new URL(baseUrl).origin + '/') || canonical.includes('&amp;'))) failures.push(`${view}@${viewport.width}: invalid canonical URL`);
				observations.push({ view, viewport: viewport.width, status: response.status(), url: page.url(), title: await page.title(), canonical, description, headingCount, overflow, schemaCount: schemas.length });
				const ogUrl = await page.locator('meta[property="og:url"]').getAttribute('content');
				const ogTitle = await page.locator('meta[property="og:title"]').getAttribute('content');
				if (ogUrl !== canonical) failures.push(`${view}@${viewport.width}: Open Graph URL differs from canonical`);
				if (!ogTitle?.trim()) failures.push(`${view}@${viewport.width}: missing Open Graph title`);
				if (canonicalCount !== 1) failures.push(`${view}@${viewport.width}: expected one canonical, got ${canonicalCount}`);
				if (!description || !description.trim()) failures.push(`${view}@${viewport.width}: missing meta description`);
				if (headingCount !== 1) failures.push(`${view}@${viewport.width}: expected one H1, got ${headingCount}`);
				if (view.includes('-empty')) {
					const robots = await page.locator('meta[name="robots"]').getAttribute('content');
					if (robots !== 'noindex,follow') failures.push(`${view}@${viewport.width}: search result is indexable`);
				}

				if (viewport.width === 1440 && !view.includes('-empty') && !view.includes('-missing')) {
					for (const href of await main.locator('a[href]').evaluateAll((links) => links.map((link) => link.href))) {
						const url = new URL(href);
						if (url.origin === new URL(baseUrl).origin && !url.pathname.includes('/administrator') && !url.searchParams.has('task') && !url.searchParams.has('format') && url.pathname !== '/') {
							url.hash = '';
							internalLinks.add(url.toString());
						}
					}
				}
			}
			for (const [view, relativeUrl] of missingCases) {
				const response = await context.request.get(new URL(relativeUrl.replace(/^index\.php/, ''), `${baseUrl}/`).toString(), { maxRedirects: 0, timeout: 45000 });
				const status = response.status();
				const location = response.headers().location || '';
				const offlineRedirect = process.env.JOOMLA_OFFLINE_AUDIT === '1' && status === 303 && new URL(location, baseUrl).pathname === '/index.php';
				missingObservations.push({ view, viewport: viewport.width, status, location, offlineRedirect });
				if (status !== 404 && !offlineRedirect) failures.push(`${view}@${viewport.width}: expected HTTP 404, got ${status}`);
			}
			await page.close();
			console.log(`Viewport ${viewport.width}: page checks completed`);
		}

		const request = await context.newPage();
		let checkedLinks = 0;
		for (const link of internalLinks) {
			const response = await request.request.get(link, { timeout: 45000 });
			if (response.status() >= 400) failures.push(`internal link HTTP ${response.status()}: ${link}`);
			if (new URL(response.url()).pathname === '/' && new URL(link).pathname !== '/') failures.push(`internal link unexpectedly redirected to homepage: ${link}`);
			if (++checkedLinks % 100 === 0) console.log(`Internal links checked: ${checkedLinks}/${internalLinks.size}`);
		}
		await request.close();
	} finally {
		await browser.close();
	}
	if (process.env.JOOMLA_AUDIT_REPORT) {
		fs.writeFileSync(process.env.JOOMLA_AUDIT_REPORT, JSON.stringify({ baseUrl, authenticated: Boolean(process.env.JOOMLA_CREDENTIALS_FILE), observations, missingObservations, failures, internalLinks: internalLinks.size }, null, 2), { mode: 0o600 });
		fs.chmodSync(process.env.JOOMLA_AUDIT_REPORT, 0o600);
	}

	if (failures.length) {
		throw new Error(`Public frontend audit failed:\n- ${failures.join('\n- ')}`);
	}

	console.log(`PUBLIC_FRONTEND_AUDIT_OK views=${cases.length} profiles=${profileCases.length} empty_cases=${emptyCases.length} metadata_cases=${metadataCases.length} missing_cases=${missingCases.length} offline_redirects=${missingObservations.filter((item) => item.offlineRedirect).length} viewports=2 internal_links=${internalLinks.size}`);
})().catch((error) => {
	console.error(error.message || error);
	process.exit(1);
});
