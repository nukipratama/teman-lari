import { expect, test } from 'vitest';

// TEMPORARY: intentional failure to demonstrate that a red `ci-gate` blocks the
// merge under branch protection. This PR is not meant to be merged — close it
// and delete the branch after observing the blocked merge.
test('red-ci demo: this assertion fails on purpose', () => {
    expect(1).toBe(2);
});
