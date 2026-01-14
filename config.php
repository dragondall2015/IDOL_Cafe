<?php
/**
 * config.php
 * - .env 파일을 읽어 환경변수로 로드하는 유틸
 * - GitHub 공개를 위해 DB 계정/비밀번호 등 민감정보를 코드에서 분리
 */

/**
 * .env 파일을 읽어 putenv()/$_ENV 에 주입합니다.
 * - KEY=VALUE 형식
 * - # 으로 시작하는 주석/빈 줄 무시
 * - "..." 또는 '...' 따옴표 값 지원
 */
function load_env(string $path): void {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));

        // 따옴표 제거
        if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
            (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
            $val = substr($val, 1, -1);
        }

        // 이미 환경변수로 주어진 값이 있으면 .env가 덮어쓰지 않음
        $existing = getenv($key);
        if ($existing === false) {
            putenv("{$key}={$val}");
            $_ENV[$key] = $val;
        }
    }
}

/**
 * 환경변수 헬퍼
 */
function env(string $key, ?string $default = null): ?string {
    $val = getenv($key);
    if ($val === false || $val === '') {
        return $default;
    }
    return $val;
}

// 프로젝트 루트의 .env 로드 (있을 경우)
load_env(__DIR__ . DIRECTORY_SEPARATOR . '.env');
