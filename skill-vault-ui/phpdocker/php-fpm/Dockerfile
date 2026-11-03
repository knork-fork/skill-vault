FROM phpdockerio/php:8.5-fpm
WORKDIR "/application"

RUN grep -rl 'ppa.launchpad.net/ondrej' /etc/apt/sources.list /etc/apt/sources.list.d | xargs -r rm -f \
    && apt-get update \
    && apt-get -y --no-install-recommends install ca-certificates curl \
    && curl -fsSL https://packages.sury.org/php/apt.gpg -o /etc/apt/keyrings/sury-php.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/sury-php.gpg] https://packages.sury.org/php noble main" > /etc/apt/sources.list.d/sury-php.list \
    && apt-get update \
    && V=$(apt-cache policy php8.5-pgsql | awk '/Candidate:/{print $2}') \
    && OLD=$(dpkg-query -W -f='${Version}' php8.5-common) \
    && PKGS=$(dpkg-query -W -f='${Package} ${Version}\n' 'php8.5-*' | awk -v o="$OLD" -v v="$V" '$2==o{printf "%s=%s ", $1, v}') \
    && apt-get -y --no-install-recommends --allow-downgrades install $PKGS php8.5-pgsql="$V" git \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

COPY Entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["php-fpm8.5", "-F"]