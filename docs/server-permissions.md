# Configuración de permisos para despliegues (Laravel)

El objetivo es que en producción el usuario del runtime (por ejemplo `www-data`) sea dueño de `storage/` y `bootstrap/cache/`, y que el usuario de deploy (por ejemplo `deploy`) no necesite adueñarse de esas carpetas. Para permitir deployments seguros sin romper permisos, usa ACLs y sudoers.

## Opción A — Ajuste único en el servidor (recomendado)

1) Instalar ACL y añadir el usuario de deploy al grupo del web user:

```
sudo apt-get update && sudo apt-get install -y acl
sudo usermod -aG www-data deploy
# cierra y vuelve a abrir sesión para que tome el grupo, o ejecuta:
newgrp www-data
```

2) Propiedad correcta + permisos de grupo + ACLs por defecto (heredables):

```
cd /var/www/futzo   # ajusta a tu path real
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 2775 storage bootstrap/cache   # setgid + rwx para grupo
sudo setfacl -R -m g:www-data:rwx storage bootstrap/cache
sudo setfacl -R -m u:deploy:rwx storage bootstrap/cache
sudo setfacl -dR -m g:www-data:rwx storage bootstrap/cache
sudo setfacl -dR -m u:deploy:rwx storage bootstrap/cache
```

3) Sudoers (permitir utilidades necesarias sin password):

Abre `visudo` y añade (conserva lo que ya tengas y ajusta rutas si difieren):

```
# ejecutar php como www-data y reiniciar horizon vía systemctl
deploy ALL=(www-data) NOPASSWD: /usr/bin/php, /usr/bin/php8.4
deploy ALL=(root)    NOPASSWD: /bin/systemctl

# utilidades opcionales para auto-sanar permisos si hace falta
deploy ALL=(root)    NOPASSWD: /bin/chown, /bin/chmod, /usr/bin/setfacl, /usr/bin/getfacl, /usr/bin/chgrp
```

Con esto:
- `www-data` sigue siendo dueño de `storage/` y `bootstrap/cache/` (runtime seguro).
- El usuario de deploy puede ejecutar Artisan como `www-data` y Composer sin romper permisos.

## Variables del Environment (GitHub → Environments → production)

- WEB_USER: `www-data`
- (opcional) PHP_BIN: ruta a PHP cli (por ejemplo `/usr/bin/php8.4`)
- (opcional) SYSTEMCTL_BIN: ruta de systemctl (por ejemplo `/bin/systemctl`)

## Notas

- Si no quieres tocar sudoers, puedes mantener sólo el ajuste de ACLs y ejecutar todo como tu usuario de deploy, pero Artisan y Horizon deberían correr como el web user.
- Si Horizon no está gestionado por systemd, el deploy usa `queue:restart` + `horizon:terminate` como fallback.

