const { chromium } = require('playwright');

(async () => {
	const baseUrl = process.env.JOOMLA_BASE_URL;
	const projectsPath = process.env.JOOMLA_PROJECTS_MENU_PATH;
	const clubsPath = process.env.JOOMLA_CLUBS_MENU_PATH;
	const venuesPath = process.env.JOOMLA_VENUES_MENU_PATH;
	const projectPath = process.env.JOOMLA_PROJECT_MENU_PATH;
	const participantsPath = process.env.JOOMLA_PARTICIPANTS_MENU_PATH;
	const participantPath = process.env.JOOMLA_PARTICIPANT_MENU_PATH;
	const personPath = process.env.JOOMLA_PERSON_MENU_PATH;
	const clubPath = process.env.JOOMLA_CLUB_MENU_PATH;
	const teamPath = process.env.JOOMLA_TEAM_MENU_PATH;
	const venuePath = process.env.JOOMLA_VENUE_MENU_PATH;
	const expectedProject = process.env.JOOMLA_EXPECTED_PROJECT;
	const expectedParticipant = process.env.JOOMLA_EXPECTED_PARTICIPANT;
	const expectedMember = process.env.JOOMLA_EXPECTED_MEMBER;
	const expectedClub = process.env.JOOMLA_EXPECTED_CLUB;
	const expectedSecondTeam = process.env.JOOMLA_EXPECTED_SECOND_TEAM;
	const expectedVenue = process.env.JOOMLA_EXPECTED_VENUE;

	if (!baseUrl || !projectsPath || !clubsPath || !venuesPath || !projectPath || !participantsPath || !participantPath || !personPath || !clubPath || !teamPath || !venuePath || !expectedProject || !expectedParticipant || !expectedMember || !expectedClub || !expectedSecondTeam || !expectedVenue) {
		throw new Error('Public project view browser test environment is incomplete.');
	}

	const browser = await chromium.launch({ headless: true });

	try {
		for (const viewport of [
			{ width: 1440, height: 1000 },
			{ width: 390, height: 844 },
		]) {
			const page = await browser.newPage({ viewport });
			const errors = [];
			page.on('pageerror', (error) => errors.push(error.message));

			await page.goto(new URL(projectsPath, baseUrl).toString(), { waitUntil: 'domcontentloaded' });
			await page.locator('main').waitFor();
			const projectsText = await page.locator('main').innerText();

			if (!projectsText.includes(expectedProject) || /COM_JOOMLEAGUE_[A-Z0-9_]+/.test(projectsText)) {
				throw new Error(`Competition catalogue is incomplete or untranslated at ${viewport.width}px.`);
			}

			const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
			if (overflow > 1) {
				throw new Error(`Competition catalogue overflows by ${overflow}px at ${viewport.width}px.`);
			}

			await page.goto(new URL(clubsPath, baseUrl).toString(), { waitUntil: 'domcontentloaded' });
			await page.locator('main').waitFor();
			const clubsText = await page.locator('main').innerText();
			const clubsOverflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);

			if (!clubsText.includes(expectedClub)
				|| !/Týmy: 2|Teams: 2/.test(clubsText)
				|| /COM_JOOMLEAGUE_[A-Z0-9_]+/.test(clubsText)
				|| clubsOverflow > 1
				|| errors.length > 0) {
				throw new Error(`Club catalogue failed at ${viewport.width}px: overflow=${clubsOverflow}, errors=${errors.join('; ')}`);
			}

			if (await page.locator('#filter_country_code option[value="CZ"]').count() !== 1) {
				throw new Error(`Club country filter is incomplete at ${viewport.width}px.`);
			}

			await page.locator('#filter_search').fill('club-that-does-not-exist');
			await page.locator('form').filter({ has: page.locator('#filter_search') }).locator('button[type="submit"]').click();
			await page.waitForLoadState('domcontentloaded');
			const filteredText = await page.locator('main').innerText();
			if (!/No published clubs|neodpovídá žádný zveřejněný klub/.test(filteredText)) {
				throw new Error(`Club search filter did not return the expected empty state at ${viewport.width}px.`);
			}

			await page.goto(new URL(venuesPath, baseUrl).toString(), { waitUntil: 'domcontentloaded' });
			await page.locator('main').waitFor();
			const venuesText = await page.locator('main').innerText();
			const venuesOverflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);

			if (!venuesText.includes(expectedVenue)
				|| !venuesText.includes('Brno')
				|| !/Kapacita: 2500|Capacity: 2500/.test(venuesText)
				|| /COM_JOOMLEAGUE_[A-Z0-9_]+/.test(venuesText)
				|| venuesOverflow > 1
				|| errors.length > 0) {
				throw new Error(`Venue catalogue failed at ${viewport.width}px: overflow=${venuesOverflow}, errors=${errors.join('; ')}`);
			}

			if (await page.locator('#filter_country_code option[value="CZ"]').count() !== 1) {
				throw new Error(`Venue country filter is incomplete at ${viewport.width}px.`);
			}

			await page.locator('#filter_search').fill('venue-that-does-not-exist');
			await page.locator('form').filter({ has: page.locator('#filter_search') }).locator('button[type="submit"]').click();
			await page.waitForLoadState('domcontentloaded');
			const filteredVenuesText = await page.locator('main').innerText();
			if (!/No published venues|neodpovídá žádné zveřejněné sportoviště/.test(filteredVenuesText)) {
				throw new Error(`Venue search filter did not return the expected empty state at ${viewport.width}px.`);
			}

			await page.goto(new URL(projectPath, baseUrl).toString(), { waitUntil: 'domcontentloaded' });
			await page.locator('main').waitFor();
			const projectText = await page.locator('main').innerText();

			if (!projectText.includes(expectedProject)
				|| !/Účastníci|Participants/.test(projectText)
				|| !/Program a výsledky|Programme and results/.test(projectText)
				|| !/Pořadí|Standings/.test(projectText)
				|| /COM_JOOMLEAGUE_[A-Z0-9_]+/.test(projectText)) {
				throw new Error(`Competition overview is incomplete or untranslated at ${viewport.width}px.`);
			}

			const detailOverflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
			if (detailOverflow > 1 || errors.length > 0) {
				throw new Error(`Competition overview failed at ${viewport.width}px: overflow=${detailOverflow}, errors=${errors.join('; ')}`);
			}

			await page.goto(new URL(participantsPath, baseUrl).toString(), { waitUntil: 'domcontentloaded' });
			await page.locator('main').waitFor();
			const participantsText = await page.locator('main').innerText();
			const participantsOverflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);

			if (!participantsText.includes(expectedProject)
				|| !participantsText.includes(expectedParticipant)
				|| !/Tým|Team|Jednotlivec|Individual|Skupina|Group/.test(participantsText)
				|| /COM_JOOMLEAGUE_[A-Z0-9_]+/.test(participantsText)
				|| participantsOverflow > 1
				|| errors.length > 0) {
				throw new Error(`Competition participants failed at ${viewport.width}px: overflow=${participantsOverflow}, errors=${errors.join('; ')}`);
			}

			const participantLink = page.locator('main a').filter({ hasText: expectedParticipant }).first();
			if (await participantLink.count() !== 1) {
				throw new Error(`Competition participant link is missing at ${viewport.width}px.`);
			}

			await page.goto(new URL(participantPath, baseUrl).toString(), { waitUntil: 'domcontentloaded' });
			await page.locator('main').waitFor();
			const participantText = await page.locator('main').innerText();
			const participantOverflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);

			if (!participantText.includes(expectedProject)
				|| !participantText.includes(expectedParticipant)
				|| !participantText.includes(expectedMember)
				|| !/Program|Programme/.test(participantText)
				|| /COM_JOOMLEAGUE_[A-Z0-9_]+/.test(participantText)
				|| participantOverflow > 1
				|| errors.length > 0) {
				throw new Error(`Competition participant detail failed at ${viewport.width}px: overflow=${participantOverflow}, errors=${errors.join('; ')}`);
			}

			const memberLink = page.locator('main a').filter({ hasText: expectedMember }).first();
			if (await memberLink.count() !== 1) {
				throw new Error(`Member profile link is missing at ${viewport.width}px.`);
			}
			const clubLink = page.locator('main a').filter({ hasText: expectedClub }).first();
			if (await clubLink.count() !== 1) {
				throw new Error(`Club profile link is missing at ${viewport.width}px.`);
			}

			await page.goto(new URL(personPath, baseUrl).toString(), { waitUntil: 'domcontentloaded' });
			await page.locator('main').waitFor();
			const personText = await page.locator('main').innerText();
			const personOverflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);

			if (!personText.includes(expectedMember)
				|| !personText.includes(expectedProject)
				|| !personText.includes(expectedParticipant)
				|| !/Aktuální působení v soutěžích|Current memberships/.test(personText)
				|| /COM_JOOMLEAGUE_[A-Z0-9_]+/.test(personText)
				|| personOverflow > 1
				|| errors.length > 0) {
				throw new Error(`Public member profile failed at ${viewport.width}px: overflow=${personOverflow}, errors=${errors.join('; ')}`);
			}

			await page.goto(new URL(clubPath, baseUrl).toString(), { waitUntil: 'domcontentloaded' });
			await page.locator('main').waitFor();
			const clubText = await page.locator('main').innerText();
			const clubOverflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);

			if (!clubText.includes(expectedClub)
				|| !clubText.includes(expectedParticipant)
				|| !clubText.includes(expectedSecondTeam)
				|| !clubText.includes(expectedProject)
				|| !/Týmy|Teams/.test(clubText)
				|| /COM_JOOMLEAGUE_[A-Z0-9_]+/.test(clubText)
				|| clubOverflow > 1
				|| errors.length > 0) {
				throw new Error(`Public club profile failed at ${viewport.width}px: overflow=${clubOverflow}, errors=${errors.join('; ')}`);
			}

			const teamLink = page.locator('main a').filter({ hasText: expectedParticipant }).first();
			if (await teamLink.count() !== 1) {
				throw new Error(`Team profile link is missing at ${viewport.width}px.`);
			}

			await page.goto(new URL(teamPath, baseUrl).toString(), { waitUntil: 'domcontentloaded' });
			await page.locator('main').waitFor();
			const teamText = await page.locator('main').innerText();
			const teamOverflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);

			if (!teamText.includes(expectedParticipant)
				|| !teamText.includes(expectedClub)
				|| !teamText.includes(expectedProject)
				|| !/Účast v soutěžích|Competition participation/.test(teamText)
				|| /COM_JOOMLEAGUE_[A-Z0-9_]+/.test(teamText)
				|| teamOverflow > 1
				|| errors.length > 0) {
				throw new Error(`Public team profile failed at ${viewport.width}px: overflow=${teamOverflow}, errors=${errors.join('; ')}`);
			}

			await page.goto(new URL(venuePath, baseUrl).toString(), { waitUntil: 'domcontentloaded' });
			await page.locator('main').waitFor();
			const venueText = await page.locator('main').innerText();
			const venueOverflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);

			if (!venueText.includes(expectedVenue)
				|| !venueText.includes(expectedClub)
				|| !venueText.includes('Sportovní 1')
				|| !venueText.includes('Europe/Prague')
				|| !venueText.includes('49.1951000, 16.6068000')
				|| /COM_JOOMLEAGUE_[A-Z0-9_]+/.test(venueText)
				|| venueOverflow > 1
				|| errors.length > 0) {
				throw new Error(`Public venue profile failed at ${viewport.width}px: overflow=${venueOverflow}, errors=${errors.join('; ')}`);
			}

			await page.close();
		}

		console.log('Public competition, club and venue catalogues plus participant, member, club, team and venue profiles passed desktop and mobile menu routes.');
	} finally {
		await browser.close();
	}
})().catch((error) => {
	console.error(error);
	process.exit(1);
});
