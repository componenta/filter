// Regenerate with Node.js: node tests/Fixtures/generate-divisibility-vectors.mjs
// The oracle uses independent BigInt integer arithmetic, never the PHP filter.
import { writeFileSync } from 'node:fs';

let state = 20261126;
const next = () => (state = (Math.imul(state, 1664525) + 1013904223) >>> 0);
const integer = digits => BigInt(String(1 + next() % 9)
    + Array.from({ length: digits - 1 }, () => String(next() % 10)).join(''));

const divisors = [
    2n, 5n, 10n, 2n ** 27n, 2n ** 59n, 2n ** 120n,
    5n ** 12n, 5n ** 26n, 5n ** 80n, (2n ** 77n) * (5n ** 23n) * 7n,
    9999n, 10000n, 10001n, 999999999n, 1000000000n, 1000000001n,
    999999999999999999n, 1000000000000000000n, 1000000000000000001n,
    500000000000000001n, 1000000001n * 999999937n,
    100000000000000000000000000000000001n,
    (10n ** 45n - 1n) * (2n ** 11n),
    ...Array.from({ length: 35 }, () => integer(2 + next() % 63)),
];
const literal = (n, exp) => n.toString() + 'e' + exp;
const divisible = (n, ne, d, de) => ne >= de
    ? n * 10n ** BigInt(ne - de) % d === 0n
    : n % (d * 10n ** BigInt(de - ne)) === 0n;

const groups = divisors.map((absolute, i) => {
    const d = i % 2 ? -absolute : absolute;
    const de = (next() % 81) - 40;
    const q = integer(1 + next() % 70);
    const n = d * q;
    const cases = [
        [0n, 0], [d, de], [-d, de],
        [n, de], [n + 1n, de], [n - 1n, de],
        [n * 10n ** 9n, de - 9],
        [n, de + 13], [n, de - 13],
        [absolute - 1n, de], [absolute + 1n, de],
        [integer(2 + next() % 100), (next() % 81) - 40],
    ];

    return {
        divisor: literal(d, de),
        values: cases.map(([v, ve]) => [literal(v, ve), divisible(v, ve, d, de)]),
    };
});

writeFileSync(
    new URL('./divisibility-vectors.json', import.meta.url),
    JSON.stringify(groups) + '\n',
);
