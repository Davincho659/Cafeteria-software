# ☁️ Montar el sistema en un servidor de internet (VPS)

> Léela solo si elegiste la Opción A en `00-DECIDIR-COMO-MONTARLO.md`.
> **Recuerda:** con esta opción, si se cae el internet la caja no puede cobrar.
> Contrata internet de respaldo antes de operar así.

---

## Revisión previa del proyecto

Antes de escribir esta guía revisé el código buscando lo que suele romperse al
pasar de Windows a Linux. Esto es lo que había y cómo quedó:

| Punto revisado | Estado |
|---|---|
| Mayúsculas en rutas (`Assets` vs `assets`) | **Corregido.** Linux distingue mayúsculas; ahora el sistema devuelve la ruta tal como está en el disco y ya no depende del `.htaccess` |
| Rutas absolutas de Windows en el código | Ninguna |
| Requires con nombres mal escritos | Todos coinciden |
| Un reporte que cargaba una vista inexistente | **Corregido** (daba página en blanco) |
| Credenciales de la base | Ya se leen de variables de entorno |
| Zona horaria | Fijada a Colombia en la conexión: un servidor en UTC no corre las ventas |
| Errores visibles al público | Se ocultan con `APP_ENV=production` |
| Extensiones de PHP necesarias | `pdo_mysql`, `mbstring`, `fileinfo`, `curl` (todas estándar) |

**Requisitos del servidor:** PHP 8.0 o superior, MySQL/MariaDB, Apache con
`mod_rewrite` y `AllowOverride All`.

**Carpetas que el servidor debe poder escribir:**
`Public/Assets/img/products`, `Public/Assets/img/categories`, `storage/logs`,
`storage/sessions`.

---

# PASO 1 · Contratar

| Qué | Dónde | Costo aprox. |
|---|---|---|
| VPS (2 GB RAM basta) | Hetzner, Vultr (Miami), DigitalOcean | US$5–7/mes |
| Dominio | Namecheap, Cloudflare | US$12/año |

Al crear el VPS elige **Ubuntu 22.04 LTS** o **24.04 LTS**. Te dan una IP y una
clave de root por correo.

**Apunta el dominio al VPS:** en el panel del dominio, crea un registro **A** con
la IP del servidor.

---

# PASO 2 · Preparar el servidor

Conéctate con PuTTY o desde `cmd`:

```bash
ssh root@LA_IP_DEL_SERVIDOR
```

Instala todo lo necesario:

```bash
apt update && apt upgrade -y
apt install -y apache2 mariadb-server php php-mysql php-mbstring php-curl php-xml unzip
a2enmod rewrite
systemctl restart apache2
```

Asegura la base de datos (responde: contraseña sí, resto **Y**):

```bash
mysql_secure_installation
```

---

# PASO 3 · Crear la base de datos

```bash
mysql -u root -p
```

Dentro de MySQL, cambia `UNA_CLAVE_LARGA` por una contraseña real:

```sql
CREATE DATABASE cafeteria_software CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'pos'@'localhost' IDENTIFIED BY 'UNA_CLAVE_LARGA';
GRANT ALL PRIVILEGES ON cafeteria_software.* TO 'pos'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

> No uses el usuario `root` para la aplicación. Si alguien encuentra un fallo,
> con `pos` solo alcanza esta base y nada más del servidor.

---

# PASO 4 · Subir el sistema

```bash
cd /var/www
git clone https://github.com/Davincho659/Cafeteria-software.git
cd Cafeteria-software
```

Permisos:

```bash
chown -R www-data:www-data /var/www/Cafeteria-software
find /var/www/Cafeteria-software -type d -exec chmod 755 {} \;
find /var/www/Cafeteria-software -type f -exec chmod 644 {} \;
chmod -R 775 /var/www/Cafeteria-software/storage
chmod -R 775 /var/www/Cafeteria-software/Public/Assets/img
```

---

# PASO 5 · Configurar Apache

```bash
nano /etc/apache2/sites-available/pos.conf
```

Pega esto, cambiando el dominio:

```apache
<VirtualHost *:80>
    ServerName pos.tudominio.com
    DocumentRoot /var/www/Cafeteria-software/Public

    <Directory /var/www/Cafeteria-software/Public>
        AllowOverride All
        Require all granted
    </Directory>

    # Credenciales y entorno: nunca van escritos en el código
    SetEnv APP_ENV production
    SetEnv DB_HOST 127.0.0.1
    SetEnv DB_NAME cafeteria_software
    SetEnv DB_USER pos
    SetEnv DB_PASS UNA_CLAVE_LARGA

    ErrorLog ${APACHE_LOG_DIR}/pos-error.log
    CustomLog ${APACHE_LOG_DIR}/pos-access.log combined
</VirtualHost>
```

Actívalo:

```bash
a2ensite pos.conf
a2dissite 000-default.conf
apache2ctl configtest
systemctl reload apache2
```

> `DocumentRoot` apunta a **Public**, no a la raíz del proyecto. Así el código y
> la configuración quedan fuera del alcance del navegador.

---

# PASO 6 · Importar los datos del negocio

Sube el paquete que generaste con `EXPORTAR PARA SERVIDOR.bat`. Desde tu PC:

```bash
scp -r "C:\ruta\PARA-EL-SERVIDOR\2026-09-09" root@LA_IP:/tmp/migracion
```

En el servidor:

```bash
# 1) Los datos
mysql -u pos -p cafeteria_software < /tmp/migracion/datos.sql

# 2) Las fotos (sin esto los productos salen sin imagen)
cp -r /tmp/migracion/img/* /var/www/Cafeteria-software/Public/Assets/img/
chown -R www-data:www-data /var/www/Cafeteria-software/Public/Assets/img
```

Comprueba que llegó todo:

```bash
mysql -u pos -p -e "SELECT COUNT(*) AS productos FROM cafeteria_software.productos;"
ls /var/www/Cafeteria-software/Public/Assets/img/products | wc -l
```

Los números deben coincidir con los que anotaste al exportar.

---

# PASO 7 · HTTPS (obligatorio)

Sin esto, los PIN y las ventas viajan sin cifrar, y los celulares no pueden
instalar la aplicación.

```bash
apt install -y certbot python3-certbot-apache
certbot --apache -d pos.tudominio.com
```

Responde el correo, acepta los términos y elige **redirigir todo a HTTPS**.
Se renueva sola.

---

# PASO 8 · Seguridad del servidor

```bash
# Cortafuegos: solo lo indispensable
ufw allow OpenSSH
ufw allow 'Apache Full'
ufw --force enable

# Bloquear a quien intente adivinar la clave por SSH
apt install -y fail2ban
systemctl enable --now fail2ban
```

---

# PASO 9 · Respaldos automáticos

```bash
mkdir -p /root/respaldos
nano /root/respaldar.sh
```

```bash
#!/bin/bash
FECHA=$(date +%F_%H-%M)
mysqldump -u pos -pUNA_CLAVE_LARGA cafeteria_software | gzip > /root/respaldos/pos_$FECHA.sql.gz
tar czf /root/respaldos/fotos_$FECHA.tar.gz -C /var/www/Cafeteria-software/Public/Assets img
find /root/respaldos -name "*.gz" -mtime +30 -delete
```

```bash
chmod +x /root/respaldar.sh
crontab -e
```

Agrega (respalda cada día a las 2 de la mañana):

```
0 2 * * * /root/respaldar.sh
```

> ⚠️ Un respaldo dentro del mismo servidor no protege de que el servidor se
> pierda. Configura **rclone** hacia Google Drive o Backblaze y **prueba una
> restauración** antes de confiar en él.

---

# PASO 10 · Conectar los equipos

**Pantalla de la caja:** el mismo acceso directo, cambiando la dirección:

```
"C:\Program Files\Google\Chrome\Application\chrome.exe" --kiosk-printing --kiosk --app=https://pos.tudominio.com
```

**Celulares:** entran a `https://pos.tudominio.com` y eligen *Agregar a pantalla
de inicio*. Con HTTPS sí se instala como aplicación completa, con su ícono.

---

# Comprobación final

- [ ] `https://pos.tudominio.com` abre con candado
- [ ] Entra con el usuario administrador
- [ ] Están todos los productos y cada uno con su foto
- [ ] El inventario cuadra
- [ ] Se puede cobrar una venta de prueba
- [ ] La factura imprime bien
- [ ] Desde el celular funciona
- [ ] El respaldo corrió y se probó una restauración
- [ ] El PC del dueño sigue intacto, por si hay que volver atrás
