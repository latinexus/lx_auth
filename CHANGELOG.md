# Changelog

## [Unreleased]

### Changed
- Updated dependency `firebase/php-jwt` to 7.0.x for security improvements and key-size validation.
- Added JWT secret length validation in `AuthManager` to provide a clear error message when the configured `LX_AUTH_JWT_SECRET` is too short for the configured algorithm (HS256/HS384/HS512).
- `AuthManager` now synchronizes `JWT::$leeway` from configuration (`tokens.jwt.leeway`).

### Notes
- HS256 requires at least 256 bits (32 bytes), HS384 requires 384 bits (48 bytes), HS512 requires 512 bits (64 bytes).
- Make sure to rotate/replace secrets in production if they are shorter than the minimum required.


## [0.0.0] - initial
- Initial project skeleton and implementation.

