#!/usr/bin/env bash
set -euo pipefail

PHP_VERSION="${PHP_VERSION:-8.5}"
PHP_FALLBACK="8.4"
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

say() { printf '\n\033[1;34m==> %s\033[0m\n' "$1"; }
warn() { printf '\033[1;33m!! %s\033[0m\n' "$1"; }
die() { printf '\033[1;31mxx %s\033[0m\n' "$1" >&2; exit 1; }
have() { command -v "$1" >/dev/null 2>&1; }

usage() {
    cat <<'USAGE'
Usage: scripts/bootstrap.sh <stage>

Stages, to run in order:
  system     Install PHP, Composer, Node, PostgreSQL, Redis (requires sudo)
  laravel    Create the Laravel 13 skeleton in this worktree
  packages   Install the mandatory Xefi package set
  osdd       Run the OSDD scaffolding and list the layer commands
  all        system, laravel, packages, osdd in sequence

Each stage is safe to re-run: it checks its own preconditions first.
USAGE
}

resolve_php_version() {
    if apt-cache show "php${PHP_VERSION}-cli" >/dev/null 2>&1; then
        echo "${PHP_VERSION}"
    else
        warn "php${PHP_VERSION} unavailable on this distribution, falling back to php${PHP_FALLBACK}"
        echo "${PHP_FALLBACK}"
    fi
}

stage_system() {
    say "Installing system dependencies (sudo password required)"

    sudo apt-get update
    sudo apt-get install -y software-properties-common unzip git curl ca-certificates
    sudo add-apt-repository -y ppa:ondrej/php
    sudo apt-get update

    local version
    version="$(resolve_php_version)"

    say "Installing PHP ${version} and extensions"
    sudo apt-get install -y \
        "php${version}-cli" "php${version}-mbstring" "php${version}-xml" \
        "php${version}-curl" "php${version}-zip" "php${version}-bcmath" \
        "php${version}-intl" "php${version}-gd" "php${version}-pgsql" \
        "php${version}-redis" "php${version}-sqlite3"

    if have composer; then
        say "Composer already present, skipping"
    else
        say "Installing Composer"
        curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
        sudo php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
        rm -f /tmp/composer-setup.php
    fi

    if have node; then
        say "Node already present, skipping"
    else
        say "Installing Node 22"
        curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
        sudo apt-get install -y nodejs
    fi

    say "Installing PostgreSQL and Redis"
    sudo apt-get install -y postgresql redis-server

    say "System stage complete"
    php -v | head -1
    composer --version
    node -v
}

stage_laravel() {
    have composer || die "Composer not found. Run: scripts/bootstrap.sh system"

    if [ -f "${PROJECT_ROOT}/artisan" ]; then
        say "Laravel skeleton already present, skipping"
        return
    fi

    say "Creating the Laravel skeleton"
    local staging
    staging="$(mktemp -d)"
    composer create-project laravel/laravel "${staging}/app" --no-interaction

    say "Merging the skeleton into the worktree without clobbering tracked files"
    cd "${staging}/app"
    local entry
    for entry in $(ls -A); do
        if [ -e "${PROJECT_ROOT}/${entry}" ]; then
            warn "keeping existing ${entry}"
        else
            cp -R "${entry}" "${PROJECT_ROOT}/"
        fi
    done
    cd "${PROJECT_ROOT}"
    rm -rf "${staging}"

    say "Laravel stage complete"
    php artisan --version
}

stage_packages() {
    [ -f "${PROJECT_ROOT}/artisan" ] || die "No Laravel skeleton. Run: scripts/bootstrap.sh laravel"
    cd "${PROJECT_ROOT}"

    say "Installing the mandatory Xefi package set"
    composer require --no-interaction \
        laravel/boost \
        xefi/laravel-osdd \
        lomkit/laravel-rest-api \
        lomkit/laravel-access-control \
        spatie/laravel-permission \
        spatie/laravel-medialibrary

    say "Installing development tooling"
    composer require --dev --no-interaction \
        xefi/faker-php-laravel \
        larastan/larastan \
        xefi/phpstan-xefi-rules

    say "Running the Boost installer"
    php artisan boost:install --no-interaction || warn "boost:install needs attention, run it manually"

    say "Packages stage complete"
    composer show --direct
}

stage_osdd() {
    [ -f "${PROJECT_ROOT}/artisan" ] || die "No Laravel skeleton. Run: scripts/bootstrap.sh laravel"
    cd "${PROJECT_ROOT}"

    if [ -d "${PROJECT_ROOT}/functional" ] && [ -d "${PROJECT_ROOT}/technical" ]; then
        say "OSDD already scaffolded, skipping osdd:start"
    else
        say "Scaffolding the OSDD layered architecture"
        php artisan osdd:start --no-interaction
    fi

    say "Available OSDD commands, needed to create the layers listed in the design document"
    php artisan list osdd

    cat <<'NEXT'

Layers to create, per docs/superpowers/specs/2026-07-25-wardrobe-management-api-design.md:

  technical/   media, ai-gateway
  functional/  identity, catalog, wardrobe, identification, styling, resale

The exact layer-creation command is printed above. It was deliberately not hardcoded
in this script so that the real signature from the installed package is used rather
than a guessed one.
NEXT
}

main() {
    case "${1:-}" in
        system)   stage_system ;;
        laravel)  stage_laravel ;;
        packages) stage_packages ;;
        osdd)     stage_osdd ;;
        all)      stage_system; stage_laravel; stage_packages; stage_osdd ;;
        *)        usage; exit 1 ;;
    esac
}

main "$@"
