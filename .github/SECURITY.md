# Security Policy

## Supported versions

| Version | Supported |
| ------- | --------- |
| 1.x     | ✅        |
| < 1.0   | ❌        |

## Reporting a vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

Email [yuriyzee@gmail.com](mailto:yuriyzee@gmail.com) with:

- a description of the issue and its impact,
- a minimal reproduction if possible,
- the package and PHP versions affected.

You'll get an acknowledgement within 48 hours and a status update within 7
days. Once a fix is released, we'll coordinate disclosure with you and credit
you in the release notes (unless you prefer to stay anonymous).

## Security-sensitive areas

Two parts of the codebase carry explicit security invariants:

- **`EncryptedCast`** — XSalsa20-Poly1305 via libsodium (nonce handling, key
  derivation, ciphertext format).
- **Metadata cache (`.meta.php` files)** — executable PHP loaded from the
  cache directory; writes are atomic and guarded by an exportability check.

Reports touching these areas are prioritized.
