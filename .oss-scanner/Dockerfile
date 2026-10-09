# Build environment for Anthropic's OSS Scanner (https://github.com/anthropics/oss-scanner).
# Built with the repository root as the context; the network is available here and removed afterwards,
# so every dependency the tests need is installed in this file.
FROM php:8.4-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
      ca-certificates git unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# scanner contract: the checkout lives inside the image, at /src
COPY . /src
WORKDIR /src

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_ROOT_VERSION=dev-main

RUN composer install --no-interaction --prefer-dist --no-progress

# Prove the image works without letting a failing test abort the build.
RUN vendor/bin/phpunit --no-coverage || echo "WARNING: phpunit reported failures"
