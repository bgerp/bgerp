#!/usr/bin/env bash
#
# ffmpeg_convert.sh
#
# Скрипт за конвертиране на видео с хардуерно ускорение (ако е налично)
# и налагане на ограничения за резолюция (~720p), FPS, битрейт, H.264 main профил.
# Решен е проблемът с "width not divisible by 2" чрез двойна скала.
#
# Използване:
#   ./ffmpeg_convert.sh <входен_файл> [изходен_файл]
#
# Ако не дадете втори параметър, изходното име ще бъде входното + "-out"
#
# Изисквания:
#  - За NVIDIA: nvidia-driver, nvidia-cuda-toolkit, libnvidia-encode, ...
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
elif lspci 2>/dev/null | grep -qi amd; then
  GPU_DETECTED="amd"
fi

# 4) Избиране на енкодер според откритата карта
case "$GPU_DETECTED" in
  "nvidia")
    echo "=== Открита е NVIDIA карта. Използваме h264_nvenc. ==="
    VIDEO_CODEC="h264_nvenc"
    HWACCEL_PARAM="-hwaccel cuda -hwaccel_output_format cuda"
    ;;

  "amd")
    echo "=== Открита е AMD карта. Опитваме VAAPI (h264_vaapi). ==="
    if command -v vainfo &> /dev/null; then
      VIDEO_CODEC="h264_vaapi"
      # Обичайно за VAAPI е:
      HWACCEL_PARAM="-hwaccel vaapi -vaapi_device /dev/dri/renderD128 -hwaccel_output_format vaapi"
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

#####################################################################
# 5) Филтър за скалиране с двойна стъпка, за да избегнем width/height
#    нечетни. Първо ограничаваме до 720 по височина (примерно),
#    след което закръгляме до четни стойности.
#
# Може да го нагласите и като "scale=1280:-2" + втори scale, ако предпочитате.
#
# Тази команда води до "приблизително 1280x720" (ако оригиналът е по-голям),
# запазва съотношението (force_original_aspect_ratio=decrease),
# и гарантира, че получените width/height са кратни на 2.
#####################################################################

DOUBLE_SCALE_FILTER="scale=-2:720:force_original_aspect_ratio=decrease,scale=2*trunc(iw/2):2*trunc(ih/2)"

#####################################################################
# 6) FFmpeg команди
#####################################################################

if [ "$VIDEO_CODEC" = "h264_nvenc" ]; then
  # NVIDIA (NVENC) - Оптимизирани параметри за по-голяма скорост
  ffmpeg \
    $HWACCEL_PARAM \
    -i "$INPUT_FILE" \
    -vf "$DOUBLE_SCALE_FILTER" -r 30 \
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
    -vf "$DOUBLE_SCALE_FILTER" -r 30 \
    -c:v h264_vaapi -profile:v main -qp 23 \
    -b:v 6000k -maxrate 6000k -bufsize 12000k \
    -pix_fmt yuv420p \
    -c:a aac -b:a 128k \
    -movflags +faststart -y "$OUTPUT_FILE"

else
  # Софтуерно (libx264)
  ffmpeg \
    -i "$INPUT_FILE" \
    -vf "$DOUBLE_SCALE_FILTER" -r 30 \
    -c:v libx264 -preset medium -profile:v main -crf 24 \
    -b:v 6000k -maxrate 6000k -bufsize 12000k \
    -pix_fmt yuv420p \
    -c:a aac -b:a 128k \
    -movflags +faststart -y "$OUTPUT_FILE"
fi

echo "=== Конвертирането приключи! Резултат: $OUTPUT_FILE ==="