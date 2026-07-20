# rasuvaeff/yii3-feature-flags

[![Stable Version](https://img.shields.io/packagist/v/rasuvaeff/yii3-feature-flags.svg?label=stable)](https://packagist.org/packages/rasuvaeff/yii3-feature-flags)
[![Total Downloads](https://img.shields.io/packagist/dt/rasuvaeff/yii3-feature-flags.svg)](https://packagist.org/packages/rasuvaeff/yii3-feature-flags)
[![Build](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-feature-flags/build.yml?branch=master)](https://github.com/rasuvaeff/yii3-feature-flags/actions)
[![Static Analysis](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-feature-flags/static-analysis.yml?branch=master&label=static%20analysis)](https://github.com/rasuvaeff/yii3-feature-flags/actions)
[![Psalm Level](https://img.shields.io/badge/Psalm-1-blue.svg)](https://github.com/rasuvaeff/yii3-feature-flags/actions)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-feature-flags/php)](https://packagist.org/packages/rasuvaeff/yii3-feature-flags)
[![License](https://img.shields.io/packagist/l/rasuvaeff/yii3-feature-flags.svg)](LICENSE.md)
[English version](README.md)

Feature flags, kill switches и процентный rollout для приложений Yii3.

Ядро без состояния — storage-бэкенды вынесены в отдельные пакеты. Детерминированный
rollout через SHA-256 hash. Работает с Yii3 config-plugin или автономно.

> Используете AI-ассистента для написания кода? В [llms.txt](llms.txt) — компактный
> API-справочник, который можно передать модели.

## Требования

- PHP 8.3+

## Установка

```bash
composer require rasuvaeff/yii3-feature-flags
```

## Использование

### Базовая проверка флага

```php
use Rasuvaeff\Yii3FeatureFlags\FeatureFlags;
use Rasuvaeff\Yii3FeatureFlags\FlagContext;

if ($featureFlags->isEnabled(
    flag: 'new-checkout',
    context: FlagContext::forUser(userId: $userId),
)) {
    // New checkout flow.
}
```

### Конфигурация

В конфиге приложения (`config/params.php`):

```php
return [
    'rasuvaeff/yii3-feature-flags' => [
        'flags' => [
            'new-checkout' => [
                'enabled' => true,
                'salt' => 'new-checkout-v1',
                'rollout' => 25,
                'killSwitch' => false,
                'environments' => ['production'],
            ],
        ],
    ],
];
```

### Параметры конфигурации

| Ключ | Тип | По умолчанию | Описание |
|---|---|---|---|
| `enabled` | `bool` | `true` | Главный переключатель флага |
| `salt` | `string` | имя флага | Hash-salt для детерминированного rollout |
| `rollout` | `int` | `100` | Процент субъектов (0..100) |
| `killSwitch` | `bool` | `false` | Немедленно выключает флаг, перекрывает все правила |
| `environments` | `list<string>` | `[]` | Ограничение окружениями (пусто = все) |

### FlagContext

```php
// By user ID
$context = FlagContext::forUser(userId: 'user-42');

// By tenant ID
$context = FlagContext::forTenant(tenantId: 'tenant-1');

// By environment
$context = FlagContext::forEnvironment(environment: 'production');

// Combined
$context = FlagContext::forUser(userId: 'user-42')
    ->withEnvironment(environment: 'production');
```

### Форсированные значения

Используйте форсированные значения для QA/debug-переопределений на существующих флагах:

```php
$context = FlagContext::forUser(userId: 'user-42')
    ->withForcedFlag(flag: 'new-checkout', enabled: true);

$featureFlags->isEnabled(flag: 'new-checkout', context: $context);
```

Форсированное значение никогда не включает флаг с активным kill switch —
kill switch имеет приоритет.

### Строгий режим

Неизвестные флаги по умолчанию возвращают `false`. Включите строгий режим,
чтобы вместо этого бросать исключение:

```php
$featureFlags = new FeatureFlags(
    provider: $provider,
    strictMode: true,
);
```

### Результат вычисления

Получите детальную информацию о том, почему флаг включён или выключен:

```php
$result = $featureFlags->evaluate(
    flag: 'new-checkout',
    context: FlagContext::forUser(userId: $userId),
);

$result->isEnabled();      // bool
$result->getReason();      // EvaluationReason enum
$result->getFlagName();    // string
```

`EvaluationReason` несёт одну машиночитаемую причину вместо набора пересекающихся
булевых значений:

| Случай | `enabled` | Когда |
|---|---|---|
| `Enabled` | `true` | Флаг включён, без исключений таргетинга/rollout |
| `Disabled` | `false` | `enabled: false` на флаге |
| `KillSwitch` | `false` | `killSwitch: true` перекрывает всё |
| `RolloutExcluded` | `false` | Субъект вне rollout-бакета |
| `EnvironmentExcluded` | `false` | Окружение контекста не в allow-list флага |
| `Forced` | задаётся вызывающим | `FlagContext::withForcedFlag()` переопределило результат |
| `Unknown` | `false` | Флаг не зарегистрирован (нестрогий режим) |

### Kill switch

Установите `killSwitch: true` в конфиге, чтобы немедленно выключить флаг, перекрыв
все правила таргетинга, rollout и форсированных значений.

### Процентный rollout

Детерминированное распределение через `sha256(salt . ':' . subjectId)`:

- Те же `salt` + `subjectId` всегда дают один и тот же результат.
- Изменение `salt` сбрасывает распределение (намеренная ре-рандомизация).
- Изменение весов сдвигает границы; некоторые субъекты могут сменить вариант.

## Публичный API

| Класс | Описание |
|---|---|
| `FeatureFlags` | Фасадный сервис: `isEnabled()`, `isDisabled()`, `evaluate()`, `has()` |
| `Flag` | Иммутабельный value object флага |
| `FlagConfig` | Config DTO для программных определений флагов |
| `FlagContext` | Контекст вычисления (userId, tenantId, environment) |
| `FlagProvider` | Read-only интерфейс для источников флагов |
| `WritableFlagProvider` | `extends FlagProvider`: добавляет `save(Flag)`, `remove(string)` |
| `ConfigFlagProvider` | Провайдер из PHP-конфига (read-only) |
| `FlagRegistry` | Named-lookup флагов |
| `FlagEvaluator` | Основная логика вычисления |
| `PercentageRollout` | Детерминированное процентное распределение |
| `EvaluationResult` | Детальный результат вычисления (private constructor; 7 статических фабрик) |
| `EvaluationReason` | String-backed enum исходов вычисления |
| `MetricsRecorder` | Интерфейс записи метрик вычисления |
| `NullMetricsRecorder` | No-op реализация по умолчанию |

## Storage-бэкенды

Ядро биндит только фасад `FeatureFlags`. Реализация `FlagProvider` поставляется
**ровно одним** провайдером — storage-бэкендом или, для флагов из конфига,
приложением. Это делает бэкенды drop-in: установите один — и он привяжется
автоматически, без конфликта `Duplicate key`.

| Пакет | Описание |
|---|---|
| [`rasuvaeff/yii3-feature-flags-db`](https://github.com/rasuvaeff/yii3-feature-flags-db) | База данных (yiisoft/db) с PSR-16 кэшем и миграцией |

Установите бэкенд — и всё, он биндит `FlagProvider` за вас:

```bash
composer require rasuvaeff/yii3-feature-flags-db
```

### Writable-бэкенды

Бэкенд может реализовывать `WritableFlagProvider` для программного CRUD флагов
(например, через admin UI). `DbFlagProvider` и `CachedFlagProvider` в
`yii3-feature-flags-db` оба его реализуют; `ConfigFlagProvider` — нет, он read-only.

```php
use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlags\WritableFlagProvider;

/** @var WritableFlagProvider $provider */
$provider->save(flag: new Flag(name: 'new-checkout', rollout: 25));
$provider->remove(name: 'old-checkout');
```

`save()` — это upsert с ключом `name`. Реализации, декорирующие кэш (например
`CachedFlagProvider`), инвалидируют себя после успешной записи.

## Метрики

`FeatureFlags` принимает опциональный `MetricsRecorder` последним аргументом
конструктора. После каждого вызова `evaluate()` он получает результирующий
`EvaluationResult` ровно один раз (никогда на пути броска в строгом режиме). По
умолчанию — `NullMetricsRecorder`, no-op: ничего не передавать безопасно.

```php
use Rasuvaeff\Yii3FeatureFlags\FeatureFlags;
use Rasuvaeff\Yii3FeatureFlags\MetricsRecorder;

$recorder = new class implements MetricsRecorder {
    #[\Override]
    public function recordEvaluation(\Rasuvaeff\Yii3FeatureFlags\EvaluationResult $result): void
    {
        // ship $result->getReason()->value to your metrics backend
    }
};

$featureFlags = new FeatureFlags(provider: $provider, recorder: $recorder);
```

Адаптеры (`-psr-logger`, `-prometheus`, …) могут быть добавлены позже; ядро
**не** биндит `MetricsRecorder` в своём `config/di.php`, поэтому установка
адаптера рядом с ядром никогда не вызовет ошибку `Duplicate key`.

### Конфигурация без бэкенда

Без storage-бэкенда определите флаги в `params.php` и привяжите `FlagProvider`
к `ConfigFlagProvider` однажды в конфиге приложения (`config/common/di/*.php`):

```php
use Rasuvaeff\Yii3FeatureFlags\ConfigFlagProvider;
use Rasuvaeff\Yii3FeatureFlags\FlagProvider;

/** @var array $params */

return [
    FlagProvider::class => [
        'class' => ConfigFlagProvider::class,
        '__construct()' => [
            'flags' => $params['rasuvaeff/yii3-feature-flags']['flags'],
        ],
    ],
];
```

Биндите `FlagProvider` из одного источника — установка двух бэкендов (или бэкенда
плюс ручной binding в конфиге) вновь приведёт к конфликту `Duplicate key`.

## Безопасность

- Имена флагов валидируются regex'ом (`/^[a-z][a-z0-9._-]*$/`).
- Процент rollout валидируется (диапазон 0..100).
- Ядро не логирует и не хранит пользовательские данные.
- Kill switch обеспечивает возможность аварийного выключения.

## Примеры

См. [examples/](examples/) — запускаемые скрипты. Ожидается, что примеры
выполняются без fatal errors и остаются согласованными с документированным
публичным API.

## Разработка

```bash
make install     # composer install
make build       # полный gate: validate + cs + psalm + test
make cs-fix      # исправить стиль кода
make psalm       # статический анализ
make test        # запустить тесты
make test-coverage  # запуск coverage
make mutation       # mutation-тестирование
make release-check  # build + rector + bc-check + mutation
```

`make test-coverage` и `make mutation` поднимают `pcov` внутри контейнера
`composer:2`, потому что в базовом образе нет драйвера покрытия.

## Лицензия

BSD-3-Clause. См. [LICENSE.md](LICENSE.md).
