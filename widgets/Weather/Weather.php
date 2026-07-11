<?php

declare(strict_types=1);

namespace App\Widgets\Weather;

use App\Widget\DataWidgetInterface;

/**
 * Weather widget powered by Open-Meteo.
 */
final class Weather implements DataWidgetInterface
{
    private const string GEO_URL = 'https://geocoding-api.open-meteo.com/v1/search';
    private const string FORECAST_URL = 'https://api.open-meteo.com/v1/forecast';
    private const int CACHE_TTL = 600;      // 10 minutes
    private const int HTTP_TIMEOUT = 5;     // seconds
    private const string CACHE_VER = 'wx3'; // bump when the payload shape changes

    public static function type(): string
    {
        return 'weather';
    }

    public static function label(): string
    {
        return 'Weather';
    }

    public static function configSchema(): array
    {
        return [
            'location' => [
                'type' => 'text',
                'label' => 'Location (city)',
                'default' => 'London',
            ],
            'units' => [
                'type' => 'select',
                'label' => 'Units',
                'options' => ['°C', '°F'],
                'default' => '°C',
            ],
        ];
    }

    public function render(array $config): string
    {
        return <<<HTML
        <div class="wx">
          <div class="wx__error" data-role="error" hidden></div>

          <div class="wx__main" data-role="main">
            <div class="wx__place" data-role="place">…</div>

            <div class="wx__now">
              <div class="wx__icon" data-role="icon">🌡️</div>
              <div class="wx__temp"><span data-role="temp">--</span><span class="wx__deg" data-role="unit">°</span></div>
            </div>

            <div class="wx__cond" data-role="cond"></div>

            <div class="wx__meta">
              <span class="wx__meta-item" data-role="feels"></span>
              <span class="wx__meta-item" data-role="humidity"></span>
              <span class="wx__meta-item" data-role="wind"></span>
            </div>

            <div class="wx__rain" data-role="rain-wrap" hidden>
              <div class="wx__rain-head">
                <span class="wx__rain-title">Chance of rain</span>
                <div class="wx__rain-nav">
                  <button class="wx__nav-btn" data-role="rain-prev" type="button" aria-label="Previous day">‹</button>
                  <span class="wx__rain-day" data-role="rain-day"></span>
                  <button class="wx__nav-btn" data-role="rain-next" type="button" aria-label="Next day">›</button>
                </div>
              </div>
              <div class="wx__hours" data-role="hours"></div>

              <div class="wx__legend">
                <span class="wx__legend-i"><i class="wx__sw"></i>Just a chance</span>
                <span class="wx__legend-i"><i class="wx__sw wx__sw--wet"></i>Rain expected</span>
                <span class="wx__legend-note">bar height = % chance</span>
              </div>
            </div>

            <div class="wx__days" data-role="days"></div>
          </div>
        </div>
        HTML;
    }

    public function data(array $config): array
    {
        $location = trim((string)($config['location'] ?? 'London'));
        if ($location === '') {
            $location = 'London';
        }
        $fahrenheit = ($config['units'] ?? '°C') === '°F';

        // The version tag is bumped whenever the payload shape changes
        $cacheFile = sys_get_temp_dir() . '/' . self::CACHE_VER . '_' . md5(
                strtolower($location) . '|' . ($fahrenheit ? 'f' : 'c')
            ) . '.json';

        // Serve a fresh cache without touching the network
        $cached = self::readCache($cacheFile, self::CACHE_TTL);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $payload = $this->fetchWeather($location, $fahrenheit);
        } catch (\Throwable $e) {
            $stale = self::readCache($cacheFile, PHP_INT_MAX);
            if ($stale !== null) {
                $stale['stale'] = true;
                return $stale;
            }
            return ['error' => 'Weather is unavailable right now.'];
        }

        if (!isset($payload['error'])) {
            @file_put_contents($cacheFile, json_encode($payload));
        }
        return $payload;
    }

    private function fetchWeather(string $location, bool $fahrenheit): array
    {
        $geo = $this->getJson(
            self::GEO_URL . '?' . http_build_query([
                'name' => $location,
                'count' => 1,
                'language' => 'en',
                'format' => 'json',
            ])
        );
        $place = $geo['results'][0] ?? null;
        if (!$place) {
            return ['error' => 'Location "' . $location . '" not found.'];
        }

        $wx = $this->getJson(
            self::FORECAST_URL . '?' . http_build_query([
                'latitude' => $place['latitude'],
                'longitude' => $place['longitude'],
                'current' => 'temperature_2m,relative_humidity_2m,apparent_temperature,is_day,weather_code,wind_speed_10m,precipitation',
                'hourly' => 'precipitation_probability,precipitation',
                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,rain_sum,precipitation_sum',
                'timezone' => 'auto',
                'forecast_days' => 5,
                'precipitation_unit' => $fahrenheit ? 'inch' : 'mm',
                'temperature_unit' => $fahrenheit ? 'fahrenheit' : 'celsius',
                'wind_speed_unit' => $fahrenheit ? 'mph' : 'kmh',
            ])
        );

        return self::buildPayload($place, $wx, $fahrenheit);
    }

    /**
     * @param  array<string, mixed>  $place  a geocoding result
     * @param  array<string, mixed>  $wx  a forecast response
     * @return array<string, mixed>
     */
    public static function buildPayload(array $place, array $wx, bool $fahrenheit): array
    {
        $cur = $wx['current'] ?? [];
        $daily = $wx['daily'] ?? [];
        $hourly = $wx['hourly'] ?? [];

        // Daily forecast, now including rain chance + rain total
        $days = [];
        foreach (($daily['time'] ?? []) as $i => $date) {
            $days[] = [
                'date' => (string)$date,
                'code' => (int)($daily['weather_code'][$i] ?? 0),
                'max' => self::num($daily['temperature_2m_max'][$i] ?? null),
                'min' => self::num($daily['temperature_2m_min'][$i] ?? null),
                'prob' => self::num($daily['precipitation_probability_max'][$i] ?? null),
                'rain' => self::mm($daily['rain_sum'][$i] ?? $daily['precipitation_sum'][$i] ?? null),
            ];
        }

        // Full hourly series for the whole forecast window
        $htime = $hourly['time'] ?? [];
        $hprob = $hourly['precipitation_probability'] ?? [];
        $hprec = $hourly['precipitation'] ?? [];
        $nowIdx = 0;
        $nowTime = $cur['time'] ?? ($htime[0] ?? null);
        if ($nowTime !== null) {
            $idx = array_search($nowTime, $htime, true);
            if ($idx !== false) {
                $nowIdx = (int)$idx;
            }
        }
        $hours = [];
        $count = count($htime);
        for ($i = 0; $i < $count; $i++) {
            $hours[] = [
                'time' => (string)$htime[$i],
                'prob' => self::num($hprob[$i] ?? null),
                'mm' => self::mm($hprec[$i] ?? null),
            ];
        }

        $name = (string)($place['name'] ?? '');
        $region = (string)($place['country_code'] ?? $place['country'] ?? '');

        return [
            'place' => $region !== '' ? $name . ', ' . $region : $name,
            'unit' => $fahrenheit ? '°F' : '°C',
            'windUnit' => $fahrenheit ? 'mph' : 'km/h',
            'rainUnit' => $fahrenheit ? 'in' : 'mm',
            'current' => [
                'temp' => self::num($cur['temperature_2m'] ?? null),
                'feels' => self::num($cur['apparent_temperature'] ?? null),
                'humidity' => self::num($cur['relative_humidity_2m'] ?? null),
                'wind' => self::num($cur['wind_speed_10m'] ?? null),
                'precip' => self::mm($cur['precipitation'] ?? null),
                'precipProb' => self::num($hprob[$nowIdx] ?? null),
                'code' => (int)($cur['weather_code'] ?? 0),
                'isDay' => (int)($cur['is_day'] ?? 1) === 1,
            ],
            'now' => $nowTime !== null ? (string)$nowTime : null,
            'hourly' => $hours,
            'daily' => $days,
            'updated' => (int)round(microtime(true) * 1000),
        ];
    }

    private function getJson(string $url): array
    {
        $ctx = stream_context_create([
            'http' => ['timeout' => self::HTTP_TIMEOUT, 'header' => "Accept: application/json\r\n"],
            'https' => ['timeout' => self::HTTP_TIMEOUT],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            throw new \RuntimeException('weather fetch failed');
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new \RuntimeException('weather bad json');
        }
        return $data;
    }

    private static function readCache(string $file, int $ttl): ?array
    {
        if (!is_file($file) || (time() - filemtime($file)) >= $ttl) {
            return null;
        }
        $data = json_decode((string)file_get_contents($file), true);
        return is_array($data) ? $data : null;
    }

    private static function num(mixed $v): ?int
    {
        return $v === null ? null : (int)round((float)$v);
    }

    private static function mm(mixed $v): ?float
    {
        return $v === null ? null : round((float)$v, 1);
    }
}
