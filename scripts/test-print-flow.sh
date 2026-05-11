#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost:8000}"
USER_NAME="${LIZZIE_USER:-admin}"
USER_PASS="${LIZZIE_PASS:-admin123}"
PEDIDOS="${PEDIDOS:-460 459 458 457 456}"
TEMPLATES="${TEMPLATES:-complete summary}"
OUT_DIR="${OUT_DIR:-/tmp/lizzie-print-tests}"

mkdir -p "$OUT_DIR"

echo "[1/4] Autenticando em ${BASE_URL}..."
TOKEN="$(
  curl -sS --max-time 10 -X POST "${BASE_URL}/api/auth/login" \
    -H 'Content-Type: application/json' \
    -H 'Accept: application/json' \
    -d "{\"usuario\":\"${USER_NAME}\",\"senha\":\"${USER_PASS}\"}" \
  | php -r '$j=json_decode(stream_get_contents(STDIN), true); echo $j["data"]["access_token"] ?? "";'
)"

if [[ -z "${TOKEN}" ]]; then
  echo "ERRO: não foi possível autenticar."
  exit 1
fi

echo "[2/4] Testando contrato de prévia + geração de documentos..."
FAIL_COUNT=0
TOTAL_COUNT=0

for ID in ${PEDIDOS}; do
  for TEMPLATE in ${TEMPLATES}; do
    TOTAL_COUNT=$((TOTAL_COUNT + 1))

    PREVIEW="$(
      curl -sS --max-time 20 -X GET "${BASE_URL}/api/documents/pedido/${ID}/preview?template=${TEMPLATE}" \
        -H "Authorization: Bearer ${TOKEN}" \
        -H 'Accept: application/json'
    )"

    PREVIEW_OK="$(echo "${PREVIEW}" | php -r '$j=json_decode(stream_get_contents(STDIN), true); echo (($j["success"] ?? false) ? "1" : "0");')"
    PREVIEW_ITEMS="$(echo "${PREVIEW}" | php -r '$j=json_decode(stream_get_contents(STDIN), true); $items=$j["data"]["items"] ?? []; echo is_array($items) ? count($items) : "-1";')"
    PREVIEW_TOTAL="$(echo "${PREVIEW}" | php -r '$j=json_decode(stream_get_contents(STDIN), true); echo ($j["data"]["payment"]["total_formatted"] ?? "");')"

    if [[ "${PREVIEW_OK}" != "1" || "${PREVIEW_ITEMS}" == "-1" || -z "${PREVIEW_TOTAL}" ]]; then
      echo "FAIL pedido=${ID} template=${TEMPLATE} stage=preview payload=${PREVIEW}"
      FAIL_COUNT=$((FAIL_COUNT + 1))
      continue
    fi

    echo "${PREVIEW}" > "${OUT_DIR}/pedido-${ID}-${TEMPLATE}-preview.json"

    GEN="$(
      curl -sS --max-time 20 -X POST "${BASE_URL}/api/documents/pedido/${ID}/generate" \
        -H "Authorization: Bearer ${TOKEN}" \
        -H 'Content-Type: application/json' \
        -d "{\"template\":\"${TEMPLATE}\",\"include_qr\":true}"
    )"

    OK="$(echo "${GEN}" | php -r '$j=json_decode(stream_get_contents(STDIN), true); echo (($j["success"] ?? false) ? "1" : "0");')"
    CONTENT_URL="$(echo "${GEN}" | php -r '$j=json_decode(stream_get_contents(STDIN), true); echo ($j["data"]["content_url"] ?? "");')"
    DOC_ID="$(echo "${GEN}" | php -r '$j=json_decode(stream_get_contents(STDIN), true); echo ($j["data"]["document_id"] ?? "");')"

    if [[ "${OK}" != "1" || -z "${CONTENT_URL}" ]]; then
      echo "FAIL pedido=${ID} template=${TEMPLATE} stage=generate payload=${GEN}"
      FAIL_COUNT=$((FAIL_COUNT + 1))
      continue
    fi

    OUT_FILE="${OUT_DIR}/pedido-${ID}-${TEMPLATE}.pdf"
    STATUS_SIZE="$(
      curl -sS --max-time 20 -o "${OUT_FILE}" -w "%{http_code}:%{size_download}" \
        "${BASE_URL}${CONTENT_URL}" -H "Authorization: Bearer ${TOKEN}"
    )"

    STATUS="${STATUS_SIZE%%:*}"
    SIZE="${STATUS_SIZE##*:}"

    if [[ "${STATUS}" != "200" || "${SIZE}" -le 0 ]]; then
      echo "FAIL pedido=${ID} template=${TEMPLATE} stage=content status_size=${STATUS_SIZE} doc_id=${DOC_ID}"
      FAIL_COUNT=$((FAIL_COUNT + 1))
      continue
    fi

    echo "OK   pedido=${ID} template=${TEMPLATE} items=${PREVIEW_ITEMS} total=${PREVIEW_TOTAL} doc_id=${DOC_ID} size=${SIZE}"
  done
done

echo "[3/4] Resultado:"
echo "Total: ${TOTAL_COUNT} | Falhas: ${FAIL_COUNT}"

echo "[4/4] Arquivos salvos em: ${OUT_DIR}"

if [[ "${FAIL_COUNT}" -gt 0 ]]; then
  exit 1
fi

exit 0
