const {execFileSync} = require('child_process');
const path = require('path');

/**
 * Client for the REST routes of wp-stash-test-plugin.
 * Every call is its own HTTP request, so the in-memory layer of WP Stash starts out empty
 * and reads have to come from the persistent Stash driver.
 */
class CacheApi {
    constructor(request) {
        this.request = request;
    }

    async info() {
        return this.json(await this.request.get('/wp-json/wp-stash-test/v1/info'));
    }

    async get(keys, options = {}) {
        return this.json(await this.request.get('/wp-json/wp-stash-test/v1/cache', {
            params: this.params({...options, keys}),
        }));
    }

    async set(items, options = {}) {
        return this.json(await this.request.post('/wp-json/wp-stash-test/v1/cache', {
            data: {...options, items},
        }));
    }

    async delete(keys, options = {}) {
        return this.json(await this.request.delete('/wp-json/wp-stash-test/v1/cache', {
            params: this.params({...options, keys}),
        }));
    }

    async incr(key, offset, options = {}) {
        return this.offset('incr', key, offset, options);
    }

    async decr(key, offset, options = {}) {
        return this.offset('decr', key, offset, options);
    }

    async offset(op, key, offset, options) {
        const response = await this.request.post(`/wp-json/wp-stash-test/v1/cache/${op}`, {
            data: {...options, key, offset},
        });

        return (await this.json(response)).value;
    }

    /**
     * WordPress expects array query args as keys[]=a&keys[]=b
     */
    params(options) {
        const params = new URLSearchParams();
        for (const [name, value] of Object.entries(options)) {
            if (Array.isArray(value)) {
                value.forEach((item) => params.append(`${name}[]`, item));
            } else {
                params.append(name, String(value));
            }
        }

        return params.toString();
    }

    async json(response) {
        if (!response.ok()) {
            throw new Error(`${response.url()} returned ${response.status()}: ${await response.text()}`);
        }

        return response.json();
    }
}

/**
 * Unique per test and retry, so tests running in parallel never share cache keys.
 */
function uniqueKey(testInfo, name) {
    return `${testInfo.testId}-${testInfo.retry}-${Date.now()}-${name}`;
}

/**
 * Runs WP-CLI in the wp-env "cli" container.
 */
function wpCli(...args) {
    return execFileSync('npx', ['wp-env', 'run', 'cli', 'wp', ...args], {
        cwd: path.resolve(__dirname, '../../..'),
        encoding: 'utf8',
        stdio: ['ignore', 'pipe', 'pipe'],
    }).trim();
}

module.exports = {CacheApi, uniqueKey, wpCli};
