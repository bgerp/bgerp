#!/usr/bin/env bash
#
# ffmpeg_convert.sh
#
# Скрипт за конвертиране на видео с хардуерно ускорение (ако е налично)
# и налагане на ограничения за резолюция, FPS, битрейт, H.264 main профил.
# Резултатът е максимално съвместим с повечето браузъри и по-слаби компютри.
#
# Използване:
#   ./ffmpeg_convert.sh <входен_файл> [изходен_файл]
#
# Ако не дадете втори параметър, изходното име ще бъде входното + "-out"
#
# Изисквания:
#  - За NVIDIA: nvidia-driver-xxx, nvidia-cuda-toolkit, libnvidia-encode-xxx, ...
#  - За AMD (VAAPI): mesa-va-drivers, vainfo
#

set -e

# 1) Проверка за входни параметри
if [ -z "$1" ]; then
  echo "Употреба: $0 <входен_файл> [изходен_файл]"
  exit 1
fi

INPUT_FILE="$1"
OUTPUT_FILE="$2"

# 2) Ако няма втори параметър, генерираме име, завършващо на -out
if [ -z "$OUTPUT_FILE" ]; then
  EXT="${INPUT_FILE##*.}"
  BASE="${INPUT_FILE%.*}"
  OUTPUT_FILE="${BASE}-out.${EXT}"
fi

# 3) Логика за откриване на GPU (NVIDIA или AMD)
VIDEO_CODEC="libx264"   # по подразбиране - софтуерно
HWACCEL_PARAM=""
GPU_DETECTED="none"

# Търсим низ "nvidia" в lspci
if lspci 2>/dev/null | grep -qi nvidia; then
  GPU_DETECTED="nvidia"

# Търсим "amd"
elif lspci 2>/dev/null | grep -qi amd; then
  GPU_DETECTED="amd"
fi

# 4) Избиране на енкодер според откритата карта
case "$GPU_DETECTED" in
  "nvidia")
    echo "=== Открита е NVIDIA карта. Използваме h264_nvenc. ==="
    VIDEO_CODEC="h264_nvenc"
    ;;
  
  "amd")
    echo "=== Открита е AMD карта. Опитваме VAAPI (h264_vaapi). ==="
    if command -v vainfo &> /dev/null; then
      VIDEO_CODEC="h264_vaapi"
      HWACCEL_PARAM="-hwaccel vaapi -vaapi_device /dev/dri/renderD128"
    else
      echo "vainfo не е намерен или VAAPI не е налично. Ползваме софтуерен енкод."
    fi
    ;;
  *)
    echo "=== Няма разпозната NVIDIA/AMD карта. Оставаме на софтуерен енкод (libx264). ==="
    ;;
esac

echo "OUTPUT_FILE = '$OUTPUT_FILE'"

echo "=== Започваме конвертиране: $INPUT_FILE -> $OUTPUT_FILE ==="

# 5) Команди за FFmpeg с ограничения (720p, 30 fps, ~6 Mbps, main профил)

#VIDEO_CODEC="h264_vaapi"

if [ "$VIDEO_CODEC" = "h264_nvenc" ]; then
  # NVIDIA (NVENC) - Оптимизирани параметри за по-голяма скорост
  ffmpeg \
    -hwaccel cuda -hwaccel_output_format cuda \
    -i "$INPUT_FILE" \
    -vf "scale_cuda=w=1280:h=720:force_original_aspect_ratio=decrease:interp_algo=bicubic" -r 30 \
    -c:v h264_nvenc  -profile:v high -level:v 4.2 \
    -b:v 6000k -maxrate 6000k -bufsize 12000k \
    -g 60 \
    -c:a aac -b:a 128k -cq 18 \
    -movflags +faststart -y "$OUTPUT_FILE"

elif [ "$VIDEO_CODEC" = "h264_vaapi" ]; then
  # AMD (VAAPI)
  ffmpeg \
    $HWACCEL_PARAM \
    -i "$INPUT_FILE" \
    -vf "scale=1280:720:force_original_aspect_ratio=decrease" -r 30 \
    -c:v h264_vaapi -profile:v main -qp 23 \
    -b:v 6000k -maxrate 6000k -bufsize 12000k \
    -pix_fmt yuv420p \
    -c:a aac -b:a 128k \
    -movflags +faststart -y "$OUTPUT_FILE"

else
  # Софтуерно (libx264)
  ffmpeg \
    -i "$INPUT_FILE" \
    -vf "scale=1280:720:force_original_aspect_ratio=decrease" -r 30 \
    -c:v libx264 -preset medium -profile:v main -crf 24 \
    -b:v 6000k -maxrate 6000k -bufsize 12000k \
    -pix_fmt yuv420p \
    -c:a aac -b:a 128k \
    -movflags +faststart -y "$OUTPUT_FILE"
fi

echo "=== Конвертирането приключи! Резултат: $OUTPUT_FILE ==="
