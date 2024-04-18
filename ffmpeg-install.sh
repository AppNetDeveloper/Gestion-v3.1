#!/bin/bash

# Clonar el repositorio de fdk-aac
git clone https://github.com/mstorsjo/fdk-aac && \
cd fdk-aac && \
autoreconf -fiv && \
./configure --enable-shared && \
make -j4 && \
sudo make install && sudo ldconfig


# Instalar dependencias
sudo apt-get -y install autoconf build-essential libass-dev libdav1d-dev libmp3lame-dev yasm libopus-dev openssl libssl-dev

# Obtener otras dependencias
sudo apt-get -y update -qq
sudo apt-get -y install \
autoconf \
automake \
build-essential \
cmake \
git \
libass-dev \
libfreetype6-dev \
libgnutls28-dev \
libmp3lame-dev \
libsdl2-dev \
libtool \
libva-dev \
libvdpau-dev \
libvorbis-dev \
libxcb1-dev \
libxcb-shm0-dev \
libxcb-xfixes0-dev \
meson \
ninja-build \
pkg-config \
texinfo \
wget \
yasm \
zlib1g-dev	


# Crear directorios para el código fuente y los binarios
mkdir -p ~/ffmpeg_sources ~/bin ~/ffmpeg_build

# Instalar NASM
sudo apt-get -y install nasm 

sudo apt -y install libx264-dev
sudo apt -y install libx265-dev

# Instalar openssl
sudo apt install openssl libssl-dev

sudo apt install libsvtav1-dev


# Compilar e instalar libx264
echo "instalar libx264"

cd ~/ffmpeg_sources && git -C x264 pull 2> /dev/null || git clone --depth 1 https://code.videolan.org/videolan/x264.git && cd x264 && PATH="$HOME/bin:$PATH" PKG_CONFIG_PATH="$HOME/ffmpeg_build/lib/pkgconfig" ./configure --prefix="$HOME/ffmpeg_build" --bindir="$HOME/bin" --enable-static --enable-pic && PATH="$HOME/bin:$PATH" make -j$(nproc) && make -j$(nproc) install


# Compilar e instalar libx265
echo "instalar libx265"

cd ~/ffmpeg_sources && git -C x265_git pull 2> /dev/null || git clone https://bitbucket.org/multicoreware/x265_git && cd ~/ffmpeg_sources/x265_git/build/linux && cd ~/ffmpeg_sources/x265_git/build/linux/ && chmod 775 multilib.sh && ./multilib.sh

# Compilar e instalar libvpx
echo "instalar libvpx"

cd ~/ffmpeg_sources && git -C libvpx pull 2> /dev/null || git clone --depth 1 https://chromium.googlesource.com/webm/libvpx.git && cd libvpx && PATH="$HOME/bin:$PATH" ./configure --prefix="$HOME/ffmpeg_build" --disable-examples --disable-unit-tests --enable-vp9-highbitdepth --as=yasm && PATH="$HOME/bin:$PATH" make -j$(nproc) && make -j$(nproc) install

# Compilar e instalar libfdk-aac
echo "libfdk-aac"

cd ~/ffmpeg_sources && git -C fdk-aac pull 2> /dev/null || git clone --depth 1 https://github.com/mstorsjo/fdk-aac && cd fdk-aac && autoreconf -fiv && ./configure --prefix="$HOME/ffmpeg_build" --disable-shared && make -j$(nproc) && make -j$(nproc) install

# Compilar e instalar libopus
echo "instalar libopus"

cd ~/ffmpeg_sources && git -C opus pull 2> /dev/null || git clone --depth 1 https://github.com/xiph/opus.git && cd opus && ./autogen.sh && ./configure --prefix="$HOME/ffmpeg_build" --disable-shared && make -j$(nproc) && make -j$(nproc) install

# Compilar e instalar libaom
echo "instalar libaom"

cd ~/ffmpeg_sources && git -C aom pull 2> /dev/null || git clone --depth 1 https://aomedia.googlesource.com/aom && mkdir -p aom_build && cd aom_build && PATH="$HOME/bin:$PATH" cmake -G "Unix Makefiles" -DCMAKE_INSTALL_PREFIX="$HOME/ffmpeg_build" -DENABLE_TESTS=OFF -DENABLE_NASM=on ../aom && PATH="$HOME/bin:$PATH" make -j$(nproc) && make -j$(nproc) install

# Compilar e instalar libsvtav1
echo "libsvtav1"

cd ~/ffmpeg_sources && git -C SVT-AV1 pull 2> /dev/null || git clone https://gitlab.com/AOMediaCodec/SVT-AV1.git && mkdir -p SVT-AV1/build && cd SVT-AV1/build && PATH="$HOME/bin:$PATH" cmake -G "Unix Makefiles" -DCMAKE_INSTALL_PREFIX="$HOME/ffmpeg_build" -DCMAKE_BUILD_TYPE=Release -DBUILD_DEC=OFF -DBUILD_SHARED_LIBS=OFF .. && PATH="$HOME/bin:$PATH" make -j$(nproc) && make -j$(nproc) install

# Compilar e instalar libdav1d
echo "libdav1d"

cd ~/ffmpeg_sources && git -C dav1d pull 2> /dev/null || git clone --depth 1 https://code.videolan.org/videolan/dav1d.git && mkdir -p dav1d/build && cd dav1d/build && meson setup -Denable_tools=false -Denable_tests=false --default-library=static .. --prefix "$HOME/ffmpeg_build" --libdir="$HOME/ffmpeg_build/lib" && ninja -j$(nproc) && ninja -j$(nproc) install

# Compilar e instalar libvmaf
echo "instalar libvmaf"
cd ~/ffmpeg_sources && wget https://github.com/Netflix/vmaf/archive/v2.1.1.tar.gz && tar xvf v2.1.1.tar.gz && mkdir -p vmaf-2.1.1/libvmaf/build && cd vmaf-2.1.1/libvmaf/build && meson setup -Denable_tests=false -Denable_docs=false --buildtype=release --default-library=static .. --prefix "$HOME/ffmpeg_build" --bindir="$HOME/ffmpeg_build/bin" --libdir="$HOME/ffmpeg_build/lib" && ninja -j$(nproc) && ninja -j$(nproc) install



# ... (continuar con las otras compilaciones e instalaciones)

# Compilar e instalar FFmpeg
echo "clonamos ffmpeg"

cd ~/ffmpeg_sources
git clone https://git.ffmpeg.org/ffmpeg.git
cd ffmpeg


# Corregir la versión de FFmpeg
touch VERSION

echo "6.1.git">RELEASE && cp VERSION VERSION.bak && echo -e "$(cat VERSION.bak) [$(date +%Y-%m-%d)] [$(cat RELEASE)] " > VERSION

echo "pasamos a compilar"

# Compilar e instalar FFmpeg
PATH="$HOME/bin:$PATH" PKG_CONFIG_PATH="$HOME/ffmpeg_build/lib/pkgconfig" ./configure \
--prefix="$HOME/ffmpeg_build" \
--pkg-config-flags="--static" \
--extra-cflags="-I$HOME/ffmpeg_build/include" \
--extra-ldflags="-L$HOME/ffmpeg_build/lib" \
--extra-libs="-lpthread -lm" \
--ld="g++" \
--bindir="$HOME/bin" \
--enable-gpl \
--enable-openssl \
--enable-libaom \
--enable-libass \
--enable-libfdk-aac \
--enable-libfreetype \
--enable-libmp3lame \
--enable-libopus \
--enable-libsvtav1 \
--enable-libdav1d \
--enable-libvorbis \
--enable-libvpx \
--enable-libx264 \
--enable-libx265 \
--enable-nonfree \
--enable-libopenjpeg \
--enable-libpulse \
--enable-chromaprint \
--enable-frei0r \
--enable-libbluray \
--enable-libbs2b \
--enable-libcdio \
--enable-librubberband \
--enable-libspeex \
--enable-libtheora \
--enable-libfontconfig \
--enable-libfribidi \
--enable-libxml2 \
--enable-libxvid \
--enable-version3 \
--enable-libvidstab \
--enable-libcaca \
--enable-libopenmpt \
--enable-libgme \
--enable-opengl \
--enable-libsnappy \
--enable-libshine \
--enable-libtwolame \
--enable-libvo-amrwbenc \
--enable-libflite \
--enable-libsoxr \
--enable-ladspa \
&& PATH="$HOME/bin:$PATH" make -j$(nproc) && make -j$(nproc) install && hash -r


source ~/.profile


export PATH="$HOME/bin:$PATH"


echo "Instalación completada con éxito."

