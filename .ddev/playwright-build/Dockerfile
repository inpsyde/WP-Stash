#ddev-generated
# Remove the line above if you don't want this file to be overwritten when you run
# ddev get julienloizelet/ddev-playwright
#
# This file comes from https://github.com/julienloizelet/ddev-playwright
#
# Keep the tag in sync with @playwright/test in tests/Playwright/package.json
FROM mcr.microsoft.com/playwright:v1.63.0-noble

# Debian images by default disable apt caching, so turn it on until we finish
# the build.
RUN mv /etc/apt/apt.conf.d/docker-clean /etc/apt/docker-clean-disabled

USER root

# Noble ships an "ubuntu" user with UID 1000, which would clash with the host UID entrypoint.sh gives pwuser
RUN deluser --remove-home ubuntu || true

RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
      --mount=type=cache,target=/var/lib/apt,sharing=locked \
    apt-get update \
    && apt-get install -y sudo libnss3-tools

# Give the pwuser user full `sudo` privileges
RUN echo "pwuser ALL=(ALL) NOPASSWD: ALL" >> /etc/sudoers.d/pwuser \
    && chmod 0440 /etc/sudoers.d/pwuser

# CAROOT for `mkcert` to use, has the CA config
ENV CAROOT=/mnt/ddev-global-cache/mkcert

# Install the correct architecture binary of `mkcert`
RUN export TARGETPLATFORM=linux/$(uname -m | sed 's/x86_64/amd64/;s/aarch64/arm64/') && mkdir -p /usr/local/bin && curl --fail -JL -s -o /usr/local/bin/mkcert "https://dl.filippo.io/mkcert/latest?for=${TARGETPLATFORM}"
RUN chmod +x /usr/local/bin/mkcert


# Install a window manager.
RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
      --mount=type=cache,target=/var/lib/apt,sharing=locked \
    apt-get update \
    && apt-get install -y icewm xauth

# Install kasmvnc for remote access.
RUN /bin/bash -c 'if [ $(arch) == "aarch64" ]; then KASM_ARCH=arm64; else KASM_ARCH=amd64; fi; wget https://github.com/kasmtech/KasmVNC/releases/download/v1.4.0/kasmvncserver_noble_1.4.0_${KASM_ARCH}.deb'
RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
      --mount=type=cache,target=/var/lib/apt,sharing=locked \
    apt-get install -y ./kasmvncserver*.deb

# We're done with apt so disable caching again for the final image.
RUN mv /etc/apt/docker-clean-disabled /etc/apt/apt.conf.d/docker-clean

# prepare KasmVNC
RUN sudo -u pwuser mkdir /home/pwuser/.vnc
COPY kasmvnc.yaml xstartup /home/pwuser/.vnc/
RUN chown pwuser:pwuser /home/pwuser/.vnc/*
RUN sudo -u pwuser touch /home/pwuser/.vnc/.de-was-selected
RUN sudo -u pwuser /bin/bash -c 'echo -e "secret\nsecret\n" | kasmvncpasswd -wo -u pwuser' # We actually disable auth, but KASM complains without it


COPY entrypoint.sh /root/entrypoint.sh
ENTRYPOINT "/root/entrypoint.sh"
