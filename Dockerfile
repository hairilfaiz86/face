FROM php:8.2-apache
# Imej rasmi PHP sudah termasuk pdo_sqlite

ENV TZ=Asia/Kuala_Lumpur \
    DB_PATH=/data/kehadiran.sqlite

# Benarkan .htaccess (lalai Apache dalam imej ini: AllowOverride None)
RUN printf '<Directory /var/www/html>\n  AllowOverride All\n</Directory>\n' \
      > /etc/apache2/conf-enabled/htaccess.conf \
 && echo 'ServerName localhost' > /etc/apache2/conf-enabled/servername.conf

COPY . /var/www/html/
RUN mkdir -p /data && chown -R www-data:www-data /data /var/www/html

# Pangkalan data SQLite — mount volume ke sini di Dokploy supaya data kekal
VOLUME /data
EXPOSE 80
