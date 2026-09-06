const { chromium } = require('playwright');

const baseUrl = process.env.JOOMLA_BASE_URL || 'https://demo.joomleague.eu';
const cases = [
	['about', 'index.php?option=com_joomleague&view=about'],
	['bracket', 'index.php?option=com_joomleague&view=bracket&project_id=1&stage_id=17'],
	['club', 'index.php?option=com_joomleague&view=club&club_id=1'],
	['clubplan', 'index.php?option=com_joomleague&view=clubplan&project_id=1&club_id=1'],
	['clubs', 'index.php?option=com_joomleague&view=clubs'],
	['comparison', 'index.php?option=com_joomleague&view=comparison&project_id=1'],
	['eventranking', 'index.php?option=com_joomleague&view=eventranking&project_id=1&event_code=assist'],
	['eventreport', 'index.php?option=com_joomleague&view=eventreport&event_id=1'],
	['nextmatch', 'index.php?option=com_joomleague&view=nextmatch&project_id=1'],
	['participant', 'index.php?option=com_joomleague&view=participant&project_id=1&entry_id=1'],
	['participants', 'index.php?option=com_joomleague&view=participants&project_id=1'],
	['participantstats', 'index.php?option=com_joomleague&view=participantstats&project_id=1&entry_id=1'],
	['person', 'index.php?option=com_joomleague&view=person&person_id=6'],
	['personnel', 'index.php?option=com_joomleague&view=personnel&project_id=1'],
	['programitem', 'index.php?option=com_joomleague&view=programitem&event_id=1'],
	['project', 'index.php?option=com_joomleague&view=project&project_id=1'],
	['projects', 'index.php?option=com_joomleague&view=projects'],
	['resultmatrix', 'index.php?option=com_joomleague&view=resultmatrix&project_id=1'],
	['results', 'index.php?option=com_joomleague&view=results&project_id=1'],
	['standingprogression', 'index.php?option=com_joomleague&view=standingprogression&project_id=1'],
	['standings', 'index.php?option=com_joomleague&view=standings&project_id=1'],
	['statisticsoverview', 'index.php?option=com_joomleague&view=statisticsoverview&project_id=1'],
	['statranking', 'index.php?option=com_joomleague&view=statranking&project_id=1&statistic_code=corners'],
	['team', 'index.php?option=com_joomleague&view=team&team_id=1'],
	['teamplan', 'index.php?option=com_joomleague&view=teamplan&project_id=1&entry_id=1'],
	['venue', 'index.php?option=com_joomleague&view=venue&venue_id=1'],
	['venues', 'index.php?option=com_joomleague&view=venues'],
];
const edgeCases = [
	['projects-empty', 'index.php?option=com_joomleague&view=projects&filter_search=definitely-no-such-project-xyz'],
	['clubs-empty', 'index.php?option=com_joomleague&view=clubs&filter_search=definitely-no-such-club-xyz'],
	['venues-empty', 'index.php?option=com_joomleague&view=venues&filter_search=definitely-no-such-venue-xyz'],
	['club-missing', 'index.php?option=com_joomleague&view=club&club_id=999999999'],
	['team-missing', 'index.php?option=com_joomleague&view=team&team_id=999999999'],
	['person-missing', 'index.php?option=com_joomleague&view=person&person_id=999999999'],
	['venue-missing', 'index.php?option=com_joomleague&view=venue&venue_id=999999999'],
	['project-missing', 'index.php?option=com_joomleague&view=project&project_id=999999999'],
	['participant-missing', 'index.php?option=com_joomleague&view=participant&project_id=1&entry_id=999999999'],
	['programitem-missing', 'index.php?option=com_joomleague&view=programitem&event_id=999999999'],
];
const profileCases = Array.from({ length: 15 }, (_, index) => [
	`profile-project-${index + 1}`,
	`index.php?option=com_joomleague&view=project&project_id=${index + 1}`,
]);

const failurePattern = /(?:COM_JOOMLEAGUE_[A-Z0-9_]+|Fatal error|Warning:|Notice:|An error has occurred|Call Stack)/;

(async () => {
	const browser = await chromium.launch({ headless: true });
	const failures = [];
	const internalLinks = new Set();

	try {
		for (const viewport of [{ width: 1440, height: 1000 }, { width: 390, height: 844 }]) {
			const page = await browser.newPage({ viewport });
			for (const [view, relativeUrl] of [...cases, ...edgeCases, ...profileCases]) {
				const runtimeErrors = [];
				page.removeAllListeners('pageerror');
				page.removeAllListeners('console');
				page.on('pageerror', (error) => runtimeErrors.push(error.message));
				page.on('console', (message) => {
					if (message.type() === 'error') runtimeErrors.push(message.text());
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

				if (failurePattern.test(text)) failures.push(`${view}@${viewport.width}: error or untranslated key`);
				if (!text.trim()) failures.push(`${view}@${viewport.width}: empty main content`);
				if (overflow > 1) failures.push(`${view}@${viewport.width}: horizontal overflow ${overflow}px`);
				if (runtimeErrors.length) failures.push(`${view}@${viewport.width}: ${runtimeErrors.join('; ')}`);

				if (viewport.width === 1440 && !view.includes('-empty') && !view.includes('-missing')) {
					for (const href of await main.locator('a[href]').evaluateAll((links) => links.map((link) => link.href))) {
						const url = new URL(href);
						if (url.origin === new URL(baseUrl).origin && url.searchParams.get('option') === 'com_joomleague') {
							internalLinks.add(url.toString());
						}
					}
				}
			}
			await page.close();
		}

		const request = await browser.newPage();
		for (const link of internalLinks) {
			const response = await request.request.get(link, { timeout: 45000 });
			if (response.status() >= 400) failures.push(`internal link HTTP ${response.status()}: ${link}`);
		}
		await request.close();
	} finally {
		await browser.close();
	}

	if (failures.length) {
		throw new Error(`Public frontend audit failed:\n- ${failures.join('\n- ')}`);
	}

	console.log(`PUBLIC_FRONTEND_AUDIT_OK views=${cases.length} profiles=${profileCases.length} edge_cases=${edgeCases.length} viewports=2 internal_links=${internalLinks.size}`);
})().catch((error) => {
	console.error(error.message || error);
	process.exit(1);
});
