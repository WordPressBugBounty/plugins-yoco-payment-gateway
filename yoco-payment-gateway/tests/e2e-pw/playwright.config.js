const { devices } = require( '@playwright/test' );
const {
	ALLURE_RESULTS_DIR,
	BASE_URL,
	CI,
	DEFAULT_TIMEOUT_OVERRIDE,
	E2E_MAX_FAILURES,
	PLAYWRIGHT_HTML_REPORT,
	SUBDOMAIN,
	LIVE_SECRET_KEY,
	TEST_SECRET_KEY,
} = process.env;

const config = {
	timeout: DEFAULT_TIMEOUT_OVERRIDE
		? Number( DEFAULT_TIMEOUT_OVERRIDE )
		: 90 * 1000,
	expect: { timeout: 20 * 1000 },
	outputDir: './test-results/report',
	globalSetup: require.resolve( './global-setup' ),
	globalTeardown: require.resolve( './global-teardown' ),
	testDir: 'tests',
	retries: CI ? 1 : 2,
	workers: 1,
	reporter: [
		[ 'list' ],
		[
			'html',
			{
				outputFolder:
					PLAYWRIGHT_HTML_REPORT ??
					'./test-results/playwright-report',
				open: CI ? 'never' : 'always',
			},
		],
		[
			'allure-playwright',
			{
				outputFolder:
					ALLURE_RESULTS_DIR ??
					'./tests/e2e-pw/test-results/allure-results',
				detail: true,
				suiteTitle: true,
			},
		],
		[ 'json', { outputFile: './test-results/test-results.json' } ],
	],
	maxFailures: E2E_MAX_FAILURES ? Number( E2E_MAX_FAILURES ) : 0,
	use: {
		baseURL: SUBDOMAIN ? 'https://' + SUBDOMAIN + '.loca.lt' : 'https://yoco-test.loca.lt',
		subdomain: SUBDOMAIN ?? SUBDOMAIN,
		liveSecretKey: LIVE_SECRET_KEY,
		testSecretKey: TEST_SECRET_KEY,
		screenshot: 'only-on-failure',
		stateDir: 'tests/e2e-pw/test-results/storage/',
		trace: 'retain-on-failure',
		video: 'on-first-retry',
		viewport: { width: 2100, height: 1200 },
	},
	projects: [
		{
			name: 'Chrome',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
};

module.exports = config;
