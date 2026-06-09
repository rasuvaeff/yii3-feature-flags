# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.0 — 2026-06-02

- Initial release.

## 1.0.1 — 2026-06-05

- Added "Storage backends" section to README and llms.txt with links to `yii3-feature-flags-db` and `yii3-feature-flags-redis`.

## 2.0.0 — 2026-06-09

- **BREAKING:** the package no longer binds `FlagProvider` in its `di` config.
  The core only wires the `FeatureFlags` facade; the `FlagProvider` implementation
  is now provided by exactly one storage backend (`yii3-feature-flags-db`,
  `yii3-feature-flags-redis`) or by the application. This removes the
  `Duplicate key "...\FlagProvider"` config error that occurred when a backend
  was installed alongside the core.
- For config-array flags without a backend, bind `FlagProvider` to
  `ConfigFlagProvider` in the application config (see README → "Config-only setup").
