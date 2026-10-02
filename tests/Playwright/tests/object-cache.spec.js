const {test, expect} = require('@playwright/test');
const {CacheApi, uniqueKey} = require('./cache-api');

let api;

test.beforeEach(async ({request}) => {
    api = new CacheApi(request);
});

test('WP Stash is the object cache and wraps a persistent driver', async () => {
    expect(await api.info()).toEqual({
        usingExtObjectCache: true,
        objectCache: 'Inpsyde\\WpStash\\ObjectCacheProxy',
        driver: 'Inpsyde\\WpStash\\Stash\\PersistenceAwareComposite',
        drivers: ['Stash\\Driver\\Ephemeral', 'Stash\\Driver\\FileSystem'],
    });
});

test('a value persists across requests', async ({}, testInfo) => {
    const key = uniqueKey(testInfo, 'single');
    const missing = uniqueKey(testInfo, 'missing');

    expect(await api.set({[key]: {nested: ['value', 1]}})).toEqual({[key]: true});

    expect(await api.get([key, missing])).toEqual({
        [key]: {found: true, value: {nested: ['value', 1]}},
        [missing]: {found: false, value: false},
    });
});

test('$found tells a cached false apart from a miss', async ({}, testInfo) => {
    const key = uniqueKey(testInfo, 'false');

    await api.set({[key]: false});

    expect(await api.get([key])).toEqual({[key]: {found: true, value: false}});
});

test('deleting a value persists across requests', async ({}, testInfo) => {
    const key = uniqueKey(testInfo, 'deleted');
    await api.set({[key]: 'value'});

    expect(await api.delete([key])).toEqual({[key]: true});

    expect(await api.get([key])).toEqual({[key]: {found: false, value: false}});
});

test('the *_multiple() functions persist across requests', async ({}, testInfo) => {
    const [a, b, c] = ['a', 'b', 'c'].map((name) => uniqueKey(testInfo, name));
    const options = {mode: 'multiple', group: 'multiple'};

    expect(await api.set({[a]: 1, [b]: [2]}, options)).toEqual({[a]: true, [b]: true});
    expect(await api.get([a, b, c], options)).toEqual({
        [a]: {found: true, value: 1},
        [b]: {found: true, value: [2]},
        [c]: {found: false, value: false},
    });

    await api.delete([a], options);
    expect(await api.get([a, b], options)).toEqual({
        [a]: {found: false, value: false},
        [b]: {found: true, value: [2]},
    });
});

test('the same key in different groups holds different values', async ({}, testInfo) => {
    const key = uniqueKey(testInfo, 'grouped');

    await api.set({[key]: 'first'}, {group: 'first'});
    await api.set({[key]: 'second'}, {group: 'second'});

    expect(await api.get([key], {group: 'first'})).toEqual({[key]: {found: true, value: 'first'}});
    expect(await api.get([key], {group: 'second'})).toEqual({[key]: {found: true, value: 'second'}});
    expect(await api.get([key])).toEqual({[key]: {found: false, value: false}});
});

test('non-persistent groups do not survive the request', async ({}, testInfo) => {
    const key = uniqueKey(testInfo, 'runtime');
    const options = {group: 'runtime-only', nonPersistent: true};

    expect(await api.set({[key]: 'value'}, options)).toEqual({[key]: true});

    expect(await api.get([key], options)).toEqual({[key]: {found: false, value: false}});
});

test('add() and replace() respect values stored by earlier requests', async ({}, testInfo) => {
    const key = uniqueKey(testInfo, 'existing');

    expect(await api.set({[key]: 'original'}, {op: 'replace'})).toEqual({[key]: false});
    expect(await api.set({[key]: 'original'}, {op: 'add'})).toEqual({[key]: true});
    expect(await api.set({[key]: 'overwritten'}, {op: 'add'})).toEqual({[key]: false});
    expect(await api.get([key])).toEqual({[key]: {found: true, value: 'original'}});

    expect(await api.set({[key]: 'replaced'}, {op: 'replace'})).toEqual({[key]: true});
    expect(await api.get([key])).toEqual({[key]: {found: true, value: 'replaced'}});
});

test('a value with a short expiration is available until it expires', async ({}, testInfo) => {
    const key = uniqueKey(testInfo, 'expiring');

    await api.set({[key]: 'value'}, {expire: 3});
    expect(await api.get([key])).toEqual({[key]: {found: true, value: 'value'}});

    await expect.poll(async () => (await api.get([key]))[key].found, {
        timeout: 10_000,
        intervals: [1_000],
    }).toBe(false);
});

test('incr() and decr() update a stored counter', async ({}, testInfo) => {
    const key = uniqueKey(testInfo, 'counter');

    expect(await api.incr(key, 1)).toBe(false);

    await api.set({[key]: 0});
    expect(await api.incr(key, 1)).toBe(1);
    expect(await api.incr(key, 5)).toBe(6);
    expect(await api.decr(key, 2)).toBe(4);
    expect(await api.decr(key, 10)).toBe(0);

    expect(await api.get([key])).toEqual({[key]: {found: true, value: 0}});
});
