<?php
// config/env.php — optional .env + getenv() with defaults

final class Environment
{
    private static bool $dotenvLoaded = false;

    private static function loadDotEnv(): void
    {
        if (self::$dotenvLoaded) {
            return;
        }
        self::$dotenvLoaded = true;

        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || (isset($line[0]) && $line[0] === '#')) {
                continue;
            }
            $eq = strpos($line, '=');
            if ($eq === false) {
                continue;
            }
            $name = trim(substr($line, 0, $eq));
            $value = trim(substr($line, $eq + 1));
            if ($name === '') {
                continue;
            }
            if (($value !== '') && ($value[0] === '"' || $value[0] === "'")) {
                $value = trim($value, "\"'");
            }
            if (getenv($name) === false) {
                putenv($name . '=' . $value);
                $_ENV[$name] = $value;
            }
        }
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        self::loadDotEnv();

        $val = getenv($key);
        if ($val !== false) {
            return $val;
        }
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        return $default;
    }
}