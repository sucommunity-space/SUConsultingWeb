#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
export PATH=/usr/sbin:/usr/bin:/sbin:/bin
unset DOCKER_HOST DOCKER_CONTEXT COMPOSE_FILE COMPOSE_PROJECT_NAME SUCONSULTING_IMAGE
base=/srv/apps/suconsulting
test "$(id -u)" = 0 || { echo 'Utiliza la cuenta limitada mediante sudo.' >&2; exit 1; }
exec 9>"$base/deploy.lock"
flock -n 9 || { echo 'Otro despliegue de SuConsulting está en curso.' >&2; exit 1; }
compose() {
  docker compose --project-directory "$base" --env-file "$1" -f "$base/compose.yaml" \
    up -d --no-deps --no-build --pull never --wait --wait-timeout 90 web
}
status() {
  docker inspect --format '{{.Config.Image}} {{.State.Status}} {{.State.Health.Status}}' suconsulting-web-1
}
record_revision() {
  local active_revision
  active_revision=$(docker inspect --format '{{index .Config.Labels "org.opencontainers.image.revision"}}' suconsulting-web-1)
  [[ "$active_revision" =~ ^[0-9a-f]{40}$ ]]
  printf '%s\n' "$active_revision" > "$base/current-revision.tmp"
  mv "$base/current-revision.tmp" "$base/current-revision"
  if test -d "$base/releases/$active_revision"; then
    ln -s "$base/releases/$active_revision" "$base/current.next"
    mv -T "$base/current.next" "$base/current"
  fi
}
case "${1:-}" in
  status) test "$#" = 1; status; exit 0 ;;
  rollback)
    test "$#" = 1
    test -f "$base/previous.env" || { echo 'No hay una versión anterior registrada.' >&2; exit 1; }
    compose "$base/previous.env"
    curl --fail --silent --show-error --max-time 20 https://suconsulting.es/ >/dev/null
    cp "$base/previous.env" "$base/current.env"
    record_revision
    status
    exit 0 ;;
  deploy) test "$#" = 2; [[ "$2" =~ ^[0-9a-f]{40}$ ]] ;;
  *) echo 'Utiliza status, deploy SHA completo o rollback.' >&2; exit 1 ;;
esac
revision=$2
image="suconsulting-web:$revision"
if ! docker image inspect "$image" >/dev/null 2>&1; then
  image="ghcr.io/sucommunity-space/suconsultingweb:$revision"
  docker image inspect "$image" >/dev/null 2>&1 || docker pull "$image"
fi
test "$(docker image inspect --format '{{index .Config.Labels "org.opencontainers.image.revision"}}' "$image")" = "$revision"
test "$(docker image inspect --format '{{index .Config.Labels "org.opencontainers.image.source"}}' "$image")" = 'https://github.com/sucommunity-space/SUConsultingWeb'
test "$(docker image inspect --format '{{.Config.User}}' "$image")" = nginx
stamp=$(date -u +%Y%m%dT%H%M%SZ)
backup="$base/backups/update-$stamp"
mkdir -m 700 "$backup"
cp "$base/current.env" "$backup/previous.env"
cp "$base/compose.yaml" "$backup/compose.yaml"
docker inspect --format '{{.Id}} {{.Image}} {{.State.StartedAt}}' \
  dazgreen-api-1 dazgreen-web-1 dazgreen-db-1 proxy-central-caddy-1 > "$backup/protected-containers.before"
printf 'SUCONSULTING_IMAGE=%s\n' "$image" > "$backup/candidate.env"
recover() {
  code=$?
  trap - ERR INT TERM
  if compose "$backup/previous.env"; then
    cp "$backup/previous.env" "$base/current.env"
    record_revision
  else
    echo 'Revisión manual requerida para SuConsulting.' >&2
  fi
  curl --fail --silent --show-error --max-time 20 https://suconsulting.es/ >/dev/null || true
  exit "$code"
}
trap recover ERR
trap 'false' INT TERM
compose "$backup/candidate.env"
docker exec suconsulting-web-1 nginx -t
curl --fail --silent --show-error --max-time 20 https://suconsulting.es/ >/dev/null
curl --fail --silent --show-error --max-time 20 https://dazgreen.com/ >/dev/null
test "$(curl --silent --show-error --max-time 20 -o /dev/null -w '%{http_code}' https://dazgreen.com/api/auth/me)" = 401
docker inspect --format '{{.Id}} {{.Image}} {{.State.StartedAt}}' \
  dazgreen-api-1 dazgreen-web-1 dazgreen-db-1 proxy-central-caddy-1 > "$backup/protected-containers.after"
cmp "$backup/protected-containers.before" "$backup/protected-containers.after"
cp "$backup/previous.env" "$base/previous.env"
cp "$backup/candidate.env" "$base/current.env.tmp"
mv "$base/current.env.tmp" "$base/current.env"
record_revision
(cd "$backup" && sha256sum previous.env compose.yaml protected-containers.before > SHA256SUMS)
trap - ERR INT TERM
status
