
# Auto-export XDG_RUNTIME_DIR for user systemd session
if test -z "$XDG_RUNTIME_DIR"
    set -x XDG_RUNTIME_DIR /run/user/(id -u)
end
