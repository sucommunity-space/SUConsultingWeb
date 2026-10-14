#!/usr/bin/env bash
set -Eeuo pipefail
case "${SSH_ORIGINAL_COMMAND:-}" in
  status|rollback)
    exec sudo -n /usr/local/sbin/suconsulting-release "$SSH_ORIGINAL_COMMAND"
    ;;
  deploy\ *)
    revision=${SSH_ORIGINAL_COMMAND#deploy }
    [[ "$revision" =~ ^[0-9a-f]{40}$ ]] || exit 1
    exec sudo -n /usr/local/sbin/suconsulting-release deploy "$revision"
    ;;
  *) echo 'Comando permitido: status, deploy SHA completo o rollback.' >&2; exit 1 ;;
esac
