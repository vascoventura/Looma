set -e

# Best-effort install of OTel SDK + exporter + Flask instrumentation for the
# Piper sidecar. Done lazily here (instead of in the Dockerfile) so existing
# images keep working without a rebuild. If pip can't reach PyPI the import
# in piper_server.py is wrapped in try/except and the server still starts
# (just without traces).
PIP_PKGS="opentelemetry-api opentelemetry-sdk opentelemetry-exporter-otlp-proto-http opentelemetry-instrumentation-flask"
if ! python3 -c "import opentelemetry" >/dev/null 2>&1; then
  pip3 install --quiet --no-warn-script-location $PIP_PKGS >/var/log/piper_otel_install.log 2>&1 || true
fi

# Where Piper sends its OTLP/HTTP traces. The looma-otel-collector container
# sits on the same `loomanet` network and forwards traces to OpenSearch via
# Data Prepper.
export OTEL_SERVICE_NAME="${OTEL_SERVICE_NAME:-piper-tts}"
export OTEL_EXPORTER_OTLP_ENDPOINT="${OTEL_EXPORTER_OTLP_ENDPOINT:-http://looma-otel-collector:4318}"
export OTEL_EXPORTER_OTLP_PROTOCOL="${OTEL_EXPORTER_OTLP_PROTOCOL:-http/protobuf}"
export OTEL_TRACES_EXPORTER="${OTEL_TRACES_EXPORTER:-otlp}"
export OTEL_METRICS_EXPORTER="${OTEL_METRICS_EXPORTER:-none}"
export OTEL_LOGS_EXPORTER="${OTEL_LOGS_EXPORTER:-none}"
export OTEL_RESOURCE_ATTRIBUTES="${OTEL_RESOURCE_ATTRIBUTES:-service.name=piper-tts,deployment.environment=looma}"

# Start Piper TTS Flask sidecar (used by looma-TTS.php -> http://127.0.0.1:5002/tts).
# Keep it bound to localhost inside the container; Apache/PHP will call it locally.
python3 /usr/local/var/www/Looma/piper_server.py >/var/log/piper_tts.log 2>&1 &

exec apachectl -D FOREGROUND
