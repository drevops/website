# ClamAV container.
#
# @see https://hub.docker.com/r/clamav/clamav/tags
#
# Allow running ClamAV in rootless mode.
# @see https://github.com/Cisco-Talos/clamav/issues/478
# hadolint global ignore=DL3018
FROM clamav/clamav:1.4.0

RUN apk add --no-cache tzdata

COPY .docker/config/clamav/clamav.conf /tmp/clamav.conf

RUN cat /tmp/clamav.conf >> /etc/clamav/clamd.conf && \
    rm /tmp/clamav.conf && \
    mkdir -p /var/run/clamav /run/lock && \
    chown -R clamav:clamav /var/run/clamav /run/clamav /var/log/clamav /var/lock /run/lock /var/lib/clamav && \
    chmod 770 -R /var/run/clamav /run/clamav /var/log/clamav /var/lock /run/lock /var/lib/clamav

#VOLUME /var/lib/clamav

#ENV CLAMAV_NO_CLAMD=true
#ENV CLAMAV_NO_FRESHCLAMD=true

USER clamav
